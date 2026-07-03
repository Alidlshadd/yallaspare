<?php

namespace Tests\Feature;

use Tests\TestCase;

class LanguageSwitchRedirectTest extends TestCase
{
    public function test_language_switch_allows_same_origin_redirect_to(): void
    {
        $target = url('/shop');

        $response = $this->post(route('language.switch', ['locale' => 'ar']), [
            'redirect_to' => $target,
        ]);

        $response->assertRedirect($target);
    }

    public function test_language_switch_rejects_lookalike_external_host_in_redirect_to(): void
    {
        // url('/') has no trailing slash, so a naive prefix check would accept
        // http://localhost.evil.com as "same origin".
        $malicious = url('/') . '.evil.com/phish';

        $response = $this->post(route('language.switch', ['locale' => 'ar']), [
            'redirect_to' => $malicious,
        ]);

        $response->assertRedirect();
        $this->assertNotSame($malicious, $response->headers->get('Location'));
    }

    public function test_language_switch_rejects_lookalike_external_port_in_redirect_to(): void
    {
        $malicious = url('/') . ':8080/phish';

        $response = $this->post(route('language.switch', ['locale' => 'ar']), [
            'redirect_to' => $malicious,
        ]);

        $response->assertRedirect();
        $this->assertNotSame($malicious, $response->headers->get('Location'));
    }

    public function test_language_switch_allows_base_url_with_query_string(): void
    {
        $target = url('/') . '?page=2';

        $response = $this->post(route('language.switch', ['locale' => 'ar']), [
            'redirect_to' => $target,
        ]);

        $response->assertRedirect($target);
    }
}
