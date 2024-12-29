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
            
            foreach ($rows as $row) {
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }
                
                ++$this->rows;
                
                // Convert to associative array
                $data = array_combine($headers, array_pad($row, count($headers), null));
                
                // Validate data
                $validator = Validator::make($data, $this->rules());
                
                if ($validator->fails()) {
                    ++$this->failures;
                    continue;
                }

                try {
                    $this->importRow($data);
                } catch (\Exception $e) {
                    ++$this->failures;
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
        // Create base user
        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make('changeme123'),
            'role' => ucfirst($this->type),
            'must_change_password' => true,
            'language_preference' => 'French'
        ]);

        // Create role-specific record
        match($this->type) {
            'student' => Student::create([
                'user_id' => $user->user_id,
                'name' => $data['name'],
                'surname' => $data['surname'],
                'master_option' => $data['master_option'],
                'overall_average' => $data['overall_average'],
                'admission_year' => $data['admission_year']
            ]),
            'teacher' => Teacher::create([
                'user_id' => $user->user_id,
                'name' => $data['name'],
                'surname' => $data['surname'],
                'recruitment_date' => $data['recruitment_date'],
                'grade' => $data['grade'],
                'research_domain' => $data['research_domain'] ?? null
            ]),
            'company' => Company::create([
                'user_id' => $user->user_id,
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'],
                'contact_surname' => $data['contact_surname'],
                'industry' => $data['industry'],
                'address' => $data['address']
            ]),
            default => null
        };
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