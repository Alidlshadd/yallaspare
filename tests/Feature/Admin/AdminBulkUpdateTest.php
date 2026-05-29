<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function makeOrder(string $status = Order::STATUS_PROCESSING): Order
    {
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 50,
            'price' => 10000,
        ]);

        $order = Order::forceCreate([
            'user_id' => $customer->id,
            'order_number' => 'ORD-' . uniqid(),
            'subtotal_amount' => 20000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 20000,
            'total_amount' => 20000,
            'status' => $status,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Test',
            'delivery_city' => 'Baghdad',
            'delivery_phone' => '07700000000',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        return $order;
    }

    public function test_bulk_status_updates_eligible_orders(): void
    {
        $admin = $this->admin();

        $a = $this->makeOrder(Order::STATUS_PROCESSING);
        $b = $this->makeOrder(Order::STATUS_PROCESSING);

        $this->actingAs($admin)
            ->post(route('admin.orders.bulk-status'), [
                'order_ids' => [$a->id, $b->id],
                'status' => Order::STATUS_SHIPPED,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Order::STATUS_SHIPPED, $a->fresh()->status);
        $this->assertSame(Order::STATUS_SHIPPED, $b->fresh()->status);
    }

    public function test_bulk_status_skips_invalid_transitions_and_reports_them(): void
    {
        $admin = $this->admin();

        $valid = $this->makeOrder(Order::STATUS_PROCESSING);
        $delivered = $this->makeOrder(Order::STATUS_DELIVERED);

        $this->actingAs($admin)
            ->post(route('admin.orders.bulk-status'), [
                'order_ids' => [$valid->id, $delivered->id],
                'status' => Order::STATUS_SHIPPED,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(Order::STATUS_SHIPPED, $valid->fresh()->status);
        $this->assertSame(Order::STATUS_DELIVERED, $delivered->fresh()->status);
    }

    public function test_bulk_cancel_restores_stock_per_order(): void
    {
        $admin = $this->admin();

        $a = $this->makeOrder(Order::STATUS_PROCESSING);
        $b = $this->makeOrder(Order::STATUS_PROCESSING);

        $stockBeforeA = $a->items->first()->product->stock_quantity;
        $stockBeforeB = $b->items->first()->product->stock_quantity;

        $this->actingAs($admin)
            ->post(route('admin.orders.bulk-status'), [
                'order_ids' => [$a->id, $b->id],
                'status' => Order::STATUS_CANCELLED,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($stockBeforeA + 2, $a->items->first()->product->fresh()->stock_quantity);
        $this->assertSame($stockBeforeB + 2, $b->items->first()->product->fresh()->stock_quantity);

        $this->assertSame(2, InventoryMovement::query()
            ->where('type', InventoryMovement::TYPE_IN)
            ->where('note', 'Order cancelled (bulk) - stock restored')
            ->count());
    }

    public function test_bulk_return_update_changes_status_and_resolves_terminal_ones(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $order = $this->makeOrder(Order::STATUS_DELIVERED);

        $r1 = ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => 'return',
            'status' => ReturnRequest::STATUS_REQUESTED,
            'reason' => 'Wrong part',
            'refund_amount' => 0,
            'requested_at' => now(),
        ]);
        $r2 = ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => 'return',
            'status' => ReturnRequest::STATUS_REQUESTED,
            'reason' => 'Damaged',
            'refund_amount' => 0,
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.returns.bulk-update'), [
                'return_ids' => [$r1->id, $r2->id],
                'status' => ReturnRequest::STATUS_REJECTED,
                'admin_note' => 'Returns rejected after warehouse review',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $r1f = $r1->fresh();
        $r2f = $r2->fresh();

        $this->assertSame(ReturnRequest::STATUS_REJECTED, $r1f->status);
        $this->assertSame(ReturnRequest::STATUS_REJECTED, $r2f->status);
        $this->assertNotNull($r1f->resolved_at);
        $this->assertNotNull($r2f->resolved_at);
        $this->assertStringContainsString('Returns rejected after warehouse review', (string) $r1f->admin_note);
    }
}
