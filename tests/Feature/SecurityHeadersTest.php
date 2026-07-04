<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_sent_on_web_responses(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-XSS-Protection', '0')
            ->assertHeader('X-DNS-Prefetch-Control', 'off')
            ->assertHeader('X-Download-Options', 'noopen')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('Origin-Agent-Cluster', '?1')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_enforced_csp_script_src_uses_nonce_and_strict_dynamic(): void
    {
        $response = $this->get('/');
        $csp = (string) $response->headers->get('Content-Security-Policy');

        preg_match('/script-src ([^;]+)/', $csp, $matches);
        $scriptSrc = $matches[1] ?? '';

        $this->assertMatchesRegularExpression("/'nonce-[0-9a-f]{32}'/", $scriptSrc);
        $this->assertStringContainsString("'strict-dynamic'", $scriptSrc);
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSrc);
        // Alpine's CSP build removed the need for eval (Faz 5C).
        $this->assertStringNotContainsString("'unsafe-eval'", $scriptSrc);

        // Reporting moved onto the enforced policy; the Report-Only
        // rollout header is retired.
        $this->assertStringContainsString('report-uri /csp-report', $csp);
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_csp_img_src_is_restricted_to_local_sources(): void
    {
        $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("img-src 'self' data: blob:", $csp);
        $this->assertStringNotContainsString('img-src \'self\' data: blob: https:', $csp);
    }

    public function test_sensitive_pages_are_not_cached(): void
    {
        $response = $this->get('/login')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');

        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }
}
