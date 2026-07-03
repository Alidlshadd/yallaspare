<?php

namespace Tests\Feature;

use App\Services\Payments\Providers\FibPaymentService;
use App\Services\Payments\Providers\ZainCashPaymentService;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentWebhookTokenTest extends TestCase
{
    public function test_fib_webhook_token_is_accepted_from_header(): void
    {
        config(['services.fib.webhook_token' => 'wh-secret']);

        $request = Request::create('/api/payments/fib/webhook', 'POST');
        $request->headers->set('X-FIB-Webhook-Token', 'wh-secret');

        $this->assertTrue(app(FibPaymentService::class)->validateWebhook($request));
    }

    public function test_fib_webhook_token_is_accepted_from_generic_header(): void
    {
        config(['services.fib.webhook_token' => 'wh-secret']);

        $request = Request::create('/api/payments/fib/webhook', 'POST');
        $request->headers->set('X-Payment-Webhook-Token', 'wh-secret');

        $this->assertTrue(app(FibPaymentService::class)->validateWebhook($request));
    }

    public function test_fib_webhook_token_is_rejected_when_only_in_query_string(): void
    {
        config(['services.fib.webhook_token' => 'wh-secret']);

        // Tokens in the query string leak into access logs and proxies,
        // so they must not be accepted as authentication.
        $request = Request::create('/api/payments/fib/webhook?token=wh-secret', 'POST');

        $this->assertFalse(app(FibPaymentService::class)->validateWebhook($request));
    }

    public function test_zaincash_webhook_token_is_accepted_from_header(): void
    {
        config(['services.zaincash.webhook_token' => 'wh-secret']);

        $request = Request::create('/api/payments/zaincash/webhook', 'POST');
        $request->headers->set('X-ZainCash-Webhook-Token', 'wh-secret');

        $this->assertTrue(app(ZainCashPaymentService::class)->validateWebhook($request));
    }

    public function test_zaincash_webhook_token_is_rejected_when_only_in_query_string(): void
    {
        config(['services.zaincash.webhook_token' => 'wh-secret']);

        $request = Request::create('/api/payments/zaincash/webhook?token=wh-secret', 'POST');

        $this->assertFalse(app(ZainCashPaymentService::class)->validateWebhook($request));
    }

    public function test_fib_webhook_wrong_header_token_is_rejected(): void
    {
        config(['services.fib.webhook_token' => 'wh-secret']);

        $request = Request::create('/api/payments/fib/webhook', 'POST');
        $request->headers->set('X-FIB-Webhook-Token', 'wrong');

        $this->assertFalse(app(FibPaymentService::class)->validateWebhook($request));
    }
}
