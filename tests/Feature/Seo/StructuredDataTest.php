<?php

namespace Tests\Feature\Seo;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Support\Seo\StructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Structured data is written for a reader nobody in the shop ever sees, so a
 * mistake in it is silent: the page looks right, and the rich result simply
 * never appears. These read the pages the way that reader does.
 */
class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_describes_the_shop_once(): void
    {
        $graph = $this->schemaNodes($this->get(route('user.shop.home'))->assertOk()->getContent());

        $shop = $this->nodeOfType($graph, 'OnlineStore');
        $this->assertNotNull($shop, 'The home page should carry the shop as an OnlineStore.');
        $this->assertSame(StructuredData::organizationId(), $shop['@id']);
        $this->assertNotEmpty($shop['name']);
        $this->assertNotEmpty($shop['logo']);

        $site = $this->nodeOfType($graph, 'WebSite');
        $this->assertNotNull($site);
        $this->assertSame(StructuredData::organizationId(), $site['publisher']['@id']);

        // Retired by Google; markup for a feature that no longer exists is
        // only something else to keep correct.
        $this->assertStringNotContainsString('SearchAction', json_encode($graph));
    }

    public function test_the_shop_is_described_on_the_home_page_and_nowhere_else(): void
    {
        $product = $this->product();

        foreach ([route('shop.index'), route('shop.show', $product), route('categories.index')] as $url) {
            $graph = $this->schemaNodes($this->get($url)->assertOk()->getContent());

            $this->assertNull(
                $this->nodeOfType($graph, 'OnlineStore'),
                "The shop entry should not be repeated on {$url}."
            );
        }
    }

    public function test_a_product_page_carries_what_a_merchant_listing_requires(): void
    {
        $product = $this->product(['price' => 12500, 'stock_quantity' => 4, 'sku' => 'BRK-9910']);

        $graph = $this->schemaNodes($this->get(route('shop.show', $product))->assertOk()->getContent());
        $schema = $this->nodeOfType($graph, 'Product');

        $this->assertNotNull($schema);

        // Google's required set for a merchant listing: name, image, and an
        // offer carrying a price above zero with an ISO currency code.
        $this->assertNotEmpty($schema['name']);
        $this->assertNotEmpty($schema['image']);
        $this->assertIsArray($schema['image']);
        $this->assertSame('12500', $schema['offers']['price']);
        $this->assertSame('IQD', $schema['offers']['priceCurrency']);
        $this->assertGreaterThan(0, (float) $schema['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $schema['offers']['availability']);
        $this->assertSame('BRK-9910', $schema['sku']);

        // The seller is a reference to the shop, not a second copy of it.
        $this->assertSame(StructuredData::organizationId(), $schema['offers']['seller']['@id']);
    }

    public function test_a_product_nobody_can_buy_is_not_offered_for_nothing(): void
    {
        $product = $this->product(['price' => 0]);

        $graph = $this->schemaNodes($this->get(route('shop.show', $product))->assertOk()->getContent());
        $schema = $this->nodeOfType($graph, 'Product');

        $this->assertNotNull($schema);
        $this->assertArrayNotHasKey('offers', $schema, 'A merchant listing needs a price above zero.');
    }

    public function test_an_out_of_stock_product_says_so(): void
    {
        $product = $this->product(['stock_quantity' => 0]);

        $graph = $this->schemaNodes($this->get(route('shop.show', $product))->assertOk()->getContent());

        $this->assertSame(
            'https://schema.org/OutOfStock',
            $this->nodeOfType($graph, 'Product')['offers']['availability']
        );
    }

    public function test_a_product_page_carries_the_trail_it_shows(): void
    {
        $product = $this->product();

        $graph = $this->schemaNodes($this->get(route('shop.show', $product))->assertOk()->getContent());
        $crumbs = $this->nodeOfType($graph, 'BreadcrumbList');

        $this->assertNotNull($crumbs);
        $positions = array_column($crumbs['itemListElement'], 'position');
        $this->assertSame(range(1, count($positions)), $positions, 'Positions must run 1..n in order.');

        // The page the visitor is already on needs no link of its own.
        $this->assertArrayNotHasKey('item', end($crumbs['itemListElement']));
    }

    public function test_the_shop_listing_lists_what_is_on_the_page(): void
    {
        $this->product(['name_en' => 'Brake Pad']);
        $this->product(['name_en' => 'Oil Filter']);

        $graph = $this->schemaNodes($this->get(route('shop.index'))->assertOk()->getContent());
        $list = $this->nodeOfType($graph, 'ItemList');

        $this->assertNotNull($list);
        $this->assertGreaterThanOrEqual(2, count($list['itemListElement']));

        foreach ($list['itemListElement'] as $entry) {
            $this->assertSame('ListItem', $entry['@type']);
            $this->assertArrayHasKey('position', $entry);
            $this->assertStringStartsWith(url('/'), $entry['url']);
        }

        $urls = array_column($list['itemListElement'], 'url');
        $this->assertSame($urls, array_unique($urls), 'Every url in a list has to be distinct.');
    }

    public function test_a_page_with_one_product_carries_no_list(): void
    {
        $this->product();

        $graph = $this->schemaNodes($this->get(route('shop.index'))->assertOk()->getContent());

        // Google asks for at least two entries; one is not a list.
        $this->assertNull($this->nodeOfType($graph, 'ItemList'));
    }

    public function test_every_page_emits_exactly_one_structured_data_block(): void
    {
        $product = $this->product();
        $this->product();

        $pages = [
            route('user.shop.home'),
            route('shop.index'),
            route('shop.show', $product),
            route('categories.index'),
        ];

        foreach ($pages as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(
                1,
                substr_count($html, 'application/ld+json'),
                "{$url} should carry one structured data block, not several."
            );
        }
    }

    public function test_the_structured_data_is_valid_json_on_every_page(): void
    {
        $product = $this->product();
        $this->product();

        foreach ([route('user.shop.home'), route('shop.index'), route('shop.show', $product), route('categories.index')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $raw = $this->rawSchema($html);

            $this->assertNotSame('', $raw, "{$url} carries no structured data at all.");
            $this->assertIsArray(json_decode($raw, true), "{$url} carries invalid JSON: ".json_last_error_msg());
            $this->assertSame(JSON_ERROR_NONE, json_last_error());
        }
    }

    public function test_a_landing_page_still_carries_its_trail_and_list(): void
    {
        $brand = ProductBrand::query()->create(['name' => 'Bosch', 'slug' => 'bosch', 'is_active' => true]);
        $this->product(['product_brand_id' => $brand->id, 'name_en' => 'Brake Pad']);
        $this->product(['product_brand_id' => $brand->id, 'name_en' => 'Spark Plug']);

        $graph = $this->schemaNodes($this->get(route('catalog.brand', $brand->slug))->assertOk()->getContent());

        $this->assertNotNull($this->nodeOfType($graph, 'BreadcrumbList'));
        $this->assertNotNull($this->nodeOfType($graph, 'ItemList'));
    }

    /**
     * Every node on the page, whether it came alone or inside an @graph.
     *
     * @return array<int, array<string, mixed>>
     */
    private function schemaNodes(string $html): array
    {
        $raw = $this->rawSchema($html);

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        return isset($decoded['@graph']) ? $decoded['@graph'] : [$decoded];
    }

    private function rawSchema(string $html): string
    {
        if (preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $matches) !== 1) {
            return '';
        }

        return trim($matches[1]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<string, mixed>|null
     */
    private function nodeOfType(array $nodes, string $type): ?array
    {
        foreach ($nodes as $node) {
            if (is_array($node) && ($node['@type'] ?? null) === $type) {
                return $node;
            }
        }

        return null;
    }

    private function product(array $attributes = []): Product
    {
        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1, 'name_en' => 'Brakes']);
        }

        return Product::factory()->create(array_merge([
            'category_id' => 1,
            'is_active' => true,
            'price' => 10000,
            'stock_quantity' => 5,
        ], $attributes));
    }
}
