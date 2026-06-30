<?php

namespace App\Services\Analytics;

use App\Models\Product;
use App\Support\BotDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckoutStartTracker
{
    public function __construct(private readonly AnalyticsRecorder $recorder) {}

    public function record(Request $request, Product $product, int $quantity): void
    {
        if (BotDetector::isBot($request->userAgent())) {
            return;
        }

        try {
            $payload = AnalyticsRecorder::visitorPayloadFor($request);

            $this->recorder->record('checkout_started', array_merge($payload, [
                'product_id' => $product->id,
                'metadata' => [
                    'qty' => $quantity,
                    'source' => 'checkout_options',
                ],
            ]));
        } catch (Throwable $e) {
            Log::warning('analytics.checkout_started_failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
