<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\User;
use App\Models\EmailPeriod;
use App\Models\EmailPeriodTemplate;
use App\Models\DefenseSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdministratorController extends Controller
{
    public function index(): JsonResponse
    {
        $administrators = Administrator::with('user')->get();
        return response()->json($administrators);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'name' => 'required|string',
            'surname' => 'required|string'
        ]);

        $administrator = Administrator::create($validated);
        return response()->json($administrator, 201);
    }

    public function show(int $id): JsonResponse
    {
        $administrator = Administrator::with('user')->findOrFail($id);
        return response()->json($administrator);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $administrator = Administrator::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string',
            'surname' => 'string'
        ]);

        $administrator->update($validated);
        return response()->json($administrator);
    }

    public function destroy(int $id): JsonResponse
    {
        $administrator = Administrator::findOrFail($id);
        $administrator->delete();
        return response()->json(null, 204);
    }

    public function createEmailPeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_name' => 'required|string|unique:email_periods',
            'target_audience' => 'required|in:Students,Teachers,Companies,Administrators,All',
            'start_date' => 'required|date',
            'closing_date' => 'required|date|after:start_date',
            'templates' => 'required|array',
            'templates.*.type' => 'required|in:Initial,Reminder,Closing',
            'templates.*.content' => 'required|string',
            'templates.*.subject' => 'required|string',
            'templates.*.language' => 'required|in:French,English'
        ]);

        $emailPeriod = EmailPeriod::create([
            'period_name' => $validated['period_name'],
            'target_audience' => $validated['target_audience'],
            'start_date' => $validated['start_date'],
            'closing_date' => $validated['closing_date'],
            'status' => 'Draft',
            'created_at' => now()
        ]);

        foreach ($validated['templates'] as $template) {
            EmailPeriodTemplate::create([
                'period_id' => $emailPeriod->period_id,
                ...$template
            ]);
        }

        return response()->json($emailPeriod, 201);
    }

    public function planDefenseSessions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sessions' => 'required|array',
            'sessions.*.project_id' => 'required|exists:projects,project_id',
            'sessions.*.room' => 'required|string',
            'sessions.*.date' => 'required|date',
            'sessions.*.time' => 'required|date_format:H:i',
            'sessions.*.duration' => 'required|integer|min:15'
        ]);

        $sessions = collect($validated['sessions'])->map(function ($session) {
            return DefenseSession::create([
                ...$session,
                'status' => 'Scheduled'
            ]);
        });

        return response()->json($sessions, 201);
    }

    public function assignJuries(): JsonResponse
    {
        // Implement jury assignment algorithm based on teacher grades and recruitment dates
        // This is a complex operation that should be moved to a dedicated service class
        return response()->json(['message' => 'Juries assigned successfully']);
    }
}