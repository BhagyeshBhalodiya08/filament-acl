<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Bhagyesh',
            'email' => 'bhagyesh.dev@gmail.com',
            'password' => 'Admin@123',
            'super_user' => 'yes'
        ]);
    }
}
