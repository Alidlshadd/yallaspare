<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Customer;
use App\Models\ManualInvoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Goals\GoalMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * A sale invoiced by hand is revenue like any other: it is counted on the
 * dashboard, on the revenue page and towards revenue goals, by what was
 * invoiced and on the invoice's own date.
 */
class ManualInvoiceRevenueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function invoice(string $date, float $unitPrice, string $state = 'finalized'): ManualInvoice
    {
        $customer = Customer::query()->firstOrCreate(['phone' => '+9647701234567'], ['name' => 'Karwan Garage']);
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock_quantity' => 50,
        ]);

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => $date,
            'action' => 'draft',
            'items' => [['product_id' => $product->id, 'description' => 'Part', 'quantity' => 1, 'unit_price' => $unitPrice]],
        ])->assertSessionHasNoErrors();

        $invoice = ManualInvoice::query()->latest('id')->firstOrFail();

        if ($state !== 'draft') {
            $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));
        }

        if ($state === 'void') {
            $this->actingAs($this->admin)->post(route('admin.manual-invoices.void', $invoice), ['void_reason' => 'Entered twice']);
        }

        return $invoice->fresh();
    }

    private function deliveredOrder(float $total): Order
    {
        $order = new Order;
        $order->forceFill([
            'user_id' => User::factory()->create()->id,
            'order_number' => 'ORD-TEST-'.uniqid(),
            'subtotal_amount' => $total,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => $total,
            'total_amount' => $total,
            'status' => 'delivered',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Street 10',
            'delivery_city' => 'Baghdad',
            'delivery_phone' => '123456789',
        ])->save();

        return $order;
    }

    public function test_only_finalized_invoices_count_and_on_their_own_date(): void
    {
        $this->travelTo('2026-10-08 12:00:00');

        $this->invoice('2026-10-08', 40000);
        $this->invoice('2026-10-07', 25000);
        $this->invoice('2026-09-15', 10000);
        $this->invoice('2026-10-08', 70000, 'draft');
        $this->invoice('2026-10-08', 90000, 'void');

        $this->assertSame(75000.0, ManualInvoice::invoicedBetween());
        $this->assertSame(40000.0, ManualInvoice::invoicedBetween(now(), now()));
        $this->assertSame(65000.0, ManualInvoice::invoicedBetween(now()->startOfMonth(), now()));
        $this->assertSame(10000.0, ManualInvoice::invoicedBetween(now()->subMonth()->startOfMonth(), now()->startOfMonth()->subSecond()));
        $this->assertSame(['2026-10-07' => 25000.0, '2026-10-08' => 40000.0], collect(ManualInvoice::invoicedByDay(now()->subDay(), now()))->sortKeys()->all());
    }

    public function test_the_dashboard_counts_manual_sales_in_its_revenue(): void
    {
        $this->travelTo('2026-10-08 12:00:00');
        Cache::flush();

        $this->deliveredOrder(30000);
        $this->invoice('2026-10-08', 40000);
        $this->invoice('2026-10-08', 70000, 'draft');

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('70,000');
    }

    public function test_the_revenue_page_and_its_export_add_manual_sales_to_the_totals(): void
    {
        $this->travelTo('2026-10-08 12:00:00');

        $this->deliveredOrder(30000);
        $this->invoice('2026-10-08', 40000);
        $this->invoice('2026-10-08', 90000, 'void');

        $this->actingAs($this->admin)
            ->get(route('admin.revenue.index'))
            ->assertOk()
            ->assertSee('70,000')
            ->assertSee('included in the revenue above');

        $csv = $this->actingAs($this->admin)
            ->get(route('admin.revenue.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Date,"Paid Orders","Site Orders","Manual Invoices",Revenue', $csv);
        $this->assertStringContainsString('2026-10-08,1,30000,40000,70000', $csv);
    }

    public function test_a_revenue_goal_counts_manual_sales_within_its_range_only(): void
    {
        $this->travelTo('2026-10-08 12:00:00');

        $this->deliveredOrder(30000);
        $this->invoice('2026-10-08', 40000);
        $this->invoice('2026-09-30', 15000);

        $value = app(GoalMetricService::class)->valueFor(GoalMetricService::REVENUE, [], [
            'start' => now()->startOfMonth(),
            'end_exclusive' => now()->startOfMonth()->addMonth(),
        ]);

        $this->assertSame(70000.0, $value);

        // The range ends before a day; an invoice dated that day is outside it.
        $throughYesterday = app(GoalMetricService::class)->valueFor(GoalMetricService::REVENUE, [], [
            'start' => now()->startOfMonth(),
            'end_exclusive' => now()->startOfDay(),
        ]);

        $this->assertSame(0.0, $throughYesterday);
    }
}
