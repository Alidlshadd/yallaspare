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

        Sanctum::actingAs($user, ['admin:mobile']);

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

        Sanctum::actingAs($user, ['admin:mobile']);

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

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/users/{$target->id}/role", [
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertForbidden();

        $this->assertSame(User::ROLE_USER, $target->fresh()->role);
    }

    public function test_mobile_dealer_manager_cannot_modify_super_admin(): void
    {
        $admin = $this->dealerManager();
        $target = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/dealers/{$target->id}", $this->dealerPayload())
            ->assertForbidden();

        $this->assertSame(User::ROLE_SUPER_ADMIN, $target->fresh()->role);
    }

    public function test_mobile_dealer_manager_cannot_modify_admin(): void
    {
        $admin = $this->dealerManager();
        $target = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/dealers/{$target->id}", $this->dealerPayload())
            ->assertForbidden();

        $this->assertSame(User::ROLE_ADMIN, $target->fresh()->role);
    }

    public function test_mobile_dealer_manager_cannot_modify_finance_manager(): void
    {
        $admin = $this->dealerManager();
        $target = User::factory()->create(['role' => User::ROLE_FINANCE_MANAGER]);

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/dealers/{$target->id}", $this->dealerPayload())
            ->assertForbidden();

        $this->assertSame(User::ROLE_FINANCE_MANAGER, $target->fresh()->role);
    }

    public function test_mobile_dealer_manager_cannot_modify_self(): void
    {
        $admin = $this->dealerManager();

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/dealers/{$admin->id}", $this->dealerPayload())
            ->assertForbidden();

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_mobile_super_admin_can_manage_dealer_lifecycle(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/dealers/{$target->id}", [
            'dealer_status' => User::DEALER_STATUS_ACTIVE,
            'dealer_discount' => 12.5,
        ])->assertOk();

        $target->refresh();
        $this->assertSame(User::ROLE_DEALER, $target->role);
        $this->assertSame(User::DEALER_STATUS_ACTIVE, $target->dealer_status);
        $this->assertSame('12.50', (string) $target->dealer_discount);
    }

    private function dealerManager(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_DEALERS_MANAGE],
            'email_verified_at' => now(),
        ]);
    }

    private function dealerPayload(): array
    {
        return [
            'dealer_status' => User::DEALER_STATUS_ACTIVE,
            'dealer_discount' => 100,
        ];
    }
}
