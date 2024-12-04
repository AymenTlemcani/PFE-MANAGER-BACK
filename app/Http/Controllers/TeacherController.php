<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherController extends Controller
{
    public function index(): JsonResponse
    {
        $teachers = Teacher::with('user')->get();
        return response()->json($teachers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'name' => 'required|string',
            'surname' => 'required|string',
            'recruitment_date' => 'required|date',
            'grade' => 'required|in:Professor,Associate Professor,Assistant Professor',
            'is_responsible' => 'boolean',
            'research_domain' => 'nullable|string'
        ]);

        $teacher = Teacher::create($validated);
        return response()->json($teacher, 201);
    }

    public function show(int $id): JsonResponse
    {
        $teacher = Teacher::with('user')->findOrFail($id);
        return response()->json($teacher);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string',
            'surname' => 'string',
            'recruitment_date' => 'date',
            'grade' => 'in:Professor,Associate Professor,Assistant Professor',
            'is_responsible' => 'boolean',
            'research_domain' => 'nullable|string'
        ]);

        $teacher->update($validated);
        return response()->json($teacher);
    }

    public function destroy(int $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();
        return response()->json(null, 204);
    }
}