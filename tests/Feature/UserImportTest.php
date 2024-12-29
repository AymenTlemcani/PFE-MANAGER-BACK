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
                'student' => ['email', 'name', 'surname', 'master_option', 'overall_average', 'admission_year'],
                'teacher' => ['email', 'name', 'surname', 'recruitment_date', 'grade', 'research_domain', 'is_responsible'],
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

    protected function createSpreadsheet(string $type, array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Updated headers to include is_responsible for teachers
        $headers = match($type) {
            'student' => ['email', 'name', 'surname', 'master_option', 'overall_average', 'admission_year'],
            'teacher' => ['email', 'name', 'surname', 'recruitment_date', 'grade', 'research_domain', 'is_responsible'],
            'company' => ['email', 'company_name', 'contact_name', 'contact_surname', 'industry', 'address'],
            default => []
        };

        // Write headers using coordinate system
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index); // Convert number to letter (A, B, C, etc.)
            $sheet->setCellValue($column . '1', $header);
        }

        // Write data
        foreach ($data as $rowIndex => $rowData) {
            foreach ($rowData as $colIndex => $value) {
                $column = chr(65 + $colIndex);
                $sheet->setCellValue($column . ($rowIndex + 2), $value);
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
                'student@esi.dz',  // Using .dz domain
                'John',
                'Doe',
                'GL',
                '16.50',
                '2023'
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
        $this->assertDatabaseHas('users', ['email' => 'student@esi.dz']);  // Fixed assertion
        $this->assertDatabaseHas('students', [
            'name' => 'John',
            'surname' => 'Doe',
            'master_option' => 'GL',
            'overall_average' => 16.50
        ]);
        $this->assertDatabaseHas('user_import_logs', [
            'import_type' => 'student',
            'successful_imports' => 1,
            'failed_imports' => 0
        ]);
    }

    public function test_cannot_import_student_with_invalid_email_domain()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'Administrator']);

        $data = [
            [
                'student@gmail.com', // Invalid domain (not .dz)
                'John',
                'Doe',
                'GL',
                '16.50',
                '2023'
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
                'teacher@department.edu',
                'Jane',
                'Smith',
                '2020-01-01',
                'Professor',
                'Computer Science'
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
                'contact@company.com',
                'Tech Corp',
                'Robert',
                'Johnson',
                'Technology',
                '123 Tech Street'
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
                'valid@esi.dz',  // Using .dz domain
                'John',
                'Doe',
                'GL',
                '16.50',
                '2023'
            ],
            [
                'invalid-email',
                'Jane',
                'Smith',
                'GL',
                '15.00',
                '2023'
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
                'student@esi.dz',  // Using .dz domain
                'John',
                'Doe',
                'GL',
                '16.50',
                '2023'
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
                'teacher@department.edu',
                'Jane',
                'Smith',
                '2020-01-01',
                'Professor',
                'Computer Science',
                'true'  // is_responsible
            ],
            [
                'teacher2@department.edu',
                'John',
                'Doe',
                '2020-01-01',
                'Associate Professor',
                'AI',
                'false' // is_responsible
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
            ['teacher1@edu.dz', 'T1', 'Test', '2020-01-01', 'Professor', 'CS', 'true'],
            ['teacher2@edu.dz', 'T2', 'Test', '2020-01-01', 'Professor', 'CS', '1'],
            ['teacher3@edu.dz', 'T3', 'Test', '2020-01-01', 'Professor', 'CS', 'yes'],
            ['teacher4@edu.dz', 'T4', 'Test', '2020-01-01', 'Professor', 'CS', 'false'],
            ['teacher5@edu.dz', 'T5', 'Test', '2020-01-01', 'Professor', 'CS', '0'],
            ['teacher6@edu.dz', 'T6', 'Test', '2020-01-01', 'Professor', 'CS', 'no']
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
                'teacher@department.edu',
                'Jane',
                'Smith',
                '2020-01-01',
                'Professor',
                'Computer Science'
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
