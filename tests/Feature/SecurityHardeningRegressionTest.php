<?php

namespace Tests\Feature;

use App\Jobs\SendEmailBroadcastJob;
use App\Models\AdminActivityLog;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\EmailBroadcast;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\UserAddress;
use App\Notifications\AdminTwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use ReflectionClass;
use Tests\TestCase;

class SecurityHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_manager_cannot_promote_self_to_super_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_USERS_MANAGE],
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update-role', $admin), [
                'role' => User::ROLE_SUPER_ADMIN,
            ])
            ->assertSessionHas('error');

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'security.role_permission_change_blocked',
            'subject_id' => $admin->id,
        ]);
    }

    public function test_mobile_login_token_cannot_call_admin_api_without_admin_mobile_ability(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock_quantity' => 2]);
        $admin = User::factory()->create([
            'role' => User::ROLE_PRODUCT_MANAGER,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk()->json('token');

        $this->withToken($token)
            ->patchJson("/api/mobile/admin/products/{$product->id}", [
                'stock_quantity' => 99,
            ])
            ->assertForbidden();

        $this->assertSame(2, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'name' => 'mobile',
        ]);
        $this->assertNotNull($admin->tokens()->first()?->expires_at);
    }

    public function test_mobile_admin_step_up_issues_dedicated_admin_mobile_token(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk()->json('token');

        $this->withToken($token)
            ->postJson('/api/mobile/admin/step-up')
            ->assertOk();

        $code = null;
        Notification::assertSentTo($admin, AdminTwoFactorCode::class, function (AdminTwoFactorCode $notification) use (&$code) {
            $reflection = new ReflectionClass($notification);
            $property = $reflection->getProperty('code');
            $property->setAccessible(true);
            $code = (string) $property->getValue($notification);

            return $code !== '';
        });

        $adminToken = $this->withToken($token)
            ->postJson('/api/mobile/admin/step-up/verify', ['code' => $code])
            ->assertOk()
            ->json('token');

        $this->assertTrue($admin->fresh()->hasPermission(User::PERMISSION_DASHBOARD_VIEW));
        $this->assertTrue($admin->tokens()->where('name', 'mobile-admin')->latest('id')->first()?->can('admin:mobile'));

        $this->app['auth']->forgetGuards();

        $this->withToken($adminToken)
            ->getJson('/api/mobile/admin/dashboard')
            ->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'name' => 'mobile-admin',
        ]);
    }

    public function test_normal_mobile_user_cannot_request_admin_step_up(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->json('token');

        $this->withToken($token)
            ->postJson('/api/mobile/admin/step-up')
            ->assertForbidden();
    }

    public function test_mobile_checkout_uses_shared_checkout_side_effects(): void
    {
        [$user, $address, $product] = $this->mobileCheckoutContext();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout', ['address_id' => $address->id])
            ->assertOk();

        $order = Order::query()->with('statusHistory')->firstOrFail();
        $this->assertSame($user->id, (int) $order->user_id);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('type', InventoryMovement::TYPE_OUT)->count());
        $this->assertSame(Order::STATUS_PENDING, (string) $order->status);
        $this->assertSame(1, $order->statusHistory()->count());
    }

    public function test_manual_payment_update_requires_finance_permission(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ORDER_MANAGER,
            'permissions' => [User::PERMISSION_ORDERS_MANAGE],
        ]);
        $order = $this->orderFor(User::factory()->create(), ['payment_status' => Order::PAYMENT_PENDING]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-payment', $order), [
                'payment_status' => Order::PAYMENT_PAID,
            ])
            ->assertForbidden();

        $this->assertSame(Order::PAYMENT_PENDING, (string) $order->fresh()->payment_status);
    }

    public function test_refund_requires_finance_permission_and_paid_order(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ORDER_MANAGER,
            'permissions' => [User::PERMISSION_ORDERS_MANAGE],
        ]);
        $order = $this->orderFor(User::factory()->create(), [
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_PAID,
            'grand_total' => 10000,
            'total_amount' => 10000,
        ]);
        $return = ReturnRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'type' => 'refund',
            'status' => ReturnRequest::STATUS_REQUESTED,
            'reason' => 'test',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.returns.update', $return), [
                'status' => ReturnRequest::STATUS_REFUNDED,
                'refund_amount' => 5000,
            ])
            ->assertForbidden();

        $this->assertSame(ReturnRequest::STATUS_REQUESTED, (string) $return->fresh()->status);
        $this->assertSame(Order::PAYMENT_PAID, (string) $order->fresh()->payment_status);
    }

    public function test_email_crlf_payload_is_rejected_before_validation(): void
    {
        $this->post('/register', [
            'name' => 'Attacker',
            'email' => "attacker@example.test\r\nBcc: victim@example.test",
            'phone' => null,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertStatus(422);
    }

    public function test_product_json_ld_escapes_script_breakout_payload(): void
    {
        $category = Category::factory()->create();
        $payload = '</script><script>alert(1)</script>';
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Brake ' . $payload,
            'name_ar' => 'Brake',
            'name_ku' => 'Brake',
            'is_active' => true,
        ]);

        $response = $this->get(route('shop.show', $product))->assertOk();

        $response->assertDontSee($payload, false);
        $response->assertSee('\u003C/script\u003E\u003Cscript\u003Ealert(1)\u003C/script\u003E', false);
    }

    public function test_admin_notification_shell_does_not_template_untrusted_fields_with_inner_html(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('container.innerHTML = items.map', false)
            ->assertDontSee('${item.title}', false)
            ->assertDontSee('${item.meta', false);
    }

    public function test_admin_orders_update_route_changes_status_without_server_error(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ORDER_MANAGER,
            'permissions' => [User::PERMISSION_ORDERS_MANAGE],
            'email_verified_at' => now(),
        ]);
        $order = $this->orderFor(User::factory()->create(), [
            'status' => Order::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => Order::STATUS_PROCESSING,
            ])
            ->assertRedirect();

        $this->assertSame(Order::STATUS_PROCESSING, (string) $order->fresh()->status);
    }

    public function test_normal_user_cannot_access_admin_orders_update_route(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER, 'email_verified_at' => now()]);
        $order = $this->orderFor($user);

        $this->actingAs($user)
            ->patch(route('admin.orders.update', $order), [
                'status' => Order::STATUS_PROCESSING,
            ])
            ->assertForbidden();

        $this->assertSame(Order::STATUS_PENDING, (string) $order->fresh()->status);
    }

    public function test_email_broadcast_job_revalidates_admin_permission(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_USER,
            'permissions' => [],
        ]);
        $broadcast = EmailBroadcast::query()->create([
            'admin_id' => $admin->id,
            'audience_type' => EmailBroadcast::AUDIENCE_ALL,
            'purpose' => EmailBroadcast::PURPOSE_OPERATIONAL,
            'subject' => 'Notice',
            'message' => 'Body',
            'status' => EmailBroadcast::STATUS_QUEUED,
            'recipient_count' => 1,
        ]);

        SendEmailBroadcastJob::dispatchSync($broadcast->id);

        $this->assertSame(EmailBroadcast::STATUS_FAILED, (string) $broadcast->fresh()->status);
    }

    private function mobileCheckoutContext(): array
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 10000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'IQ',
            'city' => 'Erbil',
            'address_line1' => '100 Test Street',
            'phone' => '+964 770 000 0000',
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

    private function orderFor(User $user, array $overrides = []): Order
    {
        return Order::query()->forceCreate(array_merge([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . uniqid(),
            'subtotal_amount' => 10000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 10000,
            'total_amount' => 10000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => 'Street',
            'delivery_city' => 'City',
            'delivery_phone' => '123456789',
        ], $overrides));
    }
}
