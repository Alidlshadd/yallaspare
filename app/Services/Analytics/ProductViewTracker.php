<?php

namespace App\Services\Analytics;

use App\Models\Product;
use App\Models\ProductView;
use App\Support\BotDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductViewTracker
{
    private const DEDUPE_MINUTES = 30;

    public function __construct(private readonly AnalyticsRecorder $recorder) {}

    public function record(Request $request, Product $product): void
    {
        if (BotDetector::isBot($request->userAgent()) || ! Schema::hasTable('product_views')) {
            return;
        }

        if ($request->hasSession()) {
            $request->session()->put('_product_view_tracking', true);
        }

        $payload = AnalyticsRecorder::visitorPayloadFor($request);
        $now = Carbon::now();

        try {
            if ($this->recentViewExists($product, $payload, $now)) {
                return;
            }

            ProductView::query()->create([
                'product_id' => $product->id,
                'user_id' => $payload['user_id'] ?? null,
                'session_id' => $payload['session_id'] ?? null,
                'ip_hash' => $payload['ip_hash'] ?? null,
                'user_agent_hash' => $payload['user_agent_hash'] ?? null,
                'url' => $payload['url'] ?? null,
                'referrer' => $payload['referrer'] ?? null,
                'viewed_at' => $now,
            ]);

            $this->recorder->record('product_view', array_merge($payload, [
                'product_id' => $product->id,
                'metadata' => ['source' => 'web_product_detail'],
            ]));
        } catch (Throwable $e) {
            Log::warning('analytics.product_view_failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recentViewExists(Product $product, array $payload, Carbon $now): bool
    {
        $query = ProductView::query()
            ->where('product_id', $product->id)
            ->where('viewed_at', '>=', $now->copy()->subMinutes(self::DEDUPE_MINUTES));

        if (! empty($payload['user_id'])) {
            if ((clone $query)->where('user_id', $payload['user_id'])->exists()) {
                return true;
            }
        }

        if (! empty($payload['session_id'])) {
            if ((clone $query)->where('session_id', $payload['session_id'])->exists()) {
                return true;
            }
        }

        if (! empty($payload['ip_hash']) && ! empty($payload['user_agent_hash'])) {
            return (clone $query)
                ->where('ip_hash', $payload['ip_hash'])
                ->where('user_agent_hash', $payload['user_agent_hash'])
                ->exists();
        }

        return false;
    }
}
