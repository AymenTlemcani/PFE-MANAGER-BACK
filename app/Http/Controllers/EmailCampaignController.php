<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailCampaignController extends Controller
{
    public function index(): JsonResponse
    {
        $campaigns = EmailCampaign::with(['reminderSchedules', 'emailLogs'])->get();
        return response()->json($campaigns);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:email_campaigns',
            'type' => 'required|in:Notification,Reminder,System',
            'target_audience' => 'required|in:Students,Teachers,Companies,Administrators,All',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:Draft,Active,Completed,Cancelled'
        ]);

        $campaign = EmailCampaign::create($validated);
        return response()->json($campaign, 201);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = EmailCampaign::with(['reminderSchedules', 'emailLogs'])->findOrFail($id);
        return response()->json($campaign);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string|unique:email_campaigns,name,' . $id . ',campaign_id',
            'type' => 'in:Notification,Reminder,System',
            'target_audience' => 'in:Students,Teachers,Companies,Administrators,All',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'status' => 'in:Draft,Active,Completed,Cancelled'
        ]);

        $campaign->update($validated);
        return response()->json($campaign);
    }

    public function destroy(int $id): JsonResponse
    {
        $campaign = EmailCampaign::findOrFail($id);
        $campaign->delete();
        return response()->json(null, 204);
    }
}
