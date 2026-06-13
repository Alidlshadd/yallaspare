<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoadingUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_targeted_loading_button_state(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-auth-form', false)
            ->assertSee('data-loading-button-text', false)
            ->assertSee('Signing in...', false)
            ->assertSee('data-loading-overlay', false);
    }

    public function test_checkout_submit_has_secure_loading_state(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Iraq',
            'phone' => '+964 770 000 0000',
            'city' => 'Erbil',
            'address_line1' => '100 Test Street',
            'is_default' => true,
        ]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 5,
            'price' => 10000,
            'is_active' => true,
        ]);
        $cart = Cart::query()->create(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->withSession([
                'checkout.review' => [
                    'address_id' => $address->id,
                    'notes' => 'Call first',
                ],
            ])
            ->get(route('checkout.review'))
            ->assertOk()
            ->assertSee('data-loading-kind="checkout"', false)
            ->assertSee('Placing your order securely...', false)
            ->assertSee('Placing order...', false);
    }

    public function test_admin_settings_form_has_targeted_loading_state(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('data-admin-settings-form', false)
            ->assertSee('data-loading-form', false)
            ->assertSee('data-loading-button-text', false);
    }
}
