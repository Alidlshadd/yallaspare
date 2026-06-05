<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Branding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteLogoRenderingTest extends TestCase
{
    use RefreshDatabase;

    private string $logoPath = 'settings/test-site-logo.png';

    protected function tearDown(): void
    {
        Storage::disk('public')->delete($this->logoPath);

        parent::tearDown();
    }

    public function test_uploaded_site_logo_uses_brand_logo_endpoint_instead_of_storage_symlink_url(): void
    {
        Storage::disk('public')->put($this->logoPath, $this->pngBytes());
        Setting::setValue('site_logo', $this->logoPath);

        $url = Branding::logoUrlFromValue($this->logoPath);

        $this->assertIsString($url);
        $this->assertStringStartsWith('/brand/logo?v=', $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_brand_logo_endpoint_serves_saved_site_logo_from_public_disk(): void
    {
        Storage::disk('public')->put($this->logoPath, $this->pngBytes());
        Setting::setValue('site_logo', $this->logoPath);

        $response = $this->get(route('brand.logo'));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
        $this->assertSame(
            $this->pngBytes(),
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_brand_mark_renders_uploaded_logo_image_and_keeps_fallback_hidden(): void
    {
        $logoUrl = '/brand/logo?v=test';

        $html = Blade::render(<<<'BLADE'
            <x-brand-mark
                :logo-url="$logoUrl"
                brand="Yalla Spare"
                wrapper-class="app-logo-mark"
                img-class="h-full w-full object-contain"
                fallback-class="inline-flex h-full w-full items-center justify-center"
                fallback-text-class="text-xs"
            />
        BLADE, ['logoUrl' => $logoUrl]);

        $this->assertStringContainsString('src="/brand/logo?v=test"', $html);
        $this->assertStringContainsString('alt="Yalla Spare logo"', $html);
        $this->assertStringContainsString('object-contain', $html);
        $this->assertStringContainsString('style="display:none"', $html);
    }

    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
    }
}
