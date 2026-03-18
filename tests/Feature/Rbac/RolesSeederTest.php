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

        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Staff']);
        $this->assertDatabaseHas('roles', ['name' => 'Customer']);
    }
}
