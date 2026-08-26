<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivityLog;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProviderLog;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\Providers\WaylPaymentService;
use App\Services\Payments\WaylProviderException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminWaylPaymentControlCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'security.admin_two_factor.enabled' => false,
            'app.url' => 'https://yallaspare.test',
            'services.wayl.enabled' => true,
            'services.wayl.env' => 'test',
            'services.wayl.token' => 'super-secret-wayl-token',
            'services.wayl.base_url' => 'https://wayl.test',
            'services.wayl.health_path' => '/api/v1/links',
            'services.wayl.webhook_url' => '',
            'services.wayl.webhook_secret' => 'super-secret-webhook-key',
            'payments.methods.wayl.enabled' => true,
            'payments.methods.wayl.minimum_amount' => 3000,
            'payments.currency' => 'IQD',
        ]);

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => User::defaultPermissionsForRole(User::ROLE_ADMIN),
            'email_verified_at' => now(),
        ]);
    }

    public function test_authorized_admin_can_open_wayl_control_center(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index'))
            ->assertOk()
            ->assertSee('WAYL Payment Gateway')
            ->assertSee('Payment Traffic')
            ->assertSee('API Traffic')
            ->assertSee('Check Connection')
            ->assertSee('Diagnostics')
            ->assertSee('Run Create-Link Diagnostic');
    }

    public function test_normal_user_cannot_open_wayl_control_center_or_health_action(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)->get(route('admin.wayl.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.wayl.health'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.wayl.diagnostics.create-link'))->assertForbidden();
    }

    public function test_create_link_diagnostic_is_hidden_and_blocked_outside_test_environment(): void
    {
        config(['services.wayl.env' => 'live']);
        Http::fake();

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index'))
            ->assertOk()
            ->assertDontSee('Run Create-Link Diagnostic');

        $this->actingAs($this->admin)
            ->post(route('admin.wayl.diagnostics.create-link'))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_422_diagnostic_has_no_commerce_side_effects_and_preserves_sanitized_validation_details(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 9,
        ]);
        $cart = Cart::query()->create(['user_id' => $this->admin->id]);
        $cartItem = CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Http::fake([
            'https://wayl.test/api/v1/links' => Http::response([
                'success' => false,
                'message' => 'Whoops, missing fields for super-secret-wayl-token',
                'errors' => [
                    [
                        'path' => 'webhookUrl',
                        'code' => 'invalid_type',
                        'expected' => 'string',
                        'message' => 'Expected string',
                    ],
                    [
                        'path' => 'webhookSecret',
                        'code' => 'invalid_type',
                        'expected' => 'string',
                        'message' => 'Expected string',
                    ],
                ],
                'token' => 'super-secret-wayl-token',
                'authorization' => 'Bearer super-secret-wayl-token',
            ], 422),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.wayl.diagnostics.create-link'));

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(9, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Http::assertSent(function (Request $request): bool {
            $reference = (string) $request['referenceId'];

            return $request->method() === 'POST'
                && $request->url() === 'https://wayl.test/api/v1/links'
                && $request->hasHeader('X-WAYL-AUTHENTICATION', 'super-secret-wayl-token')
                && $request['env'] === 'test'
                && preg_match('/^wayl-diagnostic-[0-9A-HJKMNP-TV-Z]{26}$/', $reference) === 1
                && $request['total'] === 3000
                && $request['currency'] === 'IQD'
                && $request['customParameter'] === $reference
                && $request['lineItem'] === [[
                    'label' => 'YallaSpare WAYL diagnostic',
                    'amount' => 3000,
                    'type' => 'increase',
                ]]
                && $request['redirectionUrl'] === 'https://yallaspare.test/admin/integrations/wayl'
                && ! array_key_exists('webhookUrl', $request->data())
                && ! array_key_exists('webhookSecret', $request->data());
        });

        $log = PaymentProviderLog::query()
            ->where('event_type', PaymentProviderLog::EVENT_CREATE_LINK_DIAGNOSTIC)
            ->firstOrFail();
        $this->assertNull($log->order_id);
        $this->assertNull($log->payment_id);
        $this->assertSame(422, $log->http_status);
        $this->assertSame('Validation Error', $log->result);
        $this->assertArrayNotHasKey('webhookUrl', $log->request_metadata);
        $this->assertArrayNotHasKey('webhookSecret', $log->request_metadata);
        $this->assertSame('webhookUrl', $log->response_metadata['errors'][0]['path']);
        $this->assertSame('invalid_type', $log->response_metadata['errors'][0]['code']);
        $this->assertSame('string', $log->response_metadata['errors'][0]['expected']);
        $this->assertSame('Expected string', $log->response_metadata['errors'][0]['message']);
        $this->assertSame('webhookSecret', $log->response_metadata['errors'][1]['path']);
        $this->assertSame('[redacted]', $log->response_metadata['token']);
        $this->assertSame('[redacted]', $log->response_metadata['authorization']);

        $stored = json_encode([
            $log->toArray(),
            AdminActivityLog::query()->where('action', 'wayl.create_link_diagnostic_run')->first()?->toArray(),
        ]);
        $this->assertIsString($stored);
        $this->assertStringNotContainsString('super-secret-wayl-token', $stored);
        $this->assertStringNotContainsString('super-secret-webhook-key', $stored);
        $this->assertStringNotContainsString('X-WAYL-AUTHENTICATION', $stored);

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index'))
            ->assertOk()
            ->assertSee('Failed')
            ->assertSee('HTTP 422')
            ->assertSee('Webhook Required')
            ->assertSee('Yes')
            ->assertSee('Whoops, missing fields for [redacted]')
            ->assertSee('webhookUrl')
            ->assertSee('webhookSecret')
            ->assertSee('invalid_type')
            ->assertSee('Expected string')
            ->assertDontSee('super-secret-wayl-token')
            ->assertDontSee('super-secret-webhook-key')
            ->assertDontSee('X-WAYL-AUTHENTICATION');
    }

    public function test_201_diagnostic_is_reported_as_passed_without_webhook_requirement(): void
    {
        Http::fake([
            'https://wayl.test/api/v1/links' => Http::response([
                'success' => true,
                'message' => 'Link created successfully.',
                'data' => [
                    'id' => 'diagnostic-link-id',
                    'url' => 'https://checkout.thewayl.test/pay/DIAGNOSTIC',
                    'status' => 'Created',
                ],
            ], 201),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.wayl.diagnostics.create-link'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('payment_provider_logs', [
            'provider' => 'wayl',
            'event_type' => PaymentProviderLog::EVENT_CREATE_LINK_DIAGNOSTIC,
            'http_status' => 201,
            'result' => 'Success',
        ]);

        Http::assertSent(fn (Request $request): bool => ! array_key_exists('webhookUrl', $request->data())
            && ! array_key_exists('webhookSecret', $request->data())
        );

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index'))
            ->assertOk()
            ->assertSee('Passed')
            ->assertSee('HTTP 201')
            ->assertSee('Webhook Required')
            ->assertSee('No')
            ->assertSee('Link created successfully.');
    }

    public function test_diagnostic_normalizes_associative_provider_validation_errors(): void
    {
        Http::fake([
            'https://wayl.test/api/v1/links' => Http::response([
                'success' => false,
                'message' => 'Whoops, missing fields',
                'errors' => [
                    'webhookUrl' => ['Expected string'],
                    'webhookSecret' => ['Expected string'],
                ],
            ], 422),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.wayl.diagnostics.create-link'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $log = PaymentProviderLog::query()->latest('id')->firstOrFail();
        $this->assertSame([
            ['path' => 'webhookUrl', 'message' => 'Expected string'],
            ['path' => 'webhookSecret', 'message' => 'Expected string'],
        ], $log->response_metadata['_validation_errors']);

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index'))
            ->assertOk()
            ->assertSee('Webhook Required')
            ->assertSee('Yes')
            ->assertSee('webhookUrl')
            ->assertSee('webhookSecret');
    }

    public function test_tokens_and_webhook_secrets_are_never_rendered(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.wayl.index'));

        $response->assertOk()
            ->assertSee('API Token')
            ->assertSee('Configured')
            ->assertDontSee('super-secret-wayl-token')
            ->assertDontSee('super-secret-webhook-key')
            ->assertDontSee('X-WAYL-AUTHENTICATION')
            ->assertDontSee('WAYL_API_TOKEN')
            ->assertDontSee('WAYL_WEBHOOK_SECRET');
    }

    public function test_wayl_payment_traffic_is_listed_with_order_and_reference(): void
    {
        [$order, $payment] = $this->makePayment('paid', 'wayl-visible-reference');

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('wayl-visible-reference')
            ->assertSee('#'.$payment->id)
            ->assertSee('55,000 IQD');
    }

    public function test_payment_status_and_search_filters_work(): void
    {
        $this->makePayment('paid', 'wayl-paid-reference', 'YS-WAYL-PAID');
        $this->makePayment('failed', 'wayl-failed-reference', 'YS-WAYL-FAILED');

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index', ['status' => 'paid']))
            ->assertOk()
            ->assertSee('wayl-paid-reference')
            ->assertDontSee('wayl-failed-reference');

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index', ['search' => 'YS-WAYL-FAILED']))
            ->assertOk()
            ->assertSee('wayl-failed-reference')
            ->assertDontSee('wayl-paid-reference');
    }

    public function test_successful_health_check_is_logged_and_displayed(): void
    {
        Http::fake([
            'https://wayl.test/api/v1/links*' => Http::response([
                'success' => true,
                'data' => [],
                'message' => 'Authentication valid',
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.wayl.health'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_provider_logs', [
            'provider' => 'wayl',
            'event_type' => PaymentProviderLog::EVENT_HEALTH_CHECK,
            'http_status' => 200,
            'result' => 'Success',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index'))
            ->assertOk()
            ->assertSee('Connected')
            ->assertSee('HTTP 200');
    }

    public function test_health_failures_are_classified_without_exposing_provider_secrets(): void
    {
        $response = fn (int $status): array => [
            'success' => false,
            'message' => $status === 422 ? 'Whoops, missing fields' : 'Provider refused super-secret-wayl-token',
            'errors' => [['path' => 'webhookUrl', 'message' => 'Expected string']],
            'authorization' => 'Bearer super-secret-wayl-token',
        ];
        Http::fake([
            'https://wayl.test/api/v1/links*' => Http::sequence()
                ->push($response(401), 401)
                ->push($response(422), 422)
                ->push($response(500), 500),
        ]);

        foreach ([401 => 'Authentication Error', 422 => 'Validation Error', 500 => 'Provider Error'] as $status => $result) {
            $health = app(WaylPaymentService::class)->healthCheck();
            $this->assertFalse($health['connected']);
            $this->assertSame($status, $health['http_status']);

            $log = PaymentProviderLog::query()->latest('id')->firstOrFail();
            $this->assertSame($status, $log->http_status);
            $this->assertSame($result, $log->result);
            $this->assertStringNotContainsString('super-secret-wayl-token', (string) json_encode($log->toArray()));
            $this->assertSame('[redacted]', $log->response_metadata['authorization']);
        }
    }

    public function test_provider_log_never_stores_authorization_token_or_webhook_secret(): void
    {
        [$order, $payment] = $this->makePayment('pending', null);
        Http::fake([
            'https://wayl.test/api/v1/links' => Http::response([
                'success' => false,
                'message' => 'Whoops, missing fields',
                'token' => 'super-secret-wayl-token',
                'errors' => [
                    ['path' => 'webhookUrl', 'message' => 'Expected string'],
                    ['path' => 'webhookSecret', 'message' => 'Expected string'],
                ],
            ], 422),
        ]);

        try {
            app(WaylPaymentService::class)->createPayment($order, $payment);
            $this->fail('Expected WAYL provider validation exception.');
        } catch (WaylProviderException $exception) {
            $this->assertSame(422, $exception->httpStatus);
        }

        $log = PaymentProviderLog::query()->latest('id')->firstOrFail();
        $encoded = (string) json_encode($log->toArray());

        $this->assertStringNotContainsString('super-secret-wayl-token', $encoded);
        $this->assertStringNotContainsString('super-secret-webhook-key', $encoded);
        $this->assertStringNotContainsString('X-WAYL-AUTHENTICATION', $encoded);
        $this->assertSame('[redacted]', $log->request_metadata['webhookSecret']);
        $this->assertSame('[redacted]', $log->response_metadata['token']);
    }

    public function test_full_sanitized_wayl_422_validation_errors_are_available_to_admin(): void
    {
        [$order, $payment] = $this->makePayment('pending', null);
        Http::fake([
            'https://wayl.test/api/v1/links' => Http::response([
                'success' => false,
                'message' => 'Whoops, missing fields',
                'errors' => [
                    ['path' => 'webhookUrl', 'message' => 'Expected string'],
                    ['path' => 'redirectionUrl', 'message' => 'Expected a valid HTTPS URL'],
                ],
            ], 422),
        ]);

        try {
            app(WaylPaymentService::class)->createPayment($order, $payment);
        } catch (WaylProviderException $exception) {
            $this->assertCount(2, $exception->validationErrors);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.wayl.index'))
            ->assertOk()
            ->assertSee('Whoops, missing fields')
            ->assertSee('webhookUrl')
            ->assertSee('Expected string')
            ->assertSee('redirectionUrl')
            ->assertDontSee('super-secret-wayl-token')
            ->assertDontSee('super-secret-webhook-key');
    }

    public function test_existing_sms_and_whatsapp_admin_pages_remain_available(): void
    {
        config([
            'services.otpiq.whatsapp.admin_visible' => true,
            'services.otpiq.whatsapp.enabled' => false,
        ]);

        $this->actingAs($this->admin)->get(route('admin.messaging.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.whatsapp.index'))->assertOk();
    }

    /** @return array{Order, Payment} */
    private function makePayment(string $status, ?string $reference, ?string $orderNumber = null): array
    {
        $customer = User::factory()->create();
        $order = Order::forceCreate([
            'user_id' => $customer->id,
            'order_number' => $orderNumber ?: 'YS-WAYL-'.strtoupper(substr(md5((string) microtime(true)), 0, 8)),
            'subtotal_amount' => 55000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 55000,
            'total_amount' => 55000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'wayl',
            'payment_status' => $status === Payment::STATUS_PAID ? Order::PAYMENT_PAID : Order::PAYMENT_PENDING_PAYMENT,
            'delivery_address' => 'Baghdad test address',
            'delivery_city' => 'Baghdad',
            'delivery_phone' => '07700000000',
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'provider' => 'wayl',
            'method' => 'wayl',
            'status' => $status,
            'amount' => 55000,
            'currency' => 'IQD',
            'provider_payment_id' => 'wayl-link-'.$order->id,
            'provider_reference' => $reference,
            'return_url' => route('payments.return', ['payment' => '__payment__']),
            'verified_at' => $status === Payment::STATUS_PAID ? now() : null,
            'provider_response' => ['data' => ['status' => ucfirst($status)]],
        ]);

        return [$order, $payment];
    }
}
