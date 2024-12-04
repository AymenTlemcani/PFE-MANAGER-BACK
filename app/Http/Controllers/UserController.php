<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

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
}