<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalAchievement;
use App\Models\Order;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GoalMotivationService
{
    public function overview(): array
    {
        $streak = $this->orderStreak();
        $orders = Order::query()->whereNull('archived_at')->whereNotIn('status', [Order::STATUS_CANCELLED, 'canceled'])->count();
        $revenue = (float) Order::query()->whereNull('archived_at')->whereIn('status', [Order::STATUS_DELIVERED, 'completed'])->sum('total_amount');
        $pending = Order::query()->whereNull('archived_at')->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])->count();
        $hasOrders = Order::query()->whereNull('archived_at')->exists();
        $hasProducts = Product::query()->where('is_active', true)->exists();
        $lowStock = Product::query()->lowStock()->count();

        $definitions = [
            'first_100_orders' => [__('First 100 Orders'), __('Build a record of 100 active orders.'), 'fa-box-open', $orders >= 100],
            'revenue_champion' => [__('Revenue Champion'), __('Reach 10M IQD in delivered revenue.'), 'fa-trophy', $revenue >= 10000000],
            'seven_day_streak' => [__('7 Day Streak'), __('Record orders on seven consecutive days.'), 'fa-fire', $streak >= 7],
            'inventory_master' => [__('Inventory Master'), __('Keep every active product above its low-stock threshold.'), 'fa-warehouse', $hasProducts && $lowStock === 0],
            'zero_pending_orders' => [__('Zero Pending Orders'), __('Clear the pending and processing order queue.'), 'fa-inbox', $hasOrders && $pending === 0],
        ];
        $unlocked = Schema::hasTable('goal_achievements')
            ? GoalAchievement::query()->pluck('unlocked_at', 'key')
            : collect();

        return [
            'streak' => $streak,
            'total_points' => (int) Goal::query()->whereNotNull('completed_at')->sum('reward_points'),
            'achievements' => collect($definitions)->map(function (array $definition, string $key) use ($unlocked): array {
                return [
                    'key' => $key, 'name' => $definition[0], 'description' => $definition[1], 'icon' => $definition[2],
                    'earned' => $definition[3] || $unlocked->has($key), 'unlocked_at' => $unlocked->get($key),
                ];
            })->values(),
        ];
    }

    public function captureUnlocks(): int
    {
        if (! Schema::hasTable('goal_achievements')) {
            return 0;
        }

        $created = 0;
        foreach ($this->overview()['achievements']->where('earned', true) as $achievement) {
            $record = GoalAchievement::firstOrCreate(['key' => $achievement['key']], [
                'unlocked_at' => CarbonImmutable::now((string) config('goals.timezone', 'Asia/Baghdad')),
                'context' => ['source' => 'goals_center'],
            ]);
            $created += $record->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    private function orderStreak(): int
    {
        $driver = DB::connection()->getDriverName();
        $expression = $driver === 'mysql' ? 'DATE(created_at)' : 'date(created_at)';
        $dates = Order::query()->whereNull('archived_at')->whereNotIn('status', [Order::STATUS_CANCELLED, 'canceled'])
            ->where('created_at', '>=', CarbonImmutable::now((string) config('goals.timezone', 'Asia/Baghdad'))->subDays(370))
            ->selectRaw("{$expression} as order_day")->distinct()->pluck('order_day')->flip();
        $cursor = CarbonImmutable::now((string) config('goals.timezone', 'Asia/Baghdad'))->startOfDay();
        if (! $dates->has($cursor->toDateString())) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;
        while ($dates->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }
}
