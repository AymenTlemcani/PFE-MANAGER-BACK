<?php

namespace App\Http\Controllers;

use App\Models\ReminderSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReminderScheduleController extends Controller
{
    public function index(int $campaignId): JsonResponse
    {
        $schedules = ReminderSchedule::where('campaign_id', $campaignId)
            ->with(['template'])->get();
        return response()->json($schedules);
    }

    public function store(Request $request, int $campaignId): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:email_templates,template_id',
            'days_before_deadline' => 'required|integer|min:1',
            'send_time' => 'required|date_format:H:i:s',
            'is_active' => 'boolean'
        ]);

        $schedule = ReminderSchedule::create([
            ...$validated,
            'campaign_id' => $campaignId
        ]);

        return response()->json($schedule, 201);
    }

    public function update(Request $request, int $campaignId, int $scheduleId): JsonResponse
    {
        $schedule = ReminderSchedule::where('campaign_id', $campaignId)
            ->where('schedule_id', $scheduleId)
            ->firstOrFail();

        $validated = $request->validate([
            'template_id' => 'exists:email_templates,template_id',
            'days_before_deadline' => 'integer|min:1',
            'send_time' => 'date_format:H:i:s',
            'is_active' => 'boolean'
        ]);

        $schedule->update($validated);
        return response()->json($schedule);
    }

    public function destroy(int $campaignId, int $scheduleId): JsonResponse
    {
        $schedule = ReminderSchedule::where('campaign_id', $campaignId)
            ->where('schedule_id', $scheduleId)
            ->firstOrFail();
            
        $schedule->delete();
        return response()->json(null, 204);
    }
}
