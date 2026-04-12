<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $this->customer = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
        ]);
        $this->customer->assignRole('customer');
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->postJson('/api/v1/profile', [
            'first_name' => 'John',
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_upload_avatar(): void
    {
        $file = UploadedFile::fake()->image('a.jpg', 50, 50);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/profile/avatar', [
                'avatar' => $file,
            ])
            ->assertUnauthorized();
    }

    public function test_guest_cannot_remove_avatar(): void
    {
        $this->deleteJson('/api/v1/profile/avatar')->assertUnauthorized();
    }

    public function test_customer_can_update_profile_without_avatar(): void
    {
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/v1/profile', [
                'first_name' => 'Janet',
                'last_name' => 'Smith',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Janet')
            ->assertJsonPath('data.last_name', 'Smith')
            ->assertJsonPath('data.name', 'Janet Smith');

        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'first_name' => 'Janet',
            'last_name' => 'Smith',
        ]);
    }

    public function test_customer_can_upload_avatar(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 80, 80);

        $response = $this->actingAs($this->customer, 'api')
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Avatar updated successfully');

        $this->assertNotNull($response->json('data.avatar'));
        $this->assertSame(1, $this->customer->fresh()->getMedia('avatar')->count());
    }

    public function test_customer_upload_avatar_replaces_existing(): void
    {
        $this->customer->addMedia(UploadedFile::fake()->image('old.jpg', 40, 40))
            ->toMediaCollection('avatar');

        $this->assertSame(1, $this->customer->getMedia('avatar')->count());

        $file = UploadedFile::fake()->image('new.png', 60, 60);

        $response = $this->actingAs($this->customer, 'api')
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk();
        $this->assertSame(1, $this->customer->fresh()->getMedia('avatar')->count());
        $this->assertNotNull($response->json('data.avatar'));
    }

    public function test_customer_can_remove_avatar_when_none_set(): void
    {
        $response = $this->actingAs($this->customer, 'api')
            ->deleteJson('/api/v1/profile/avatar');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Avatar removed successfully')
            ->assertJsonPath('data.avatar', null);
    }

    public function test_customer_can_remove_avatar_after_upload(): void
    {
        $this->actingAs($this->customer, 'api')
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('x.webp', 40, 40),
            ])
            ->assertOk();

        $response = $this->actingAs($this->customer, 'api')
            ->deleteJson('/api/v1/profile/avatar');

        $response->assertOk()
            ->assertJsonPath('data.avatar', null);

        $this->assertSame(0, $this->customer->fresh()->getMedia('avatar')->count());
    }

    public function test_upload_avatar_requires_file(): void
    {
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/v1/profile/avatar', []);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_upload_avatar_rejects_non_image(): void
    {
        $response = $this->actingAs($this->customer, 'api')
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_profile_update_does_not_accept_avatar_field_in_validation(): void
    {
        $this->actingAs($this->customer, 'api')
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/profile', [
                'first_name' => 'OnlyName',
                'avatar' => UploadedFile::fake()->image('ignored.jpg', 20, 20),
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }
}
