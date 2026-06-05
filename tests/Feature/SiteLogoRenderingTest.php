<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
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

    public function test_brand_mark_renders_fallback_only_when_logo_url_is_missing(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-brand-mark
                brand="Yalla Spare"
                wrapper-class="app-logo-mark"
                img-class="h-full w-full object-contain"
                fallback-class="inline-flex h-full w-full items-center justify-center"
                fallback-text-class="text-xs"
            />
        BLADE);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('inline-flex h-full w-full items-center justify-center', $html);
        $this->assertStringNotContainsString('style="display:none"', $html);
    }

    public function test_admin_settings_preview_and_sidebar_include_uploaded_logo_url(): void
    {
        Storage::disk('public')->put($this->logoPath, $this->pngBytes());
        Setting::setMany([
            'site_name' => 'Yalla Spare',
            'site_logo' => $this->logoPath,
            'site_logo_version' => 'test-version',
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'admin_2fa.verified_user_id' => $admin->id,
                'auth.password_confirmed_at' => time(),
            ])
            ->actingAs($admin)
            ->get(route('admin.settings.edit'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, '/brand/logo?v='));
        $this->assertStringContainsString('sv=test-version', $html);
    }

    public function test_storefront_desktop_and_mobile_headers_include_uploaded_logo_url(): void
    {
        Storage::disk('public')->put($this->logoPath, $this->pngBytes());
        Setting::setMany([
            'site_name' => 'Yalla Spare',
            'site_logo' => $this->logoPath,
            'site_logo_version' => 'storefront-version',
        ]);

        $response = $this->get(route('user.shop.home'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, '/brand/logo?v='));
        $this->assertStringContainsString('sv=storefront-version', $html);
    }

    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
    }
}
