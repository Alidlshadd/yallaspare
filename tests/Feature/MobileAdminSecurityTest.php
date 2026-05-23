<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_admin_product_update_requires_product_permission(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 2,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_ORDER_MANAGER,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/admin/products/{$product->id}", [
            'stock_quantity' => 8,
        ])->assertForbidden();

        $this->assertSame(2, $product->fresh()->stock_quantity);
    }

    public function test_mobile_product_manager_can_update_product_stock(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 2,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_PRODUCT_MANAGER,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/admin/products/{$product->id}", [
            'stock_quantity' => 8,
        ])->assertOk();

        $this->assertSame(8, $product->fresh()->stock_quantity);
    }

    public function test_mobile_admin_cannot_promote_super_admin_without_super_admin_role(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_USERS_MANAGE],
            'email_verified_at' => now(),
        ]);
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/mobile/admin/users/{$target->id}/role", [
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertForbidden();

        $this->assertSame(User::ROLE_USER, $target->fresh()->role);
    }
}
