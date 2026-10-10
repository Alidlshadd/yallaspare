<?php

namespace Tests\Feature;

use App\Jobs\ProcessOtpiqWhatsAppWebhook;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OtpiqWebhookEvent;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Orders\OrderStatusService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaymentVerificationResult;
use App\Services\Payments\Providers\WaylPaymentService;
use App\Services\Payments\Providers\ZainCashPaymentService;
use App\Support\CheckoutSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * One group of tests per finding of the October 2026 application security
 * audit and its follow-up. Each reproduces the fault as it was.
 */
class SecurityAudit202610Test extends TestCase
{
    use RefreshDatabase;

    // ── Buy-now placed one order per click ──

    public function test_the_same_buy_now_form_sent_twice_places_one_order(): void
    {
        [$user, $address, $product] = $this->shopper();
        $form = ['quantity' => 2, 'address_id' => $address->id, 'submission_token' => CheckoutSubmission::issue($user, $product)];

        $first = $this->actingAs($user)->post(route('checkout.buy-now.place', $product), $form);
        $order = Order::query()->sole();
        $first->assertRedirect(route('checkout.success', $order));

        $this->actingAs($user)->post(route('checkout.buy-now.place', $product), $form)
            ->assertRedirect(route('checkout.success', $order));

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('product_id', $product->id)->count());
    }

    public function test_a_copy_that_arrives_while_the_first_is_still_being_placed_orders_nothing(): void
    {
        [$user, $address, $product] = $this->shopper();
        $token = CheckoutSubmission::issue($user, $product);
        $contents = ['product' => $product->id, 'quantity' => 1, 'address' => $address->id, 'payment_method' => PaymentService::METHOD_COD, 'coupon' => '', 'notes' => sha1('')];
        // The first copy has claimed the token and has not finished.
        $this->assertSame(CheckoutSubmission::CLAIMED, CheckoutSubmission::claim($token, $user, $contents)[0]);

        $this->actingAs($user)
            ->post(route('checkout.buy-now.place', $product), ['quantity' => 1, 'address_id' => $address->id, 'submission_token' => $token])
            ->assertRedirect(route('account.orders.index'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_used_token_cannot_carry_a_different_order(): void
    {
        [$user, $address, $product] = $this->shopper();
        $token = CheckoutSubmission::issue($user, $product);

        $this->actingAs($user)->post(route('checkout.buy-now.place', $product), ['quantity' => 1, 'address_id' => $address->id, 'submission_token' => $token]);

        // Same token, another quantity: not a copy of the first request.
        $this->actingAs($user)
            ->post(route('checkout.buy-now.place', $product), ['quantity' => 3, 'address_id' => $address->id, 'submission_token' => $token])
            ->assertSessionHasErrors('submission_token');

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(4, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_token_is_good_only_for_the_customer_and_product_it_was_issued_for(): void
    {
        [$user, $address, $product] = $this->shopper();
        [$other, $otherAddress, $otherProduct] = $this->shopper();

        // Another customer's token.
        $this->actingAs($other)
            ->post(route('checkout.buy-now.place', $product), ['quantity' => 1, 'address_id' => $otherAddress->id, 'submission_token' => CheckoutSubmission::issue($user, $product)])
            ->assertSessionHasErrors('submission_token');

        // The right customer's token, for another product.
        $this->actingAs($user)
            ->post(route('checkout.buy-now.place', $product), ['quantity' => 1, 'address_id' => $address->id, 'submission_token' => CheckoutSubmission::issue($user, $otherProduct)])
            ->assertSessionHasErrors('submission_token');

        // A token nobody issued, and none at all.
        foreach (['a1b2c3d4-0000-4000-8000-000000000001', str_repeat('a', 32).'.'.str_repeat('0', 64), ''] as $forged) {
            $this->actingAs($user)
                ->post(route('checkout.buy-now.place', $product), ['quantity' => 1, 'address_id' => $address->id, 'submission_token' => $forged])
                ->assertSessionHasErrors('submission_token');
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_buy_now_form_that_failed_can_be_sent_again(): void
    {
        [$user, $address, $product] = $this->shopper();
        $token = CheckoutSubmission::issue($user, $product);
        $form = ['quantity' => 9, 'address_id' => $address->id, 'submission_token' => $token];

        // Nine of a part there are five of: refused, and the token is given
        // back each time rather than left claimed.
        $this->actingAs($user)->post(route('checkout.buy-now.place', $product), $form)->assertSessionHas('error');
        $this->actingAs($user)->post(route('checkout.buy-now.place', $product), $form)->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);

        $product->update(['stock_quantity' => 20]);
        $this->actingAs($user)->post(route('checkout.buy-now.place', $product), $form);

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(11, (int) $product->fresh()->stock_quantity);
    }

    public function test_the_buy_now_page_carries_a_token_the_server_will_accept(): void
    {
        [$user, $address, $product] = $this->shopper();

        $html = (string) $this->actingAs($user)
            ->post(route('checkout.buy-now', $product), ['quantity' => 1, 'address_id' => $address->id])
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/name="submission_token" value="([^"]+)"/', $html, $match));
        $this->assertTrue(CheckoutSubmission::isValid($match[1], $user, $product));
    }

    // ── Saving a product put sold stock back ──

    public function test_saving_a_product_without_touching_stock_keeps_a_sale_made_meanwhile(): void
    {
        [$product, $admin] = $this->stockedProduct();
        $form = $this->productForm($product, ['stock_quantity' => 10, 'stock_quantity_seen' => 10]);
        $product->update(['stock_quantity' => 8]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), $form)->assertSessionHasNoErrors();

        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_typed_stock_figure_is_saved_when_nothing_else_moved_the_stock(): void
    {
        [$product, $admin] = $this->stockedProduct();

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->productForm($product, ['stock_quantity' => 15, 'stock_quantity_seen' => 10]))
            ->assertSessionHasNoErrors();

        $this->assertSame(15, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_typed_stock_figure_is_refused_when_the_stock_moved_while_the_form_was_open(): void
    {
        [$product, $admin] = $this->stockedProduct();
        $form = $this->productForm($product, ['stock_quantity' => 15, 'stock_quantity_seen' => 10, 'name_en' => 'Renamed while stale']);
        $product->update(['stock_quantity' => 8]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), $form)->assertSessionHasErrors('stock_quantity');

        // Nothing of the save went through, the rename included.
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
        $this->assertNotSame('Renamed while stale', $product->fresh()->name_en);
    }

    public function test_the_same_stock_form_sent_twice_does_not_raise_stock_twice(): void
    {
        [$product, $admin] = $this->stockedProduct();
        $form = $this->productForm($product, ['stock_quantity' => 15, 'stock_quantity_seen' => 10]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), $form)->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.products.update', $product->fresh()), $form)->assertSessionHasNoErrors();

        $this->assertSame(15, (int) $product->fresh()->stock_quantity);
    }

    public function test_two_admins_saving_different_stock_from_the_same_page_do_not_add_up(): void
    {
        [$product, $admin] = $this->stockedProduct();
        $second = User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);
        $firstForm = $this->productForm($product, ['stock_quantity' => 15, 'stock_quantity_seen' => 10]);
        $secondForm = $this->productForm($product, ['stock_quantity' => 12, 'stock_quantity_seen' => 10]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), $firstForm)->assertSessionHasNoErrors();
        $this->actingAs($second)->put(route('admin.products.update', $product->fresh()), $secondForm)->assertSessionHasErrors('stock_quantity');

        $this->assertSame(15, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_false_seen_figure_cannot_be_used_to_move_stock(): void
    {
        [$product, $admin] = $this->stockedProduct();

        // "I saw 999" with 50 typed: the page was not showing 999, so this
        // is a conflict like any other, not a difference to apply.
        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->productForm($product, ['stock_quantity' => 50, 'stock_quantity_seen' => 999]))
            ->assertSessionHasErrors('stock_quantity');

        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_request_without_a_seen_figure_sets_the_stock_it_names(): void
    {
        [$product, $admin] = $this->stockedProduct();

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->productForm($product, ['stock_quantity' => 7]))
            ->assertSessionHasNoErrors();

        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
    }

    public function test_the_edit_page_reports_the_stock_it_is_showing(): void
    {
        [$product, $admin] = $this->stockedProduct();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="stock_quantity_seen" value="10"', false);
    }

    // ── An older "canceled" order could be cancelled, and restocked, again ──

    public function test_an_order_stored_as_canceled_is_not_restocked_when_cancelled_again(): void
    {
        [$user, , $product] = $this->shopper();
        $order = $this->order($user, $product, ['status' => 'canceled']);

        $this->assertSame(Order::STATUS_CANCELLED, Order::normalizedStatus('canceled'));

        app(OrderStatusService::class)->changeStatus($order, Order::STATUS_CANCELLED, $this->admin());

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->where('product_id', $product->id)->count());
    }

    public function test_cancelling_a_pending_order_returns_its_stock_once(): void
    {
        [$user, , $product] = $this->shopper();
        $order = $this->order($user, $product);
        $statuses = app(OrderStatusService::class);
        $admin = $this->admin();

        $statuses->changeStatus($order, Order::STATUS_CANCELLED, $admin);
        $statuses->changeStatus($order->fresh(), Order::STATUS_CANCELLED, $admin);

        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
    }

    // ── ZainCash: "the lookup succeeded" was read as "the customer paid" ──

    public function test_a_zaincash_reply_is_paid_only_when_its_status_says_so_and_it_is_about_this_payment(): void
    {
        config(['services.zaincash.secret' => 'test-secret', 'services.zaincash.merchant_id' => 'm', 'services.zaincash.msisdn' => '9647800000000', 'services.zaincash.base_url' => 'https://zaincash.test']);
        $payment = new Payment;
        $payment->forceFill(['provider' => 'zaincash', 'method' => 'zaincash', 'status' => Payment::STATUS_PENDING, 'amount' => 50000, 'currency' => 'IQD', 'provider_payment_id' => 'zc-1', 'order_id' => 77]);

        Http::fake(['zaincash.test/*' => Http::sequence()
            ->push(['success' => true, 'status' => 'pending', 'id' => 'zc-1', 'orderId' => '77', 'amount' => 50000])
            ->push(['success' => true, 'status' => 'failed', 'id' => 'zc-1'])
            ->push(['success' => true, 'id' => 'zc-1', 'orderId' => '77', 'amount' => 50000])
            ->push(['status' => 'success', 'id' => 'zc-1'])
            ->push(['status' => 'success', 'id' => 'zc-1', 'orderId' => '77', 'amount' => 50000])]);
        $zainCash = app(ZainCashPaymentService::class);

        $this->assertSame(Payment::STATUS_PENDING, $zainCash->verifyPayment($payment)->status);
        $this->assertSame(Payment::STATUS_FAILED, $zainCash->verifyPayment($payment)->status);
        // The flag alone: "the call worked" is not "the customer paid".
        $this->assertSame(Payment::STATUS_PENDING, $zainCash->verifyPayment($payment)->status);
        // "Paid", with nothing tying it to this order or this amount.
        $this->assertSame(Payment::STATUS_PENDING, $zainCash->verifyPayment($payment)->status);
        $this->assertSame(Payment::STATUS_PAID, $zainCash->verifyPayment($payment)->status);
    }

    // ── A user manager could strip a customer account of permissions a super admin gave it ──

    public function test_a_user_manager_cannot_edit_a_customer_account_that_holds_admin_permissions(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_ADMIN, 'permissions' => [User::PERMISSION_USERS_MANAGE], 'email_verified_at' => now()]);
        $trusted = User::factory()->create(['role' => User::ROLE_USER, 'permissions' => [User::PERMISSION_ORDERS_MANAGE], 'email_verified_at' => now()]);

        $this->actingAs($manager)
            ->patch(route('admin.users.update-details', $trusted), $this->userForm($trusted, ['email' => 'taken-over@example.com']))
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
            ->patch(route('admin.users.update-details', $customer), $this->userForm($customer, ['name' => 'Renamed Customer']))
            ->assertSessionHas('success');

        $this->assertSame('Renamed Customer', $customer->fresh()->name);
    }

    // ── Any staff account could set any product's stock through the dealer endpoint ──

    public function test_staff_without_the_product_permission_cannot_set_stock_through_the_dealer_endpoint(): void
    {
        [, , $product] = $this->shopper();
        $viewer = User::factory()->create(['role' => User::ROLE_USER, 'permissions' => [User::PERMISSION_DASHBOARD_VIEW], 'email_verified_at' => now()]);

        Sanctum::actingAs($viewer, ['admin:mobile']);
        $this->patchJson('/api/mobile/dealer/products/'.$product->id.'/stock', ['stock_quantity' => 999])->assertForbidden();

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
    }

    public function test_staff_with_the_permission_need_a_stepped_up_token_to_set_stock(): void
    {
        [, , $product] = $this->shopper();
        $manager = User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);

        // Signed in on the phone, but the second factor has not been given.
        Sanctum::actingAs($manager, ['mobile']);
        $this->patchJson('/api/mobile/dealer/products/'.$product->id.'/stock', ['stock_quantity' => 999])->assertForbidden();
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);

        Sanctum::actingAs($manager, ['admin:mobile']);
        $this->patchJson('/api/mobile/dealer/products/'.$product->id.'/stock', ['stock_quantity' => 12])->assertOk();
        $this->assertSame(12, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_dealer_cannot_set_the_stock_of_a_product_that_is_not_theirs(): void
    {
        [, , $product] = $this->shopper();
        $dealer = User::factory()->create(['role' => User::ROLE_DEALER, 'email_verified_at' => now()]);
        $dealer->forceFill(['dealer_status' => User::DEALER_STATUS_ACTIVE])->save();

        // Even holding the admin token ability, which a dealer is never issued.
        Sanctum::actingAs($dealer, ['admin:mobile']);
        $this->patchJson('/api/mobile/dealer/products/'.$product->id.'/stock', ['stock_quantity' => 999])->assertForbidden();
        $this->patchJson('/api/mobile/admin/products/'.$product->id, ['stock_quantity' => 999])->assertForbidden();

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_dealer_who_is_not_active_cannot_reach_the_dealer_endpoints(): void
    {
        [, , $product] = $this->shopper();
        $dealer = User::factory()->create(['role' => User::ROLE_DEALER, 'email_verified_at' => now()]);
        $dealer->forceFill(['dealer_status' => User::DEALER_STATUS_INACTIVE])->save();

        Sanctum::actingAs($dealer, ['mobile']);
        $this->getJson('/api/mobile/dealer/orders')->assertForbidden();
        $this->patchJson('/api/mobile/dealer/products/'.$product->id.'/stock', ['stock_quantity' => 1])->assertForbidden();
    }

    public function test_the_dealer_order_screens_show_every_order_only_to_staff_who_may_see_orders(): void
    {
        [$customer, , $product] = $this->shopper();
        $this->order($customer, $product);

        $viewer = User::factory()->create(['role' => User::ROLE_USER, 'permissions' => [User::PERMISSION_DASHBOARD_VIEW], 'email_verified_at' => now()]);
        Sanctum::actingAs($viewer, ['admin:mobile']);
        $this->getJson('/api/mobile/dealer/orders')->assertOk()->assertJsonCount(0, 'data');

        $dealer = User::factory()->create(['role' => User::ROLE_DEALER, 'email_verified_at' => now()]);
        $dealer->forceFill(['dealer_status' => User::DEALER_STATUS_ACTIVE])->save();
        Sanctum::actingAs($dealer, ['mobile']);
        $this->getJson('/api/mobile/dealer/orders')->assertOk()->assertJsonCount(0, 'data');

        $orders = User::factory()->create(['role' => User::ROLE_USER, 'permissions' => [User::PERMISSION_ORDERS_MANAGE], 'email_verified_at' => now()]);
        Sanctum::actingAs($orders, ['admin:mobile']);
        $this->getJson('/api/mobile/dealer/orders')->assertOk()->assertJsonCount(1, 'data');
    }

    // ── GET /api/user returned the whole user row ──

    public function test_the_account_endpoint_returns_a_chosen_list_of_fields(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'name' => 'Sara']);
        $user->forceFill([
            'permissions' => [User::PERMISSION_ORDERS_MANAGE],
            'google_id' => 'google-secret-id',
            'apple_id' => 'apple-secret-id',
            'ban_reason' => 'internal note about this customer',
            'remember_token' => 'remember-me-token',
        ])->save();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/user')->assertOk();

        $this->assertSame(
            ['id', 'name', 'email', 'phone', 'role', 'email_verified_at', 'phone_verified_at', 'locale_preference', 'theme_preference', 'dealer_status', 'dealer_discount', 'created_at'],
            array_keys($response->json())
        );
        $response->assertJsonPath('id', $user->id)->assertJsonPath('name', 'Sara')->assertJsonPath('email', $user->email);

        $body = (string) $response->getContent();
        foreach (['google-secret-id', 'apple-secret-id', 'internal note about this customer', 'remember-me-token', 'permissions', 'password', 'ban_reason', 'google_id'] as $private) {
            $this->assertStringNotContainsString($private, $body);
        }
    }

    public function test_the_account_endpoint_still_requires_a_signed_in_account(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    // ── A signed WhatsApp webhook could be replayed under a new event id ──

    public function test_a_signed_webhook_replayed_under_a_new_event_id_is_a_duplicate(): void
    {
        Queue::fake();
        config(['services.otpiq.webhook_secret' => 'audit-secret', 'services.otpiq.whatsapp_enabled' => true]);
        $body = (string) json_encode(['message' => ['id' => 'wamid.1', 'text' => 'Where is my order?']]);

        $this->webhook($body, 'event-original')->assertOk()->assertJson(['duplicate' => false]);
        // The captured request, word for word, with only the unsigned id changed.
        $this->webhook($body, 'event-replayed')->assertOk()->assertExactJson(['success' => true, 'duplicate' => true]);
        $this->webhook($body, 'event-replayed-again', attempt: '7')->assertOk()->assertJson(['duplicate' => true]);

        $this->assertSame(1, OtpiqWebhookEvent::query()->count());
        $this->assertSame('event-original', OtpiqWebhookEvent::query()->value('event_id'));
        Queue::assertPushed(ProcessOtpiqWhatsAppWebhook::class, 1);
    }

    public function test_the_providers_own_retry_and_a_new_message_are_both_handled(): void
    {
        Queue::fake();
        config(['services.otpiq.webhook_secret' => 'audit-secret', 'services.otpiq.whatsapp_enabled' => true]);
        $first = (string) json_encode(['message' => ['id' => 'wamid.1', 'text' => 'Hello']]);
        $second = (string) json_encode(['message' => ['id' => 'wamid.2', 'text' => 'Hello']]);

        $this->webhook($first, 'event-1')->assertOk()->assertJson(['duplicate' => false]);
        // A retry: same event, same body, a later attempt and a new timestamp.
        $this->webhook($first, 'event-1', attempt: '2', timestamp: '1786003600')->assertOk()->assertJson(['duplicate' => true]);
        // A different message is a different event.
        $this->webhook($second, 'event-2')->assertOk()->assertJson(['duplicate' => false]);

        $this->assertSame(2, OtpiqWebhookEvent::query()->count());
        Queue::assertPushed(ProcessOtpiqWhatsAppWebhook::class, 2);
    }

    public function test_a_replay_with_a_changed_body_fails_the_signature(): void
    {
        Queue::fake();
        config(['services.otpiq.webhook_secret' => 'audit-secret', 'services.otpiq.whatsapp_enabled' => true]);
        $body = (string) json_encode(['message' => ['id' => 'wamid.1', 'text' => 'Hello']]);
        $signature = 'sha256='.hash_hmac('sha256', '1786000000.'.$body, 'audit-secret');

        $this->webhook(str_replace('Hello', 'Hullo', $body), 'event-forged', signature: $signature)->assertStatus(401);

        $this->assertSame(0, OtpiqWebhookEvent::query()->count());
    }

    // ── An unpaid online order kept its stock for ever ──

    public function test_an_unpaid_online_order_is_cancelled_and_its_stock_returned_once(): void
    {
        [$user, , $product] = $this->shopper();
        $order = $this->unpaidOnlineOrder($user, $product);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);

        $this->artisan('orders:release-unpaid')->assertSuccessful();
        $this->artisan('orders:release-unpaid')->assertSuccessful();

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::PAYMENT_FAILED, $order->payment_status);
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->assertSame(Payment::STATUS_CANCELLED, $order->payments()->value('status'));
        $this->assertSame(1, InventoryMovement::query()->where('product_id', $product->id)->where('type', InventoryMovement::TYPE_IN)->count());
    }

    public function test_orders_still_inside_their_payment_window_and_cash_orders_are_left_alone(): void
    {
        [$user, , $product] = $this->shopper();
        $recent = $this->unpaidOnlineOrder($user, $product, createdMinutesAgo: 20);
        $cash = $this->order($user, $product, ['payment_method' => PaymentService::METHOD_COD, 'payment_status' => Order::PAYMENT_PENDING]);
        Order::query()->whereKey($cash->id)->update(['created_at' => now()->subDays(3)]);
        $stock = (int) $product->fresh()->stock_quantity;

        $this->artisan('orders:release-unpaid')->assertSuccessful();

        $this->assertSame(Order::STATUS_PENDING, $recent->fresh()->status);
        $this->assertSame(Order::STATUS_PENDING, $cash->fresh()->status);
        $this->assertSame($stock, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        [$user, , $product] = $this->shopper();
        $order = $this->unpaidOnlineOrder($user, $product);

        $this->artisan('orders:release-unpaid', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
    }

    public function test_an_order_the_provider_confirms_at_the_last_look_is_kept_not_cancelled(): void
    {
        [$user, , $product] = $this->shopper();
        $order = $this->unpaidOnlineOrder($user, $product, providerPaymentId: 'wayl-link-1');
        $this->providerAnswers(new PaymentVerificationResult(Payment::STATUS_PAID, 'wayl-link-1', 'WAYL1'));

        $this->artisan('orders:release-unpaid')->assertSuccessful();

        $order->refresh();
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
    }

    public function test_an_order_is_not_cancelled_while_the_provider_cannot_be_asked(): void
    {
        [$user, , $product] = $this->shopper();
        $order = $this->unpaidOnlineOrder($user, $product, providerPaymentId: 'wayl-link-1');
        $this->mock(WaylPaymentService::class, function (MockInterface $wayl): void {
            $wayl->shouldReceive('provider')->andReturn('wayl');
            $wayl->shouldReceive('verifyPayment')->andThrow(new \RuntimeException('gateway timeout'));
        });
        $this->app->forgetInstance(PaymentService::class);

        $this->artisan('orders:release-unpaid')->assertSuccessful();

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_payment_confirmed_after_the_order_was_released_does_not_reopen_it_or_touch_stock(): void
    {
        Exceptions::fake();
        [$user, , $product] = $this->shopper();
        $order = $this->unpaidOnlineOrder($user, $product, providerPaymentId: 'wayl-link-1');
        $this->providerAnswers(new PaymentVerificationResult(Payment::STATUS_PENDING, 'wayl-link-1'));

        $this->artisan('orders:release-unpaid')->assertSuccessful();
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);

        // The customer's money arrives after all, and the provider says so twice.
        $this->providerAnswers(new PaymentVerificationResult(Payment::STATUS_PAID, 'wayl-link-1', 'WAYL1'));
        $payment = app(PaymentService::class)->verifyAndApply($order->payments()->firstOrFail(), 'webhook');
        app(PaymentService::class)->verifyAndApply($payment, 'webhook');

        $order->refresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        // Returned once by the release, and neither taken nor returned again.
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        // Staff are told, once.
        Exceptions::assertReported(fn (\RuntimeException $e): bool => str_contains($e->getMessage(), (string) $order->order_number));
        $this->assertSame(1, $order->statusHistory()->where('note', 'like', 'Payment received after%')->count());
    }

    public function test_online_payment_methods_remain_switched_off(): void
    {
        $this->assertFalse((bool) config('payments.customer_online_payments_enabled'));
        $this->assertSame([PaymentService::METHOD_COD], app(PaymentService::class)->allowedCheckoutMethods());
    }

    // ── Helpers ──

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

    /**
     * @return array{0: Product, 1: User}
     */
    private function stockedProduct(): array
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock_quantity' => 10,
            'sku' => 'AUD-'.fake()->unique()->numerify('####'),
        ]);

        return [$product, $this->admin()];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);
    }

    /**
     * An order for two of the product. It does not move stock by itself.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function order(User $user, Product $product, array $attributes = []): Order
    {
        $order = new Order;
        $order->forceFill(array_merge([
            'user_id' => $user->id,
            'order_number' => 'ORD-AUDIT-'.fake()->unique()->numerify('#####'),
            'status' => Order::STATUS_PENDING,
            'total_amount' => 50000, 'grand_total' => 50000, 'subtotal_amount' => 50000,
            'delivery_address' => 'Street 10', 'delivery_city' => 'Baghdad', 'delivery_phone' => '123456789',
        ], $attributes))->save();

        OrderItem::query()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 25000, 'subtotal' => 50000]);

        return $order;
    }

    /**
     * An order placed with an online method and never paid: its two units
     * are off the shelf, as checkout leaves them.
     */
    private function unpaidOnlineOrder(User $user, Product $product, int $createdMinutesAgo = 200, ?string $providerPaymentId = null): Order
    {
        $order = $this->order($user, $product, ['payment_method' => 'wayl', 'payment_status' => Order::PAYMENT_PENDING_PAYMENT]);
        Order::query()->whereKey($order->id)->update(['created_at' => now()->subMinutes($createdMinutesAgo)]);
        $product->decrement('stock_quantity', 2);

        Payment::query()->create([
            'order_id' => $order->id, 'user_id' => $user->id, 'provider' => 'wayl', 'method' => 'wayl',
            'status' => Payment::STATUS_PENDING, 'amount' => 50000, 'currency' => 'IQD',
            'provider_payment_id' => $providerPaymentId,
        ]);

        return $order->fresh();
    }

    private function providerAnswers(PaymentVerificationResult $result): void
    {
        $this->mock(WaylPaymentService::class, function (MockInterface $wayl) use ($result): void {
            $wayl->shouldReceive('provider')->andReturn('wayl');
            $wayl->shouldReceive('verifyPayment')->andReturn($result);
        });
        $this->app->forgetInstance(PaymentService::class);
    }

    private function webhook(string $body, string $eventId, string $attempt = '1', string $timestamp = '1786000000', ?string $signature = null)
    {
        return $this->call('POST', '/api/webhooks/otpiq', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_OTPIQ_WEBHOOK_EVENT' => 'whatsapp.message.received',
            'HTTP_X_OTPIQ_WEBHOOK_ATTEMPT' => $attempt,
            'HTTP_X_OTPIQ_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_OTPIQ_WEBHOOK_EVENT_ID' => $eventId,
            'HTTP_X_OTPIQ_WEBHOOK_SIGNATURE' => $signature ?? 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, 'audit-secret'),
        ], $body);
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function userForm(User $user, array $overrides): array
    {
        return array_merge([
            'name' => $user->name, 'email' => $user->email, 'phone' => null, 'date_of_birth' => null,
            'role' => User::ROLE_USER, 'permissions' => [], 'dealer_status' => User::DEALER_STATUS_INACTIVE, 'dealer_discount' => 0,
        ], $overrides);
    }
}
