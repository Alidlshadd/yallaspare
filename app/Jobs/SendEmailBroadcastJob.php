<?php

namespace App\Jobs;

use App\Mail\OperationalNotificationMail;
use App\Models\EmailBroadcast;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $broadcastId)
    {
        $this->onQueue('mail');
    }

    public function handle(): void
    {
        $broadcast = EmailBroadcast::query()->find($this->broadcastId);

        if (! $broadcast) {
            return;
        }

        $broadcast->forceFill([
            'status' => EmailBroadcast::STATUS_SENDING,
            'started_at' => now(),
            'last_error' => null,
        ])->save();

        $query = $this->recipientQuery($broadcast);
        $broadcast->forceFill(['recipient_count' => (clone $query)->count()])->save();

        $sent = 0;
        $failed = 0;
        $lastError = null;

        $query->select(['id', 'name', 'email', 'locale_preference'])
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($broadcast, &$sent, &$failed, &$lastError): void {
                foreach ($users as $user) {
                    try {
                        Mail::to((string) $user->email)->send(new OperationalNotificationMail(
                            (string) $broadcast->subject,
                            (string) $broadcast->message,
                            [
                                'type' => 'admin_broadcast',
                                'locale' => $user->preferredLocale(),
                                'broadcast_id' => $broadcast->id,
                                'purpose' => $broadcast->purpose,
                                'action_url' => $broadcast->action_url,
                                'action_text' => $broadcast->action_text,
                            ],
                        ));
                        $sent++;
                    } catch (Throwable $e) {
                        $failed++;
                        $lastError = mb_substr($e->getMessage(), 0, 2000);
                    }
                }

                $broadcast->forceFill([
                    'sent_count' => $sent,
                    'failed_count' => $failed,
                    'last_error' => $lastError,
                ])->save();
            });

        $broadcast->forceFill([
            'status' => $failed > 0 && $sent === 0
                ? EmailBroadcast::STATUS_FAILED
                : EmailBroadcast::STATUS_SENT,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'last_error' => $lastError,
            'completed_at' => now(),
        ])->save();
    }

    private function recipientQuery(EmailBroadcast $broadcast): Builder
    {
        $query = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('email_verified_at')
            ->where(function (Builder $q): void {
                $q->where('email_notifications', true)
                    ->orWhereNull('email_notifications');
            });

        if ($broadcast->purpose === EmailBroadcast::PURPOSE_PROMOTIONAL) {
            $query->where('marketing_consent', true);
        }

        if ($broadcast->audience_type === EmailBroadcast::AUDIENCE_ROLE && $broadcast->audience_role) {
            $query->where('role', $broadcast->audience_role);
        }

        if ($broadcast->audience_type === EmailBroadcast::AUDIENCE_USER) {
            $query->whereKey((int) $broadcast->target_user_id);
        }

        return $query;
    }
}
