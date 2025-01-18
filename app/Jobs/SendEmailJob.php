<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\EmailTemplate;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $template;
    private $data;

    public function __construct(User $user, EmailTemplate $template, array $data = [])
    {
        $this->user = $user;
        $this->template = $template;
        $this->data = $data;
    }

    public function handle(EmailService $emailService): void
    {
        $emailService->sendEmail($this->user, $this->template, $this->data);
    }
}
