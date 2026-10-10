<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Orders\OrderStatusService;
use App\Services\Payments\Providers\ZainCashPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * One test per finding of the October 2026 application security audit. Each
 * reproduces the fault as it was and fails without its fix.
 */
class SecurityAudit202610Test extends TestCase
{
    use RefreshDatabase;

    // ── Buy-now placed one order per click ──

    public function test_the_same_buy_now_form_sent_twice_places_one_order(): void
    {
        [$user, $address, $product] = $this->shopper();
        $form = ['quantity' => 2, 'address_id' => $address->id, 'submission_token' => 'a1b2c3d4-0000-4000-8000-000000000001'];

        $first = $this->actingAs($user)->post(route('checkout.buy-now.place', $product), $form);
        $order = Order::query()->sole();
        $first->assertRedirect(route('checkout.success', $order));

        $second = $this->actingAs($user)->post(route('checkout.buy-now.place', $product), $form);

        $second->assertRedirect(route('checkout.success', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('product_id', $product->id)->count());
    }

    public function test_a_copy_that_arrives_while_the_first_is_still_being_placed_orders_nothing(): void
    {
        [$user, $address, $product] = $this->shopper();
        $token = 'a1b2c3d4-0000-4000-8000-000000000002';
        Cache::put('buy-now-submission:'.$user->id.':'.sha1($token), 'placing', now()->addMinutes(5));

        $this->actingAs($user)
            ->post(route('checkout.buy-now.place', $product), ['quantity' => 1, 'address_id' => $address->id, 'submission_token' => $token])
            ->assertRedirect(route('account.orders.index'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_buy_now_form_that_failed_can_be_sent_again(): void
    {
        [$user, $address, $product] = $this->shopper();
        $form = ['quantity' => 9, 'address_id' => $address->id, 'submission_token' => 'a1b2c3d4-0000-4000-8000-000000000003'];

        // Nine of a part there are five of: refused, and nothing is claimed.
        $this->actingAs($user)->post(route('checkout.buy-now.place', $product), $form)->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($user)->post(route('checkout.buy-now.place', $product), ['quantity' => 1] + $form);

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(4, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_different_form_is_a_different_order(): void
    {
        [$user, $address, $product] = $this->shopper();

        foreach (['a1b2c3d4-0000-4000-8000-00000000000a', 'a1b2c3d4-0000-4000-8000-00000000000b'] as $token) {
            $this->actingAs($user)->post(route('checkout.buy-now.place', $product), [
                'quantity' => 1, 'address_id' => $address->id, 'submission_token' => $token,
            ]);
        }

        $this->assertDatabaseCount('orders', 2);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
    }

    public function test_one_customers_token_cannot_reach_anothers_order(): void
    {
        [$user, $address, $product] = $this->shopper();
        [$other, $otherAddress] = $this->shopper();
        $token = 'a1b2c3d4-0000-4000-8000-00000000000c';

        $this->actingAs($user)->post(route('checkout.buy-now.place', $product), ['quantity' => 1, 'address_id' => $address->id, 'submission_token' => $token]);
        $this->actingAs($other)->post(route('checkout.buy-now.place', $product), ['quantity' => 1, 'address_id' => $otherAddress->id, 'submission_token' => $token]);

        // Same token, two people: two orders, each their own.
        $this->assertSame(1, Order::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, Order::query()->where('user_id', $other->id)->count());
    }

    // ── Saving a product put sold stock back ──

    public function test_saving_a_product_does_not_undo_a_sale_made_while_the_form_was_open(): void
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'stock_quantity' => 10, 'sku' => 'AUD-STOCK']);
        $form = $this->productForm($product, ['stock_quantity' => 10, 'stock_quantity_seen' => 10]);

        // Two are sold after the admin opened the page.
        $product->update(['stock_quantity' => 8]);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $form)->assertSessionHasNoErrors();

        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_stock_change_typed_in_the_form_is_applied_to_the_current_count(): void
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'stock_quantity' => 10, 'sku' => 'AUD-STOCK2']);
        // The admin saw 10 and typed 15: five were received.
        $form = $this->productForm($product, ['stock_quantity' => 15, 'stock_quantity_seen' => 10]);
        $product->update(['stock_quantity' => 8]);

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $form)->assertSessionHasNoErrors();

