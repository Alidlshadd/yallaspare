<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\Security\WebhookSecurityService;
use App\Support\UserCommunication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_localhost_blocked(): void
    {
        config(['security.notification_webhooks.allowed_hosts' => ['localhost']]);

        $this->assertFalse(app(WebhookSecurityService::class)->isAllowed('https://localhost/hook'));
    }

    public function test_private_ip_blocked(): void
    {
        config(['security.notification_webhooks.allowed_hosts' => ['10.0.0.5']]);

        $this->assertFalse(app(WebhookSecurityService::class)->isAllowed('https://10.0.0.5/hook'));
    }

    public function test_metadata_endpoint_blocked(): void
    {
        config(['security.notification_webhooks.allowed_hosts' => ['169.254.169.254']]);

        $this->assertFalse(app(WebhookSecurityService::class)->isAllowed('https://169.254.169.254/latest/meta-data'));
    }

    public function test_dns_rebinding_host_is_blocked_when_not_allowlisted(): void
    {
        config(['security.notification_webhooks.allowed_hosts' => ['provider.example.test']]);

        $this->assertFalse(app(WebhookSecurityService::class)->isAllowed('https://rebind.example.test/hook'));
    }

    public function test_allowlisted_public_provider_works(): void
    {
        config(['security.notification_webhooks.allowed_hosts' => ['93.184.216.34']]);

        $this->assertTrue(app(WebhookSecurityService::class)->isAllowed('https://93.184.216.34/hook'));
    }

    public function test_redirect_to_private_endpoint_is_not_followed(): void
    {
        config(['security.notification_webhooks.allowed_hosts' => ['93.184.216.34']]);
        Setting::setMany(['sms_provider_webhook_url' => 'https://93.184.216.34/hook']);
        Http::fake([
            '93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data']),
            '169.254.169.254/*' => Http::response('metadata', 200),
        ]);

        $user = User::factory()->create([
            'sms_notifications' => true,
            'email_notifications' => false,
            'phone' => '+9647700000000',
        ]);
        $order = Order::query()->forceCreate([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . uniqid(),
            'subtotal_amount' => 10000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'grand_total' => 10000,
            'total_amount' => 10000,
            'status' => Order::STATUS_PROCESSING,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PAID,
            'delivery_address' => 'Street',
            'delivery_city' => 'City',
            'delivery_phone' => '123456789',
        ]);

        UserCommunication::sendOrderStatusUpdated($user, $order, Order::STATUS_PENDING, Order::STATUS_PROCESSING);

        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '169.254.169.254'));
    }
}
