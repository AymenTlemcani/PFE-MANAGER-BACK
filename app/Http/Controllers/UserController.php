<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserImportLog;
use App\Imports\UserImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    private function checkAdminAccess(): void
    {
        if (!auth()->user() || auth()->user()->role !== 'Administrator') {
            abort(403, 'Unauthorized. Admin access required.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $request->validate([
            'search' => 'nullable|string',
            'role' => 'nullable|in:Administrator,Teacher,Student,Company',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = User::with(['administrator', 'teacher', 'student', 'company']);

        // Apply search filter
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('email', 'like', "%{$searchTerm}%")
                  ->orWhereHas('administrator', function($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('surname', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('teacher', function($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('surname', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('student', function($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('surname', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('company', function($q) use ($searchTerm) {
                      $q->where('company_name', 'like', "%{$searchTerm}%")
                        ->orWhere('contact_name', 'like', "%{$searchTerm}%")
                        ->orWhere('contact_surname', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Apply role filter
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Apply sorting
        $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));

        $users = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'filters' => [
                'search' => $request->search,
                'role' => $request->role,
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        // Base validation rules
        $rules = [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:Administrator,Teacher,Student,Company',
            'language_preference' => 'required|in:French,English',
            'date_of_birth' => 'nullable|date'
        ];

        // Add role-specific validation rules
        switch ($request->role) {
            case 'Student':
                $rules += [
                    'name' => 'required|string',
                    'surname' => 'required|string',
                    'master_option' => 'required|in:GL,IA,RSD,SIC',
                    'overall_average' => 'required|numeric|between:0,20',
                    'admission_year' => 'required|integer'
                ];
                break;
            case 'Teacher':
                $rules += [
                    'name' => 'required|string',
                    'surname' => 'required|string',
                    'recruitment_date' => 'required|date',
                    'grade' => 'required|in:MAA,MAB,MCA,MCB,PR',
                    'research_domain' => 'nullable|string',
                    'is_responsible' => 'boolean'
                ];
                break;
            case 'Company':
                $rules += [
                    'company_name' => 'required|string',
                    'contact_name' => 'required|string',
                    'contact_surname' => 'required|string',
                    'industry' => 'required|string',
                    'address' => 'required|string'
                ];
                break;
            case 'Administrator':
                $rules += [
                    'name' => 'required|string',
                    'surname' => 'required|string'
                ];
                break;
        }

        $validated = $request->validate($rules);

        \DB::beginTransaction();
        try {
            // Create base user
            $userData = array_intersect_key($validated, array_flip([
                'email', 'password', 'role', 'language_preference', 'date_of_birth'
            ]));
            $userData['password'] = Hash::make($userData['password']);
            $user = User::create($userData);

            // Create role-specific record
            switch ($request->role) {
                case 'Student':
                    $user->student()->create(array_intersect_key($validated, array_flip([
                        'name', 'surname', 'master_option', 'overall_average', 'admission_year'
                    ])));
                    break;
                case 'Teacher':
                    $user->teacher()->create(array_intersect_key($validated, array_flip([
                        'name', 'surname', 'recruitment_date', 'grade', 'research_domain', 'is_responsible'
                    ])));
                    break;
                case 'Company':
                    $user->company()->create(array_intersect_key($validated, array_flip([
                        'company_name', 'contact_name', 'contact_surname', 'industry', 'address'
                    ])));
                    break;
                case 'Administrator':
                    $user->administrator()->create(array_intersect_key($validated, array_flip([
                        'name', 'surname'
                    ])));
                    break;
            }

            \DB::commit();
            return response()->json($user->load(strtolower($request->role)), 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Error creating user', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $this->checkAdminAccess();

        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkAdminAccess();

        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'email' => 'email|unique:users,email,' . $id . ',user_id',
            'is_active' => 'boolean',
            'language_preference' => 'in:French,English',
            'date_of_birth' => 'nullable|date'
        ]);

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->checkAdminAccess();

        if ($id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(null, 204);
    }

    public function importUsers(Request $request): JsonResponse 
    {
        $this->checkAdminAccess();

        try {
            // Check file size first before validation
            if ($request->hasFile('file') && $request->file('file')->getSize() > 5242880) { // 5MB in bytes
                return response()->json([
                    'message' => 'File size exceeds limit',
                    'error' => 'Maximum file size is 5MB'
                ], 400);
            }

            $validated = $request->validate([
                'type' => 'required|in:student,teacher,company',
                'file' => 'required|file|mimes:xlsx,csv,txt'
            ]);

            $import = new UserImport($request->type);
            $import->import($request->file('file'));

            $status = $import->getFailureCount() === 0 ? 'Completed' : 'Completed with errors';
            
            // Create import log
            $log = UserImportLog::create([
                'imported_by' => auth()->id(),
                'import_type' => $request->type,
                'total_records_imported' => $import->getRowCount(),
                'successful_imports' => $import->getRowCount() - $import->getFailureCount(),
                'failed_imports' => $import->getFailureCount(),
                'import_file_name' => $request->file('file')->getClientOriginalName(),
                'import_status' => $status,
                'import_date' => now()
            ]);

            return response()->json([
                'message' => "Import completed with status: $status",
                'import_log_id' => $log->import_log_id,
                'statistics' => [
                    'total_records' => $import->getRowCount(),
                    'successful_imports' => $import->getRowCount() - $import->getFailureCount(),
                    'failed_imports' => $import->getFailureCount(),
                ],
                'successful_rows' => $import->getSuccessfulRows(),
                'failed_rows' => $import->getFailedRows(),
                'errors' => $import->getErrors()
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Import failed', [
                'error' => $e->getMessage(),
                'file' => $request->file('file')->getClientOriginalName()
            ]);
            
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|integer|exists:users,user_id'
        ]);

        if (in_array(auth()->id(), $validated['user_ids'])) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        try {
            \DB::beginTransaction();
            User::whereIn('user_id', $validated['user_ids'])->delete();
            \DB::commit();

            return response()->json([
                'message' => 'Users deleted successfully',
                'count' => count($validated['user_ids'])
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Error deleting users', 'error' => $e->getMessage()], 500);
        }
    }
}