<?php

namespace Tests\Feature\Rbac;

use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_seeder_creates_base_roles(): void
    {
        $this->seed(RolesSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'Admin', 'guard_name' => 'api']);
        $this->assertDatabaseHas('roles', ['name' => 'Staff', 'guard_name' => 'api']);
        $this->assertDatabaseHas('roles', ['name' => 'Customer', 'guard_name' => 'api']);
    }
}
