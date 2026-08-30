<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GoalAnalyticsService
{
    public function __construct(
        private readonly GoalMetricService $metrics,
        private readonly GoalProgressService $progress,
    ) {}

    public function dashboard(array $period, Collection $goals): array
    {
        $definitions = [
            GoalMetricService::REVENUE => ['label' => __('Revenue'), 'icon' => 'fa-coins'],
            GoalMetricService::ORDERS => ['label' => __('Orders'), 'icon' => 'fa-box'],
            GoalMetricService::PRODUCTS_ADDED => ['label' => __('Products Added'), 'icon' => 'fa-layer-group'],
            GoalMetricService::NEW_CUSTOMERS => ['label' => __('New Customers'), 'icon' => 'fa-user-plus'],
            GoalMetricService::DELIVERED_ORDERS => ['label' => __('Delivered Orders'), 'icon' => 'fa-truck-fast'],
        ];
        $cards = [];
        foreach ($definitions as $key => $meta) {
            $current = $this->metrics->valueFor($key, [], $period);
            $previous = $this->metrics->valueFor($key, [], [
                'start' => $period['previous_start'], 'end_exclusive' => $period['previous_end_exclusive'],
            ]);
            $cards[$key] = $meta + [
                'value' => $current,
                'previous' => $previous,
                'change' => $this->percentageChange($current, $previous),
                'unit' => $this->metrics->definitions()[$key]['unit'],
            ];
        }

        $previousGoals = Goal::query()->where('period_type', $period['type'])
            ->whereDate('period_start', $period['previous_start']->toDateString())->get();
        $currentCompleted = $goals->filter(fn (Goal $goal) => $goal->evaluation['status'] === 'completed')->count();
        $previousCompleted = $previousGoals->filter(fn (Goal $goal) => $this->progress->evaluate($goal, [
            'start' => $period['previous_start'], 'end_exclusive' => $period['previous_end_exclusive'],
        ])['status'] === 'completed')->count();
        $cards['completed_goals'] = [
            'label' => __('Completed Goals'), 'icon' => 'fa-circle-check', 'value' => $currentCompleted,
            'previous' => $previousCompleted, 'change' => $this->percentageChange($currentCompleted, $previousCompleted), 'unit' => 'goals',
        ];

        $trend = $this->trend($period);
        $bestIndex = collect($trend['orders'])->keys()->sortByDesc(fn (int $index) => $trend['orders'][$index])->first();
        $elapsedDays = $period['is_current']
            ? max(1, $period['start']->diffInDays(CarbonImmutable::now($period['timezone'])->startOfDay()) + 1)
            : max(1, $period['start']->diffInDays($period['end_exclusive']));
        $totalDays = max(1, $period['start']->diffInDays($period['end_exclusive']));
        $orders = $cards[GoalMetricService::ORDERS]['value'];
        $mostImproved = collect($cards)->filter(fn (array $card) => $card['change'] !== null)->sortByDesc('change')->first();

        return [
            'cards' => $cards,
            'trend' => $trend,
            'insights' => [
                'best_day' => $bestIndex !== null && ($trend['orders'][$bestIndex] ?? 0) > 0 ? $trend['labels'][$bestIndex] : __('No order data yet'),
                'average_daily_orders' => round($orders / $elapsedDays, 1),
                'projected_orders' => $period['is_current'] ? (int) round(($orders / $elapsedDays) * $totalDays) : (int) $orders,
                'most_improved' => $mostImproved ? $mostImproved['label'].' '.sprintf('%+.1f%%', $mostImproved['change']) : __('Not enough comparison data'),
            ],
        ];
    }

    private function trend(array $period): array
    {
        $yearly = $period['type'] === Goal::PERIOD_YEARLY;
        $driver = DB::connection()->getDriverName();
        $expression = $yearly
            ? ($driver === 'mysql' ? "DATE_FORMAT(created_at, '%Y-%m')" : "strftime('%Y-%m', created_at)")
            : ($driver === 'mysql' ? 'DATE(created_at)' : 'date(created_at)');
        $rows = Order::query()->whereNull('archived_at')
            ->where('created_at', '>=', $period['start'])->where('created_at', '<', $period['end_exclusive'])
            ->selectRaw("{$expression} as bucket")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('cancelled','canceled') THEN 1 ELSE 0 END) as orders_count")
            ->selectRaw("SUM(CASE WHEN status IN ('delivered','completed') THEN total_amount ELSE 0 END) as revenue_total")
            ->groupBy('bucket')->pluck('revenue_total', 'bucket')->map(fn ($value) => (float) $value);
        $orderRows = Order::query()->whereNull('archived_at')
            ->where('created_at', '>=', $period['start'])->where('created_at', '<', $period['end_exclusive'])
            ->selectRaw("{$expression} as bucket")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('cancelled','canceled') THEN 1 ELSE 0 END) as orders_count")
            ->groupBy('bucket')->pluck('orders_count', 'bucket')->map(fn ($value) => (int) $value);

        $cursor = $period['start'];
        $labels = $keys = [];
        while ($cursor->lessThan($period['end_exclusive'])) {
            $keys[] = $yearly ? $cursor->format('Y-m') : $cursor->toDateString();
            $labels[] = $yearly ? $cursor->isoFormat('MMM') : $cursor->isoFormat('MMM D');
            $cursor = $yearly ? $cursor->addMonth() : $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'orders' => array_map(fn (string $key) => $orderRows[$key] ?? 0, $keys),
            'revenue' => array_map(fn (string $key) => $rows[$key] ?? 0, $keys),
        ];
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
