<?php

namespace App\Services\Email;

use App\Jobs\SendAdminEmailAlert;
use App\Models\AdminEmailAlert;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AdminEmailAlertService
{
    public function recipients(string $type): array
    {
        $addresses = json_decode((string) Setting::getValue('admin_alert_'.$type.'_recipients', '[]'), true);

        return array_values(array_unique(array_filter(
            is_array($addresses) ? $addresses : [],
            fn ($address) => is_string($address) && filter_var($address, FILTER_VALIDATE_EMAIL),
        )));
    }

    public function enabled(string $type): bool
    {
        return Setting::getValue('admin_alert_'.$type.'_enabled', '0') === '1';
    }

    public function orderPlaced(Order $order): void
    {
        if (! $this->enabled('order')) {
            return;
        }

        $locale = $this->locale();
        $this->deliver('order', 'order:'.$order->id,
            __('alerts.order_subject', ['number' => $order->order_number], $locale),
            __('alerts.order_message', ['number' => $order->order_number], $locale),
            [
                'order_number' => $order->order_number,
                'total' => number_format((float) ($order->grand_total ?? $order->total_amount), (int) Setting::getValue('currency_decimals', 0)).' '.Setting::getValue('currency_code', 'IQD'),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'action_url' => route('admin.orders.show', $order),
                'action_text' => __('View order', [], $locale),
            ],
        );
    }

    public function systemError(Throwable $exception): void
    {
        // Error reporting must never throw another exception or recursively email itself.
        try {
            if (! $this->enabled('system') || $this->recipients('system') === []) {
                return;
            }

            $fingerprint = hash('sha256', $exception::class.$exception->getFile().$exception->getLine());
            if (! Cache::add('admin-alert:'.$fingerprint, true, now()->addMinutes(10))) {
                return;
            }

            $locale = $this->locale();
            $reference = (string) Str::uuid();
            Log::error('System alert reference', ['reference' => $reference, 'exception' => $exception::class]);
            $this->deliver('system', 'system:'.$reference,
                __('alerts.system_subject', [], $locale),
                __('alerts.system_message', ['reference' => $reference], $locale),
                ['error_type' => class_basename($exception), 'reference' => $reference, 'time' => now()->toIso8601String(), 'action_url' => route('admin.email-alerts.index')],
                immediate: true,
            );
        } catch (Throwable $ignored) {
            // The original exception still reaches Laravel's normal error log.
        }
    }

    public function test(string $type): void
    {
        $locale = $this->locale();
        $this->deliver($type, 'test:'.Str::uuid(), __('alerts.test_subject', [], $locale), __('alerts.test_message', [], $locale), [
            'action_url' => route('admin.email-alerts.index'),
        ], immediate: true);
    }

    public function failedJobs(int $count, int $lastId): void
    {
        if (! $this->enabled('system')) {
            return;
        }

        $this->deliver('system', 'failed-jobs:'.$lastId,
            __('alerts.system_subject', [], $this->locale()),
            __('alerts.jobs_message', ['count' => $count], $this->locale()),
            ['failed_jobs' => $count, 'action_url' => route('admin.email-alerts.index')],
            immediate: true,
        );
    }

    private function locale(): string
    {
        $locale = (string) Setting::getValue('admin_alert_locale', 'en');

        return in_array($locale, ['en', 'ar', 'ku'], true) ? $locale : 'en';
    }

    private function deliver(string $type, string $eventKey, string $subject, string $message, array $context, bool $immediate = false): void
    {
        foreach ($this->recipients($type) as $recipient) {
            $alert = AdminEmailAlert::firstOrCreate(['event_key' => $eventKey, 'recipient' => $recipient], [
                'type' => $type, 'locale' => $this->locale(), 'subject' => $subject,
                'message' => $message, 'context' => $context, 'status' => 'queued',
            ]);
            if (! $alert->wasRecentlyCreated) {
                continue;
            }

            try {
                $job = new SendAdminEmailAlert($alert->id);
                if ($immediate) {
                    $job->handle();
                } else {
                    Bus::dispatch($job);
                }
            } catch (Throwable $exception) {
                $alert->update(['status' => 'failed', 'error_code' => class_basename($exception)]);
            }
        }
    }
}
