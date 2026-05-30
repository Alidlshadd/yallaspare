<?php

namespace Tests\Feature;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HeaderComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dropdown_categories_returns_mapped_collection_for_locale(): void
    {
        Category::factory()->create(['name_en' => 'Brakes', 'name_ar' => 'فرامل', 'name_ku' => 'بڕەک', 'slug' => 'brakes']);
        Category::factory()->create(['name_en' => 'Engines', 'name_ar' => 'محركات', 'name_ku' => 'بزوێنەر', 'slug' => 'engines']);

        $result = (new HeaderComposer())->dropdownCategories('en');

        $this->assertCount(2, $result);
        $this->assertSame('Brakes', $result->first()['label']);
        $this->assertArrayHasKey('url', $result->first());
        $this->assertArrayHasKey('image', $result->first());
    }

    public function test_dropdown_categories_pick_arabic_name_for_ar_locale(): void
    {
        Category::factory()->create(['name_en' => 'Brakes', 'name_ar' => 'فرامل', 'name_ku' => 'بڕەک', 'slug' => 'brakes']);

        $result = (new HeaderComposer())->dropdownCategories('ar');

        $this->assertSame('فرامل', $result->first()['label']);
    }

    public function test_dropdown_categories_are_cached_per_locale(): void
    {
        Category::factory()->count(3)->create();
        Cache::flush();
        DB::enableQueryLog();

        $first = (new HeaderComposer())->dropdownCategories('en');
        $second = (new HeaderComposer())->dropdownCategories('en');

        $categoryQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains(strtolower($q['query']), 'from "categories"'))
            ->count();

        $this->assertSame(1, $categoryQueries, 'Categories should be queried only once across two compose calls');
        $this->assertEquals($first->toArray(), $second->toArray());
    }

    public function test_cart_count_for_guest_is_zero(): void
    {
        $this->assertSame(0, (new HeaderComposer())->cartCountFor(null));
    }

    public function test_cart_count_for_authenticated_user_sums_item_quantities(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $productA->id, 'quantity' => 2]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $productB->id, 'quantity' => 3]);

        $this->assertSame(5, (new HeaderComposer())->cartCountFor($user));
    }

    public function test_cart_for_authenticated_user_eager_loads_items(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $headerCart = (new HeaderComposer())->cartFor($user);

        $this->assertNotNull($headerCart);
        $this->assertTrue($headerCart->relationLoaded('items'));
        $this->assertTrue($headerCart->items->first()->relationLoaded('product'));
    }

    public function test_wishlist_count_for_guest_is_zero(): void
    {
        $this->assertSame(0, (new HeaderComposer())->wishlistCountFor(null));
    }

    public function test_wishlist_count_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();
        $a = Product::factory()->create();
        $b = Product::factory()->create();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $a->id]);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $b->id]);

        $this->assertSame(2, (new HeaderComposer())->wishlistCountFor($user));
    }

    public function test_compose_shares_expected_variables_on_view(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();
        $this->actingAs($user);

        $view = view('welcome');
        (new HeaderComposer())->compose($view);
        $data = $view->getData();

        $this->assertArrayHasKey('headerCart', $data);
        $this->assertArrayHasKey('headerCartCount', $data);
        $this->assertArrayHasKey('headerWishlistCount', $data);
        $this->assertArrayHasKey('dropdownCategories', $data);
    }

    public function test_category_cache_invalidates_when_category_saved(): void
    {
        Cache::flush();
        Category::factory()->create(['name_en' => 'First', 'slug' => 'first']);
        $before = (new HeaderComposer())->dropdownCategories('en');
        $this->assertCount(1, $before);

        Category::factory()->create(['name_en' => 'Second', 'slug' => 'second']);
        $after = (new HeaderComposer())->dropdownCategories('en');

        $this->assertCount(2, $after, 'Cache must invalidate when a Category is saved');
    }
}
