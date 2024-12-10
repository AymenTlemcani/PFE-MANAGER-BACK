<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $type;
    protected $rows = 0;
    protected $failures = 0;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            ++$this->rows;
            
            try {
                DB::transaction(function () use ($row) {
                    // Create base user record
                    $user = User::create([
                        'email' => $row['email'],
                        'password' => Hash::make($row['password'] ?? 'changeme123'),
                        'role' => ucfirst($this->type),
                        'must_change_password' => $row['must_change_password'] ?? true,
                        'language_preference' => $row['language_preference'] ?? 'French',
                        'is_active' => $row['is_active'] ?? true,
                        'date_of_birth' => $row['date_of_birth'] ?? null,
                        'profile_picture_url' => $row['profile_picture_url'] ?? null
                    ]);

                    // Create role-specific record
                    match($this->type) {
                        'student' => Student::create([
                            'user_id' => $user->user_id,
                            'name' => $row['name'],
                            'surname' => $row['surname'],
                            'master_option' => $row['master_option'],
                            'overall_average' => $row['overall_average'],
                            'admission_year' => $row['admission_year']
                        ]),
                        'teacher' => Teacher::create([
                            'user_id' => $user->user_id,
                            'name' => $row['name'],
                            'surname' => $row['surname'],
                            'recruitment_date' => $row['recruitment_date'],
                            'grade' => $row['grade'],
                            'is_responsible' => $row['is_responsible'] ?? false,
                            'research_domain' => $row['research_domain'] ?? null
                        ]),
                        'company' => Company::create([
                            'user_id' => $user->user_id,
                            'company_name' => $row['company_name'],
                            'contact_name' => $row['contact_name'],
                            'contact_surname' => $row['contact_surname'],
                            'industry' => $row['industry'],
                            'address' => $row['address']
                        ]),
                        default => null
                    };
                });
            } catch (\Exception $e) {
                ++$this->failures;
                continue; // Skip to next row on failure
            }
        }
    }

    public function rules(): array
    {
        $commonRules = [
            'email' => 'required|email|unique:users',
            'password' => 'nullable|min:8',
            'language_preference' => 'nullable|in:French,English',
            'must_change_password' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'date_of_birth' => 'nullable|date',
            'profile_picture_url' => 'nullable|url'
        ];

        $typeSpecificRules = match($this->type) {
            'student' => [
                'name' => 'required|string',
                'surname' => 'required|string',
                'master_option' => 'required|in:GL,IA,RSD,SIC',
                'overall_average' => 'required|numeric|between:0,20',
                'admission_year' => 'required|integer'
            ],
            'teacher' => [
                'name' => 'required|string',
                'surname' => 'required|string',
                'recruitment_date' => 'required|date',
                'grade' => 'required|in:Professor,Associate Professor,Assistant Professor',
                'is_responsible' => 'nullable|boolean',
                'research_domain' => 'nullable|string'
            ],
            'company' => [
                'company_name' => 'required|string',
                'contact_name' => 'required|string',
                'contact_surname' => 'required|string',
                'industry' => 'required|string',
                'address' => 'required|string'
            ],
            default => []
        };

        return array_merge($commonRules, $typeSpecificRules);
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function getFailureCount(): int
    {
        return $this->failures;
    }
}