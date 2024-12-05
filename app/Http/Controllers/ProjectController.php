<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $projects = Project::with(['submitter', 'proposal', 'assignment'])->get();
        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Common validation rules
        $commonRules = [
            'title' => 'required|string',
            'summary' => 'required|string',
            'technologies' => 'required|string',
            'material_needs' => 'nullable|string',
            'option' => 'required|in:GL,IA,RSD,SIC'
        ];

        // Role-specific validation rules
        $roleSpecificRules = [];
        
        switch ($user->role) {
            case 'Teacher':
                $roleSpecificRules = [
                    'type' => 'required|in:Classical,Innovative',
                    'co_supervisor_name' => 'required|string',
                    'co_supervisor_surname' => 'required|string'
                ];
                break;
            case 'Student':
                $roleSpecificRules = [
                    'type' => 'required|in:Innovative,StartUp,Patent',
                    'partner_id' => 'nullable|exists:students,student_id|different:' . $user->student->student_id
                ];
                break;
            case 'Company':
                $roleSpecificRules = [
                    'type' => 'required|in:Classical,StartUp',
                    'company_name' => 'required|string'
                ];
                break;
            default:
                return response()->json(['message' => 'Unauthorized to propose projects'], 403);
        }

        $validated = $request->validate(array_merge($commonRules, $roleSpecificRules));

        // Create project
        $project = Project::create([
            ...$validated,
            'status' => 'Proposed',
            'submitted_by' => $user->user_id,
            'submission_date' => now(),
            'last_updated_date' => now()
        ]);

        // Handle role-specific logic
        if ($user->role === 'Teacher' && isset($validated['co_supervisor_name'])) {
            $project->proposal()->create([
                'submitted_by' => $user->user_id,
                'co_supervisor_name' => $validated['co_supervisor_name'],
                'co_supervisor_surname' => $validated['co_supervisor_surname'],
                'proposal_status' => 'Pending'
            ]);
        } elseif ($user->role === 'Company') {
            // Create company project proposal
            $project->proposal()->create([
                'submitted_by' => $user->user_id,
                'co_supervisor_name' => $user->company->contact_name,
                'co_supervisor_surname' => $user->company->contact_surname,
                'proposal_status' => 'Pending'
            ]);
        }

        return response()->json($project, 201);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with(['submitter', 'proposal', 'assignment', 'defenseSession'])->findOrFail($id);
        return response()->json($project);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'string',
            'summary' => 'string',
            'technologies' => 'string',
            'material_needs' => 'nullable|string',
            'type' => 'in:Classical,Innovative,StartUp,Patent',
            'option' => 'in:GL,IA,RSD,SIC',
            'status' => 'in:Proposed,Validated,Assigned,InProgress,Completed'
        ]);

        $project->update([
            ...$validated,
            'last_updated_date' => now()
        ]);

        return response()->json($project);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->delete();
        return response()->json(null, 204);
    }
}