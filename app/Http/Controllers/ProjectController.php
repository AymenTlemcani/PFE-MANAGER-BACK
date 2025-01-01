<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'technologies' => 'required|string',
            'material_needs' => 'nullable|string',
            'option' => 'required|in:GL,IA,RSD,SIC'
        ];

        // Role-specific validation rules and submission limits
        switch ($user->role) {
            case 'Teacher':
                $this->validateTeacherSubmission($request);
                break;
                
            case 'Student':
                $this->validateStudentSubmission($request);
                break;
                
            case 'Company':
                $this->validateCompanySubmission($request);
                break;
                
            default:
                return response()->json(['message' => 'Unauthorized to propose projects'], 403);
        }

        $validated = $request->validate($commonRules);

        // Create project with common fields
        $project = Project::create([
            ...$validated,
            'status' => 'Proposed',
            'submitted_by' => $user->user_id,
            'submission_date' => now(),
            'last_updated_date' => now(),
            'type' => $request->type
        ]);

        // Handle type-specific data
        if ($request->type === 'Internship') {
            $project->update([
                'company_name' => $request->company_name,
                'internship_location' => $request->internship_location,
                'internship_salary' => $request->internship_salary,
                'internship_start_date' => $request->internship_start_date,
                'internship_duration_months' => $request->internship_duration_months,
            ]);
        }

        // Create proposal record
        $project->proposal()->create([
            'submitted_by' => $user->user_id,
            'co_supervisor_name' => $request->co_supervisor_name ?? null,
            'co_supervisor_surname' => $request->co_supervisor_surname ?? null,
            'proposal_status' => 'Pending'
        ]);

        return response()->json($project, 201);
    }

    private function validateTeacherSubmission(Request $request): void
    {
        if (!in_array($request->type, ['Classical', 'Innovative'])) {
            throw ValidationException::withMessages([
                'type' => ['Teachers can only submit Classical or Innovative projects']
            ]);
        }

        $request->validate([
            'type' => 'required|in:Classical,Innovative',
            'co_supervisor_name' => 'required|string',
            'co_supervisor_surname' => 'required|string'
        ]);
    }

    private function validateStudentSubmission(Request $request): void
    {
        // Check if student has already submitted 3 projects
        $submissionCount = Project::where('submitted_by', auth()->id())
            ->where('status', '!=', 'Rejected')
            ->count();

        if ($submissionCount >= 3) {
            throw ValidationException::withMessages([
                'submissions' => ['Maximum number of project proposals (3) reached']
            ]);
        }

        $currentStudent = auth()->user()->student;

        // Validate partner_id is not self
        if ($request->partner_id === $currentStudent->student_id) {
            throw ValidationException::withMessages([
                'partner_id' => ['Cannot partner with yourself']
            ]);
        }

        $request->validate([
            'type' => 'required|in:Innovative,StartUp,Patent',
            'partner_id' => [
                'nullable',
                'exists:students,student_id',
                'different:' . $currentStudent->student_id
            ]
        ]);
    }

    private function validateCompanySubmission(Request $request): void
    {
        $rules = [
            'type' => 'required|in:Internship',
            'company_name' => 'required|string',
            'internship_location' => 'required|string',
            'internship_salary' => 'nullable|numeric|min:0',
            'internship_start_date' => 'required|date|after:today',
            'internship_duration_months' => 'required|integer|min:4|max:12'
        ];

        $request->validate($rules);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with(['submitter', 'proposal', 'assignment', 'defenseSession'])->findOrFail($id);
        return response()->json($project);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        
        // Check if project is already validated
        if ($project->status === 'Validated') {
            return response()->json(['message' => 'Cannot modify a validated project'], 403);
        }
        
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