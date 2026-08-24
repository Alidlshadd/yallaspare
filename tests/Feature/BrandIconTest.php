<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\BrandIcon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The icon endpoint is the single source every icon slot resolves to once a
 * logo is configured, so it has to render at exactly the size it was asked
 * for and refuse everything else.
 */
class BrandIconTest extends TestCase
{
    use RefreshDatabase;

    private string $logoPath = 'settings/brand-icon-test.png';

    protected function tearDown(): void
    {
        Storage::disk('public')->delete($this->logoPath);
        Storage::disk('local')->deleteDirectory('brand-icons');

        parent::tearDown();
    }

    public function test_each_supported_size_renders_a_square_png_of_that_size(): void
    {
        $this->withSiteLogo();

        foreach (BrandIcon::sizes() as $size) {
            $response = $this->get(route('brand.icon', ['size' => $size]));

            $response->assertOk();
            $this->assertSame('image/png', $response->headers->get('Content-Type'));

            $image = imagecreatefromstring($response->getContent());

            $this->assertNotFalse($image, "size {$size} did not decode as an image.");
            $this->assertSame($size, imagesx($image), "size {$size} rendered at the wrong width.");
            $this->assertSame($size, imagesy($image), "size {$size} rendered at the wrong height.");

            imagedestroy($image);
        }
    }

    /**
     * The tile is the brand, not the upload: a logo with transparent corners
     * still has to come back on navy rather than on nothing, or the icon
     * disappears against a dark browser chrome.
     */
    public function test_the_rendered_tile_is_brand_navy(): void
    {
        $this->withSiteLogo();

        $png = $this->get(route('brand.icon', ['size' => 32]))->assertOk()->getContent();
        $image = imagecreatefromstring($png);
        $corner = imagecolorsforindex($image, imagecolorat($image, 0, 0));

        $this->assertSame(7, $corner['red']);
        $this->assertSame(7, $corner['green']);
        $this->assertSame(64, $corner['blue']);

        imagedestroy($image);
    }

    public function test_an_unsupported_size_is_not_rendered(): void
    {
        $this->withSiteLogo();

        $this->get('/brand/icon-64.png')->assertNotFound();
        $this->get('/brand/icon-1024.png')->assertNotFound();
    }

    public function test_the_endpoint_is_absent_until_a_logo_is_configured(): void
    {
        Setting::setMany(['site_logo' => '', 'site_logo_version' => '']);

        $this->assertNull(BrandIcon::version());
        $this->get(route('brand.icon', ['size' => 32]))->assertNotFound();
    }

    /**
     * The version stamp is what busts every cached icon, so replacing the logo
     * has to move it — otherwise a browser keeps the old tab mark forever.
     */
    public function test_the_version_stamp_changes_when_the_logo_does(): void
    {
        $this->withSiteLogo();
        $before = BrandIcon::version();

        Setting::setValue('site_logo_version', 'a-later-upload');
        $after = BrandIcon::version();

        $this->assertNotNull($before);
        $this->assertNotSame($before, $after);
    }

    private function withSiteLogo(): void
    {
        Storage::disk('public')->put($this->logoPath, $this->pngBytes());

        Setting::setMany([
            'site_logo' => $this->logoPath,
            'site_logo_version' => 'brand-icon-version',
        ]);
    }

    /** A 4x4 opaque red square — enough to prove placement without a fixture file. */
    private function pngBytes(): string
    {
        $image = imagecreatetruecolor(4, 4);
        imagefilledrectangle($image, 0, 0, 4, 4, imagecolorallocate($image, 220, 30, 30));

        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }
}
