<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteIconHeadTest extends TestCase
{
    use RefreshDatabase;

    private string $logoPath = 'settings/test-head-logo.png';

    protected function tearDown(): void
    {
        Storage::disk('public')->delete($this->logoPath);

        parent::tearDown();
    }

    public function test_storefront_head_publishes_favicon_manifest_and_social_preview_tags(): void
    {
        Storage::disk('public')->put($this->logoPath, $this->pngBytes());
        Setting::setMany([
            'site_name' => 'Yalla Spare',
            'site_logo' => $this->logoPath,
            'site_logo_version' => 'head-logo-version',
        ]);

        $response = $this->get(route('user.shop.home'));

        $response->assertOk();

        $head = $this->extractHead($response->getContent());

        $this->assertStringContainsString('rel="icon"', $head);
        $this->assertStringContainsString('favicon.ico?v=20260605', $head);
        $this->assertStringContainsString('favicon.png?v=20260605', $head);
        $this->assertStringContainsString('favicon-32x32.png?v=20260605', $head);
        $this->assertStringContainsString('favicon-16x16.png?v=20260605', $head);
        $this->assertStringContainsString('apple-touch-icon.png?v=20260605', $head);
        $this->assertStringContainsString('site.webmanifest?v=20260605', $head);
        $this->assertStringContainsString('/brand/logo?v=', $head);
        $this->assertStringContainsString('sv=head-logo-version', $head);
        $this->assertStringContainsString('property="og:image"', $head);
        $this->assertStringContainsString('icons/yallaspare-og-preview.png?v=20260605', $head);
        $this->assertStringContainsString('name="twitter:image"', $head);
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
            'manifest.json',
        ] as $path) {
            $fullPath = public_path($path);

            $this->assertFileExists($fullPath, $path . ' is missing.');
            $this->assertGreaterThan(0, filesize($fullPath), $path . ' is empty.');
        }

        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

        $this->assertSame('Yalla Spare', $manifest['name'] ?? null);
        $this->assertSame('/android-chrome-192x192.png?v=20260605', $manifest['icons'][0]['src'] ?? null);
        $this->assertSame('/android-chrome-512x512.png?v=20260605', $manifest['icons'][1]['src'] ?? null);
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
