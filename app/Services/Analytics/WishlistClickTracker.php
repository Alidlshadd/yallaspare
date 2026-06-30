<?php

namespace App\Services\Analytics;

use App\Models\Product;
use App\Support\BotDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WishlistClickTracker
{
    public function __construct(private readonly AnalyticsRecorder $recorder) {}

    public function record(Request $request, Product $product): void
    {
        if (BotDetector::isBot($request->userAgent())) {
            return;
        }

        try {
            $payload = AnalyticsRecorder::visitorPayloadFor($request);

            $this->recorder->record('wishlist_click', array_merge($payload, [
                'product_id' => $product->id,
                'metadata' => ['source' => 'wishlist_controller'],
            ]));
        } catch (Throwable $e) {
            Log::warning('analytics.wishlist_click_failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
