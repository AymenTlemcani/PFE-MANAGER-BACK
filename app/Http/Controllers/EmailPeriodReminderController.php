<?php

namespace App\Http\Controllers;

use App\Models\EmailPeriod;
use App\Models\EmailPeriodReminder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailPeriodReminderController extends Controller
{
    public function index(int $periodId): JsonResponse
    {
        $reminders = EmailPeriodReminder::where('period_id', $periodId)->get();
        return response()->json($reminders);
    }

    public function store(Request $request, int $periodId): JsonResponse
    {
        $period = EmailPeriod::findOrFail($periodId);
        
        $validated = $request->validate([
            'reminder_date' => 'required|date|after:' . $period->start_date . '|before:' . $period->closing_date,
            'reminder_number' => 'required|integer|min:1',
            'status' => 'required|in:Scheduled,Sent,Cancelled'
        ]);

        $reminder = $period->reminders()->create([
            ...$validated,
            'period_id' => $periodId
        ]);

        return response()->json($reminder, 201);
    }

    public function update(Request $request, int $periodId, int $reminderId): JsonResponse
    {
        $period = EmailPeriod::findOrFail($periodId);
        $reminder = EmailPeriodReminder::findOrFail($reminderId);
        
        $validated = $request->validate([
            'reminder_date' => 'date|after:' . $period->start_date . '|before:' . $period->closing_date,
            'reminder_number' => 'integer|min:1',
            'status' => 'in:Scheduled,Sent,Cancelled'
        ]);

        $reminder->update($validated);
        return response()->json($reminder);
    }

    public function destroy(int $periodId, int $reminderId): JsonResponse
    {
        $reminder = EmailPeriodReminder::where('period_id', $periodId)
            ->where('reminder_id', $reminderId)
            ->firstOrFail();
        
        $reminder->delete();
        return response()->json(null, 204);
    }
}