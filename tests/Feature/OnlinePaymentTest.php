<?php

namespace Tests\Feature;

use App\Mail\OperationalNotificationMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_payment_success_marks_order_paid_after_server_verification(): void
    {
        Mail::fake();
        [$user, $address] = $this->makeCheckoutContext();
        $this->enableFib();
        $this->fakeFib('PAID');

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'fib',
        ]);

        $response->assertSessionHasNoErrors();
        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect('https://fib.test/pay/fib-123');
        $this->assertSame(Order::PAYMENT_PENDING_PAYMENT, $order->payment_status);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        Mail::assertNothingQueued();

        $this->actingAs($user)
            ->get(route('payments.return', $payment))
            ->assertRedirect(route('checkout.success', $order));

        $this->assertSame(Order::PAYMENT_PAID, (string) $order->fresh()->payment_status);
        $this->assertSame(Order::STATUS_PROCESSING, (string) $order->fresh()->status);
        $this->assertSame(Payment::STATUS_PAID, (string) $payment->fresh()->status);
        Mail::assertQueued(OperationalNotificationMail::class, 1);
    }

    public function test_failed_online_payment_marks_payment_and_order_failed(): void
    {
        [$user, $address] = $this->makeCheckoutContext();
        $this->enableFib();
        $this->fakeFib('DECLINED');

        $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'fib',
        ]);

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('payments.return', $payment))
            ->assertRedirect(route('account.orders.show', $order));

        $this->assertSame(Order::PAYMENT_FAILED, (string) $order->fresh()->payment_status);
        $this->assertSame(Order::STATUS_PENDING, (string) $order->fresh()->status);
        $this->assertSame(Payment::STATUS_FAILED, (string) $payment->fresh()->status);
    }

    public function test_invalid_payment_callback_is_rejected(): void
    {
        $this->enableFib();

        $this->postJson(route('payments.webhook', ['provider' => 'fib']), [
            'id' => 'missing-payment',
            'status' => 'PAID',
        ])->assertStatus(400);

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        Mail::fake();
        [$user, $address] = $this->makeCheckoutContext();
        $this->enableFib();
        $this->fakeFib('PAID');

        $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'fib',
        ]);

        $payment = Payment::query()->firstOrFail();

        $payload = ['id' => 'fib-123', 'status' => 'PAID'];
        $this->postJson(route('payments.webhook', ['provider' => 'fib']), $payload)->assertOk();
        $this->postJson(route('payments.webhook', ['provider' => 'fib']), $payload)->assertOk();

        $this->assertSame(Payment::STATUS_PAID, (string) $payment->fresh()->status);
        Mail::assertQueued(OperationalNotificationMail::class, 1);
    }

    public function test_wayl_payment_link_redirect_and_return_are_verified_server_side(): void
    {
        Mail::fake();
        [$user, $address] = $this->makeCheckoutContext();
        $this->enableWayl();
        $this->fakeWayl('Complete');

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'wayl',
        ]);

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('https://checkout.thewayl.test/pay/WAYL123');
        $this->assertSame('wayl', $order->payment_method);
        $this->assertSame(Order::PAYMENT_PENDING_PAYMENT, $order->payment_status);
        $this->assertSame('wayl', $payment->provider);
        $this->assertSame('wayl-link-123', $payment->provider_payment_id);
        $this->assertSame('WAYL123', $payment->provider_transaction_id);
        $this->assertSame('yallaspare-payment-'.$payment->id, $payment->provider_reference);
        $this->assertSame('[redacted]', $payment->provider_response['token']);

        Http::assertSent(function (HttpRequest $request) use ($order, $payment): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://wayl.test/api/v1/links'
                && $request->hasHeader('X-WAYL-AUTHENTICATION', 'wayl-test-token')
                && $request['env'] === 'test'
                && $request['referenceId'] === 'yallaspare-payment-'.$payment->id
                && $request['total'] === (int) $order->grand_total
                && $request['currency'] === 'IQD'
                && $request['lineItem'][0]['amount'] === (int) $order->grand_total
                && $request['redirectionUrl'] === route('payments.return', $payment)
                && ! array_key_exists('webhookUrl', $request->data())
                && ! array_key_exists('webhookSecret', $request->data());
        });

        $this->actingAs($user)
            ->get(route('payments.return', $payment))
            ->assertOk()
            ->assertSee('Payment confirmed')
            ->assertSee($order->order_number);

        $this->assertSame(Order::PAYMENT_PAID, (string) $order->fresh()->payment_status);
        $this->assertSame(Order::STATUS_PROCESSING, (string) $order->fresh()->status);
        $this->assertSame(Payment::STATUS_PAID, (string) $payment->fresh()->status);
        Mail::assertQueued(OperationalNotificationMail::class, 1);
    }

    public function test_wayl_pending_status_keeps_order_pending_on_result_page(): void
    {
        [$user, $address] = $this->makeCheckoutContext();
        $this->enableWayl();
        $this->fakeWayl('Processing');

        $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'wayl',
        ]);

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('payments.return', $payment))
            ->assertOk()
            ->assertSee('Payment is pending');

        $this->assertSame(Order::PAYMENT_PENDING_PAYMENT, (string) $order->fresh()->payment_status);
        $this->assertSame(Order::STATUS_PENDING, (string) $order->fresh()->status);
        $this->assertSame(Payment::STATUS_PENDING, (string) $payment->fresh()->status);
    }

    public function test_wayl_complete_status_with_wrong_amount_is_not_accepted(): void
    {
        [$user, $address] = $this->makeCheckoutContext();
        $this->enableWayl();
        $this->fakeWayl('Complete', '1');

        $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'wayl',
        ]);

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('payments.return', $payment))
            ->assertOk()
            ->assertSee('Payment verification is temporarily unavailable');

        $this->assertSame(Order::PAYMENT_PENDING_PAYMENT, (string) $order->fresh()->payment_status);
        $this->assertSame(Order::STATUS_PENDING, (string) $order->fresh()->status);
        $this->assertSame(Payment::STATUS_PENDING, (string) $payment->fresh()->status);
    }

    public function test_admin_can_refresh_wayl_payment_status_from_provider(): void
    {
        Mail::fake();
        [$user, $address] = $this->makeCheckoutContext();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
        $this->enableWayl();
        $this->fakeWayl('Complete');

        $this->actingAs($user)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'payment_method' => 'wayl',
        ]);

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();
        $adminPage = route('admin.orders.show', $order);

        $this->actingAs($admin)
            ->get($adminPage)
            ->assertOk()
            ->assertSee('Verify with WAYL');

        $this->from($adminPage)
            ->actingAs($admin)
            ->post(route('admin.orders.payments.verify-wayl', [$order, $payment]))
            ->assertRedirect($adminPage)
            ->assertSessionHas('success');

        $this->assertSame(Order::PAYMENT_PAID, (string) $order->fresh()->payment_status);
        $this->assertSame(Payment::STATUS_PAID, (string) $payment->fresh()->status);
    }

    private function enableFib(): void
    {
        config([
            'payments.methods.fib.enabled' => true,
            'services.fib.enabled' => true,
            'services.fib.base_url' => 'https://fib.test',
            'services.fib.client_id' => 'client-id',
            'services.fib.client_secret' => 'client-secret',
        ]);
    }

    private function enableWayl(): void
    {
        config([
            'payments.currency' => 'IQD',
            'payments.methods.wayl.enabled' => true,
            'services.wayl.enabled' => true,
            'services.wayl.env' => 'test',
            'services.wayl.token' => 'wayl-test-token',
            'services.wayl.base_url' => 'https://wayl.test',
        ]);
    }

    private function fakeWayl(string $status, ?string $verifiedTotal = null): void
    {
        Http::fake(function (HttpRequest $request) use ($status, $verifiedTotal) {
            if ($request->method() === 'POST' && $request->url() === 'https://wayl.test/api/v1/links') {
                return Http::response([
                    'data' => [
                        'referenceId' => $request['referenceId'],
                        'id' => 'wayl-link-123',
                        'code' => 'WAYL123',
                        'total' => (string) $request['total'],
                        'currency' => 'IQD',
                        'status' => 'Created',
                        'url' => 'https://checkout.thewayl.test/pay/WAYL123',
                    ],
                    'message' => 'Link created successfully.',
                    'token' => 'response-token-that-must-not-be-stored',
                ], 201);
            }

            if ($request->method() === 'GET' && str_contains($request->url(), '/api/v1/links/')) {
                $referenceId = rawurldecode((string) str($request->url())->afterLast('/'));

                return Http::response([
                    'data' => [
                        'referenceId' => $referenceId,
                        'id' => 'wayl-link-123',
                        'code' => 'WAYL123',
                        'total' => $verifiedTotal ?? '55000',
                        'currency' => 'IQD',
                        'paymentMethod' => $status === 'Complete' ? 'Card' : null,
                        'status' => $status,
                        'completedAt' => $status === 'Complete' ? now()->toIso8601String() : null,
                    ],
                    'message' => 'Link retrieved successfully.',
                ]);
            }

            return Http::response([], 404);
        });
    }

    private function fakeFib(string $status): void
    {
        Http::fake(function (HttpRequest $request) use ($status) {
            $url = $request->url();

            if (str_contains($url, '/auth/realms/fib-online-shop/protocol/openid-connect/token')) {
                return Http::response(['access_token' => 'access-token'], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/protected/v1/payments')) {
                return Http::response([
                    'paymentId' => 'fib-123',
                    'readableCode' => 'READ-123',
                    'personalAppLink' => 'https://fib.test/pay/fib-123',
                ], 202);
            }

            if (str_ends_with($url, '/protected/v1/payments/fib-123/status')) {
                return Http::response([
                    'paymentId' => 'fib-123',
                    'status' => $status,
                    'transactionId' => 'txn-123',
                    'decliningReason' => $status === 'PAID' ? null : 'PAYMENT_CANCELLATION',
                ], 200);
            }

            return Http::response([], 404);
        });
    }

    /**
     * @return array{User, UserAddress, Product}
     */
    private function makeCheckoutContext(): array
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 5,
            'price' => 25000,
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Iraq',
            'city' => 'Baghdad',
            'address_line1' => 'Street 10',
            'phone' => '123456789',
            'is_default' => true,
        ]);
        $cart = Cart::query()->create(['user_id' => $user->id]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        return [$user, $address, $product];
    }
}
