<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    private const LOCALES = [
        'en' => 'en',
        'ar' => 'ar',
        'ku' => 'ckb',
    ];

    private const STATIC_ROUTES = [
        ['url' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
        ['url' => '/shop', 'changefreq' => 'daily', 'priority' => '0.9'],
        ['url' => '/categories', 'changefreq' => 'daily', 'priority' => '0.8'],
        ['url' => '/privacy-policy', 'changefreq' => 'yearly', 'priority' => '0.3'],
        ['url' => '/terms', 'changefreq' => 'yearly', 'priority' => '0.3'],
        ['url' => '/support', 'changefreq' => 'monthly', 'priority' => '0.4'],
        ['url' => '/about-us', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['url' => '/contact', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['url' => '/return-exchange', 'changefreq' => 'yearly', 'priority' => '0.3'],
        ['url' => '/shipping-delivery', 'changefreq' => 'yearly', 'priority' => '0.3'],
        ['url' => '/distance-sales-agreement', 'changefreq' => 'yearly', 'priority' => '0.3'],
    ];

    public function index(): Response
    {
        $entries = [];

        foreach (self::STATIC_ROUTES as $route) {
            $entries[] = [
                'loc' => url($route['url']),
                'lastmod' => null,
                'changefreq' => $route['changefreq'],
                'priority' => $route['priority'],
            ];
        }

        if (Schema::hasTable('categories')) {
            foreach (Category::query()->select(['id', 'slug', 'updated_at'])->get() as $category) {
                $slug = $category->slug ?: (string) $category->id;
                $entries[] = [
                    'loc' => url('/categories/' . $slug),
                    'lastmod' => optional($category->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
        }

        if (Schema::hasTable('products')) {
            $products = Product::query()
                ->where('is_active', true)
                ->select(['id', 'slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->limit(5000)
                ->get();

            foreach ($products as $product) {
                $slug = $product->slug ?: (string) $product->id;
                $entries[] = [
                    'loc' => url('/shop/products/' . $slug),
                    'lastmod' => optional($product->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ];
            }
        }

        $xml = $this->renderXml($entries);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * @param  array<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string}>  $entries
     */
    private function renderXml(array $entries): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES) . '</loc>';
            if ($entry['lastmod'] !== null) {
                $lines[] = '    <lastmod>' . htmlspecialchars($entry['lastmod'], ENT_XML1 | ENT_QUOTES) . '</lastmod>';
            }
            $lines[] = '    <changefreq>' . $entry['changefreq'] . '</changefreq>';
            $lines[] = '    <priority>' . $entry['priority'] . '</priority>';

            foreach (self::LOCALES as $appLocale => $hreflang) {
                $separator = str_contains($entry['loc'], '?') ? '&amp;' : '?';
                $href = $entry['loc'] . $separator . 'lang=' . $appLocale;
                $lines[] = '    <xhtml:link rel="alternate" hreflang="' . $hreflang . '" href="' . htmlspecialchars($href, ENT_XML1 | ENT_QUOTES) . '" />';
            }
            $lines[] = '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES) . '" />';

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }
}
