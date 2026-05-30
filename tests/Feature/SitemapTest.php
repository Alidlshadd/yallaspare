<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_endpoint_returns_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_sitemap_includes_static_routes(): void
    {
        $response = $this->get('/sitemap.xml');

        $xml = $response->getContent();
        $this->assertStringContainsString('<loc>' . url('/') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/shop') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/categories') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/privacy-policy') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . url('/terms') . '</loc>', $xml);
    }

    public function test_sitemap_includes_active_categories(): void
    {
        Category::factory()->create(['slug' => 'brakes', 'name_en' => 'Brakes']);

        $response = $this->get('/sitemap.xml');

        $this->assertStringContainsString('/categories/brakes', $response->getContent());
    }

    public function test_sitemap_includes_active_products(): void
    {
        Category::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $response = $this->get('/sitemap.xml');

        $this->assertStringContainsString('/shop/products/' . $product->slug, $response->getContent());
    }

    public function test_sitemap_emits_hreflang_alternates_per_url(): void
    {
        $response = $this->get('/sitemap.xml');

        $xml = $response->getContent();
        $this->assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $xml);
        $this->assertStringContainsString('hreflang="en"', $xml);
        $this->assertStringContainsString('hreflang="ar"', $xml);
        $this->assertStringContainsString('hreflang="ckb"', $xml);
    }
}
