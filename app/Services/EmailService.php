<?php

namespace App\Services;

use App\Mail\GenericEmail;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
// this is the last commit i think keep in mind that did this project is supposed to be a team work project
// but i did it alone and i did my best to make it as good as possible
// even though i hate php and laravel SO MUCH
class EmailService
{
    public function sendTemporaryPassword(User $user, string $tempPassword, int $expiryDays = 7): bool
    {
        try {
            $template = EmailTemplate::firstOrCreate(
                [
                    'name' => 'temporary_password_template',
                    'language' => $user->language_preference ?? 'French'
                ],
                [
                    'subject' => 'Your PFE Manager Temporary Password',
                    'content' => 'Hello {name},

Your account has been created in the PFE Management System.
Your temporary password is: {temporary_password}

Please log in and change your password as soon as possible.
This temporary password will expire in {expiry_days} days.

Best regards,
PFE Management System',
                    'type' => 'System',
                    'is_active' => true
                ]
            );

            $userName = match($user->role) {
                'Student' => $user->student->name ?? 'Student',
                'Teacher' => $user->teacher->name ?? 'Teacher',
                'Company' => $user->company->contact_name ?? 'User',
                default => 'User'
            };

            $data = [
                'name' => $userName,
                'temporary_password' => $tempPassword,
                'expiry_days' => $expiryDays
            ];

            // Process template content
            $content = $this->replacePlaceholders($template->content, $data);
            $subject = $this->replacePlaceholders($template->subject, $data);

            Mail::to($user->email)->send(new GenericEmail($subject, $content));

            // Log success
            EmailLog::create([
                'template_id' => $template->template_id,
                'recipient_email' => $user->email,
                'user_id' => $user->user_id,
                'status' => 'Sent',
                'sent_at' => now(),
                'template_data' => $data
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send temporary password email', [
                'user_id' => $user->user_id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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
            \Log::error('Email sending failed', [
                'error' => $e->getMessage(),
                'user' => $user->user_id,
                'template' => $template->template_id
            ]);
            
            // Log failure
            EmailLog::create([
                'template_id' => $template->template_id,
                'recipient_email' => $user->email,
                'user_id' => $user->user_id,
                'status' => 'Failed',
                'error_message' => $e->getMessage(),
                'template_data' => $data
            ]);

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
