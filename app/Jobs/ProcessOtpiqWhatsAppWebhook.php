<?php

namespace App\Jobs;

use App\Models\OtpiqWebhookEvent;
use App\Services\Otpiq\OtpiqWhatsAppEventProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProcessOtpiqWhatsAppWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public readonly int $eventId) {}

    public function handle(OtpiqWhatsAppEventProcessor $processor): void
    {
        $event = OtpiqWebhookEvent::query()->find($this->eventId);

        if (! $event || in_array($event->processing_status, [
            OtpiqWebhookEvent::STATUS_PROCESSED,
            OtpiqWebhookEvent::STATUS_IGNORED,
        ], true)) {
            return;
        }

        $claimed = OtpiqWebhookEvent::query()
            ->whereKey($event->id)
            ->whereIn('processing_status', [
                OtpiqWebhookEvent::STATUS_RECEIVED,
                OtpiqWebhookEvent::STATUS_FAILED,
            ])
            ->update([
                'processing_status' => OtpiqWebhookEvent::STATUS_PROCESSING,
                'error_message' => null,
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            return;
        }

        $event->refresh();

        try {
            $processor->process($event);

            OtpiqWebhookEvent::query()
                ->whereKey($event->id)
                ->where('processing_status', OtpiqWebhookEvent::STATUS_PROCESSING)
                ->update([
                    'processing_status' => OtpiqWebhookEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'error_message' => null,
                    'updated_at' => now(),
                ]);

            Log::info('OTPiQ webhook processed', [
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'processing_status' => OtpiqWebhookEvent::STATUS_PROCESSED,
            ]);
        } catch (Throwable) {
            OtpiqWebhookEvent::query()
                ->whereKey($event->id)
                ->where('processing_status', OtpiqWebhookEvent::STATUS_PROCESSING)
                ->update([
                    'processing_status' => OtpiqWebhookEvent::STATUS_FAILED,
                    'error_message' => 'Webhook processing failed.',
                    'updated_at' => now(),
                ]);

            Log::warning('OTPiQ webhook processing failed', [
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'processing_status' => OtpiqWebhookEvent::STATUS_FAILED,
            ]);

            throw new RuntimeException('Webhook processing failed.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        $event = OtpiqWebhookEvent::query()->find($this->eventId);

        if (! $event || $event->processing_status === OtpiqWebhookEvent::STATUS_PROCESSED) {
            return;
        }

        $event->forceFill([
            'processing_status' => OtpiqWebhookEvent::STATUS_FAILED,
            'error_message' => 'Webhook processing failed after all retry attempts.',
        ])->save();
    }
}
