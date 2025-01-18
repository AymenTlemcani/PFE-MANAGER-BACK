<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailCampaignController extends Controller
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    private function checkAdminAccess(): void
    {
        if (!auth()->user() || auth()->user()->role !== 'Administrator') {
            abort(403, 'Only administrators can manage email campaigns.');
        }
    }

    public function index(): JsonResponse
    {
        $this->checkAdminAccess();
        $campaigns = EmailCampaign::with(['reminderSchedules', 'emailLogs'])->get();
        return response()->json($campaigns);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAdminAccess();
        $validated = $request->validate([
            'name' => 'required|string|unique:email_campaigns',
            'type' => 'required|in:Notification,Reminder,System',
            'target_audience' => 'required|in:Students,Teachers,Companies,Administrators,All',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'template_id' => 'required|exists:email_templates,template_id',
            'reminders' => 'array',
            'reminders.*.days_before_deadline' => 'required|integer|min:1',
            'reminders.*.send_time' => 'required|date_format:H:i:s'
        ]);

        $campaign = EmailCampaign::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'target_audience' => $validated['target_audience'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'Draft'
        ]);

        // Create reminders if any
        if (!empty($validated['reminders'])) {
            foreach ($validated['reminders'] as $reminder) {
                $campaign->reminderSchedules()->create([
                    'template_id' => $validated['template_id'],
                    'days_before_deadline' => $reminder['days_before_deadline'],
                    'send_time' => $reminder['send_time']
                ]);
            }
        }

        return response()->json($campaign->load('reminderSchedules'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $this->checkAdminAccess();
        $campaign = EmailCampaign::with(['reminderSchedules', 'emailLogs'])->findOrFail($id);
        return response()->json($campaign);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkAdminAccess();
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
        $this->checkAdminAccess();
        $campaign = EmailCampaign::findOrFail($id);
        $campaign->delete();
        return response()->json(null, 204);
    }

    public function activate(int $id): JsonResponse
    {
        $this->checkAdminAccess();
        try {
            $campaign = EmailCampaign::with('reminderSchedules')->findOrFail($id);
            
            if ($campaign->status !== 'Draft') {
                return response()->json(['message' => 'Campaign must be in Draft status to activate'], 400);
            }

            if (!$campaign->reminderSchedules->count()) {
                return response()->json(['message' => 'Campaign must have at least one reminder schedule'], 400);
            }

            // Get target users
            $users = $this->getTargetUsers($campaign->target_audience);
            
            // Schedule emails
            $template = EmailTemplate::findOrFail($campaign->reminderSchedules->first()->template_id);
            
            foreach ($users as $user) {
                SendEmailJob::dispatch(
                    $user, 
                    $template,
                    [
                        'campaign_name' => $campaign->name,
                        'deadline' => $campaign->end_date
                    ]
                );
            }

            $campaign->update(['status' => 'Active']);

            return response()->json(['message' => 'Campaign activated and emails scheduled']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error activating campaign: ' . $e->getMessage()], 500);
        }
    }

    public function logs(int $id): JsonResponse
    {
        $this->checkAdminAccess();
        $campaign = EmailCampaign::findOrFail($id);
        $logs = $campaign->emailLogs()->with('user')->paginate(10);
        
        return response()->json([
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'total' => $logs->total(),
                'per_page' => $logs->perPage()
            ]
        ]);
    }

    private function getTargetUsers(string $audience)
    {
        return match($audience) {
            'Students' => User::where('role', 'Student')->get(),
            'Teachers' => User::where('role', 'Teacher')->get(),
            'Companies' => User::where('role', 'Company')->get(),
            'Administrators' => User::where('role', 'Administrator')->get(),
            'All' => User::all(),
            default => collect()
        };
    }
}
