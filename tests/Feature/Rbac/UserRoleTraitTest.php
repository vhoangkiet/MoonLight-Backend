<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_assigned_role(): void
    {
        Role::create(['name' => 'Admin']);

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->assertTrue($user->hasRole('Admin'));
    }

    public function test_user_sync_roles_replaces_existing_roles(): void
    {
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Staff']);
        Role::create(['name' => 'Customer']);

        $user = User::factory()->create();
        $user->assignRole('Admin');
        $this->assertTrue($user->hasRole('Admin'));

        $user->syncRoles(['Staff', 'Customer']);
        $this->assertFalse($user->hasRole('Admin'));
        $this->assertTrue($user->hasRole('Staff'));
        $this->assertTrue($user->hasRole('Customer'));
    }
}
