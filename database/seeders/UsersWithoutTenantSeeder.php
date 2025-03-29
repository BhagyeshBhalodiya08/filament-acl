<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UsersWithoutTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Super Admin Without Tenant',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'), // Change as needed
            'super_user' => 'yes', // Ensuring it's a super user
        ]);

        User::factory()->create([
            'name' => 'Regular User Without Tenant',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'super_user' => 'no', // Regular user
        ]);
    }
}
