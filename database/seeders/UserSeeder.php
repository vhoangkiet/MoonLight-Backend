<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'first_name' => 'Staff',
                'last_name' => 'Member',
                'email' => 'staff@example.com',
                'role' => 'staff',
            ],
            [
                'first_name' => 'Default',
                'last_name' => 'Customer',
                'email' => 'customer@example.com',
                'role' => 'customer',
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ])
            );

            $user->assignRole($role);
        }
    }
}
