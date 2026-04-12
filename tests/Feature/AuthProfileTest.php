<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthProfileTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $this->user = User::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'River',
            'name' => 'Alex River',
        ]);
        $this->user->assignRole('customer');
    }

    public function test_guest_cannot_update_auth_profile(): void
    {
        $this->postJson('/api/v1/auth/profile', [
            'first_name' => 'Sam',
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_upload_auth_profile_avatar(): void
    {
        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/auth/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('a.jpg', 40, 40),
            ])
            ->assertUnauthorized();
    }

    public function test_guest_cannot_remove_auth_profile_avatar(): void
    {
        $this->deleteJson('/api/v1/auth/profile/avatar')->assertUnauthorized();
    }

    public function test_user_can_update_auth_profile_names(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/auth/profile', [
                'first_name' => 'Alexis',
                'last_name' => 'Brook',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Alexis')
            ->assertJsonPath('data.last_name', 'Brook')
            ->assertJsonPath('data.full_name', 'Alexis Brook');
    }

    public function test_user_can_upload_auth_profile_avatar(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/auth/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('p.jpg', 90, 90),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Avatar updated successfully.');

        $this->assertNotNull($response->json('data.avatar'));
        $this->assertSame(1, $this->user->fresh()->getMedia('avatar')->count());
    }

    public function test_user_can_remove_auth_profile_avatar(): void
    {
        $this->user->addMedia(UploadedFile::fake()->image('z.webp', 30, 30))
            ->toMediaCollection('avatar');

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/v1/auth/profile/avatar');

        $response->assertOk()
            ->assertJsonPath('data.avatar', null);

        $this->assertSame(0, $this->user->fresh()->getMedia('avatar')->count());
    }

    public function test_auth_upload_avatar_requires_file(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/auth/profile/avatar', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_auth_profile_update_rejects_avatar_payload(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/auth/profile', [
                'first_name' => 'X',
                'avatar' => UploadedFile::fake()->image('bad.jpg', 20, 20),
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
