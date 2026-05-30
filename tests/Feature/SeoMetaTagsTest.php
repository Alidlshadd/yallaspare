<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetaTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_page_emits_canonical_link(): void
    {
        $response = $this->get('/shop');

        $response->assertOk();
        $response->assertSee('<link rel="canonical"', false);
    }

    public function test_shop_page_emits_three_hreflang_alternates(): void
    {
        $response = $this->get('/shop');

        $content = $response->getContent();
        $this->assertStringContainsString('hreflang="en"', $content);
        $this->assertStringContainsString('hreflang="ar"', $content);
        $this->assertStringContainsString('hreflang="ckb"', $content);
        $this->assertStringContainsString('hreflang="x-default"', $content);
    }

    public function test_shop_page_emits_og_locale_for_default_english(): void
    {
        $response = $this->get('/shop');

        $response->assertSee('property="og:locale"', false);
        $response->assertSee('content="en_US"', false);
    }

    public function test_arabic_request_emits_arabic_og_locale(): void
    {
        $response = $this->get('/shop?lang=ar');

        $response->assertSee('content="ar_IQ"', false);
    }
}
