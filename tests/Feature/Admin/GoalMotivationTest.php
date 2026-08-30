<?php

namespace Tests\Feature\Admin;

use App\Models\Goal;
use App\Models\GoalAchievement;
use App\Models\Order;
use App\Models\User;
use App\Services\Goals\GoalMotivationService;
use App\Services\Goals\GoalProgressService;
use App\Services\Goals\PeriodRangeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalMotivationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_streak_and_business_achievements_are_derived_from_live_data_and_unlock_once(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-10 12:00', 'Asia/Baghdad'));
        $customer = User::factory()->create();
        foreach (range(0, 6) as $offset) {
            $this->order($customer, 'STREAK-'.$offset, 2000000, CarbonImmutable::now('Asia/Baghdad')->subDays($offset));
        }

        $service = app(GoalMotivationService::class);
        $overview = $service->overview();

        $this->assertSame(7, $overview['streak']);
        $this->assertTrue($overview['achievements']->firstWhere('key', 'seven_day_streak')['earned']);
        $this->assertTrue($overview['achievements']->firstWhere('key', 'revenue_champion')['earned']);
        $this->assertSame(3, $service->captureUnlocks());
        $this->assertSame(0, $service->captureUnlocks());
        $this->assertSame(3, GoalAchievement::query()->count());
    }

    public function test_completed_goals_contribute_reward_points(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-10', 'Asia/Baghdad'));
        $goal = Goal::create([
            'name' => 'Manual target', 'period_type' => 'monthly', 'period_start' => '2026-09-01',
            'start_date' => '2026-09-01', 'deadline' => '2026-09-30', 'tracking_mode' => 'manual',
            'direction' => 'increase', 'baseline_value' => 0, 'target_value' => 10, 'manual_value' => 10,
            'unit' => 'orders', 'priority' => 'medium', 'reward_points' => 450,
        ]);
        $range = app(PeriodRangeResolver::class)->resolve('monthly', '2026-09-01');
        app(GoalProgressService::class)->syncCompletion($goal, $range);

        $this->assertNotNull($goal->fresh()->completed_at);
        $this->assertSame(450, app(GoalMotivationService::class)->overview()['total_points']);
    }

    private function order(User $user, string $number, float $amount, CarbonImmutable $createdAt): Order
    {
        return Order::forceCreate([
            'user_id' => $user->id, 'order_number' => $number, 'total_amount' => $amount, 'status' => 'delivered',
            'payment_method' => 'cash_on_delivery', 'delivery_address' => 'Baghdad', 'delivery_city' => 'Baghdad',
            'delivery_phone' => '+9647700000000', 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }
}
