<?php

namespace Tests\Feature\Admin;

use App\Models\Goal;
use App\Models\Order;
use App\Models\User;
use App\Services\Goals\GoalMetricService;
use App\Services\Goals\PeriodRangeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalsCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_view_and_manage_permissions_are_separate(): void
    {
        $viewer = $this->staff([User::PERMISSION_GOALS_VIEW]);
        $this->asAdmin($viewer)->get(route('admin.goals.index'))->assertOk();
        $this->asAdmin($viewer)->post(route('admin.goals.store'), [])->assertForbidden();

        $outsider = $this->staff([User::PERMISSION_DASHBOARD_VIEW]);
        $this->asAdmin($outsider)->get(route('admin.goals.index'))->assertForbidden();
    }

    public function test_manager_can_create_update_manual_progress_and_archive_a_goal(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-10', 'Asia/Baghdad'));
        $manager = $this->staff([User::PERMISSION_GOALS_VIEW, User::PERMISSION_GOALS_MANAGE]);
        $payload = [
            'name' => 'Reach 200 orders', 'description' => 'September target', 'period_type' => 'monthly',
            'period_anchor' => '2026-09-01', 'start_date' => '2026-09-01', 'deadline' => '2026-09-30',
            'tracking_mode' => 'manual', 'target_value' => 200, 'unit' => 'orders', 'priority' => 'high', 'reward_points' => 250,
        ];

        $this->asAdmin($manager)->post(route('admin.goals.store'), $payload)->assertRedirect();
        $goal = Goal::firstOrFail();
        $this->assertSame('2026-09-01', $goal->period_start->toDateString());
        $this->assertNull($goal->metric_key);

        $this->asAdmin($manager)->patch(route('admin.goals.progress', $goal), ['value' => 143, 'note' => 'Daily close'])->assertRedirect();
        $this->assertSame('143.00', $goal->fresh()->manual_value);
        $this->assertDatabaseHas('goal_progress_updates', ['goal_id' => $goal->id, 'value' => 143]);

        $this->asAdmin($manager)->delete(route('admin.goals.destroy', $goal))->assertRedirect();
        $this->assertSoftDeleted('goals', ['id' => $goal->id]);
    }

    public function test_revenue_uses_paid_orders_and_excludes_archived_orders(): void
    {
        $customer = User::factory()->create();
        $this->order($customer, 'YS-1', 7000000, 'delivered', null, '2026-09-05');
        $this->order($customer, 'YS-2', 5000000, 'completed', now(), '2026-09-06');
        $this->order($customer, 'YS-3', 9000000, 'pending', null, '2026-09-07');

        $range = app(PeriodRangeResolver::class)->resolve('monthly', '2026-09-01');
        $value = app(GoalMetricService::class)->valueFor(GoalMetricService::REVENUE, [], $range);

        $this->assertSame(7000000.0, $value);
    }

    private function staff(array $permissions): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill(['role' => User::ROLE_ADMIN, 'permissions' => $permissions])->save();

        return $user;
    }

    private function asAdmin(User $user): static
    {
        return $this->actingAs($user)->withSession(['admin_2fa.verified_user_id' => $user->id]);
    }

    private function order(User $user, string $number, float $amount, string $status, mixed $archivedAt, string $createdAt): Order
    {
        return Order::forceCreate([
            'user_id' => $user->id, 'order_number' => $number, 'total_amount' => $amount, 'status' => $status,
            'payment_method' => 'cash_on_delivery', 'delivery_address' => 'Baghdad', 'delivery_city' => 'Baghdad',
            'delivery_phone' => '+9647700000000', 'archived_at' => $archivedAt, 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }
}
