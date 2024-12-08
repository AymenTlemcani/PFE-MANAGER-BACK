<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Administrator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // TODO: Remove this seeder after development phase. This is only for initial setup.
        
        // Create admin user
        $user = User::factory()->create([
            'email' => 'admin@pfe.com',
            'password' => Hash::make('admin123'),
            'role' => 'Administrator',
            'must_change_password' => true,
            'date_of_birth' => '2002-06-20' // Added date of birth in YYYY-MM-DD format
        ]);

        // Create corresponding administrator record
        Administrator::create([
            'user_id' => $user->user_id,
            'name' => 'Admin',
            'surname' => 'System'
        ]);
    }
}
