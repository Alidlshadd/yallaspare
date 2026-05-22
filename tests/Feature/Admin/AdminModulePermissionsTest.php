<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_manager_can_open_products_but_not_finance(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PRODUCT_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.revenue.index'))
            ->assertForbidden();
    }

    public function test_order_manager_can_open_orders_but_not_products(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ORDER_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.orders.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_finance_manager_can_open_coupon_and_discount_rule_pages(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_FINANCE_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.discounts.edit'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.discounts.rules'))
            ->assertOk();
    }

    public function test_custom_permissions_override_role_defaults(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_PRODUCTS_MANAGE],
            'email_verified_at' => now(),
        ]);

        $this->assertTrue($user->hasPermission(User::PERMISSION_DASHBOARD_VIEW));
        $this->assertTrue($user->hasPermission(User::PERMISSION_PRODUCTS_MANAGE));
        $this->assertFalse($user->hasPermission(User::PERMISSION_ORDERS_MANAGE));

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    }
}
