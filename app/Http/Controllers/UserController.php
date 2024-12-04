<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
            'file' => 'required|file|mimes:csv,txt',
            'type' => 'required|in:student,teacher,company'
        ]);

        DB::beginTransaction();
        try {
            // Import logic here
            $file = $request->file('file');
            $imported = $this->processImport($file, $request->type);
            
            DB::commit();
            return response()->json([
                'message' => 'Users imported successfully',
                'imported' => $imported
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function processImport($file, string $type): array 
    {
        // Process CSV file based on type
        // Return import statistics
        return [
            'total' => 0,
            'success' => 0,
            'failed' => 0
        ];
    }
}