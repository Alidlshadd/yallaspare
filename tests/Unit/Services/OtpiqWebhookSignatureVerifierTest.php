<?php

namespace Tests\Unit\Services;

use App\Services\Otpiq\OtpiqWebhookSignatureVerifier;
use Tests\TestCase;

class OtpiqWebhookSignatureVerifierTest extends TestCase
{
    public function test_it_verifies_the_exact_raw_body_with_the_configured_secret(): void
    {
        $secret = 'unit-test-webhook-secret';
        $timestamp = '1786000000';
        $rawBody = '{"message":{"text":"hello"}}';
        config()->set('services.otpiq.webhook_secret', $secret);
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        $verifier = app(OtpiqWebhookSignatureVerifier::class);

        $this->assertTrue($verifier->isConfigured());
        $this->assertTrue($verifier->verify($rawBody, $timestamp, $signature));
        $this->assertFalse($verifier->verify($rawBody.' ', $timestamp, $signature));
    }

    public function test_it_fails_closed_when_secret_is_not_configured(): void
    {
        config()->set('services.otpiq.webhook_secret', null);

        $verifier = app(OtpiqWebhookSignatureVerifier::class);

        $this->assertFalse($verifier->isConfigured());
        $this->assertFalse($verifier->verify('{}', '1786000000', 'sha256=invalid'));
    }
}
