<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductDiscountPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1, 'name_en' => 'Brake Parts', 'slug' => 'brake-parts']);
        }
    }

    private function makeProduct(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'price' => 10000,
            'is_active' => true,
        ], $attributes));
    }

    private function makeDiscount(array $attributes = []): Discount
    {
        return Discount::query()->create(array_merge([
            'name' => 'Test discount ' . fake()->unique()->word(),
            'scope' => 'catalog',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ], $attributes));
    }

    public function test_no_discount_returns_base_price(): void
    {
        $product = $this->makeProduct();

        $pricing = $product->pricingFor();

        $this->assertSame(10000.0, $pricing['price']);
        $this->assertSame(10000.0, $pricing['base_price']);
        $this->assertFalse($pricing['has_discount']);
        $this->assertSame([], $pricing['discount_ids']);
    }

    public function test_catalog_percent_discount_applies_to_all_products(): void
    {
        $discount = $this->makeDiscount(['value' => 20]);
        $product = $this->makeProduct();

        $pricing = $product->pricingFor();

        $this->assertSame(8000.0, $pricing['price']);
        $this->assertSame(2000.0, $pricing['discount_amount']);
        $this->assertSame(20, $pricing['discount_percent']);
        $this->assertTrue($pricing['has_discount']);
        $this->assertSame([$discount->id], $pricing['discount_ids']);
    }

    public function test_catalog_fixed_discount_subtracts_amount(): void
    {
        $this->makeDiscount(['type' => 'fixed', 'value' => 1500]);
        $product = $this->makeProduct();

        $this->assertSame(8500.0, $product->pricingFor()['price']);
    }

    public function test_product_scope_discount_applies_only_to_attached_product(): void
    {
        $target = $this->makeProduct();
        $other = $this->makeProduct();
        $discount = $this->makeDiscount(['scope' => 'product', 'value' => 50]);
        $discount->products()->attach($target->id);

        $this->assertSame(5000.0, $target->pricingFor()['price']);
        $this->assertSame(10000.0, $other->pricingFor()['price']);
    }

    public function test_category_scope_discount_applies_only_to_attached_category(): void
    {
        $otherCategory = Category::factory()->create(['slug' => 'other-cat']);
        $inCategory = $this->makeProduct(['category_id' => 1]);
        $outOfCategory = $this->makeProduct(['category_id' => $otherCategory->id]);
        $discount = $this->makeDiscount(['scope' => 'category', 'value' => 25]);
        $discount->categories()->attach(1);

        $this->assertSame(7500.0, $inCategory->pricingFor()['price']);
        $this->assertSame(10000.0, $outOfCategory->pricingFor()['price']);
    }

    public function test_brand_scope_discount_matches_product_brand(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('brand scope enum value only exists on mysql');
        }

        $branded = $this->makeProduct(['brand' => 'Toyota']);
        $unbranded = $this->makeProduct(['brand' => 'Kia']);
        $this->makeDiscount(['scope' => 'brand', 'value' => 30, 'brand_names' => ['Toyota']]);

        $this->assertSame(7000.0, $branded->pricingFor()['price']);
        $this->assertSame(10000.0, $unbranded->pricingFor()['price']);
    }

    public function test_inactive_expired_and_future_discounts_are_ignored(): void
    {
        $this->makeDiscount(['is_active' => false, 'value' => 50]);
        $this->makeDiscount(['value' => 50, 'ends_at' => now()->subDay()]);
        $this->makeDiscount(['value' => 50, 'starts_at' => now()->addDay()]);
        $product = $this->makeProduct();

        $this->assertSame(10000.0, $product->pricingFor()['price']);
    }

    public function test_discount_within_active_window_applies(): void
    {
        $this->makeDiscount([
            'value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        $product = $this->makeProduct();

        $this->assertSame(9000.0, $product->pricingFor()['price']);
    }

    public function test_exhausted_usage_limit_is_ignored(): void
    {
        $this->makeDiscount(['value' => 50, 'usage_limit' => 5, 'used_count' => 5]);
        $product = $this->makeProduct();

        $this->assertSame(10000.0, $product->pricingFor()['price']);
    }

    public function test_remaining_usage_limit_applies(): void
    {
        $this->makeDiscount(['value' => 10, 'usage_limit' => 5, 'used_count' => 4]);
        $product = $this->makeProduct();

        $this->assertSame(9000.0, $product->pricingFor()['price']);
    }

    public function test_minimum_subtotal_gates_discount(): void
    {
        $this->makeDiscount(['value' => 10, 'minimum_subtotal' => 20000]);
        $cheap = $this->makeProduct(['price' => 10000]);
        $expensive = $this->makeProduct(['price' => 25000]);

        $this->assertSame(10000.0, $cheap->pricingFor()['price']);
        $this->assertSame(22500.0, $expensive->pricingFor()['price']);
    }

    public function test_best_discount_wins_when_multiple_apply(): void
    {
        $this->makeDiscount(['value' => 10]);
        $best = $this->makeDiscount(['value' => 30]);
        $product = $this->makeProduct();

        $pricing = $product->pricingFor();

        $this->assertSame(7000.0, $pricing['price']);
        $this->assertSame([$best->id], $pricing['discount_ids']);
    }

    public function test_discount_created_after_a_pricing_call_is_picked_up(): void
    {
        $product = $this->makeProduct();
        $this->assertSame(10000.0, $product->pricingFor()['price']);

        $this->makeDiscount(['value' => 20]);

        $this->assertSame(8000.0, $product->pricingFor()['price']);
    }

    public function test_discount_deactivated_after_a_pricing_call_is_dropped(): void
    {
        $discount = $this->makeDiscount(['value' => 20]);
        $product = $this->makeProduct();
        $this->assertSame(8000.0, $product->pricingFor()['price']);

        $discount->update(['is_active' => false]);

        $this->assertSame(10000.0, $product->pricingFor()['price']);
    }
}