        $this->assertSame(13, (int) $product->fresh()->stock_quantity);
    }

    public function test_stock_never_goes_below_zero_and_a_plain_request_is_taken_at_its_word(): void
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'stock_quantity' => 10, 'sku' => 'AUD-STOCK3']);
        $admin = $this->admin();

        // Saw 10, typed 0, but 9 had already gone: not minus nine.
        $form = $this->productForm($product, ['stock_quantity' => 0, 'stock_quantity_seen' => 10]);
        $product->update(['stock_quantity' => 1]);
        $this->actingAs($admin)->put(route('admin.products.update', $product), $form)->assertSessionHasNoErrors();
        $this->assertSame(0, (int) $product->fresh()->stock_quantity);

        // No "seen" figure: the typed number is the stock.
        $this->actingAs($admin)->put(route('admin.products.update', $product->fresh()), $this->productForm($product->fresh(), ['stock_quantity' => 7]))->assertSessionHasNoErrors();
        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
    }

    // ── An older "canceled" order could be cancelled, and restocked, again ──

    public function test_an_order_stored_as_canceled_is_not_restocked_when_cancelled_again(): void
    {
        [$user, , $product] = $this->shopper();
        $order = new Order;
        $order->forceFill([
            'user_id' => $user->id, 'order_number' => 'ORD-LEGACY-1', 'status' => 'canceled',
            'total_amount' => 50000, 'grand_total' => 50000, 'subtotal_amount' => 50000,
            'delivery_address' => 'Street 10', 'delivery_city' => 'Baghdad', 'delivery_phone' => '123456789',
        ])->save();
        OrderItem::query()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 25000, 'subtotal' => 50000]);

        $this->assertSame(Order::STATUS_CANCELLED, Order::normalizedStatus('canceled'));

        app(OrderStatusService::class)->changeStatus($order, Order::STATUS_CANCELLED, $this->admin());

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->where('product_id', $product->id)->count());
    }

    public function test_cancelling_a_pending_order_still_returns_its_stock_once(): void
    {
        [$user, , $product] = $this->shopper();
        $order = new Order;
        $order->forceFill([
            'user_id' => $user->id, 'order_number' => 'ORD-PENDING-1', 'status' => Order::STATUS_PENDING,
            'total_amount' => 50000, 'grand_total' => 50000, 'subtotal_amount' => 50000,
            'delivery_address' => 'Street 10', 'delivery_city' => 'Baghdad', 'delivery_phone' => '123456789',
        ])->save();
        OrderItem::query()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 25000, 'subtotal' => 50000]);
        $statuses = app(OrderStatusService::class);
        $admin = $this->admin();

        $statuses->changeStatus($order, Order::STATUS_CANCELLED, $admin);
        $statuses->changeStatus($order->fresh(), Order::STATUS_CANCELLED, $admin);

        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
    }

    // ── ZainCash: "the lookup succeeded" was read as "the customer paid" ──

    public function test_a_zaincash_reply_that_is_pending_is_not_paid_whatever_its_success_flag_says(): void
    {
        config(['services.zaincash.secret' => 'test-secret', 'services.zaincash.merchant_id' => 'm', 'services.zaincash.msisdn' => '9647800000000', 'services.zaincash.base_url' => 'https://zaincash.test']);
        $payment = new Payment;
        $payment->forceFill(['provider' => 'zaincash', 'method' => 'zaincash', 'status' => Payment::STATUS_PENDING, 'amount' => 50000, 'currency' => 'IQD', 'provider_payment_id' => 'zc-1']);

        Http::fake(['zaincash.test/*' => Http::sequence()
            ->push(['success' => true, 'status' => 'pending', 'id' => 'zc-1'])
            ->push(['success' => true, 'status' => 'failed', 'id' => 'zc-1'])
            ->push(['status' => 'success', 'id' => 'zc-1'])
            ->push(['success' => true, 'id' => 'zc-1'])]);
        $zainCash = app(ZainCashPaymentService::class);

        $this->assertSame(Payment::STATUS_PENDING, $zainCash->verifyPayment($payment)->status);
        $this->assertSame(Payment::STATUS_FAILED, $zainCash->verifyPayment($payment)->status);
        $this->assertSame(Payment::STATUS_PAID, $zainCash->verifyPayment($payment)->status);
        // No status at all: the flag is all there is to go on, as before.
        $this->assertSame(Payment::STATUS_PAID, $zainCash->verifyPayment($payment)->status);
    }

    // ── A user manager could strip a customer account of permissions a super admin gave it ──

    public function test_a_user_manager_cannot_edit_a_customer_account_that_holds_admin_permissions(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_ADMIN, 'permissions' => [User::PERMISSION_USERS_MANAGE], 'email_verified_at' => now()]);
        $trusted = User::factory()->create(['role' => User::ROLE_USER, 'permissions' => [User::PERMISSION_ORDERS_MANAGE], 'email_verified_at' => now()]);

        $this->actingAs($manager)
            ->patch(route('admin.users.update-details', $trusted), [
                'name' => $trusted->name, 'email' => 'taken-over@example.com', 'phone' => null, 'date_of_birth' => null,
                'role' => User::ROLE_USER, 'permissions' => [], 'dealer_status' => User::DEALER_STATUS_INACTIVE, 'dealer_discount' => 0,
            ])
            ->assertSessionHas('error');

        $trusted->refresh();
        $this->assertNotSame('taken-over@example.com', $trusted->email);
        $this->assertTrue($trusted->hasPermission(User::PERMISSION_ORDERS_MANAGE));
    }

    public function test_a_user_manager_can_still_edit_an_ordinary_customer(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_ADMIN, 'permissions' => [User::PERMISSION_USERS_MANAGE], 'email_verified_at' => now()]);
        $customer = User::factory()->create(['role' => User::ROLE_USER, 'email_verified_at' => now()]);

        $this->actingAs($manager)
            ->patch(route('admin.users.update-details', $customer), [
                'name' => 'Renamed Customer', 'email' => $customer->email, 'phone' => null, 'date_of_birth' => null,
                'role' => User::ROLE_USER, 'permissions' => [], 'dealer_status' => User::DEALER_STATUS_INACTIVE, 'dealer_discount' => 0,
            ])
            ->assertSessionHas('success');

        $this->assertSame('Renamed Customer', $customer->fresh()->name);
    }

    /**
     * @return array{0: User, 1: UserAddress, 2: Product}
     */
    private function shopper(): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock_quantity' => 5,
            'price' => 25000,
            'is_active' => true,
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id, 'label' => 'Home', 'country' => 'Iraq', 'city' => 'Baghdad',
            'address_line1' => 'Street 10', 'phone' => '123456789', 'is_default' => true,
        ]);

        return [$user, $address, $product];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productForm(Product $product, array $overrides): array
    {
        return array_merge([
            'name_en' => $product->name_en,
            'name_ar' => $product->name_ar ?: $product->name_en,
            'name_ku' => $product->name_ku ?: $product->name_en,
            'description_en' => 'Audit',
            'price' => 15000,
            'dealer_price' => 12000,
            'sku' => $product->sku,
            'brand' => 'Bosch',
            'category_id' => $product->category_id,
            'is_active' => true,
        ], $overrides);
    }
}
