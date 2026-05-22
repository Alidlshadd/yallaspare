<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $order = Order::query()->create([
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
}
