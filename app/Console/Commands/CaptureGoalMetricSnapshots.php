<?php

namespace App\Console\Commands;

use App\Models\Goal;
use App\Models\GoalMetricSnapshot;
use App\Services\Goals\GoalMetricService;
use App\Services\Goals\PeriodRangeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class CaptureGoalMetricSnapshots extends Command
{
    protected $signature = 'goals:capture-snapshots';

    protected $description = 'Capture daily business metrics used by the Goals Center';

    public function handle(PeriodRangeResolver $periods, GoalMetricService $metrics): int
    {
        if (! Schema::hasTable('goal_metric_snapshots')) {
            $this->warn('goal_metric_snapshots table is missing. Nothing captured.');

            return self::SUCCESS;
        }

        $now = CarbonImmutable::now((string) config('goals.timezone', 'Asia/Baghdad'));
        $captured = 0;
        foreach (Goal::periodTypes() as $periodType) {
            $period = $periods->resolve($periodType, $now->toDateString());
            $metricConfigurations = collect([
                ['metric_key' => GoalMetricService::REVENUE, 'metric_config' => []],
                ['metric_key' => GoalMetricService::ORDERS, 'metric_config' => []],
                ['metric_key' => GoalMetricService::DELIVERED_ORDERS, 'metric_config' => []],
                ['metric_key' => GoalMetricService::NEW_CUSTOMERS, 'metric_config' => []],
                ['metric_key' => GoalMetricService::PRODUCTS_ADDED, 'metric_config' => []],
                ['metric_key' => GoalMetricService::PENDING_ORDERS, 'metric_config' => []],
                ['metric_key' => GoalMetricService::LOW_STOCK_PRODUCTS, 'metric_config' => []],
            ])->merge(
                Goal::query()->where('period_type', $periodType)->whereDate('period_start', $period['anchor'])
                    ->where('tracking_mode', Goal::TRACKING_AUTOMATIC)->get(['metric_key', 'metric_config'])
                    ->map(fn (Goal $goal) => ['metric_key' => $goal->metric_key, 'metric_config' => $goal->metric_config ?? []])
            )->unique(fn (array $item) => $item['metric_key'].'|'.$this->configHash($item['metric_config']));

            foreach ($metricConfigurations as $item) {
                GoalMetricSnapshot::updateOrCreate([
                    'period_type' => $periodType, 'period_start' => $period['start'], 'metric_key' => $item['metric_key'],
                    'metric_config_hash' => $this->configHash($item['metric_config']), 'captured_on' => $now->startOfDay(),
                ], [
                    'metric_config' => $item['metric_config'] ?: null,
                    'value' => $metrics->valueFor($item['metric_key'], $item['metric_config'], $period),
                    'captured_at' => $now,
                ]);
                $captured++;
            }
        }

        $this->info("Captured {$captured} goal metric snapshot(s).");

        return self::SUCCESS;
    }

    private function configHash(array $config): string
    {
        $config = Arr::sortRecursive($config);

        return $config === [] ? 'none' : hash('sha256', json_encode($config, JSON_THROW_ON_ERROR));
    }
}
