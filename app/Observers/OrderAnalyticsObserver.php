<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Analytics\AnalyticsRecorder;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderAnalyticsObserver
{
    public function __construct(private readonly AnalyticsRecorder $recorder) {}

    public function created(Order $order): void
    {
        try {
            $this->recorder->record('order_completed', [
                'user_id'  => $order->user_id,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => (string) ($order->grand_total ?? $order->total_amount ?? '0'),
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('analytics.order_completed_failed', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
