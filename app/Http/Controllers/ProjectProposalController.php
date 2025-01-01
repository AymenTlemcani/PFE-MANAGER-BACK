<?php

namespace App\Http\Controllers;

use App\Models\ProjectProposal;
use App\Models\Project; // Add this import
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProjectProposalController extends Controller
{
    public function index(): JsonResponse
    {
        $proposals = ProjectProposal::with(['project', 'submitter'])->get();
        return response()->json($proposals);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Check proposal limits for students
        if ($user->role === 'Student') {
            $proposalCount = ProjectProposal::where('submitted_by', $user->user_id)
                ->where('proposal_status', '!=', 'Rejected')
                ->count();
                
            if ($proposalCount >= 3) {
                throw ValidationException::withMessages([
                    'proposals' => ['You have reached the maximum limit of 3 project proposals']
                ]);
            }
        }

        $validated = $this->validateProposal($request);
        
        $proposal = ProjectProposal::create([
            ...$validated,
            'submitted_by' => $user->user_id,  // Fix: Use user_id instead of id
            'proposer_type' => $user->role,
            'proposal_order' => $this->getNextProposalOrder($user->user_id),  // Fix: Use user_id
            'proposal_status' => 'Pending'
        ]);

        return response()->json($proposal, 201);
    }

    private function validateProposal(Request $request): array
    {
        $user = auth()->user();
        $project = Project::findOrFail($request->project_id);

        // Validate project type matches user role
        if ($user->role === 'Teacher' && !in_array($project->type, ['Classical', 'Innovative'])) {
            throw ValidationException::withMessages([
                'type' => ['Teachers can only submit Classical or Innovative projects']
            ]);
        }

        $common = [
            'project_id' => 'required|exists:projects,project_id',
            'review_comments' => 'nullable|string',
            'co_supervisor_name' => 'nullable|string',
            'co_supervisor_surname' => 'nullable|string'
        ];

        $roleSpecific = match($user->role) {
            'Teacher' => [
                'co_supervisor_name' => 'required|string',
                'co_supervisor_surname' => 'required|string',
                'additional_details' => 'nullable|array'
            ],
            'Student' => [
                'partner_id' => [
                    'nullable',
                    'exists:students,student_id',
                    function($attribute, $value, $fail) use ($user) {
                        if ($value == $user->student->student_id) {
                            $fail('Cannot partner with yourself.');
                        }
                    }
                ],
                'additional_details' => 'nullable|array'
            ],
            'Company' => [
                'internship_details' => 'required|array',
                'internship_details.duration' => 'required|integer|min:4|max:12',
                'internship_details.location' => 'required|string'
            ],
            default => []
        };

        return $request->validate(array_merge($common, $roleSpecific));
    }

    private function getNextProposalOrder(int $userId): int
    {
        return ProjectProposal::where('submitted_by', $userId)
            ->where('proposal_status', '!=', 'Rejected')
            ->count() + 1;
    }

    public function show(int $id): JsonResponse
    {
        $proposal = ProjectProposal::with(['project', 'submitter'])->findOrFail($id);
        return response()->json($proposal);
    }

    public function update(Request $request, int $id): JsonResponse
{
    $proposal = ProjectProposal::findOrFail($id);
    $user = auth()->user();

    // Check if user can approve proposals
    if ($request->has('proposal_status') && $request->proposal_status === 'Approved') {
        if (!($user->role === 'Teacher' && $user->teacher->is_responsible)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update project status when proposal is approved
        $proposal->project()->update(['status' => 'Validated']);
    }

    $validated = $request->validate([
        'co_supervisor_name' => 'sometimes|string',
        'co_supervisor_surname' => 'sometimes|string',
        'proposal_status' => 'sometimes|in:Pending,Approved,Rejected',
        'review_comments' => 'nullable|string',
        'is_final_version' => 'sometimes|boolean'
    ]);

    // If marking as final version, ensure only one final version exists per student
    if ($request->has('is_final_version') && $request->is_final_version) {
        ProjectProposal::where('submitted_by', $proposal->submitted_by)
            ->where('proposal_id', '!=', $proposal->proposal_id)
            ->update(['is_final_version' => false]);
    }

    $proposal->update($validated);
    return response()->json($proposal);
}

    public function destroy(int $id): JsonResponse
    {
        $proposal = ProjectProposal::findOrFail($id);
        $proposal->delete();
        return response()->json(null, 204);
    }
}