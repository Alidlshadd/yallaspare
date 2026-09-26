<?php

namespace App\Mail;

use App\Services\Email\EmailTemplateOverrides;
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

        $templateVars = [
            'brand' => 'YallaSpare',
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'topic' => (string) ($this->data['topic'] ?? 'general'),
        ];
        $overrides = app(EmailTemplateOverrides::class);
        // Only the HTML lead copy: the plain-text part carries the customer's
        // own message and stays as it is.
        $custom = array_intersect_key($overrides->viewData('support', $templateVars), ['customBodyHtml' => true]);

        $mail = $this
            ->subject($overrides->subject('support', __('Support request: :subject', ['subject' => $subject]), $templateVars))
            ->view('emails.support.contact-request', $custom + $this->viewData())
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
