<?php

namespace App\Http\Controllers;

use App\Models\ProjectProposal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectProposalController extends Controller
{
    public function index(): JsonResponse
    {
        $proposals = ProjectProposal::with(['project', 'submitter'])->get();
        return response()->json($proposals);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,project_id',
            'co_supervisor_name' => 'required|string',
            'co_supervisor_surname' => 'required|string',
            'proposal_status' => 'required|in:Pending,Approved,Rejected',
            'review_comments' => 'nullable|string'
        ]);

        $proposal = ProjectProposal::create([
            ...$validated,
            'submitted_by' => auth()->id()
        ]);

        return response()->json($proposal, 201);
    }

    public function show(int $id): JsonResponse
    {
        $proposal = ProjectProposal::with(['project', 'submitter'])->findOrFail($id);
        return response()->json($proposal);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $proposal = ProjectProposal::findOrFail($id);
        
        $validated = $request->validate([
            'co_supervisor_name' => 'string',
            'co_supervisor_surname' => 'string',
            'proposal_status' => 'in:Pending,Approved,Rejected',
            'review_comments' => 'nullable|string'
        ]);

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