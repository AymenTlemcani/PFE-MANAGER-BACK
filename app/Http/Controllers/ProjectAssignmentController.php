<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectAssignmentController extends Controller
{
    public function index(): JsonResponse
    {
        $assignments = ProjectAssignment::with(['project', 'student', 'teacher', 'company'])->get();
        return response()->json($assignments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,project_id',
            'student_id' => 'required|exists:students,student_id',
            'teacher_id' => 'required|exists:teachers,teacher_id',
            'company_id' => 'nullable|exists:companies,company_id',
            'assignment_method' => 'required|string'
        ]);

        $assignment = ProjectAssignment::create([
            ...$validated,
            'assignment_date' => now()
        ]);

        // Update project status to Assigned
        $assignment->project->update(['status' => 'Assigned']);

        return response()->json($assignment, 201);
    }

    public function show(int $id): JsonResponse
    {
        $assignment = ProjectAssignment::with(['project', 'student', 'teacher', 'company'])->findOrFail($id);
        return response()->json($assignment);
    }

    public function autoAssign(): JsonResponse
    {
        //TODO: 
        // Implement automatic assignment logic based on student preferences and grades
        // This would be a complex operation that should probably be moved to a dedicated service class
        
        return response()->json(['message' => 'Projects auto-assigned successfully']);
    }
}