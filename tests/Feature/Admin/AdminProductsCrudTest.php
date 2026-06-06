<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminProductsCrudTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function createCategory(): Category
    {
        return Category::factory()->create([
            'name_en' => 'Test Category',
            'name_ar' => 'Test Category',
            'name_ku' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    public function test_admin_can_view_products_index(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Image Product',
            'image' => 'products/test-product.jpg',
            'sku' => 'SKU-IMAGE-01',
        ]);

        $response = $this->actingAs($user)->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee('Image');
        $response->assertSee('storage/products/test-product.jpg');
    }

    public function test_products_index_active_filter_shows_only_active_products(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();

        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Visible Active Product',
            'sku' => 'SKU-FILTER-ACTIVE',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Hidden Inactive Product',
            'sku' => 'SKU-FILTER-INACTIVE-HIDDEN',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.products.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee('SKU-FILTER-ACTIVE');
        $response->assertDontSee('SKU-FILTER-INACTIVE-HIDDEN');
    }

    public function test_products_index_inactive_filter_shows_only_inactive_products(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();

        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Hidden Active Product',
            'sku' => 'SKU-FILTER-ACTIVE-HIDDEN',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Visible Inactive Product',
            'sku' => 'SKU-FILTER-INACTIVE',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.products.index', ['status' => 'inactive']));

        $response->assertOk();
        $response->assertSee('SKU-FILTER-INACTIVE');
        $response->assertDontSee('SKU-FILTER-ACTIVE-HIDDEN');
    }

    public function test_products_index_low_stock_filter_excludes_out_of_stock_products(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        \App\Models\Setting::setValue('low_stock_threshold', '5');

        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-FILTER-LOW-STOCK',
            'stock_quantity' => 3,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-FILTER-OUT-HIDDEN',
            'stock_quantity' => 0,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-FILTER-STOCKED-HIDDEN',
            'stock_quantity' => 8,
        ]);

        $response = $this->actingAs($user)->get(route('admin.products.index', ['status' => 'low_stock']));

        $response->assertOk();
        $response->assertSee('SKU-FILTER-LOW-STOCK');
        $response->assertDontSee('SKU-FILTER-OUT-HIDDEN');
        $response->assertDontSee('SKU-FILTER-STOCKED-HIDDEN');
    }

    public function test_products_index_out_of_stock_filter_shows_only_zero_stock_products(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();

        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-FILTER-OUT-STOCK',
            'stock_quantity' => 0,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-FILTER-LOW-HIDDEN',
            'stock_quantity' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('admin.products.index', ['status' => 'out_of_stock']));

        $response->assertOk();
        $response->assertSee('SKU-FILTER-OUT-STOCK');
        $response->assertDontSee('SKU-FILTER-LOW-HIDDEN');
    }

    public function test_products_index_pagination_preserves_status_filter(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();

        for ($i = 1; $i <= 12; $i++) {
            Product::factory()->create([
                'category_id' => $category->id,
                'sku' => sprintf('SKU-INACTIVE-PAGE-%02d', $i),
                'is_active' => false,
            ]);
        }

        $response = $this->actingAs($user)->get(route('admin.products.index', ['status' => 'inactive']));

        $response->assertOk();
        $this->assertStringContainsString('status=inactive', $response->getContent());
        $this->assertStringContainsString('page=2', $response->getContent());
    }

    public function test_products_index_search_and_brand_work_with_status_filter(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();

        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Brake Filter Match',
            'sku' => 'SKU-SEARCH-INACTIVE-MATCH',
            'brand' => 'Bosch',
            'is_active' => false,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Brake Filter Active',
            'sku' => 'SKU-SEARCH-ACTIVE-HIDDEN',
            'brand' => 'Bosch',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Brake Filter Wrong Brand',
            'sku' => 'SKU-SEARCH-BRAND-HIDDEN',
            'brand' => 'Denso',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.products.index', [
            'status' => 'inactive',
            'search' => 'brake',
            'brand' => 'Bosch',
        ]));

        $response->assertOk();
        $response->assertSee('SKU-SEARCH-INACTIVE-MATCH');
        $response->assertDontSee('SKU-SEARCH-ACTIVE-HIDDEN');
        $response->assertDontSee('SKU-SEARCH-BRAND-HIDDEN');
    }

    public function test_products_index_inactive_empty_state_is_specific(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.products.index', ['status' => 'inactive']));

        $response->assertOk();
        $response->assertSee('No inactive products found.');
    }

    public function test_products_index_uses_stable_pagination_order(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        $createdAt = now()->subDay();

        for ($i = 1; $i <= 12; $i++) {
            Product::factory()->create([
                'category_id' => $category->id,
                'name_en' => sprintf('Stable Product %02d', $i),
                'sku' => sprintf('SKU-STABLE-%02d', $i),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $pageOne = $this->actingAs($user)->get(route('admin.products.index'));
        $pageTwo = $this->actingAs($user)->get(route('admin.products.index', ['page' => 2]));

        $pageOne->assertOk();
        $pageOne->assertSeeInOrder([
            'SKU-STABLE-12',
            'SKU-STABLE-11',
            'SKU-STABLE-10',
            'SKU-STABLE-09',
            'SKU-STABLE-08',
            'SKU-STABLE-07',
            'SKU-STABLE-06',
            'SKU-STABLE-05',
            'SKU-STABLE-04',
            'SKU-STABLE-03',
        ]);
        $pageOne->assertDontSee('SKU-STABLE-02');
        $pageOne->assertDontSee('SKU-STABLE-01');

        $pageTwo->assertOk();
        $pageTwo->assertSeeInOrder([
            'SKU-STABLE-02',
            'SKU-STABLE-01',
        ]);
        $pageTwo->assertDontSee('SKU-STABLE-12');
    }

    public function test_admin_can_create_product(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();

        $payload = [
            'name_en' => 'Oil Filter',
            'name_ar' => 'Oil Filter',
            'name_ku' => 'Oil Filter',
            'description_en' => 'Test description',
            'price' => 15000,
            'dealer_price' => 12000,
            'stock_quantity' => 20,
            'sku' => 'SKU-TEST-01',
            'brand' => 'Bosch',
            'category_id' => $category->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->post(route('admin.products.store'), $payload);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'name_en' => 'Oil Filter',
            'sku' => 'SKU-TEST-01',
            'category_id' => $category->id,
        ]);
    }

    public function test_admin_created_product_with_stock_20_shows_20_on_index(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();

        $payload = [
            'name_en' => 'Exact Stock Product',
            'name_ar' => 'Exact Stock Product',
            'name_ku' => 'Exact Stock Product',
            'description_en' => 'Test description',
            'price' => 15000,
            'dealer_price' => 12000,
            'stock_quantity' => 20,
            'sku' => 'SKU-EXACT-STOCK-20',
            'brand' => 'Bosch',
            'category_id' => $category->id,
            'is_active' => true,
        ];

        $this->actingAs($user)->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'SKU-EXACT-STOCK-20')->firstOrFail();
        $this->assertSame(20, (int) $product->stock_quantity);
        $this->assertSame(0, $product->inventoryMovements()->count());
        $this->assertSame(0, $product->orderItems()->count());

        $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => 'SKU-EXACT-STOCK-20']))
            ->assertOk()
            ->assertSee('20 units');
    }

    public function test_admin_imported_product_with_stock_20_shows_20_on_index(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        $csv = implode("\n", [
            'name_en,name_ar,name_ku,price,stock_quantity,sku,brand,category_id,is_active',
            'Imported Stock Product,Imported Stock Product,Imported Stock Product,15000,20,SKU-IMPORT-STOCK-20,Bosch,' . $category->id . ',1',
        ]);

        $this->actingAs($user)->post(route('admin.products.import'), [
            'import_file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'SKU-IMPORT-STOCK-20')->firstOrFail();
        $this->assertSame(20, (int) $product->stock_quantity);
        $this->assertSame(0, $product->inventoryMovements()->count());
        $this->assertSame(0, $product->orderItems()->count());

        $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => 'SKU-IMPORT-STOCK-20']))
            ->assertOk()
            ->assertSee('20 units');
    }

    public function test_products_index_renders_long_table_content(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Very Long Product Name For Admin Table Balance With Several Descriptive Words',
            'sku' => 'SKU-LONG-CONTENT-1234567890-ABCDEFG',
            'brand' => 'Very Long Brand Name That Should Truncate Cleanly',
            'price' => 123456789,
            'dealer_price' => 98765432,
            'stock_quantity' => 20,
        ]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => 'SKU-LONG-CONTENT']))
            ->assertOk()
            ->assertSee('admin-products-table')
            ->assertSee('product-name-clamp')
            ->assertSee('20 units');
    }

    public function test_admin_create_returns_to_original_products_filter(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();

        $payload = [
            'name_en' => 'Active Oil Filter',
            'name_ar' => 'Active Oil Filter',
            'name_ku' => 'Active Oil Filter',
            'description_en' => 'Test description',
            'price' => 15000,
            'dealer_price' => 12000,
            'stock_quantity' => 20,
            'sku' => 'SKU-TEST-RETURN-CREATE',
            'brand' => 'Bosch',
            'category_id' => $category->id,
            'is_active' => true,
            'return_to' => route('admin.products.index', ['status' => 'active']),
        ];

        $response = $this->actingAs($user)->post(route('admin.products.store'), $payload);

        $response->assertRedirect('/admin/products?status=active');
    }

    public function test_admin_can_update_product(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-TEST-02',
        ]);

        $payload = [
            'name_en' => 'Brake Pads',
            'name_ar' => 'Brake Pads',
            'name_ku' => 'Brake Pads',
            'description_en' => 'Updated description',
            'price' => 22000,
            'dealer_price' => 18000,
            'stock_quantity' => 15,
            'sku' => 'SKU-TEST-02',
            'brand' => 'Brembo',
            'category_id' => $category->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->put(route('admin.products.update', $product), $payload);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name_en' => 'Brake Pads',
            'brand' => 'Brembo',
        ]);
    }

    public function test_admin_returns_to_original_products_page_after_update(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-TEST-RETURN',
        ]);

        $payload = [
            'name_en' => 'Brake Pads',
            'name_ar' => 'Brake Pads',
            'name_ku' => 'Brake Pads',
            'description_en' => 'Updated description',
            'price' => 22000,
            'dealer_price' => 18000,
            'stock_quantity' => 15,
            'sku' => 'SKU-TEST-RETURN',
            'brand' => 'Brembo',
            'category_id' => $category->id,
            'is_active' => true,
            'return_to' => route('admin.products.index', [
                'page' => 4,
                'search' => 'brake',
                'sort' => 'name_en',
                'direction' => 'asc',
            ]),
        ];

        $response = $this->actingAs($user)->put(route('admin.products.update', $product), $payload);

        $response->assertRedirect('/admin/products?page=4&search=brake&sort=name_en&direction=asc');
    }

    public function test_admin_product_update_ignores_unsafe_return_url(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-TEST-UNSAFE',
        ]);

        $payload = [
            'name_en' => 'Brake Pads',
            'name_ar' => 'Brake Pads',
            'name_ku' => 'Brake Pads',
            'description_en' => 'Updated description',
            'price' => 22000,
            'dealer_price' => 18000,
            'stock_quantity' => 15,
            'sku' => 'SKU-TEST-UNSAFE',
            'brand' => 'Brembo',
            'category_id' => $category->id,
            'is_active' => true,
            'return_to' => 'https://example.com/phishing',
        ];

        $response = $this->actingAs($user)->put(route('admin.products.update', $product), $payload);

        $response->assertRedirect(route('admin.products.index'));
    }

    public function test_admin_can_delete_product(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-TEST-03',
        ]);

        $response = $this->actingAs($user)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_admin_delete_returns_to_original_products_filter(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-TEST-RETURN-DELETE',
        ]);

        $response = $this->actingAs($user)->delete(route('admin.products.destroy', $product), [
            'return_to' => route('admin.products.index', ['status' => 'active']),
        ]);

        $response->assertRedirect('/admin/products?status=active');
    }

    public function test_admin_archives_product_when_it_has_order_history(): void
    {
        $user = $this->adminUser();
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-TEST-04',
            'is_active' => true,
        ]);
        $customer = User::factory()->create();
        $order = Order::forceCreate([
            'user_id' => $customer->id,
            'order_number' => 'ORD-TEST-ARCHIVE',
            'total_amount' => 100,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash_on_delivery',
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

        $response = $this->actingAs($user)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product is linked to existing orders, so it was archived instead of deleted.');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_admin_can_view_reviews_module(): void
    {
        $admin = $this->adminUser();
        $customer = User::factory()->create(['name' => 'Review Customer']);
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-REVIEWS-MODULE',
            'name_en' => 'Module Review Product',
        ]);

        ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 4,
            'title' => 'Module visible review',
            'comment' => 'This review appears in the admin reviews module.',
            'is_approved' => true,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reviews.index'));

        $response->assertOk();
        $response->assertSee('Customer Reviews');
        $response->assertSee('Module visible review');
        $response->assertSee('Module Review Product');
        $response->assertSee('Review Customer');
    }

    public function test_admin_can_delete_review_from_reviews_module(): void
    {
        $admin = $this->adminUser();
        $customer = User::factory()->create();
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-REVIEWS-MODULE-DELETE',
        ]);

        $review = ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 1,
            'title' => 'Module delete me',
            'comment' => 'This review will be deleted from the module.',
            'is_approved' => true,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.reviews.destroy', $review));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Review deleted successfully.');
        $this->assertDatabaseMissing('product_reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_super_admin_can_view_user_reviews_on_user_details(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $customer = User::factory()->create([
            'name' => 'Reviewed User',
            'email' => 'reviewed-user@example.com',
        ]);
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-USER-REVIEW',
            'name_en' => 'User Detail Review Product',
            'image' => 'products/user-review-product.jpg',
        ]);

        ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'title' => 'User detail visible review',
            'comment' => 'This review appears on the customer detail page.',
            'is_approved' => true,
            'reviewed_at' => now(),
        ]);

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.users.show', $customer));

        $response->assertOk();
        $response->assertSee('Customer Reviews');
        $response->assertSee('User detail visible review');
        $response->assertSee('This review appears on the customer detail page.');
        $response->assertSee('User Detail Review Product');
        $response->assertSee('SKU-USER-REVIEW');
        $response->assertSee('storage/products/user-review-product.jpg');
    }

    public function test_non_admin_cannot_access_products(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.products.index'));

        $response->assertForbidden();
    }
}
