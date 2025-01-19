<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $emailContent;
    private $emailSubject;

    public function __construct(string $subject, string $content)
    {
        $this->emailSubject = $subject;
        $this->emailContent = $content;
    }

    public function build()
    {
        return $this->subject($this->emailSubject)
                    ->view('emails.generic')
                    ->with(['content' => $this->emailContent]);
    }
}
