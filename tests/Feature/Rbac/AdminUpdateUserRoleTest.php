<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class AdminUpdateUserRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $client = app(ClientRepository::class)->createPasswordGrantClient('Test Password Client', 'users', true);
        config([
            'passport.password_client_id' => (string) $client->id,
            'passport.password_client_secret' => $client->plainSecret,
        ]);

        $this->seed(RolesSeeder::class);
    }

    private function loginAs(User $user, string $password = 'password'): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ])->assertOk();

        return $response->json('data.access_token');
    }

    public function test_customer_calling_endpoint_returns_403(): void
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        $customer->assignRole('Customer');

        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        $token = $this->loginAs($customer, 'password');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/admin/users/{$targetUser->id}/role", [
                'role' => 'Staff',
            ])
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_staff_calling_endpoint_updates_role(): void
    {
        $staff = User::factory()->create([
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        $staff->assignRole('Staff');

        $targetUser = User::factory()->create([
            'email' => 'target@example.com',
            'email_verified_at' => now(),
        ]);
        $targetUser->assignRole('Customer');

        $token = $this->loginAs($staff, 'password');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/admin/users/{$targetUser->id}/role", [
                'role' => 'Staff',
            ])
            ->assertOk();

        $response->assertJsonPath('data.id', $targetUser->id);
        $response->assertJsonPath('data.email', 'target@example.com');
        $response->assertJsonPath('data.role', 'Staff');

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('Staff'));
    }

    public function test_admin_calling_endpoint_updates_role(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Admin');

        $targetUser = User::factory()->create([
            'email' => 'target2@example.com',
            'email_verified_at' => now(),
        ]);

        $token = $this->loginAs($admin, 'password');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/admin/users/{$targetUser->id}/role", [
                'role' => 'Admin',
            ])
            ->assertOk();

        $response->assertJsonPath('data.id', $targetUser->id);
        $response->assertJsonPath('data.role', 'Admin');

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('Admin'));
    }

    public function test_invalid_role_returns_422(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Admin');

        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        $token = $this->loginAs($admin, 'password');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/admin/users/{$targetUser->id}/role", [
                'role' => 'InvalidRole',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['errors' => ['role']]);
    }
}
