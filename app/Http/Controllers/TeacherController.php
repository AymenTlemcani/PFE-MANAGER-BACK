<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Project;
use App\Models\JuryPreference;
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

    public function selectProjectsToSupervise(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_ids' => 'required|array',
            'project_ids.*' => 'exists:projects,project_id'
        ]);

        foreach ($validated['project_ids'] as $projectId) {
            Project::where('project_id', $projectId)
                  ->whereNull('supervisor_id')
                  ->update(['supervisor_id' => auth()->id()]);
        }

        return response()->json(['message' => 'Projects selected for supervision']);
    }

    public function submitJuryPreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.project_id' => 'required|exists:projects,project_id',
            'preferences.*.order' => 'required|integer|min:1'
        ]);

        foreach ($validated['preferences'] as $preference) {
            JuryPreference::create([
                'teacher_id' => auth()->id(),
                'project_id' => $preference['project_id'],
                'preference_order' => $preference['order'],
                'preference_date' => now()
            ]);
        }

        return response()->json(['message' => 'Jury preferences submitted']);
    }

    public function validateProject(Request $request, int $projectId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Validated,Rejected',
            'comments' => 'nullable|string'
        ]);

        $project = Project::findOrFail($projectId);
        $project->update([
            'status' => $validated['status'],
            'review_comments' => $validated['comments'],
            'last_updated_date' => now()
        ]);

        return response()->json($project);
    }
}