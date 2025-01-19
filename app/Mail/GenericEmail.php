<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmailTemplate;

class GenericEmail extends Mailable
{
    use SerializesModels;

    public EmailTemplate $template;
    public array $data;

    public function __construct(EmailTemplate $template, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject($this->replaceVariables($this->template->subject))
                   ->view('emails.generic')
                   ->with(['content' => $this->replaceVariables($this->template->content)]);
    }

    private function replaceVariables(string $text): string
    {
        foreach ($this->data as $key => $value) {
            $text = str_replace('{' . $key . '}', (string)$value, $text);
        }
        return $text;
    }
}
