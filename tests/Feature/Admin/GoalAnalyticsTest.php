<?php

namespace Tests\Feature\Admin;

use App\Models\GoalMetricSnapshot;
use App\Models\Order;
use App\Models\User;
use App\Services\Goals\GoalAnalyticsService;
use App\Services\Goals\PeriodRangeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GoalAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_kpis_trend_and_insights_use_current_and_previous_period_data(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-10 12:00', 'Asia/Baghdad'));
        $customer = User::factory()->create();
        $this->order($customer, 'CUR-1', 1000000, 'delivered', '2026-09-03');
        $this->order($customer, 'CUR-2', 2000000, 'pending', '2026-09-04');
        $this->order($customer, 'PRE-1', 500000, 'delivered', '2026-08-03');
        $this->order($customer, 'ARC-1', 9000000, 'delivered', '2026-09-05', now());

        $period = app(PeriodRangeResolver::class)->resolve('monthly', '2026-09-01');
        $data = app(GoalAnalyticsService::class)->dashboard($period, new Collection);

        $this->assertSame(1000000.0, $data['cards']['revenue']['value']);
        $this->assertSame(100.0, $data['cards']['revenue']['change']);
        $this->assertSame(2.0, $data['cards']['orders']['value']);
        $this->assertSame(2, array_sum($data['trend']['orders']));
        $this->assertSame(1000000.0, array_sum($data['trend']['revenue']));
        $this->assertNotSame('No order data yet', $data['insights']['best_day']);
    }

    public function test_snapshot_command_is_daily_and_idempotent(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-10 00:15', 'Asia/Baghdad'));

        $this->artisan('goals:capture-snapshots')->assertSuccessful();
        $this->artisan('goals:capture-snapshots')->assertSuccessful();

        $this->assertSame(21, GoalMetricSnapshot::query()->count());
        $this->assertDatabaseHas('goal_metric_snapshots', [
            'period_type' => 'monthly', 'period_start' => '2026-09-01 00:00:00',
            'metric_key' => 'revenue', 'captured_on' => '2026-09-10 00:00:00',
        ]);
    }

    private function order(User $user, string $number, float $amount, string $status, string $createdAt, mixed $archivedAt = null): Order
    {
        return Order::forceCreate([
            'user_id' => $user->id, 'order_number' => $number, 'total_amount' => $amount, 'status' => $status,
            'payment_method' => 'cash_on_delivery', 'delivery_address' => 'Baghdad', 'delivery_city' => 'Baghdad',
            'delivery_phone' => '+9647700000000', 'archived_at' => $archivedAt, 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }
}
