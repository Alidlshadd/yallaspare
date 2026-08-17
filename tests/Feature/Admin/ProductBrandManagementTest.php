<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductBrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_brand_with_a_logo(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.product-brands.store'), [
            'name' => 'Bosch',
            'logo' => UploadedFile::fake()->image('bosch.png', 240, 120),
        ]);

        $response->assertRedirect(route('admin.product-brands.index'));
        $brand = ProductBrand::query()->where('name', 'Bosch')->firstOrFail();
        $this->assertSame('bosch', $brand->slug);
        $this->assertNotNull($brand->logo_path);
        Storage::disk('public')->assertExists($brand->logo_path);
    }

    public function test_product_form_assigns_the_selected_brand_and_brand_filter_separates_products(): void
    {
        $admin = $this->adminUser();
        $category = $this->category();
        $bosch = ProductBrand::query()->create(['name' => 'Bosch', 'slug' => 'bosch']);
        $denso = ProductBrand::query()->create(['name' => 'Denso', 'slug' => 'denso']);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name_en' => 'Bosch Oil Filter',
            'name_ar' => 'Bosch Oil Filter',
            'name_ku' => 'Bosch Oil Filter',
            'price' => 15000,
            'stock_quantity' => 12,
            'sku' => 'BRAND-ASSIGN-1',
            'product_brand_id' => $bosch->id,
            'category_id' => $category->id,
            'is_active' => true,
        ])->assertRedirect(route('admin.products.index'));

        Product::factory()->create([
            'category_id' => $category->id,
            'product_brand_id' => $denso->id,
            'brand' => 'Denso',
            'name_en' => 'Denso Fuel Pump',
            'sku' => 'BRAND-ASSIGN-2',
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'BRAND-ASSIGN-1',
            'product_brand_id' => $bosch->id,
            'brand' => 'Bosch',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['product_brand_id' => $bosch->id]))
            ->assertOk()
            ->assertSee('BRAND-ASSIGN-1')
            ->assertDontSee('BRAND-ASSIGN-2');
    }

    public function test_renaming_a_brand_updates_its_assigned_product_names(): void
    {
        $admin = $this->adminUser();
        $category = $this->category();
        $brand = ProductBrand::query()->create(['name' => 'Old Brand', 'slug' => 'old-brand']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_brand_id' => $brand->id,
            'brand' => 'Old Brand',
        ]);

        $this->actingAs($admin)->put(route('admin.product-brands.update', $brand), [
            'name' => 'New Brand',
        ])->assertRedirect(route('admin.product-brands.index'));

        $this->assertDatabaseHas('product_brands', ['id' => $brand->id, 'name' => 'New Brand', 'slug' => 'new-brand']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'brand' => 'New Brand']);
    }

    public function test_brand_with_products_cannot_be_deleted(): void
    {
        $admin = $this->adminUser();
        $category = $this->category();
        $brand = ProductBrand::query()->create(['name' => 'Brembo', 'slug' => 'brembo']);
        Product::factory()->create(['category_id' => $category->id, 'product_brand_id' => $brand->id, 'brand' => 'Brembo']);

        $this->actingAs($admin)
            ->delete(route('admin.product-brands.destroy', $brand))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('product_brands', ['id' => $brand->id, 'deleted_at' => null]);
    }

    public function test_non_admin_cannot_manage_product_brands(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('admin.product-brands.index'))
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function category(): Category
    {
        return Category::factory()->create([
            'name_en' => 'Test Category',
            'name_ar' => 'Test Category',
            'name_ku' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }
}
