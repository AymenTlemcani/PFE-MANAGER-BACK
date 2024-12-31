<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Company;
use App\Models\UserImportLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserImportTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestFile(string $type, array $data, string $format = 'xlsx'): string
    {
        if ($format === 'csv') {
            $filename = Storage::path('test_import.csv');
            
            // Updated headers to include is_responsible for teachers
            $headers = match($type) {
                'student' => ['email', 'name', 'surname', 'master_option', 'overall_average', 'admission_year', 'date_of_birth'],
                'teacher' => ['email', 'name', 'surname', 'recruitment_date', 'grade', 'research_domain', 'is_responsible', 'date_of_birth'],
                'company' => ['email', 'company_name', 'contact_name', 'contact_surname', 'industry', 'address'],
                default => []
            };

            $handle = fopen($filename, 'w');
            fputcsv($handle, $headers);
            
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            
            fclose($handle);
            return $filename;
        }
        
        // Existing spreadsheet creation for xlsx
        return $this->createSpreadsheet($type, $data);
    }

    private function getHeaders(string $type): array
    {
        return match($type) {
            'student' => ['email', 'name', 'surname', 'master_option', 'overall_average', 'admission_year', 'date_of_birth'],
            'teacher' => ['email', 'name', 'surname', 'recruitment_date', 'grade', 'research_domain', 'is_responsible', 'date_of_birth'],
            'company' => ['email', 'company_name', 'contact_name', 'contact_surname', 'industry', 'address', 'date_of_birth'],
            default => []
        };
    }

    protected function createSpreadsheet(string $type, array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = $this->getHeaders($type);

        // Write headers
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . '1', $header);
        }

        // Write data rows
        foreach ($data as $rowIndex => $row) {
            foreach ($headers as $colIndex => $header) {
                $value = is_array($row) && isset($row[$header]) ? $row[$header] : ($row[$colIndex] ?? null);
                $sheet->setCellValue(chr(65 + $colIndex) . ($rowIndex + 2), $value);
            }
        }

        $filename = Storage::path('test_import.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        return $filename;
    }

    public function test_can_import_valid_student_data()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'email' => 'student@esi.dz',
                'name' => 'John',
                'surname' => 'Doe',
                'master_option' => 'GL',
                'overall_average' => '16.50',
                'admission_year' => '2023',
                'date_of_birth' => '2000-01-01'
            ]
        ];

        $filename = $this->createSpreadsheet('student', $data);
        $file = new UploadedFile($filename, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'student',
                'file' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'student@esi.dz']);
        $this->assertDatabaseHas('students', [
            'name' => 'John',
            'surname' => 'Doe',
            'master_option' => 'GL'
        ]);
    }

    public function test_cannot_import_student_with_invalid_email_domain()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'email' => 'student@gmail.com',
                'name' => 'John',
                'surname' => 'Doe',
                'master_option' => 'GL',
                'overall_average' => '16.50',
                'admission_year' => '2023',
                'date_of_birth' => '2000-01-01'
            ]
        ];

        $filename = $this->createSpreadsheet('student', $data);
        $file = new UploadedFile($filename, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'student',
                'file' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseMissing('users', ['email' => 'student@gmail.com']);
        $this->assertDatabaseHas('user_import_logs', [
            'import_type' => 'student',
            'successful_imports' => 0,
            'failed_imports' => 1
        ]);
    }

    public function test_can_import_valid_teacher_data()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'email' => 'teacher@department.edu',
                'name' => 'Jane',
                'surname' => 'Smith',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'Computer Science',
                'is_responsible' => 'true',
                'date_of_birth' => '1980-01-01'
            ]
        ];

        $filename = $this->createSpreadsheet('teacher', $data);
        $file = new UploadedFile($filename, 'teachers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'teacher',
                'file' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'teacher@department.edu']);
        $this->assertDatabaseHas('teachers', [
            'name' => 'Jane',
            'surname' => 'Smith',
            'grade' => 'Professor'
        ]);
    }

    public function test_can_import_valid_company_data()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'email' => 'contact@company.com',
                'company_name' => 'Tech Corp',
                'contact_name' => 'Robert',
                'contact_surname' => 'Johnson',
                'industry' => 'Technology',
                'address' => '123 Tech Street',
                'date_of_birth' => '1980-01-01'  // Add required date_of_birth
            ]
        ];

        $filename = $this->createSpreadsheet('company', $data);
        $file = new UploadedFile($filename, 'companies.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'company',
                'file' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'contact@company.com']);
        $this->assertDatabaseHas('companies', [
            'company_name' => 'Tech Corp',
            'contact_name' => 'Robert',
            'contact_surname' => 'Johnson'
        ]);
    }

    public function test_logs_partial_import_failures()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'email' => 'valid@esi.dz',
                'name' => 'John',
                'surname' => 'Doe',
                'master_option' => 'GL',
                'overall_average' => '16.50',
                'admission_year' => '2023',
                'date_of_birth' => '2000-01-01'
            ],
            [
                'email' => 'invalid-email',
                'name' => 'Jane',
                'surname' => 'Smith',
                'master_option' => 'GL',
                'overall_average' => '15.00',
                'admission_year' => '2023',
                'date_of_birth' => '2000-01-01'
            ]
        ];

        $filename = $this->createSpreadsheet('student', $data);
        $file = new UploadedFile($filename, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'student',
                'file' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'valid@esi.dz']);  // Fixed assertion
        $this->assertDatabaseMissing('users', ['email' => 'invalid-email']);
        $this->assertDatabaseHas('user_import_logs', [
            'import_type' => 'student',
            'successful_imports' => 1,
            'failed_imports' => 1,
            'import_status' => 'Completed with errors'
        ]);
    }

    public function test_can_import_from_csv_file()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'email' => 'student@esi.dz',
                'name' => 'John',
                'surname' => 'Doe',
                'master_option' => 'GL',
                'overall_average' => '16.50',
                'admission_year' => '2023',
                'date_of_birth' => '2000-01-01'
            ]
        ];

        $filename = $this->createTestFile('student', $data, 'csv');
        $file = new UploadedFile($filename, 'students.csv', 'text/csv', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'student',
                'file' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'student@esi.dz']);  // Fixed assertion
    }

    public function test_can_import_valid_teacher_data_with_responsibility()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'email' => 'teacher@department.edu',
                'name' => 'Jane',
                'surname' => 'Smith',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'Computer Science',
                'is_responsible' => 'true',
                'date_of_birth' => '1980-01-01'
            ],
            [
                'email' => 'teacher2@department.edu',
                'name' => 'John',
                'surname' => 'Doe',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Associate Professor',
                'research_domain' => 'AI',
                'is_responsible' => 'false',
                'date_of_birth' => '1980-01-01'
            ]
        ];

        $filename = $this->createTestFile('teacher', $data);
        $file = new UploadedFile($filename, 'teachers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'teacher',
                'file' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('teachers', [
            'name' => 'Jane',
            'surname' => 'Smith',
            'grade' => 'Professor',
            'is_responsible' => true
        ]);
        $this->assertDatabaseHas('teachers', [
            'name' => 'John',
            'surname' => 'Doe',
            'grade' => 'Associate Professor',
            'is_responsible' => false
        ]);
    }

    public function test_can_import_teachers_with_various_boolean_formats()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            // Test different boolean formats
            [
                'email' => 'teacher1@edu.dz',
                'name' => 'T1',
                'surname' => 'Test',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'CS',
                'is_responsible' => 'true',
                'date_of_birth' => '1980-01-01'
            ],
            [
                'email' => 'teacher2@edu.dz',
                'name' => 'T2',
                'surname' => 'Test',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'CS',
                'is_responsible' => '1',
                'date_of_birth' => '1980-01-01'
            ],
            [
                'email' => 'teacher3@edu.dz',
                'name' => 'T3',
                'surname' => 'Test',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'CS',
                'is_responsible' => 'yes',
                'date_of_birth' => '1980-01-01'
            ],
            [
                'email' => 'teacher4@edu.dz',
                'name' => 'T4',
                'surname' => 'Test',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'CS',
                'is_responsible' => 'false',
                'date_of_birth' => '1980-01-01'
            ],
            [
                'email' => 'teacher5@edu.dz',
                'name' => 'T5',
                'surname' => 'Test',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'CS',
                'is_responsible' => '0',
                'date_of_birth' => '1980-01-01'
            ],
            [
                'email' => 'teacher6@edu.dz',
                'name' => 'T6',
                'surname' => 'Test',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'CS',
                'is_responsible' => 'no',
                'date_of_birth' => '1980-01-01'
            ]
        ];

        $filename = $this->createTestFile('teacher', $data);
        $file = new UploadedFile($filename, 'teachers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'teacher',
                'file' => $file
            ]);

        $response->assertStatus(201);
        
        // Check true values
        foreach (['T1', 'T2', 'T3'] as $name) {
            $this->assertDatabaseHas('teachers', [
                'name' => $name,
                'is_responsible' => true
            ]);
        }

        // Check false values
        foreach (['T4', 'T5', 'T6'] as $name) {
            $this->assertDatabaseHas('teachers', [
                'name' => $name,
                'is_responsible' => false
            ]);
        }
    }

    public function test_teacher_import_handles_missing_responsibility()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'email' => 'teacher@department.edu',
                'name' => 'Jane',
                'surname' => 'Smith',
                'recruitment_date' => '2020-01-01',
                'grade' => 'Professor',
                'research_domain' => 'Computer Science',
                'date_of_birth' => '1980-01-01'
                // is_responsible is missing
            ]
        ];

        $filename = $this->createTestFile('teacher', $data);
        $file = new UploadedFile($filename, 'teachers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'teacher',
                'file' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('teachers', [
            'name' => 'Jane',
            'surname' => 'Smith',
            'is_responsible' => false // Should default to false
        ]);
    }

    public function test_rejects_files_larger_than_5mb()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        // Create a fake large file instead of actual spreadsheet
        $fakeContent = str_repeat('x', 6 * 1024 * 1024); // 6MB of data
        $filename = Storage::path('large_test_import.xlsx');
        file_put_contents($filename, $fakeContent);

        $file = new UploadedFile(
            $filename,
            'large_teachers.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->assertGreaterThan(5242880, filesize($filename), 'Test file is not large enough');

        $response = $this->actingAs($admin)
            ->postJson('/api/users/import', [
                'type' => 'teacher',
                'file' => $file
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'File size exceeds limit',
                'error' => 'Maximum file size is 5MB'
            ]);

        $this->assertDatabaseMissing('user_import_logs', [
            'import_file_name' => 'large_teachers.xlsx'
        ]);

        // Cleanup
        @unlink($filename);
    }
}
