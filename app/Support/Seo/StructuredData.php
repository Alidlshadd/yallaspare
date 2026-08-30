<?php

namespace App\Support\Seo;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * The one place that builds schema.org nodes for the storefront.
 *
 * Every page used to assemble its own array inline, which is how a site ends
 * up describing the same shop three slightly different ways. These are the
 * shapes; where each one belongs is the page's business.
 *
 * Nodes come back without an "@context" of their own. The partial that renders
 * them adds one, and wraps several into a single "@graph" — one script tag per
 * page, never the same entity written twice.
 */
final class StructuredData
{
    /**
     * The shop itself.
     *
     * OnlineStore rather than plain Organization: Google asks for the most
     * specific subtype that fits, and this one sells things over the internet.
     *
     * Only what the shop actually knows about itself goes in. An address or a
     * phone number invented to fill a recommended field is worse than a
     * missing one — it is published, and people act on it.
     *
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $name = self::siteName();
        $logo = self::logoUrl();
        $email = trim((string) config('mail.support.address', ''));

        return array_filter([
            '@type' => 'OnlineStore',
            '@id' => self::organizationId(),
            'name' => $name,
            'url' => url('/'),
            'logo' => $logo,
            'image' => $logo,
            'email' => $email !== '' ? $email : null,
        ], static fn ($value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * The site the pages belong to, tied back to the shop that publishes it.
     *
     * No SearchAction here: the sitelinks search box it used to feed was
     * retired by Google, and markup for a feature that no longer exists is
     * just something else to keep correct.
     *
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::siteRoot().'#website',
            'name' => self::siteName(),
            'url' => url('/'),
            'publisher' => ['@id' => self::organizationId()],
        ];
    }

    /**
     * The trail from the home page to where the visitor is.
     *
     * @param  array<int, array{label: string, url: string}>  $trail
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $trail): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($trail)
                ->values()
                ->map(fn (array $crumb, int $index): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => (string) ($crumb['label'] ?? ''),
                    'item' => (string) ($crumb['url'] ?? '') ?: null,
                ], static fn ($value): bool => $value !== null && $value !== ''))
                ->all(),
        ];
    }

    /**
     * The trail as the crumbs a page already shows, from label => url pairs.
     *
     * @param  array<string, string>  $trail
     * @return array<int, array{label: string, url: string}>
     */
    public static function trailFromHome(array $trail): array
    {
        $crumbs = [['label' => __('Home'), 'url' => route('home')]];

        foreach ($trail as $label => $url) {
            $crumbs[] = ['label' => $label, 'url' => $url];
        }

        return $crumbs;
    }

    /**
     * What is on this page of a listing, in the order it is shown.
     *
     * The summary-page form — position and url per entry, with the detail on
     * the page each one points at. Only the page in hand: the list describes
     * what the visitor is looking at, not the whole catalogue behind the
     * pagination.
     *
     * @param  iterable<int, Product>  $products
     * @return array<string, mixed>|null
     */
    public static function productList(string $name, iterable $products, int $offset = 0, ?int $total = null): ?array
    {
        $items = Collection::make($products)
            ->values()
            ->map(fn (Product $product, int $index): array => [
                '@type' => 'ListItem',
                'position' => $offset + $index + 1,
                'name' => $product->localizedName(),
                'url' => route('shop.show', $product),
            ])
            ->all();

        // A list of one is not a list, and Google asks for at least two.
        if (count($items) < 2) {
            return null;
        }

        return array_filter([
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => $total ?? count($items),
            'itemListElement' => $items,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * A list of pages rather than of products — the categories board.
     *
     * @param  array<int, array{name: string, url: string}>  $entries
     * @return array<string, mixed>|null
     */
    public static function linkList(string $name, array $entries): ?array
    {
        $items = collect($entries)
            ->values()
            ->map(fn (array $entry, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => (string) $entry['name'],
                'url' => (string) $entry['url'],
            ])
            ->all();

        if (count($items) < 2) {
            return null;
        }

        return [
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => count($items),
            'itemListElement' => $items,
        ];
    }

    /**
     * One product, as a page a shopper can buy from.
     *
     * name, image and offers are what Google requires of a merchant listing,
     * and the price has to be above zero to qualify — so a part with no price
     * on it is described as a product without an offer rather than with an
     * offer of nothing.
     *
     * @param  array<int, string>  $images
     * @return array<string, mixed>
     */
    public static function product(
        Product $product,
        array $images,
        float $price,
        bool $inStock,
        string $url,
        ?float $averageRating = null,
        int $reviewCount = 0,
        ?string $description = null,
        ?string $categoryName = null,
    ): array {
        $schema = array_filter([
            '@type' => 'Product',
            'name' => $product->localizedName(),
            'description' => $description,
            'image' => array_values(array_filter($images)),
            'sku' => (string) ($product->sku ?? '') ?: null,
            'mpn' => (string) ($product->part_number ?: $product->oem_number ?: '') ?: null,
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => (string) $product->brand] : null,
            'category' => $categoryName,
        ], static fn ($value): bool => $value !== null && $value !== '' && $value !== []);

        if ($price > 0) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'url' => $url,
                'price' => number_format($price, self::currencyDecimals(), '.', ''),
                'priceCurrency' => self::currencyCode(),
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                // A reference, not a second copy of the shop. The node itself
                // lives on the home page.
                'seller' => ['@id' => self::organizationId()],
            ];
        }

        if ($reviewCount > 0 && $averageRating !== null && $averageRating > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($averageRating, 1, '.', ''),
                'reviewCount' => $reviewCount,
            ];
        }

        return $schema;
    }

    /**
     * The whole page's structured data as one JSON document.
     *
     * A single node is written on its own; several are wrapped in "@graph",
     * which is what keeps a page from carrying three script tags that each
     * re-declare the same context.
     *
     * @param  array<int, array<string, mixed>|null>  $nodes
     */
    public static function encode(array $nodes): string
    {
        $nodes = array_values(array_filter($nodes, static fn ($node): bool => is_array($node) && $node !== []));

        if ($nodes === []) {
            return '';
        }

        $document = count($nodes) === 1
            ? ['@context' => 'https://schema.org'] + $nodes[0]
            : ['@context' => 'https://schema.org', '@graph' => $nodes];

        return (string) json_encode(
            $document,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );
    }

    /**
     * The shop's identifier, written once so the node on the home page and
     * every reference to it from elsewhere cannot drift apart.
     */
    public static function organizationId(): string
    {
        return self::siteRoot().'#organization';
    }

    private static function siteRoot(): string
    {
        return rtrim(url('/'), '/').'/';
    }

    private static function siteName(): string
    {
        return (string) (Setting::getValue('site_name', '') ?: config('app.name', 'YallaSpare'));
    }

    private static function logoUrl(): string
    {
        $logo = trim((string) Setting::getValue('site_logo', ''));

        if ($logo === '') {
            return asset('icons/yallaspare-og-preview.png');
        }

        return str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')
            ? $logo
            : asset('storage/'.ltrim($logo, '/'));
    }

    private static function currencyCode(): string
    {
        return (string) (Setting::getValue('currency_code', 'IQD') ?: 'IQD');
    }

    private static function currencyDecimals(): int
    {
        return strtoupper(self::currencyCode()) === 'IQD' ? 0 : 2;
    }
}
