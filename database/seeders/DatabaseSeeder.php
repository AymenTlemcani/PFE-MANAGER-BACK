<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Company;
use App\Models\Administrator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->create([
            'email' => 'admin@pfe.com',
            'password' => Hash::make('admin123'),
            'role' => 'Administrator',
            'must_change_password' => true,
            'date_of_birth' => '1990-01-01'
        ]);
        Administrator::create([
            'user_id' => $admin->user_id,
            'name' => 'Admin',
            'surname' => 'System'
        ]);

        // Create student user
        $student = User::factory()->create([
            'email' => 'student@pfe.com',
            'password' => Hash::make('student123'),
            'role' => 'Student',
            'must_change_password' => true,
            'date_of_birth' => '2000-01-01'
        ]);
        Student::create([
            'user_id' => $student->user_id,
            'name' => 'John',
            'surname' => 'Doe',
            'master_option' => 'GL',
            'overall_average' => 15.50,
            'admission_year' => 2023
        ]);

        // Create company user
        $company = User::factory()->create([
            'email' => 'company@pfe.com',
            'password' => Hash::make('company123'),
            'role' => 'Company',
            'must_change_password' => true,
            'date_of_birth' => '1985-01-01'
        ]);
        Company::create([
            'user_id' => $company->user_id,
            'company_name' => 'Tech Corp',
            'contact_name' => 'Jane',
            'contact_surname' => 'Smith',
            'industry' => 'Software Development',
            'address' => '123 Tech Street'
        ]);

        // Create responsible teacher
        $respTeacher = User::factory()->create([
            'email' => 'responsible@pfe.com',
            'password' => Hash::make('teacher123'),
            'role' => 'Teacher',
            'must_change_password' => true,
            'date_of_birth' => '1975-01-01'
        ]);
        Teacher::create([
            'user_id' => $respTeacher->user_id,
            'name' => 'Robert',
            'surname' => 'Johnson',
            'recruitment_date' => '2010-01-01',
            'grade' => 'PR',
            'is_responsible' => true,
            'research_domain' => 'Artificial Intelligence'
        ]);

        // Create regular teacher
        $teacher = User::factory()->create([
            'email' => 'teacher@pfe.com',
            'password' => Hash::make('teacher123'),
            'role' => 'Teacher',
            'must_change_password' => true,
            'date_of_birth' => '1980-01-01'
        ]);
        Teacher::create([
            'user_id' => $teacher->user_id,
            'name' => 'Mary',
            'surname' => 'Wilson',
            'recruitment_date' => '2015-01-01',
            'grade' => 'MAA',
            'is_responsible' => false,
            'research_domain' => 'Software Engineering'
        ]);
    }
}
