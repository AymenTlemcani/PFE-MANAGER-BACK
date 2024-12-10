<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'credentials' => ['Invalid credentials provided.']
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // Only load the relationship that matches the user's role
        $relationshipToLoad = strtolower($user->role);
        $user->load($relationshipToLoad);

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function profile(): JsonResponse
    {
        $user = Auth::user();
        return response()->json($user->load(['administrator', 'teacher', 'student', 'company']));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'language_preference' => 'in:French,English',
            'profile_picture_url' => 'nullable|url'
        ]);

        $user->update($validated);
        return response()->json($user);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|different:current_password'
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password is incorrect.']
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
            'must_change_password' => false
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        // Logic to handle password reset request
        return response()->json(['message' => 'Password reset link sent']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);
        // Logic to reset password
        return response()->json(['message' => 'Password has been reset']);
    }

    public function validateResetToken(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required']);
        // Logic to validate reset token
        return response()->json(['valid' => true]);
    }

    public function logout(): JsonResponse
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}