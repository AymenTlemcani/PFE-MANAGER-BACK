<?php

namespace App\Http\Controllers;

use App\Models\ProjectProposal;
use App\Models\Project; // Add this import
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use DB;

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
        
        if ($user->role === 'Student') {
            $activeProposals = ProjectProposal::getActiveProposalsCount($user->user_id);
            if ($activeProposals >= ProjectProposal::MAX_STUDENT_PROPOSALS) {
                throw ValidationException::withMessages([
                    'proposals' => ['Maximum limit of ' . ProjectProposal::MAX_STUDENT_PROPOSALS . ' active proposals reached']
                ]);
            }
        }

        $validated = $this->validateProposal($request);
        
        DB::beginTransaction();
        try {
            $proposal = ProjectProposal::create([
                ...$validated,
                'submitted_by' => $user->user_id,
                'proposer_type' => $user->role,
                'proposal_order' => ProjectProposal::getActiveProposalsCount($user->user_id) + 1,
                'proposal_status' => ProjectProposal::STATUS_PENDING
            ]);

            if ($request->has('is_final_version') && $request->is_final_version) {
                $this->setAsFinalVersion($proposal);
            }

            DB::commit();
            return response()->json($proposal, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
            'review_comments' => 'nullable|string'
        ];

        $roleSpecific = match($user->role) {
            'Teacher' => [
                'co_supervisor_name' => 'nullable|string',
                'co_supervisor_surname' => 'nullable|string',
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

        // Check for status approval/rejection first
        if ($request->has('proposal_status') && 
            in_array($request->proposal_status, [ProjectProposal::STATUS_APPROVED, ProjectProposal::STATUS_REJECTED])) {
            if (!$user->isResponsibleTeacher()) {
                return response()->json(['message' => 'Only responsible teachers can approve/reject proposals'], 403);
            }
            return $this->handleStatusUpdate($proposal, $request);
        }

        // Handle other actions
        if ($user->role === 'Teacher' && $proposal->canBeEditedBy($user)) {
            return $this->handleTeacherEdit($request, $proposal);
        }

        // Handle student accepting edits
        if ($user->role === 'Student' && $proposal->needsStudentReview()) {
            return $this->handleStudentReview($request, $proposal);
        }

        // Check if trying to approve/reject
        if ($request->has('proposal_status') && 
            in_array($request->proposal_status, [ProjectProposal::STATUS_APPROVED, ProjectProposal::STATUS_REJECTED])) {
            if (!$user->isResponsibleTeacher()) {
                return response()->json(['message' => 'Only responsible teachers can approve/reject proposals'], 403);
            }
        }

        // Check if proposal can be modified
        if (!$proposal->canBeModified() && !$user->isResponsibleTeacher()) {
            return response()->json(['message' => 'Proposal cannot be modified'], 403);
        }

        DB::beginTransaction();
        try {
            if ($request->has('proposal_status') && $user->isResponsibleTeacher()) {
                $this->handleStatusUpdate($proposal, $request->proposal_status);
            }

            if ($request->has('is_final_version') && $request->is_final_version) {
                $this->setAsFinalVersion($proposal);
            }

            $validated = $this->validateUpdateRequest($request);
            $proposal->update($validated);

            DB::commit();
            return response()->json($proposal);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function handleTeacherEdit(Request $request, ProjectProposal $proposal): JsonResponse
    {
        $validated = $request->validate([
            'co_supervisor_name' => 'sometimes|string',
            'co_supervisor_surname' => 'sometimes|string',
            'comments' => 'required|string',
            'suggested_changes' => 'required|array'
        ]);

        DB::transaction(function() use ($proposal, $validated) {
            $proposal->last_edited_version = $proposal->getAttributes();
            $proposal->edited_by = auth()->id();
            $proposal->edited_at = now();
            $proposal->edit_accepted = false;
            $proposal->proposal_status = ProjectProposal::STATUS_EDITED;
            $proposal->update($validated);
            
            // TODO: Notify student of changes
        });

        return response()->json($proposal);
    }

    private function handleStudentReview(Request $request, ProjectProposal $proposal): JsonResponse
    {
        $validated = $request->validate([
            'accept_changes' => 'required|boolean',
            'is_final_version' => 'sometimes|boolean',
            'comments' => 'required|string'
        ]);

        DB::transaction(function() use ($proposal, $validated, $request) {
            if ($validated['accept_changes']) {
                $proposal->acceptEdit();
                
                if ($request->has('is_final_version') && $request->is_final_version) {
                    $this->setAsFinalVersion($proposal);
                }
            } else {
                // Revert to last version and keep as pending
                $proposal->fill($proposal->last_edited_version);
                $proposal->proposal_status = ProjectProposal::STATUS_PENDING;
                $proposal->edited_by = null;
                $proposal->edited_at = null;
                $proposal->edit_accepted = null;
                $proposal->review_comments = $validated['comments'];
            }
            $proposal->save();
        });

        return response()->json($proposal);
    }

    private function handleStatusUpdate(ProjectProposal $proposal, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proposal_status' => 'required|in:' . implode(',', [
                ProjectProposal::STATUS_APPROVED,
                ProjectProposal::STATUS_REJECTED
            ]),
            'comments' => 'required|string'
        ]);

        DB::transaction(function() use ($proposal, $validated) {
            if ($validated['proposal_status'] === ProjectProposal::STATUS_APPROVED) {
                $proposal->project->update(['status' => 'Validated']);
            }
            $proposal->proposal_status = $validated['proposal_status'];
            $proposal->review_comments = $validated['comments'];
            $proposal->save();
        });

        return response()->json($proposal);
    }

    private function validateUpdateRequest(Request $request): array
    {
        $user = auth()->user();

        // Handle teacher edits
        if ($user->role === 'Teacher' && !$user->isResponsibleTeacher()) {
            return $request->validate([
                'co_supervisor_name' => 'sometimes|string',
                'co_supervisor_surname' => 'sometimes|string',
                'comments' => 'required|string',
                'suggested_changes' => 'required|array'
            ]);
        }

        // Student or default rules
        return $request->validate([
            'co_supervisor_name' => 'sometimes|string',
            'co_supervisor_surname' => 'sometimes|string',
            'review_comments' => 'nullable|string',
            'is_final_version' => 'sometimes|boolean',
            'accept_changes' => 'sometimes|boolean',
            'comments' => 'required_with:accept_changes|string'
        ]);
    }

    private function setAsFinalVersion(ProjectProposal $proposal): void
    {
        ProjectProposal::where('submitted_by', $proposal->submitted_by)
            ->where('proposal_id', '!=', $proposal->proposal_id)
            ->update(['is_final_version' => false]);
    }

    public function destroy(int $id): JsonResponse
    {
        $proposal = ProjectProposal::findOrFail($id);
        $proposal->delete();
        return response()->json(null, 204);
    }
}