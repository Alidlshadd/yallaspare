<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDeadStockTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function makeDeliveredOrder(Product $product, \DateTimeInterface $createdAt): Order
    {
        $customer = User::factory()->create(['email_verified_at' => now()]);

        $order = Order::forceCreate([
            'user_id' => $customer->id,
            'order_number' => 'ORD-DEAD-' . uniqid(),
            'subtotal_amount' => 25000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 25000,
            'total_amount' => 25000,
            'status' => Order::STATUS_DELIVERED,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Baghdad demo address',
            'delivery_city' => 'Baghdad',
            'delivery_phone' => '07700000000',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 25000,
            'subtotal' => 25000,
        ]);

        return $order;
    }

    public function test_dead_stock_page_lists_idle_and_never_sold_products(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Never Sold Radiator',
            'stock_quantity' => 10,
        ]);

        $idleProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Idle Brake Pad',
            'stock_quantity' => 5,
        ]);
        $this->makeDeliveredOrder($idleProduct, now()->subDays(120));

        $freshProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Fresh Alternator',
            'stock_quantity' => 8,
        ]);
        $this->makeDeliveredOrder($freshProduct, now()->subDays(10));

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.dead-stock.index'));

        $response->assertOk();
        $response->assertSee('Never Sold Radiator');
        $response->assertSee('Idle Brake Pad');
        $response->assertDontSee('Fresh Alternator');
        $response->assertSee('Never sold only');
    }

    public function test_never_sold_filter_hides_products_with_old_sales(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Never Sold Radiator',
            'stock_quantity' => 10,
        ]);

        $idleProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Idle Brake Pad',
            'stock_quantity' => 5,
        ]);
        $this->makeDeliveredOrder($idleProduct, now()->subDays(120));

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.dead-stock.index', ['never_sold' => 1]));

        $response->assertOk();
        $response->assertSee('Never Sold Radiator');
        $response->assertDontSee('Idle Brake Pad');
    }
}
