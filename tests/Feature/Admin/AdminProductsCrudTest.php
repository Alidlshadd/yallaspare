<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->actingAs($user)->get(route('admin.products.index'));

        $response->assertOk();
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

    public function test_non_admin_cannot_access_products(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.products.index'));

        $response->assertForbidden();
    }
}
