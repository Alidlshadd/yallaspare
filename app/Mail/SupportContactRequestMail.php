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
            ->subject(__('Support request: :subject', ['subject' => $subject]))
            ->view('emails.support.contact-request', $this->viewData())
            ->text('emails.text.generic', [
                'title' => __('Support request: :subject', ['subject' => $subject]),
                'bodyText' => (string) ($this->data['message'] ?? ''),
            ]);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($email, $name);
        }

        return $mail;
    }

    private function viewData(): array
    {
        return [
            'title' => __('New YallaSpare support request'),
            'preheader' => __('A customer submitted a new support request.'),
            'name' => (string) ($this->data['name'] ?? ''),
            'email' => (string) ($this->data['email'] ?? ''),
            'phone' => (string) ($this->data['phone'] ?? ''),
            'topic' => (string) ($this->data['topic'] ?? 'general'),
            'requestSubject' => (string) ($this->data['subject'] ?? ''),
            'messageText' => (string) ($this->data['message'] ?? ''),
        ];
    }
}
