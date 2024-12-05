<?php

namespace App\Http\Controllers;

use App\Models\StudentPair;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StudentPairController extends Controller
{
    public function index(): JsonResponse
    {
        $pairs = StudentPair::with(['student1', 'student2'])->get();
        return response()->json($pairs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student1_id' => 'required|exists:students,student_id',
            'student2_id' => 'required|exists:students,student_id|different:student1_id'
        ]);

        // Ensure student1_id is always less than student2_id for consistency
        if ($validated['student1_id'] > $validated['student2_id']) {
            [$validated['student1_id'], $validated['student2_id']] = 
            [$validated['student2_id'], $validated['student1_id']];
        }

        // Check if either student is already in an accepted pair
        $existingPair = StudentPair::where(function($query) use ($validated) {
            $query->where('student1_id', $validated['student1_id'])
                  ->orWhere('student1_id', $validated['student2_id'])
                  ->orWhere('student2_id', $validated['student1_id'])
                  ->orWhere('student2_id', $validated['student2_id']);
        })->where('status', 'Accepted')->first();

        if ($existingPair) {
            return response()->json([
                'message' => 'One or both students are already in an accepted pair'
            ], 400);
        }

        // Check if students are in the same master option
        $student1 = Student::find($validated['student1_id']);
        $student2 = Student::find($validated['student2_id']);

        if ($student1->master_option !== $student2->master_option) {
            return response()->json([
                'message' => 'Students must be in the same master option'
            ], 400);
        }

        $pair = StudentPair::create([
            'student1_id' => $validated['student1_id'],
            'student2_id' => $validated['student2_id'],
            'status' => 'Proposed',
            'proposed_date' => now()
        ]);

        // TODO: Send notification to student2

        return response()->json($pair, 201);
    }

    public function show(int $id): JsonResponse
    {
        $pair = StudentPair::with(['student1', 'student2'])->findOrFail($id);
        return response()->json($pair);
    }

    public function respondToPairRequest(Request $request, int $id): JsonResponse
    {
        $pair = StudentPair::findOrFail($id);
        
        $validated = $request->validate([
            'response' => 'required|in:Accepted,Rejected'
        ]);

        // Only student2 can respond to the request
        if (auth()->id() != $pair->student2->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($pair->status !== 'Proposed') {
            return response()->json([
                'message' => 'This pair request has already been processed'
            ], 400);
        }

        $pair->update([
            'status' => $validated['response'],
            'updated_date' => now()
        ]);

        // TODO: Send notification to student1

        return response()->json($pair);
    }

    public function destroy(int $id): JsonResponse
    {
        $pair = StudentPair::findOrFail($id);
        
        if ($pair->status === 'Accepted') {
            return response()->json([
                'message' => 'Cannot delete an accepted pair'
            ], 400);
        }
        
        $pair->delete();
        return response()->json(null, 204);
    }

    public function getStudentPairs(int $studentId): JsonResponse
    {
        $pairs = StudentPair::where('student1_id', $studentId)
            ->orWhere('student2_id', $studentId)
            ->with(['student1', 'student2'])
            ->get();
            
        return response()->json($pairs);
    }
}