<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
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

    public function test_a_product_page_names_its_canonical_address_once(): void
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'is_active' => true,
        ]);

        $content = (string) $this->get(route('shop.show', $product).'?lang=ar')->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, '<link rel="canonical"'));
        $this->assertStringContainsString('<link rel="canonical" href="'.route('shop.show', $product).'">', $content);
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
