<?php

namespace App\Services\Analytics;

use App\Support\SearchKeywordNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyticsRecorder
{
    /**
     * @param  array<string, mixed>  $payload  Optional keys: product_id, user_id, session_id,
     *                                         ip_hash, user_agent_hash, url, referrer,
     *                                         plus event-specific metadata.
     */
    public function record(string $type, array $payload = []): void
    {
        try {
            $now = Carbon::now();

            DB::table('analytics_events')->insert([
                'event_type'      => $type,
                'product_id'      => $payload['product_id'] ?? null,
                'user_id'         => $payload['user_id']    ?? null,
                'session_id'      => $payload['session_id'] ?? null,
                'ip_hash'         => $payload['ip_hash']    ?? null,
                'user_agent_hash' => $payload['user_agent_hash'] ?? null,
                'url'             => $payload['url']        ?? null,
                'referrer'        => $payload['referrer']   ?? null,
                'metadata'        => isset($payload['metadata']) ? json_encode($payload['metadata']) : null,
                'created_at'      => $now,
            ]);

            $productId = $payload['product_id'] ?? null;
            if ($productId !== null) {
                $this->touchProductCounter($type, (int) $productId, $now);
            }
        } catch (Throwable $e) {
            Log::channel('stack')->warning('analytics.record_failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recordSearch(string $rawKeyword, int $resultsCount): void
    {
        $keyword = SearchKeywordNormalizer::normalize($rawKeyword);
        if ($keyword === null) {
            return;
        }

        try {
            $now = Carbon::now();

            $this->record('search', [
                'metadata' => ['keyword' => $keyword, 'results_count' => $resultsCount],
            ]);

            $affected = DB::table('search_analytics')
                ->where('keyword', $keyword)
                ->update([
                    'search_count' => DB::raw('search_count + 1'),
                    'last_searched_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                DB::table('search_analytics')->insert([
                    'keyword' => $keyword,
                    'search_count' => 1,
                    'last_searched_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (Throwable $e) {
            Log::channel('stack')->warning('analytics.record_search_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function touchProductCounter(string $type, int $productId, Carbon $now): void
    {
        $column = match ($type) {
            'product_view'   => 'views_count',
            'add_to_cart'    => 'add_to_cart_count',
            'wishlist_click' => 'wishlist_count',
            default          => null,
        };
        if ($column === null) {
            return;
        }

        $updates = [
            $column => DB::raw($column . ' + 1'),
            'updated_at' => $now,
        ];
        if ($type === 'product_view') {
            $updates['last_viewed_at'] = $now;
        }

        $affected = DB::table('product_analytics')
            ->where('product_id', $productId)
            ->update($updates);

        if ($affected === 0) {
            DB::table('product_analytics')->insert([
                'product_id'        => $productId,
                'views_count'       => $type === 'product_view'   ? 1 : 0,
                'add_to_cart_count' => $type === 'add_to_cart'    ? 1 : 0,
                'wishlist_count'    => $type === 'wishlist_click' ? 1 : 0,
                'last_viewed_at'    => $type === 'product_view'   ? $now : null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }
    }

    /**
     * Builds a payload pre-populated with hashed visitor identifiers from the current request.
     * Callers use this then merge in their own keys (product_id, metadata, etc.).
     *
     * @return array<string, mixed>
     */
    public static function visitorPayloadFor(Request $request): array
    {
        $key = (string) config('app.key');
        $ip = $request->ip();
        $ua = $request->userAgent();

        return [
            'user_id'         => optional($request->user())->id,
            'session_id'      => $request->hasSession() ? substr(hash('sha256', $request->session()->getId()), 0, 40) : null,
            'ip_hash'         => $ip !== null ? hash('sha256', $ip . $key) : null,
            'user_agent_hash' => $ua !== null ? hash('sha256', $ua . $key) : null,
            'url'             => substr($request->fullUrl(), 0, 2048),
            'referrer'        => substr((string) $request->headers->get('referer', ''), 0, 2048) ?: null,
        ];
    }
}
