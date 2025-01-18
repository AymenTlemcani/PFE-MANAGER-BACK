<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use Illuminate\Console\Command;

class CheckCompletedCampaigns extends Command
{
    protected $signature = 'emails:check-completed-campaigns';
    protected $description = 'Check and update status of completed email campaigns';

    public function handle(): void
    {
        $updatedCount = EmailCampaign::where('status', 'Active')
            ->where('end_date', '<', now())
            ->update(['status' => 'Completed']);

        $this->info("Updated {$updatedCount} campaigns to Completed status.");

        // Also check for campaigns that should be activated
        $activatedCount = EmailCampaign::where('status', 'Draft')
            ->where('start_date', '<=', now())
            ->update(['status' => 'Active']);

        $this->info("Activated {$activatedCount} campaigns.");
    }
}
