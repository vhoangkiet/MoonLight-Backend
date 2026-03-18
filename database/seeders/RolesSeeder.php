<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Admin', 'Staff', 'Customer'] as $roleName) {
            Role::findOrCreate($roleName);
        }

        $bootstrapEmail = config('rbac.bootstrap_admin_email');
        if (! empty($bootstrapEmail)) {
            $user = User::query()->where('email', $bootstrapEmail)->first();
            if ($user !== null && ! $user->hasRole('Admin')) {
                $user->assignRole('Admin');
            }
        }
    }
}
