<?php

namespace App\Services\Goals;

use App\Models\Goal;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class PeriodRangeResolver
{
    public function resolve(?string $type = null, ?string $anchor = null): array
    {
        $type = $type ?: Goal::PERIOD_MONTHLY;
        if (! in_array($type, Goal::periodTypes(), true)) {
            throw new InvalidArgumentException('Unsupported goal period.');
        }

        $timezone = (string) config('goals.timezone', 'Asia/Baghdad');
        $now = CarbonImmutable::now($timezone);
        $date = $anchor
            ? CarbonImmutable::createFromFormat('Y-m-d', $anchor, $timezone)->startOfDay()
            : $now->startOfDay();

        [$start, $endExclusive, $previousAnchor, $nextAnchor] = match ($type) {
            Goal::PERIOD_WEEKLY => [
                $date->startOfWeek(CarbonImmutable::MONDAY),
                $date->startOfWeek(CarbonImmutable::MONDAY)->addWeek(),
                $date->startOfWeek(CarbonImmutable::MONDAY)->subWeek(),
                $date->startOfWeek(CarbonImmutable::MONDAY)->addWeek(),
            ],
            Goal::PERIOD_YEARLY => [
                $date->startOfYear(),
                $date->startOfYear()->addYear(),
                $date->startOfYear()->subYear(),
                $date->startOfYear()->addYear(),
            ],
            default => [
                $date->startOfMonth(),
                $date->startOfMonth()->addMonth(),
                $date->startOfMonth()->subMonth(),
                $date->startOfMonth()->addMonth(),
            ],
        };

        $displayEnd = $endExclusive->subDay();
        $previousStart = match ($type) {
            Goal::PERIOD_WEEKLY => $start->subWeek(),
            Goal::PERIOD_YEARLY => $start->subYear(),
            default => $start->subMonth(),
        };

        return [
            'type' => $type,
            'anchor' => $start->toDateString(),
            'start' => $start,
            'end_exclusive' => $endExclusive,
            'display_end' => $displayEnd,
            'previous_start' => $previousStart,
            'previous_end_exclusive' => $start,
            'previous_anchor' => $previousAnchor->toDateString(),
            'next_anchor' => $nextAnchor->toDateString(),
            'title' => $this->title($type, $start, $displayEnd),
            'date_range' => $this->dateRange($type, $start, $displayEnd),
            'is_current' => $now->betweenIncluded($start, $displayEnd->endOfDay()),
            'is_past' => $now->greaterThanOrEqualTo($endExclusive),
            'days_remaining' => $now->greaterThanOrEqualTo($endExclusive) ? 0 : max(0, $now->startOfDay()->diffInDays($displayEnd) + 1),
            'timezone' => $timezone,
        ];
    }

    private function title(string $type, CarbonImmutable $start, CarbonImmutable $end): string
    {
        return match ($type) {
            Goal::PERIOD_WEEKLY => $start->isoFormat('MMM D').' – '.$end->isoFormat('MMM D'),
            Goal::PERIOD_YEARLY => $start->format('Y'),
            default => $start->isoFormat('MMMM Y'),
        };
    }

    private function dateRange(string $type, CarbonImmutable $start, CarbonImmutable $end): string
    {
        return $start->isoFormat('MMM D, Y').' – '.$end->isoFormat('MMM D, Y');
    }
}
