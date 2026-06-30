<?php

namespace App\Services\Analytics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsQueryService
{
    public const CACHE_TTL_SECONDS = 300;
    public const ALLOWED_DAYS = [7, 30, 90, 365];

    /**
     * @return array<string, mixed>
     */
    public function snapshot(int $days): array
    {
        $days = $this->normalizeDays($days);

        return Cache::remember(
            "analytics.snapshot.{$days}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildSnapshot($days),
        );
    }

    public function normalizeDays(int $days): int
    {
        return in_array($days, self::ALLOWED_DAYS, true) ? $days : 30;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(int $days): array
    {
        if (! Schema::hasTable('analytics_events')) {
            return $this->emptySnapshot($days);
        }

        $end = Carbon::now();
        $start = $end->copy()->subDays($days)->startOfDay();
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subSecond();

        $kpi = $this->buildKpi($start, $end, $previousStart, $previousEnd);
        $topViewed = $this->topProducts('product_view', $start, $end);
        $topCartAdds = $this->topProducts('add_to_cart', $start, $end);
        $topWishlisted = $this->topProducts('wishlist_click', $start, $end);
        $topSearches = $this->topSearchKeywords($start, $end);
        $recentSearches = $this->recentSearches();

        return [
            'days' => $days,
            'allowedDays' => self::ALLOWED_DAYS,
            'start' => $start,
            'end' => $end,
            'kpi' => $kpi,
            'topViewed' => $topViewed,
            'topCartAdds' => $topCartAdds,
            'topWishlisted' => $topWishlisted,
            'topSearches' => $topSearches,
            'dailySeries' => $this->dailyEventSeries($start, $end),
            'recentSearches' => $recentSearches,
            'hasData' => $this->snapshotHasData($kpi, $topViewed, $topCartAdds, $topWishlisted, $topSearches, $recentSearches),
            'generatedAt' => Carbon::now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildKpi(Carbon $start, Carbon $end, Carbon $previousStart, Carbon $previousEnd): array
    {
        $countByType = function (string $type, Carbon $from, Carbon $to): int {
            return (int) DB::table('analytics_events')
                ->where('event_type', $type)
                ->whereBetween('created_at', [$from, $to])
                ->count();
        };

        $uniqueVisitors = function (Carbon $from, Carbon $to): int {
            return (int) DB::table('analytics_events')
                ->where('event_type', 'page_view')
                ->whereBetween('created_at', [$from, $to])
                ->distinct()
                ->count(DB::raw('COALESCE(session_id, ip_hash)'));
        };

        $pageViews = $countByType('page_view', $start, $end);
        $productViews = $countByType('product_view', $start, $end);
        $cartAdds = $countByType('add_to_cart', $start, $end);
        $wishlistClicks = $countByType('wishlist_click', $start, $end);
        $searchKeywords = $this->searchKeywordCount($start, $end);
        $checkoutStarts = $countByType('checkout_started', $start, $end);
        $ordersCompleted = $countByType('order_completed', $start, $end);
        $uniques = $uniqueVisitors($start, $end);

        $prevPageViews = $countByType('page_view', $previousStart, $previousEnd);
        $prevCartAdds = $countByType('add_to_cart', $previousStart, $previousEnd);
        $prevWishlist = $countByType('wishlist_click', $previousStart, $previousEnd);
        $prevUniques = $uniqueVisitors($previousStart, $previousEnd);

        $cartConversion = $productViews > 0 ? ($cartAdds / $productViews) * 100 : 0.0;
        $checkoutConversion = $cartAdds > 0 ? ($ordersCompleted / $cartAdds) * 100 : 0.0;

        return [
            'page_views' => $pageViews,
            'page_views_delta' => $this->percentChange($pageViews, $prevPageViews),
            'unique_visitors' => $uniques,
            'unique_visitors_delta' => $this->percentChange($uniques, $prevUniques),
            'product_views' => $productViews,
            'cart_adds' => $cartAdds,
            'cart_adds_delta' => $this->percentChange($cartAdds, $prevCartAdds),
            'wishlist_clicks' => $wishlistClicks,
            'wishlist_clicks_delta' => $this->percentChange($wishlistClicks, $prevWishlist),
            'search_keywords' => $searchKeywords,
            'checkout_starts' => $checkoutStarts,
            'orders_completed' => $ordersCompleted,
            'cart_conversion_pct' => round($cartConversion, 1),
            'checkout_conversion_pct' => round($checkoutConversion, 1),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function topProducts(string $eventType, Carbon $start, Carbon $end, int $limit = 7): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        $localeColumn = $this->localizedProductColumn();

        return DB::table('analytics_events')
            ->join('products', 'products.id', '=', 'analytics_events.product_id')
            ->where('analytics_events.event_type', $eventType)
            ->whereBetween('analytics_events.created_at', [$start, $end])
            ->select(
                'products.id',
                DB::raw("COALESCE(NULLIF({$localeColumn}, ''), products.name_en) as name"),
                'products.sku',
                DB::raw('COUNT(analytics_events.id) as event_count'),
            )
            ->groupBy('products.id', 'products.name_en', 'products.name_ar', 'products.name_ku', 'products.sku')
            ->orderByDesc('event_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'sku' => (string) ($row->sku ?? ''),
                'count' => (int) $row->event_count,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function topSearchKeywords(Carbon $start, Carbon $end, int $limit = 10): Collection
    {
        if (! Schema::hasTable('search_analytics')) {
            return collect();
        }

        return DB::table('search_analytics')
            ->where('last_searched_at', '>=', $start)
            ->where('last_searched_at', '<=', $end)
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get(['keyword', 'search_count', 'last_searched_at'])
            ->map(fn ($row) => [
                'keyword' => (string) $row->keyword,
                'count' => (int) $row->search_count,
                'last_searched_at' => Carbon::parse($row->last_searched_at),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function recentSearches(int $limit = 12): Collection
    {
        if (! Schema::hasTable('search_analytics')) {
            return collect();
        }

        return DB::table('search_analytics')
            ->orderByDesc('last_searched_at')
            ->limit($limit)
            ->get(['keyword', 'search_count', 'last_searched_at'])
            ->map(fn ($row) => [
                'keyword' => (string) $row->keyword,
                'count' => (int) $row->search_count,
                'last_searched_at' => Carbon::parse($row->last_searched_at),
            ]);
    }

    /**
     * @return array{labels: array<int,string>, datasets: array<string, array<int,int>>}
     */
    private function dailyEventSeries(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('analytics_events')
            ->selectRaw('DATE(created_at) as day, event_type, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('day', 'event_type')
            ->orderBy('day')
            ->get();

        $rangeDays = max(1, $start->diffInDays($end) + 1);
        $labels = [];
        $pageViews = [];
        $productViews = [];
        $cartAdds = [];
        $orders = [];

        $byDayType = $rows->groupBy('day')->map(
            fn ($group) => $group->keyBy('event_type'),
        );

        for ($i = $rangeDays - 1; $i >= 0; $i--) {
            $day = $end->copy()->subDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('M d');

            $bucket = $byDayType->get($key);
            $pageViews[] = (int) ($bucket?->get('page_view')->total ?? 0);
            $productViews[] = (int) ($bucket?->get('product_view')->total ?? 0);
            $cartAdds[] = (int) ($bucket?->get('add_to_cart')->total ?? 0);
            $orders[] = (int) ($bucket?->get('order_completed')->total ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'page_views' => $pageViews,
                'product_views' => $productViews,
                'cart_adds' => $cartAdds,
                'orders' => $orders,
            ],
        ];
    }

    private function percentChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function searchKeywordCount(Carbon $start, Carbon $end): int
    {
        if (! Schema::hasTable('search_analytics')) {
            return 0;
        }

        return (int) DB::table('search_analytics')
            ->whereBetween('last_searched_at', [$start, $end])
            ->count();
    }

    /**
     * @param array<string, mixed> $kpi
     */
    private function snapshotHasData(array $kpi, Collection ...$collections): bool
    {
        $trackedKeys = [
            'page_views',
            'product_views',
            'cart_adds',
            'wishlist_clicks',
            'search_keywords',
            'checkout_starts',
            'orders_completed',
        ];

        foreach ($trackedKeys as $key) {
            if ((int) ($kpi[$key] ?? 0) > 0) {
                return true;
            }
        }

        foreach ($collections as $collection) {
            if ($collection->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    private function localizedProductColumn(): string
    {
        return match (true) {
            str_starts_with(app()->getLocale(), 'ar') => 'products.name_ar',
            str_starts_with(app()->getLocale(), 'ku') => 'products.name_ku',
            default => 'products.name_en',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySnapshot(int $days): array
    {
        return [
            'days' => $days,
            'allowedDays' => self::ALLOWED_DAYS,
            'start' => Carbon::now()->subDays($days),
            'end' => Carbon::now(),
            'kpi' => [
                'page_views' => 0, 'page_views_delta' => 0.0,
                'unique_visitors' => 0, 'unique_visitors_delta' => 0.0,
                'product_views' => 0,
                'cart_adds' => 0, 'cart_adds_delta' => 0.0,
                'wishlist_clicks' => 0, 'wishlist_clicks_delta' => 0.0,
                'search_keywords' => 0,
                'checkout_starts' => 0,
                'orders_completed' => 0,
                'cart_conversion_pct' => 0.0,
                'checkout_conversion_pct' => 0.0,
            ],
            'topViewed' => collect(),
            'topCartAdds' => collect(),
            'topWishlisted' => collect(),
            'topSearches' => collect(),
            'dailySeries' => ['labels' => [], 'datasets' => [
                'page_views' => [], 'product_views' => [], 'cart_adds' => [], 'orders' => [],
            ]],
            'recentSearches' => collect(),
            'hasData' => false,
            'generatedAt' => Carbon::now(),
        ];
    }
}
