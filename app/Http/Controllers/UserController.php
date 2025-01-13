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
    public function index(Request $request): JsonResponse
    {
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
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:Administrator,Teacher,Student,Company',
            'language_preference' => 'required|in:French,English',
            'date_of_birth' => 'nullable|date'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, int $id): JsonResponse
    {
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
        if ($id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(null, 204);
    }

    public function importUsers(Request $request): JsonResponse 
    {
        if (!auth()->user() || auth()->user()->role !== 'Administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type' => 'required|in:student,teacher,company',
            'file' => 'required|file|mimes:xlsx,csv,txt'
        ]);

        try {
            // Validate file size
            if ($request->file('file')->getSize() > 5242880) { // 5MB limit
                return response()->json([
                    'message' => 'File size exceeds limit',
                    'error' => 'Maximum file size is 5MB'
                ], 400);
            }

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
                'successful_rows' => array_map(function($row) {
                    if (isset($row['details'])) {
                        $row['details']->date_of_birth = User::find($row['details']->user_id)->date_of_birth;
                    }
                    return $row;
                }, $import->getSuccessfulRows()),
                'failed_rows' => $import->getFailedRows(),
                'errors' => $import->getErrors(),
                'warnings' => [], // For future use with non-critical issues
                'file_info' => [
                    'name' => $request->file('file')->getClientOriginalName(),
                    'size' => $request->file('file')->getSize(),
                    'type' => $request->file('file')->getMimeType(),
                ]
            ], 201);

        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            return response()->json([
                'message' => 'File reading error',
                'error' => $e->getMessage(),
                'type' => 'file_error'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
                'type' => 'system_error'
            ], 500);
        }
    }
}