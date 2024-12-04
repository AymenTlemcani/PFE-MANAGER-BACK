<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{
    public function index(): JsonResponse
    {
        $students = Student::with('user')->get();
        return response()->json($students);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'name' => 'required|string',
            'surname' => 'required|string',
            'master_option' => 'required|in:GL,IA,RSD,SIC',
            'overall_average' => 'required|numeric|between:0,20',
            'admission_year' => 'required|integer'
        ]);

        $student = Student::create($validated);
        return response()->json($student, 201);
    }

    public function show(int $id): JsonResponse
    {
        $student = Student::with(['user', 'pairs'])->findOrFail($id);
        return response()->json($student);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string',
            'surname' => 'string',
            'master_option' => 'in:GL,IA,RSD,SIC',
            'overall_average' => 'numeric|between:0,20',
            'admission_year' => 'integer'
        ]);

        $student->update($validated);
        return response()->json($student);
    }

    public function destroy(int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json(null, 204);
    }
}