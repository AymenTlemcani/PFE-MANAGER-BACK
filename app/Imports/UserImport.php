<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Company;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Notifications\TemporaryPasswordNotification;
use App\Services\EmailService;

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
            } else {
                $reader = IOFactory::createReader('Xlsx');
            }
            
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Get and validate headers
            $headers = array_filter(array_map('trim', array_shift($rows)));
            if (empty($headers)) {
                throw new \Exception('No headers found in file');
            }

            foreach ($rows as $rowIndex => $row) {
                // Skip completely empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                ++$this->rows;
                
                // Clean and combine data
                $rowData = array_combine(
                    $headers,
                    array_pad(array_slice($row, 0, count($headers)), count($headers), null)
                );
                
                // Clean the data
                $rowData = array_map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                }, $rowData);

                try {
                    $this->importRow($rowData);
                } catch (\Exception $e) {
                    ++$this->failures;
                    $this->failedRows[] = [
                        'row_number' => $rowIndex + 2,
                        'data' => $rowData,
                        'error_message' => $e->getMessage()
                    ];
                }
            }

            return $this;
        } catch (\Exception $e) {
            throw new \Exception('Error processing file: ' . $e->getMessage());
        }
    }

    protected function importRow(array $data)
    {
        try {
            \DB::beginTransaction();
            
            // Validate data
            $validator = Validator::make($data, $this->rules());
            if ($validator->fails()) {
                throw new \Exception(implode(', ', array_map(
                    fn($messages) => implode(', ', $messages),
                    $validator->errors()->toArray()
                )));
            }

            // Create user record
            $tempPass = $this->generateTemporaryPasswordFromData($data);
            $user = $this->createUserRecord($data, $tempPass);

            // Create role-specific record
            $roleRecord = match($this->type) {
                'student' => $this->createStudent($user, $data),
                'teacher' => $this->createTeacher($user, $data),
                'company' => $this->createCompany($user, $data),
                default => throw new \Exception("Invalid user type: {$this->type}")
            };

            // Send email with temporary password
            $emailService = app(EmailService::class);
            $emailSent = $emailService->sendTemporaryPassword(
                $user,
                $tempPass,
                7 // expiry days
            );

            if (!$emailSent) {
                \Log::warning('Failed to send temporary password email', [
                    'user_id' => $user->user_id,
                    'email' => $user->email
                ]);
            }

            \DB::commit();

            // Record successful import
            $this->successfulRows[] = [
                'row_number' => $this->rows,
                'email' => $data['email'],
                'type' => $this->type,
                'details' => $roleRecord,
                'email_sent' => $emailSent
            ];

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->handleImportError($e, $data);
        }
    }

    protected function createUserRecord(array $data, string $tempPass)
    {
        $expirationDate = now()->addDays(7);

        return User::create([
            'email' => $data['email'],
            'password' => Hash::make($tempPass),
            'role' => ucfirst($this->type),
            'must_change_password' => true,
            'language_preference' => 'French',
            'temporary_password' => $tempPass,
            'temporary_password_expiration' => $expirationDate,
            'date_of_birth' => $data['date_of_birth'] ?? null
        ]);
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
        
        // Convert is_responsible to boolean with explicit default
        $isResponsible = false;
        if (isset($data['is_responsible'])) {
            $isResponsible = $this->parseBoolean($data['is_responsible']);
        }

        $teacherData = [
            'user_id' => $user->user_id,
            'name' => $data['name'],
            'surname' => $data['surname'],
            'recruitment_date' => $data['recruitment_date'],
            'grade' => $data['grade'],
            'research_domain' => $data['research_domain'] ?? null,
            'is_responsible' => $isResponsible
        ];

        return Teacher::create($teacherData);
    }

    protected function createCompany($user, $data)
    {
        $this->validateCompanyData($data);
        
        // Ensure company data matches expected format
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

    protected function generateTemporaryPasswordFromData(array $data): string 
    {
        if ($this->type === 'company') {
            // For companies, use company name instead of personal name
            $name = preg_replace('/[^a-zA-Z]/', '', $data['company_name']);
            $name = substr($name, 0, 3); // Take first 3 characters
            
            // Use current date instead of birth date for companies
            $date = date('dmy');
        } else {
            // For students and teachers
            $name = preg_replace('/[^a-zA-Z]/', '', $data['name']);
            $name = substr($name, 0, 3);
            $date = date('dmy', strtotime($data['date_of_birth']));
        }
        
        // Add a random special character
        $specialChars = '!@#$%^&*';
        $specialChar = $specialChars[rand(0, strlen($specialChars) - 1)];
        
        // Add a random number (0-9)
        $randomNum = rand(0, 9);
        
        // Combine all parts and make first letter uppercase
        $password = ucfirst(strtolower($name)) . $specialChar . $date . $randomNum;
        
        return $password;
    }

    public function rules(): array
    {
        return match($this->type) {
            'student' => [
                'email' => ['required', 'email', 'unique:users', 'regex:/@.*\.(dz|com)$/i'], // Updated regex
                'name' => 'required|string',
                'surname' => 'required|string',
                'master_option' => 'required|in:GL,IA,RSD,SIC',
                'overall_average' => 'required|numeric|between:0,20',
                'admission_year' => 'required|integer',
                'date_of_birth' => 'required|date|before:today'
            ],
            'teacher' => [
                'email' => ['required', 'email', 'unique:users'],
                'name' => 'required|string',
                'surname' => 'required|string',
                'recruitment_date' => 'required|date',
                'grade' => 'required|in:MAA,MAB,MCA,MCB,PR',
                'research_domain' => 'nullable|string',
                'is_responsible' => 'nullable',
                'date_of_birth' => 'required|date|before:today'
            ],
            'company' => [
                'email' => ['required', 'email', 'unique:users'],
                'company_name' => 'required|string',
                'contact_name' => 'required|string',
                'contact_surname' => 'required|string',
                'industry' => 'required|string',
                'address' => 'required|string'
            ],
            default => throw new \Exception("Invalid user type: {$this->type}")
        };
    }

    protected function parseBoolean($value): bool
    {
        if ($value === null) {
            return false;
        }
        
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return match($normalized) {
                'true', '1', 'yes', 'on' => true,
                'false', '0', 'no', 'off', '', 'null' => false,
                default => false
            };
        }

        return (bool)$value;
    }

    protected function validateStudentData($data): void
    {
        // Check for invalid domain first
        if (!preg_match('/@.*\.(dz|com)$/i', $data['email'])) { 
            throw new \Exception("Student email must use a .dz or .com domain");
        }

        // Continue with other validations only if email is valid
        if (!in_array($data['master_option'], ['GL', 'IA', 'RSD', 'SIC'])) {
            throw new \Exception("Invalid master option: {$data['master_option']}");
        }
        if ($data['overall_average'] < 0 || $data['overall_average'] > 20) {
            throw new \Exception("Overall average must be between 0 and 20");
        }
    }

    protected function validateTeacherData($data): void
    {
        $validGrades = ['MAA', 'MAB', 'MCA', 'MCB', 'PR'];
        if (!in_array($data['grade'], $validGrades)) {
            throw new \Exception("Invalid teacher grade: '{$data['grade']}'. Valid grades are: " . implode(', ', $validGrades));
        }
        if (!strtotime($data['recruitment_date'])) {
            throw new \Exception("Invalid recruitment date format for: {$data['email']}");
        }
    }

    protected function validateCompanyData($data): void
    {
        if (empty($data['company_name'])) {
            throw new \Exception("Company name is required");
        }
        if (empty($data['industry'])) {
            throw new \Exception("Industry field is required");
        }
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function getFailureCount(): int
    {
        return $this->failures;
    }

    protected function handleImportError(\Exception $e, array $data): void
    {
        ++$this->failures;
        
        $errorMessage = $e->getMessage();
        $this->errors[] = [
            'row' => $this->rows,
            'email' => $data['email'] ?? 'unknown',
            'error' => $errorMessage,
            'data' => $data
        ];

        $this->failedRows[] = [
            'row_number' => $this->rows,
            'data' => $data,
            'error_message' => $errorMessage
        ];
    }
}