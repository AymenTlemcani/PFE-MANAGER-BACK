<?php

namespace App\Http\Controllers;

use App\Models\JuryPreference;
use App\Models\Teacher;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JuryPreferenceController extends Controller
{
    public function index(): JsonResponse
    {
        $preferences = JuryPreference::with(['teacher', 'project'])->get();
        return response()->json($preferences);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,project_id',
            'preference_order' => 'required|integer|min:1'
        ]);

        // Get authenticated teacher
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();

        $preference = JuryPreference::create([
            'teacher_id' => $teacher->teacher_id,
            'project_id' => $validated['project_id'],
            'preference_order' => $validated['preference_order'],
            'preference_date' => now()
        ]);

        return response()->json($preference, 201);
    }

    public function getTeacherPreferences(): JsonResponse
    {
        $teacher = Teacher::where('user_id', auth()->id())->firstOrFail();
        $preferences = JuryPreference::with('project')
            ->where('teacher_id', $teacher->teacher_id)
            ->orderBy('preference_order')
            ->get();

        return response()->json($preferences);
    }

    public function delete(int $id): JsonResponse
    {
        $preference = JuryPreference::findOrFail($id);
        $preference->delete();
        return response()->json(null, 204);
    }
}