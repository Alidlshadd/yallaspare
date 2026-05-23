<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SqlInjectionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_product_search_treats_sql_payload_as_text(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Brake Pad',
            'sku' => 'SAFE-SKU-1',
            'is_active' => true,
        ]);

        $this->get('/user/shop?q=' . urlencode("' OR 1=1 --"))
            ->assertOk()
            ->assertDontSee('Brake Pad');
    }

    public function test_public_product_search_escapes_like_wildcards(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Brake Pad',
            'sku' => 'SAFE-SKU-2',
            'is_active' => true,
        ]);

        $this->get('/user/shop?q=' . urlencode('%'))
            ->assertOk()
            ->assertDontSee('Brake Pad');
    }

    public function test_admin_product_search_treats_sql_payload_as_text(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PRODUCT_MANAGER,
            'email_verified_at' => now(),
        ]);
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Admin Brake Pad',
            'sku' => 'ADMIN-SAFE-SKU',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['search' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertDontSee('Admin Brake Pad');
    }
}
