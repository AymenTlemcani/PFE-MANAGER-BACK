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
            
            // Write headers
            $headers = match($type) {
                'student' => ['email', 'name', 'surname', 'master_option', 'overall_average', 'admission_year'],
                'teacher' => ['email', 'name', 'surname', 'recruitment_date', 'grade', 'research_domain'],
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
        
        // Set headers based on type
        $headers = match($type) {
            'student' => ['email', 'name', 'surname', 'master_option', 'overall_average', 'admission_year'],
            'teacher' => ['email', 'name', 'surname', 'recruitment_date', 'grade', 'research_domain'],
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
}
