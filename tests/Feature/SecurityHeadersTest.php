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
            ->assertHeader('X-DNS-Prefetch-Control', 'off')
            ->assertHeader('X-Download-Options', 'noopen')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('Origin-Agent-Cluster', '?1')
            ->assertHeader('Content-Security-Policy');
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
