<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPair;
use App\Models\Project;
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

    public function proposePair(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student1_id' => 'required|exists:students,student_id',
            'student2_id' => 'required|exists:students,student_id|different:student1_id'
        ]);

        $pair = StudentPair::create([
            'student1_id' => $validated['student1_id'],
            'student2_id' => $validated['student2_id'],
            'status' => 'Proposed',
            'proposed_date' => now()
        ]);

        // Send notification to student2
        
        return response()->json($pair, 201);
    }

    public function respondToPairRequest(Request $request, int $pairId): JsonResponse
    {
        $validated = $request->validate([
            'response' => 'required|in:Accepted,Rejected'
        ]);

        $pair = StudentPair::findOrFail($pairId);
        $pair->update([
            'status' => $validated['response'],
            'updated_date' => now()
        ]);

        return response()->json($pair);
    }

    public function proposeProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'summary' => 'required|string',
            'technologies' => 'required|string',
            'material_needs' => 'nullable|string',
            'type' => 'required|in:Classical,Innovative,StartUp,Patent',
            'option' => 'required|in:GL,IA,RSD,SIC'
        ]);

        $project = Project::create([
            ...$validated,
            'status' => 'Proposed',
            'submitted_by' => auth()->id(),
            'submission_date' => now()
        ]);

        return response()->json($project, 201);
    }
}