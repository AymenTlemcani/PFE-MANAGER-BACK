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
    public function index(): JsonResponse
    {
        $users = User::all();
        return response()->json($users);
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
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(null, 204);
    }

    public function importUsers(Request $request): JsonResponse 
    {
        $request->validate([
            'type' => 'required|in:student,teacher,company',
            'file' => 'required|file|mimes:xlsx,csv,txt'
        ]);

        try {
            $import = new UserImport($request->type);
            $import->import($request->file('file'));

            // Create import log
            UserImportLog::create([
                'imported_by' => auth()->id(),
                'import_type' => $request->type,
                'total_records_imported' => $import->getRowCount(),
                'successful_imports' => $import->getRowCount() - $import->getFailureCount(),
                'failed_imports' => $import->getFailureCount(),
                'import_file_name' => $request->file('file')->getClientOriginalName(),
                'import_status' => $import->getFailureCount() === 0 ? 'Completed' : 'Completed with errors',
                'import_date' => now()
            ]);

            return response()->json([
                'message' => 'Import completed successfully',
                'total' => $import->getRowCount(),
                'success' => $import->getRowCount() - $import->getFailureCount(),
                'failures' => $import->getFailureCount()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}