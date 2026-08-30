<?php

namespace Tests\Unit\Services\Goals;

use App\Models\Goal;
use App\Services\Goals\GoalMetricService;
use App\Services\Goals\GoalProgressService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class GoalProgressServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_progress_and_status_are_derived_not_stored(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-15 12:00', 'Asia/Baghdad'));
        $goal = new Goal([
            'tracking_mode' => 'manual', 'manual_value' => 75, 'baseline_value' => 0, 'target_value' => 100,
            'direction' => 'increase', 'start_date' => '2026-09-01', 'deadline' => '2026-09-30',
        ]);

        $result = (new GoalProgressService(app(GoalMetricService::class)))->evaluate($goal, []);

        $this->assertSame(75.0, $result['progress']);
        $this->assertSame('in_progress', $result['status']);
        $this->assertArrayNotHasKey('status', $goal->getAttributes());
    }

    public function test_decrease_goals_reach_completion_at_or_below_target(): void
    {
        $service = new GoalProgressService(app(GoalMetricService::class));
        $this->assertSame(50.0, $service->percentage(30, 50, 10, 'decrease'));
        $this->assertSame(100.0, $service->percentage(10, 50, 10, 'decrease'));
    }
}
