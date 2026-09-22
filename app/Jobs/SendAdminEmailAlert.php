<?php

namespace App\Jobs;

use App\Mail\OperationalNotificationMail;
use App\Models\AdminEmailAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAdminEmailAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public readonly int $alertId)
    {
        $this->onQueue('mail');
    }

    public function handle(): void
    {
        // Claim once so duplicated queue deliveries cannot resend a completed alert.
        if (! AdminEmailAlert::whereKey($this->alertId)->where('status', 'queued')->update(['status' => 'sending'])) {
            return;
        }

        $alert = AdminEmailAlert::findOrFail($this->alertId);
        $previousLocale = app()->getLocale();

        try {
            $sent = Mail::to($alert->recipient)->sendNow(new OperationalNotificationMail(
                $alert->subject,
                $alert->message,
                array_merge($alert->context ?? [], ['type' => 'admin_'.$alert->type, 'locale' => $alert->locale]),
            ));

            if ($sent === null) {
                $alert->update(['status' => 'failed', 'error_code' => 'message_not_sent']);

                return;
            }

            // Local log/array transports do not deliver to an inbox.
            $transport = (string) config('mail.mailers.'.config('mail.default').'.transport');
            $alert->update([
                'status' => in_array($transport, ['log', 'array'], true) ? 'simulated' : 'sent',
                'error_code' => null,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            $this->failed($e);
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    public function failed(?Throwable $exception): void
    {
        AdminEmailAlert::whereKey($this->alertId)->where('status', '!=', 'sent')->update([
            'status' => 'failed',
            // Exception messages can contain SMTP credentials or customer data.
            'error_code' => $exception ? class_basename($exception) : 'delivery_failed',
        ]);
    }
}
