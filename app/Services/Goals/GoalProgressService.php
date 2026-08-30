<?php

namespace App\Services\Goals;

use App\Models\Goal;
use Carbon\CarbonImmutable;

class GoalProgressService
{
    public function __construct(private readonly GoalMetricService $metrics) {}

    public function evaluate(Goal $goal, array $range): array
    {
        $actual = $goal->tracking_mode === Goal::TRACKING_AUTOMATIC ? $this->metrics->value($goal, $range) : (float) $goal->manual_value;
        $baseline = (float) $goal->baseline_value;
        $target = (float) $goal->target_value;
        $progress = $this->percentage($actual, $baseline, $target, (string) $goal->direction);
        $now = CarbonImmutable::now((string) config('goals.timezone', 'Asia/Baghdad'));
        $start = CarbonImmutable::parse($goal->start_date, $now->timezone)->startOfDay();
        $deadline = CarbonImmutable::parse($goal->deadline, $now->timezone)->endOfDay();

        return [
            'actual' => $actual, 'target' => $target, 'progress' => $progress,
            'status' => $this->status($progress, $actual, $baseline, $start, $deadline, $now),
            'days_left' => $now->greaterThan($deadline) ? 0 : max(0, $now->startOfDay()->diffInDays($deadline->startOfDay()) + 1),
        ];
    }

    public function percentage(float $actual, float $baseline, float $target, string $direction): float
    {
        if ($direction === Goal::DIRECTION_DECREASE) {
            if ($actual <= $target) {
                return 100.0;
            }
            $span = $baseline - $target;

            return $span > 0 ? round(max(0, min(100, (($baseline - $actual) / $span) * 100)), 1) : 0.0;
        }
        if ($actual >= $target) {
            return 100.0;
        }
        $span = $target - $baseline;

        return $span > 0 ? round(max(0, min(100, (($actual - $baseline) / $span) * 100)), 1) : 0.0;
    }

    public function syncCompletion(Goal $goal, array $range): array
    {
        $evaluation = $this->evaluate($goal, $range);
        $completedAt = $evaluation['status'] === 'completed'
            ? ($goal->completed_at ?? CarbonImmutable::now((string) config('goals.timezone', 'Asia/Baghdad')))
            : null;

        if (($goal->completed_at?->toDateTimeString()) !== ($completedAt?->toDateTimeString())) {
            $goal->updateQuietly(['completed_at' => $completedAt]);
        }

        return $evaluation;
    }

    private function status(float $progress, float $actual, float $baseline, CarbonImmutable $start, CarbonImmutable $deadline, CarbonImmutable $now): string
    {
        if ($progress >= 100) {
            return 'completed';
        }
        if ($now->greaterThan($deadline)) {
            return 'failed';
        }
        if ($now->lessThan($start) || abs($actual - $baseline) < 0.0001) {
            return 'not_started';
        }
        $totalSeconds = max(1, $start->diffInSeconds($deadline));
        $elapsed = min($totalSeconds, max(0, $start->diffInSeconds($now)));

        return $progress + (float) config('goals.at_risk_gap_percent', 15) < (($elapsed / $totalSeconds) * 100) ? 'at_risk' : 'in_progress';
    }
}
