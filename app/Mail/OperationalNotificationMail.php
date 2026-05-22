<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OperationalNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Operational customer emails are queued so checkout and order updates
     * do not depend on real-time SMTP availability.
     */
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyText,
        public readonly array $context = [],
    ) {
        $this->onQueue('mail');
    }

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->html($this->renderHtml());
    }

    private function renderHtml(): string
    {
        $escapedBody = nl2br(e($this->bodyText));

        return <<<HTML
<div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a">
    <h1 style="margin:0 0 16px;font-size:20px;color:#070740">YallaSpare Auto Parts System</h1>
    <div>{$escapedBody}</div>
</div>
HTML;
    }
}
