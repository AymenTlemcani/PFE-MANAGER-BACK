<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
            'role' => 'Administrator',
            'must_change_password' => true
        ]);
    }
}
