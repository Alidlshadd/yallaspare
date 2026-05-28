<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BroadcastMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    public string $bodyHtml;

    /** @var array<int, array{path:string, original_name?:string, mime?:string, size?:int}> */
    public array $broadcastAttachments;

    public function __construct(string $subjectLine, string $bodyHtml, array $broadcastAttachments = [])
    {
        $this->subjectLine = $subjectLine;
        $this->bodyHtml = $bodyHtml;
        $this->broadcastAttachments = $broadcastAttachments;
        $this->onQueue('mail-broadcast');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
            with: [
                'bodyHtml' => $this->bodyHtml,
                'subjectLine' => $this->subjectLine,
                'preheader' => mb_substr(strip_tags($this->bodyHtml), 0, 120),
                'title' => $this->subjectLine,
            ],
        );
    }

    public function attachments(): array
    {
        return collect($this->broadcastAttachments)
            ->map(fn ($a) => Attachment::fromStorageDisk('local', $a['path'])
                ->as($a['original_name'] ?? basename($a['path']))
                ->withMime($a['mime'] ?? 'application/octet-stream'))
            ->all();
    }
}
