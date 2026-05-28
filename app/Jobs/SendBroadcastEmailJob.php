<?php

namespace App\Jobs;

use App\Mail\BroadcastMail;
use App\Models\EmailBroadcast;
use App\Models\EmailBroadcastRecipient;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        public readonly int $broadcastId,
        public readonly int $recipientRowId,
    ) {
        $this->onQueue('mail-broadcast');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $broadcast = EmailBroadcast::find($this->broadcastId);
        $row = EmailBroadcastRecipient::with('user')->find($this->recipientRowId);

        if (! $broadcast || ! $row) {
            return;
        }

        try {
            $mail = new BroadcastMail(
                subjectLine: $broadcast->subject,
                bodyHtml: $broadcast->body_html,
                broadcastAttachments: $broadcast->attachments ?? [],
            );

            // Route via the user model when available — HasLocalePreference auto-sets locale.
            if ($row->user) {
                Mail::to($row->user)->send($mail);
            } else {
                Mail::to($row->email)->send($mail);
            }

            $row->forceFill([
                'status' => EmailBroadcastRecipient::STATUS_SENT,
                'sent_at' => now(),
            ])->save();

            $broadcast->increment('sent_count');
        } catch (Throwable $e) {
            $row->forceFill([
                'status' => EmailBroadcastRecipient::STATUS_FAILED,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ])->save();

            $broadcast->increment('failed_count');

            throw $e;
        }
    }
}
