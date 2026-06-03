<?php

namespace Tests\Feature\Admin;

use App\Exports\OrdersExport;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_order_details(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $customer = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Presentation Brake Pad',
            'sku' => 'SKU-PRESENT-01',
            'price' => 25000,
        ]);

        $order = Order::forceCreate([
            'user_id' => $customer->id,
            'order_number' => 'ORD-PRESENTATION-001',
            'subtotal_amount' => 50000,
            'shipping_fee' => 5000,
            'discount_amount' => 0,
            'grand_total' => 55000,
            'total_amount' => 55000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Baghdad demo address',
            'delivery_city' => 'Baghdad',
            'delivery_phone' => '07700000000',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25000,
            'subtotal' => 50000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('ORD-PRESENTATION-001');
        $response->assertSee('Presentation Brake Pad');
    }

    public function test_order_export_uses_the_active_order_filters(): void
    {
        Excel::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $dealer = User::factory()->create([
            'role' => User::ROLE_DEALER,
            'email_verified_at' => now(),
        ]);

        $retail = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $makeOrder = function (array $attributes): Order {
            return Order::forceCreate(array_merge([
                'subtotal_amount' => 50000,
                'shipping_fee' => 0,
                'discount_amount' => 0,
                'grand_total' => 50000,
                'total_amount' => 50000,
                'payment_method' => 'cash_on_delivery',
                'payment_status' => Order::PAYMENT_PENDING,
                'delivery_address' => 'Baghdad test address',
                'delivery_city' => 'Baghdad',
                'delivery_phone' => '07700000000',
            ], $attributes));
        };

        $match = $makeOrder([
            'user_id' => $dealer->id,
            'order_number' => 'ORD-EXPORT-MATCH',
            'status' => Order::STATUS_PROCESSING,
        ]);

        $makeOrder([
            'user_id' => $retail->id,
            'order_number' => 'ORD-EXPORT-MATCH-RETAIL',
            'status' => Order::STATUS_PROCESSING,
        ]);

        $makeOrder([
            'user_id' => $dealer->id,
            'order_number' => 'ORD-EXPORT-MATCH-PENDING',
            'status' => Order::STATUS_PENDING,
        ]);

        $makeOrder([
            'user_id' => $dealer->id,
            'order_number' => 'ORD-EXPORT-MATCH-ARCHIVED',
            'status' => Order::STATUS_PROCESSING,
            'archived_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.export-excel', [
                'search' => 'EXPORT-MATCH',
                'status' => Order::STATUS_PROCESSING,
                'association' => 'dealer',
            ]))
            ->assertOk();

        Excel::assertDownloaded('orders.xlsx', function (OrdersExport $export) use ($match) {
            return $export->query()->pluck('order_number')->all() === [$match->order_number];
        });
    }

    public function test_order_details_render_when_optional_payment_table_is_missing(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $customer = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::forceCreate([
            'user_id' => $customer->id,
            'order_number' => 'ORD-WITHOUT-PAYMENTS-TABLE',
            'subtotal_amount' => 50000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 50000,
            'total_amount' => 50000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Baghdad demo address',
            'delivery_city' => 'Baghdad',
            'delivery_phone' => '07700000000',
        ]);

        Schema::dropIfExists('payments');

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('ORD-WITHOUT-PAYMENTS-TABLE');
    }
}
