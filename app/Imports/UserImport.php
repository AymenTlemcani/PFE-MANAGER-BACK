<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Company;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserImport
{
    protected $type;
    protected $rows = 0;
    protected $failures = 0;
    protected $errors = [];
    protected $successfulRows = [];
    protected $failedRows = [];  // Add this property

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function import($file)
    {
        $fileType = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        
        try {
            if ($fileType === 'csv') {
                $reader = IOFactory::createReader('Csv');
                $reader->setDelimiter(',');
                $spreadsheet = $reader->load($file);
            } else {
                $spreadsheet = IOFactory::load($file);
            }
            
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header row and empty rows
            $headers = array_filter(array_shift($rows));
            
            foreach ($rows as $rowIndex => $row) {
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }
                
                ++$this->rows;
                
                // Convert to associative array
                $data = array_combine($headers, array_pad($row, count($headers), null));
                
                try {
                    // Validate data first
                    $validator = Validator::make($data, $this->rules());
                    if ($validator->fails()) {
                        throw new \Exception(implode(', ', array_map(
                            fn($messages) => implode(', ', $messages),
                            $validator->errors()->toArray()
                        )));
                    }
                    
                    $this->importRow($data);
                } catch (\Exception $e) {
                    ++$this->failures;
                    $this->failedRows[] = [
                        'row_number' => $rowIndex + 2, // Add 2 to account for header and 0-based index
                        'data' => $data,
                        'error_message' => $e->getMessage()
                    ];
                    continue;
                }
            }

            return $this;
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            throw new \Exception('Error reading file: ' . $e->getMessage());
        }
    }

    protected function importRow(array $data)
    {
        try {
            // Begin transaction
            \DB::beginTransaction();
            
            // Validate data first
            $validator = Validator::make($data, $this->rules());
            if ($validator->fails()) {
                throw new \Exception(implode(', ', array_map(
                    fn($messages) => implode(', ', $messages),
                    $validator->errors()->toArray()
                )));
            }

            // Create base user
            $user = User::create([
                'email' => $data['email'],
                'password' => Hash::make('changeme123'),
                'role' => ucfirst($this->type),
                'must_change_password' => true,
                'language_preference' => 'French'
            ]);

            // Create role-specific record with enhanced error handling
            $roleRecord = match($this->type) {
                'student' => $this->createStudent($user, $data),
                'teacher' => $this->createTeacher($user, $data),
                'company' => $this->createCompany($user, $data),
                default => throw new \Exception("Invalid user type: {$this->type}")
            };

            \DB::commit();

            // Record successful import
            $this->successfulRows[] = [
                'row_number' => $this->rows,
                'email' => $data['email'],
                'type' => $this->type,
                'details' => $roleRecord
            ];

        } catch (\Exception $e) {
            \DB::rollBack();
            ++$this->failures;
            
            // Store detailed failure information
            $this->failedRows[] = [
                'row_number' => $this->rows,
                'data' => $data,
                'error_message' => $e->getMessage()
            ];
            
            $this->errors[] = [
                'row' => $this->rows,
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'data' => $data
            ];
        }
    }

    protected function createStudent($user, $data)
    {
        $this->validateStudentData($data);
        return Student::create([
            'user_id' => $user->user_id,
            'name' => $data['name'],
            'surname' => $data['surname'],
            'master_option' => $data['master_option'],
            'overall_average' => $data['overall_average'],
            'admission_year' => $data['admission_year']
        ]);
    }

    protected function createTeacher($user, $data)
    {
        $this->validateTeacherData($data);
        return Teacher::create([
            'user_id' => $user->user_id,
            'name' => $data['name'],
            'surname' => $data['surname'],
            'recruitment_date' => $data['recruitment_date'],
            'grade' => $data['grade'],
            'research_domain' => $data['research_domain'] ?? null
        ]);
    }

    protected function createCompany($user, $data)
    {
        $this->validateCompanyData($data);
        return Company::create([
            'user_id' => $user->user_id,
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'],
            'contact_surname' => $data['contact_surname'],
            'industry' => $data['industry'],
            'address' => $data['address']
        ]);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessfulRows(): array
    {
        return $this->successfulRows;
    }

    // Add new getter method
    public function getFailedRows(): array
    {
        return $this->failedRows;
    }

    protected function validateStudentData($data)
    {
        if (!preg_match('/@.*\.dz$/i', $data['email'])) {
            throw new \Exception("Student email must use a .dz domain");
        }
        if (!in_array($data['master_option'], ['GL', 'IA', 'RSD', 'SIC'])) {
            throw new \Exception("Invalid master option: {$data['master_option']}");
        }
        if ($data['overall_average'] < 0 || $data['overall_average'] > 20) {
            throw new \Exception("Overall average must be between 0 and 20");
        }
    }

    protected function validateTeacherData($data)
    {
        $validGrades = ['Professor', 'Associate Professor', 'Assistant Professor'];
        if (!in_array($data['grade'], $validGrades)) {
            throw new \Exception("Invalid teacher grade: '{$data['grade']}'. Valid grades are: " . implode(', ', $validGrades));
        }
        if (!strtotime($data['recruitment_date'])) {
            throw new \Exception("Invalid recruitment date format for: {$data['email']}");
        }
    }

    protected function validateCompanyData($data)
    {
        if (empty($data['company_name'])) {
            throw new \Exception("Company name is required");
        }
        if (empty($data['industry'])) {
            throw new \Exception("Industry field is required");
        }
    }

    public function rules(): array
    {
        return match($this->type) {
            'student' => [
                'email' => ['required', 'email', 'unique:users', 'regex:/@.*\.dz$/i'],
                'name' => 'required|string',
                'surname' => 'required|string',
                'master_option' => 'required|in:GL,IA,RSD,SIC',
                'overall_average' => 'required|numeric|between:0,20',
                'admission_year' => 'required|integer'
            ],
            'teacher' => [
                'email' => ['required', 'email', 'unique:users'],
                'name' => 'required|string',
                'surname' => 'required|string',
                'recruitment_date' => 'required|date',
                'grade' => 'required|in:Professor,Associate Professor,Assistant Professor',
                'research_domain' => 'nullable|string'
            ],
            'company' => [
                'email' => ['required', 'email', 'unique:users'],
                'company_name' => 'required|string',
                'contact_name' => 'required|string',
                'contact_surname' => 'required|string',
                'industry' => 'required|string',
                'address' => 'required|string'
            ],
            default => []
        };
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