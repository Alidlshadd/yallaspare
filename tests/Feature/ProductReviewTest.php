<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::factory()->create([
            'name_en' => 'Review Category',
            'name_ar' => 'Review Category',
            'name_ku' => 'Review Category',
            'slug' => 'review-category',
        ]);

        return Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Review Product',
            'sku' => 'SKU-REVIEW-01',
            'is_active' => true,
        ]);
    }

    private function deliveredOrderFor(User $user, Product $product): Order
    {
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'order_number' => 'ORD-REVIEW-' . $user->id . '-' . $product->id,
            'total_amount' => 100,
            'status' => Order::STATUS_DELIVERED,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PAID,
            'delivery_address' => 'Street 10',
            'delivery_city' => 'Baghdad',
            'delivery_phone' => '123456789',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);

        return $order;
    }

    public function test_customer_cannot_review_before_delivered_order(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();

        $response = $this->actingAs($user)->post(route('shop.reviews.store', $product), [
            'rating' => 5,
            'title' => 'Good',
            'comment' => 'Good product',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('review_error');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_customer_can_review_delivered_product_once(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        $this->deliveredOrderFor($user, $product);

        $response = $this->actingAs($user)->post(route('shop.reviews.store', $product), [
            'rating' => 4,
            'title' => 'Fits well',
            'comment' => 'The product arrived and fits correctly.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('review_status', 'Thank you. Your review has been saved.');
        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 4,
            'title' => 'Fits well',
        ]);
    }

    public function test_delivered_order_detail_shows_review_form_for_ordered_product(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        $order = $this->deliveredOrderFor($user, $product);

        $response = $this->actingAs($user)->get(route('account.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Rate Ordered Items');
        $response->assertSee('Submit Review');
        $response->assertSee($product->name_en);
    }

    public function test_product_detail_shows_reviews_but_not_review_form(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        $this->deliveredOrderFor($user, $product);

        ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Visible review',
            'comment' => 'This review should appear on the product page.',
            'is_approved' => true,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('Customer Reviews');
        $response->assertSee('Visible review');
        $response->assertSee('This review should appear on the product page.');
        $response->assertDontSee('Submit Review');
        $response->assertDontSee('Write a Review');
    }

    public function test_customer_cannot_submit_second_review_for_same_product(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = $this->product();
        $this->deliveredOrderFor($user, $product);

        ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Original review',
            'comment' => 'Original comment',
            'is_approved' => true,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('shop.reviews.store', $product), [
            'rating' => 1,
            'title' => 'Second review',
            'comment' => 'This should not replace the first review.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('review_error', 'You have already reviewed this product.');
        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Original review',
        ]);
    }
}
