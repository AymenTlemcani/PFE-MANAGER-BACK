<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Models\ReminderSchedule;
use App\Jobs\SendEmailJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCampaignReminders extends Command
{
    protected $signature = 'emails:send-reminders';
    protected $description = 'Send scheduled campaign reminders';

    public function handle()
    {
        $now = Carbon::now();
        
        // Get active campaigns
        $activeCampaigns = EmailCampaign::where('status', 'Active')
            ->where('end_date', '>', $now)
            ->get();

        foreach ($activeCampaigns as $campaign) {
            $this->processReminders($campaign, $now);
        }

        $this->info('Reminders processed successfully.');
    }

    private function processReminders(EmailCampaign $campaign, Carbon $now)
    {
        $reminderSchedules = $campaign->reminderSchedules()
            ->where('is_active', true)
            ->get();

        foreach ($reminderSchedules as $schedule) {
            $sendTime = Carbon::parse($schedule->send_time);
            $deadlineDate = Carbon::parse($campaign->end_date);
            $scheduledDate = $deadlineDate->copy()->subDays($schedule->days_before_deadline);

            if ($now->format('Y-m-d') === $scheduledDate->format('Y-m-d') && 
                $now->format('H:i') === $sendTime->format('H:i')) {
                
                $this->sendReminders($campaign, $schedule);
            }
        }
    }

    private function sendReminders(EmailCampaign $campaign, ReminderSchedule $schedule)
    {
        $users = $this->getTargetUsers($campaign->target_audience);
        
        foreach ($users as $user) {
            SendEmailJob::dispatch(
                $user,
                $schedule->template,
                [
                    'campaign_name' => $campaign->name,
                    'deadline' => $campaign->end_date,
                    'days_remaining' => Carbon::now()->diffInDays($campaign->end_date)
                ]
            );
        }
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
