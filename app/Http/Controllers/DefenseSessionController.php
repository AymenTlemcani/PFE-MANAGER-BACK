<?php

namespace App\Http\Controllers;

use App\Models\DefenseSession;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DefenseSessionController extends Controller
{
    public function index(): JsonResponse
    {
        $sessions = DefenseSession::with(['project.assignment', 'project.juryAssignment'])->get();
        return response()->json($sessions);
    }

    public function store(Request $request): JsonResponse
    {
        // Only administrators can create defense sessions
        if (auth()->user()->role !== 'Administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,project_id',
            'room' => 'required|string',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:15'
        ]);

        $session = DefenseSession::create([
            ...$validated,
            'status' => 'Scheduled'
        ]);

        // Notify all parties involved
        $this->notifyDefenseParties($session);

        return response()->json($session, 201);
    }

    public function show(int $id): JsonResponse
    {
        $session = DefenseSession::with(['project.assignment', 'project.juryAssignment'])->findOrFail($id);
        return response()->json($session);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (auth()->user()->role !== 'Administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = DefenseSession::findOrFail($id);

        $validated = $request->validate([
            'room' => 'string',
            'date' => 'date',
            'time' => 'date_format:H:i',
            'duration' => 'integer|min:15',
            'status' => 'in:Scheduled,Completed,Cancelled'
        ]);

        $session->update($validated);

        // Notify parties if significant changes
        if (isset($validated['date']) || isset($validated['time']) || isset($validated['room'])) {
            $this->notifyDefenseParties($session);
        }

        return response()->json($session);
    }

    public function destroy(int $id): JsonResponse
    {
        if (auth()->user()->role !== 'Administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = DefenseSession::findOrFail($id);
        $session->delete();
        return response()->json(null, 204);
    }

    public function authorizeDefense(Request $request, int $projectId): JsonResponse
    {
        if (auth()->user()->role !== 'Teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project = Project::findOrFail($projectId);

        // Verify that the authenticated teacher is the supervisor
        if ($project->supervisor_id !== auth()->user()->teacher->teacher_id) {
            return response()->json(['message' => 'Only the supervisor can authorize defense'], 403);
        }

        $validated = $request->validate([
            'session' => 'required|in:1,2', // Session 1 or 2
            'authorization_comments' => 'nullable|string'
        ]);

        // Update project status to indicate defense authorization
        $project->update([
            'status' => 'DefenseAuthorized',
            'defense_session' => $validated['session']
        ]);

        return response()->json(['message' => 'Defense authorized successfully']);
    }

    public function autoSchedule(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'Administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'session_period' => 'required|array',
                'session_period.start_date' => 'required|date',
                'session_period.end_date' => 'required|date|after:session_period.start_date',
                'available_rooms' => 'required|array',
                'available_rooms.*' => 'string',
                'daily_slots' => 'required|array',
                'daily_slots.*.start_time' => 'required|date_format:H:i',
                'daily_slots.*.duration' => 'required|integer|min:15'
            ]);

            // Get all projects authorized for defense that don't have a session yet
            $projects = Project::where('status', 'DefenseAuthorized')
                             ->whereDoesntHave('defenseSession')
                             ->get();

            // Schedule logic implementation here
            // ... scheduling algorithm ...

            DB::commit();
            return response()->json(['message' => 'Defense sessions scheduled successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    private function notifyDefenseParties(DefenseSession $session): void
    {
        // Get all parties involved
        $project = $session->project;
        $student = $project->assignment->student;
        $supervisor = $project->juryAssignment->supervisor;
        $examiner = $project->juryAssignment->examiner;
        $president = $project->juryAssignment->president;
        $company = $project->assignment->company;

        // Send notifications
        // ... notification logic ...
    }
}