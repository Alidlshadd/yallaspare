<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteIconHeadTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_SEO_TITLE = 'YallaSpare | Auto Spare Parts Platform in Iraq';

    private const PUBLIC_SEO_DESCRIPTION = 'YallaSpare is an auto spare parts platform built for Iraq, helping customers find trusted parts, check vehicle compatibility, order easily, and get reliable support.';

    private const HEAD_ICON_VERSION = '20260824';

    private string $logoPath = 'settings/test-head-logo.png';

    protected function tearDown(): void
    {
        Storage::disk('public')->delete($this->logoPath);

        parent::tearDown();
    }

    /**
     * The packaged favicons declare explicit sizes. Left in the head alongside
     * the uploaded logo they win the browser's pick, which is why the tab kept
     * the old mark after an admin changed the logo. Once a logo exists every
     * slot has to be rendered from it instead.
     */
    public function test_an_uploaded_logo_replaces_every_packaged_icon(): void
    {
        $this->withSiteLogo();

        $head = $this->extractHead($this->get(route('user.shop.home'))->getContent());

        $this->assertStringContainsString('rel="icon"', $head);

        foreach ([16, 32, 48, 192] as $size) {
            $this->assertStringContainsString('/brand/icon-'.$size.'.png?v=', $head);
            $this->assertStringContainsString('sizes="'.$size.'x'.$size.'"', $head);
        }

        $this->assertStringContainsString('/brand/icon-180.png?v=', $head);
        $this->assertStringContainsString('rel="apple-touch-icon" sizes="180x180"', $head);

        foreach (['favicon.ico', 'favicon.png', 'favicon-32x32.png', 'favicon-16x16.png', 'apple-touch-icon.png'] as $packaged) {
            $this->assertStringNotContainsString($packaged, $head, $packaged.' still competes with the uploaded logo.');
        }

        // A square logo is not a 1200x630 banner; the card must not claim it is.
        $this->assertStringContainsString('property="og:image"', $head);
        $this->assertStringNotContainsString('icons/yallaspare-og-preview.png', $head);
        $this->assertStringNotContainsString('og:image:width', $head);
        $this->assertStringContainsString('name="twitter:card" content="summary"', $head);
    }

    public function test_packaged_icons_are_used_when_no_logo_is_set(): void
    {
        Setting::setMany(['site_name' => 'Yalla Spare', 'site_logo' => '', 'site_logo_version' => '']);

        $head = $this->extractHead($this->get(route('user.shop.home'))->getContent());
        $iconVersion = self::HEAD_ICON_VERSION;

        $this->assertStringContainsString('favicon.ico?v='.$iconVersion, $head);
        $this->assertStringContainsString('favicon-32x32.png?v='.$iconVersion, $head);
        $this->assertStringContainsString('favicon-16x16.png?v='.$iconVersion, $head);
        $this->assertStringContainsString('apple-touch-icon.png?v='.$iconVersion, $head);
        $this->assertStringContainsString('icons/yallaspare-og-preview.png?v='.$iconVersion, $head);
        $this->assertStringContainsString('og:image:width', $head);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $head);
        $this->assertStringNotContainsString('/brand/icon-', $head);
    }

    public function test_head_points_at_the_generated_manifest_not_the_packaged_file(): void
    {
        $head = $this->extractHead($this->get(route('user.shop.home'))->getContent());

        $this->assertStringContainsString(route('brand.manifest'), $head);
        $this->assertStringNotContainsString('site.webmanifest?v=', $head);
    }

    public function test_generated_manifest_serves_the_uploaded_logo(): void
    {
        $this->withSiteLogo();

        $response = $this->get(route('brand.manifest'));

        $response->assertOk();
        $this->assertStringContainsString('application/manifest+json', (string) $response->headers->get('Content-Type'));

        $manifest = $response->json();

        $this->assertSame('Yalla Spare', $manifest['name']);
        $this->assertCount(2, $manifest['icons']);
        $this->assertStringContainsString('/brand/icon-192.png?v=', $manifest['icons'][0]['src']);
        $this->assertSame('192x192', $manifest['icons'][0]['sizes']);
        $this->assertStringContainsString('/brand/icon-512.png?v=', $manifest['icons'][1]['src']);
        $this->assertSame('512x512', $manifest['icons'][1]['sizes']);
    }

    public function test_generated_manifest_falls_back_to_packaged_icons(): void
    {
        Setting::setMany(['site_name' => 'Yalla Spare', 'site_logo' => '', 'site_logo_version' => '']);

        $manifest = $this->get(route('brand.manifest'))->assertOk()->json();

        $this->assertCount(2, $manifest['icons']);
        $this->assertStringContainsString('android-chrome-192x192.png', $manifest['icons'][0]['src']);
        $this->assertStringContainsString('android-chrome-512x512.png', $manifest['icons'][1]['src']);
    }

    public function test_storefront_head_publishes_social_preview_and_seo_tags(): void
    {
        $this->withSiteLogo();

        $head = $this->extractHead($this->get(route('user.shop.home'))->getContent());

        $this->assertStringContainsString('name="twitter:image"', $head);
        $this->assertStringContainsString('<title>Yalla Spare</title>', $head);
        $this->assertStringContainsString('name="description" content="'.self::PUBLIC_SEO_DESCRIPTION.'"', $head);
        $this->assertStringContainsString('property="og:title" content="'.self::PUBLIC_SEO_TITLE.'"', $head);
        $this->assertStringContainsString('property="og:description" content="'.self::PUBLIC_SEO_DESCRIPTION.'"', $head);
        $this->assertStringContainsString('name="twitter:title" content="'.self::PUBLIC_SEO_TITLE.'"', $head);
        $this->assertStringContainsString('name="twitter:description" content="'.self::PUBLIC_SEO_DESCRIPTION.'"', $head);
    }

    private function withSiteLogo(): void
    {
        Storage::disk('public')->put($this->logoPath, $this->pngBytes());
        Setting::setMany([
            'site_name' => 'Yalla Spare',
            'site_logo' => $this->logoPath,
            'site_logo_version' => 'head-logo-version',
        ]);
    }

    public function test_public_head_does_not_reference_old_cube_fallback_assets(): void
    {
        $response = $this->get(route('user.shop.home'));

        $response->assertOk();

        $head = $this->extractHead($response->getContent());

        $this->assertStringNotContainsString('application-logo', $head);
        $this->assertStringNotContainsString('x-application-logo', $head);
        $this->assertStringNotContainsString('cube', strtolower($head));
        $this->assertStringNotContainsString('FpZKyFJyoHT9eP0EOZPvjEPkpe7Vzr5DA56RgTP3.png', $head);
    }

    public function test_static_icon_and_manifest_files_exist_and_are_not_empty(): void
    {
        foreach ([
            'favicon.ico',
            'favicon.png',
            'favicon-16x16.png',
            'favicon-32x32.png',
            'apple-touch-icon.png',
            'android-chrome-192x192.png',
            'android-chrome-512x512.png',
            'icons/yallaspare-og-preview.png',
            'site.webmanifest',
        ] as $path) {
            $fullPath = public_path($path);

            $this->assertFileExists($fullPath, $path.' is missing.');
            $this->assertGreaterThan(0, filesize($fullPath), $path.' is empty.');
        }

        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

        $this->assertSame('YallaSpare', $manifest['name'] ?? null);
        $this->assertSame('/android-chrome-192x192.png?v='.self::HEAD_ICON_VERSION, $manifest['icons'][0]['src'] ?? null);
        $this->assertSame('/android-chrome-512x512.png?v='.self::HEAD_ICON_VERSION, $manifest['icons'][1]['src'] ?? null);
    }

    private function extractHead(string $html): string
    {
        preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $matches);

        return $matches[1] ?? '';
    }

    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
    }
}
