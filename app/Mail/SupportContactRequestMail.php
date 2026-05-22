<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportContactRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Contact requests are queued to keep the public support form fast and
     * resilient if Google SMTP is temporarily unavailable.
     */
    public function __construct(public readonly array $data)
    {
        $this->onQueue('mail');
    }

    public function build(): self
    {
        $name = (string) ($this->data['name'] ?? 'Customer');
        $email = (string) ($this->data['email'] ?? '');
        $subject = (string) ($this->data['subject'] ?? 'Support request');

        $mail = $this
            ->subject('Support request: ' . $subject)
            ->html($this->renderHtml());

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($email, $name);
        }

        return $mail;
    }

    private function renderHtml(): string
    {
        $name = e((string) ($this->data['name'] ?? ''));
        $email = e((string) ($this->data['email'] ?? ''));
        $phone = e((string) ($this->data['phone'] ?? ''));
        $topic = e((string) ($this->data['topic'] ?? 'general'));
        $subject = e((string) ($this->data['subject'] ?? ''));
        $message = nl2br(e((string) ($this->data['message'] ?? '')));

        return <<<HTML
<div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a">
    <h1 style="margin:0 0 16px;font-size:20px;color:#070740">New YallaSpare Support Request</h1>
    <p><strong>Name:</strong> {$name}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Phone:</strong> {$phone}</p>
    <p><strong>Topic:</strong> {$topic}</p>
    <p><strong>Subject:</strong> {$subject}</p>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:16px 0">
    <p>{$message}</p>
</div>
HTML;
    }
}
