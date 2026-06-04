<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileProductReviewSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_cannot_review_product_never_purchased(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Fake review',
        ])->assertForbidden();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_mobile_user_cannot_review_unpaid_order_product(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        $this->orderFor($user, $product, Order::STATUS_DELIVERED, Order::PAYMENT_PENDING);
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Not paid yet',
        ])->assertForbidden();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_mobile_user_cannot_review_undelivered_order_product(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        $this->orderFor($user, $product, Order::STATUS_PROCESSING, Order::PAYMENT_PAID);
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Not delivered yet',
        ])->assertForbidden();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_mobile_user_can_review_paid_delivered_purchased_product(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        $this->orderFor($user, $product, Order::STATUS_DELIVERED, Order::PAYMENT_PAID);
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/products/{$product->id}/reviews", [
            'rating' => 4,
            'comment' => 'Fits correctly',
        ])->assertCreated();

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 4,
            'comment' => 'Fits correctly',
        ]);
    }

    public function test_mobile_user_cannot_review_same_product_twice(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        $this->orderFor($user, $product, Order::STATUS_DELIVERED, Order::PAYMENT_PAID);
        ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Original',
            'is_approved' => true,
            'reviewed_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/products/{$product->id}/reviews", [
            'rating' => 1,
            'comment' => 'Duplicate',
        ])->assertStatus(422);

        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Original',
        ]);
    }

    private function product(): Product
    {
        return Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'is_active' => true,
            'price' => 10000,
        ]);
    }

    private function orderFor(User $user, Product $product, string $status, string $paymentStatus): Order
    {
        $order = Order::query()->forceCreate([
            'user_id' => $user->id,
            'order_number' => 'ORD-MOBILE-REVIEW-' . uniqid(),
            'subtotal_amount' => 10000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 10000,
            'total_amount' => 10000,
            'status' => $status,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => $paymentStatus,
            'delivery_address' => 'Street',
            'delivery_city' => 'City',
            'delivery_phone' => '123456789',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        return $order;
    }
}
