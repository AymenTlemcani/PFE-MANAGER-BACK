<?php

namespace App\Services;

use App\Mail\GenericEmail;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class EmailService
{
    public function sendTemporaryPassword(User $user, string $tempPassword, int $expiryDays = 7): bool
    {
        $language = $user->language_preference ?? 'French';
        $templateName = 'temporary_password_' . strtolower(substr($language, 0, 2));
        
        $template = EmailTemplate::where('name', $templateName)
            ->where('language', $language)
            ->first();

        if (!$template) {
            return $this->sendDefaultTemporaryPasswordEmail($user, $tempPassword, $expiryDays);
        }

        return $this->sendEmail($user, $template, [
            'name' => $user->getName(),
            'temporary_password' => $tempPassword,
            'expiry_days' => $expiryDays
        ]);
    }

    public function sendEmail(User $user, EmailTemplate $template, array $data = []): bool
    {
        try {
            $content = $this->replacePlaceholders($template->content, $data);
            $subject = $this->replacePlaceholders($template->subject, $data);

            Mail::to($user->email)
                ->send(new GenericEmail($subject, $content));

            // Log success
            EmailLog::create([
                'template_id' => $template->template_id,
                'recipient_email' => $user->email,
                'user_id' => $user->user_id,
                'sent_at' => now(),
                'status' => 'Sent',
                'template_data' => $data
            ]);

            return true;
        } catch (\Exception $e) {
            // Log failure
            EmailLog::create([
                'template_id' => $template->template_id,
                'recipient_email' => $user->email,
                'user_id' => $user->user_id,
                'status' => 'Failed',
                'error_message' => $e->getMessage(),
                'template_data' => $data
            ]);

            \Log::error('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    private function sendDefaultTemporaryPasswordEmail(User $user, string $tempPassword, int $expiryDays): bool
    {
        try {
            Mail::raw(
                "Hello {$user->getName()},\n\n" .
                "Your account has been created in the PFE Management System.\n" .
                "Your temporary password is: {$tempPassword}\n\n" .
                "Please log in and change your password as soon as possible.\n" .
                "This temporary password will expire in {$expiryDays} days.\n\n" .
                "Best regards,\nPFE Management System",
                function(Message $message) use ($user) {
                    $message->to($user->email)
                            ->subject('Your PFE Manager Account Details');
                }
            );

            // Log the default email sending
            EmailLog::create([
                'recipient_email' => $user->email,
                'user_id' => $user->user_id,
                'sent_at' => now(),
                'status' => 'Sent',
                'template_data' => [
                    'name' => $user->getName(),
                    'temporary_password' => $tempPassword,
                    'expiry_days' => $expiryDays
                ]
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Default email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    private function replacePlaceholders(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }
}
