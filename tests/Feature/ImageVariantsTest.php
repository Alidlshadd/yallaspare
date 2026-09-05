<?php

namespace Tests\Feature;

use App\Support\ImageVariants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ImageVariants::flushMemo();
    }

    protected function tearDown(): void
    {
        ImageVariants::flushMemo();

        parent::tearDown();
    }

    public function test_the_original_is_served_while_no_variant_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/photo.png', 'png');

        $this->assertSame(
            asset('storage/products/photo.png'),
            ImageVariants::url('products/photo.png', 400)
        );
    }

    public function test_a_generated_variant_is_preferred_over_the_original(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/photo.png', 'png');
        Storage::disk('public')->put('variants/400/products/photo.webp', 'webp');

        $this->assertSame(
            asset('storage/variants/400/products/photo.webp'),
            ImageVariants::url('products/photo.png', 400)
        );
    }

    public function test_a_url_built_with_asset_is_accepted_as_well_as_a_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('variants/400/products/photo.webp', 'webp');

        $this->assertSame(
            asset('storage/variants/400/products/photo.webp'),
            ImageVariants::url(asset('storage/products/photo.png'), 400)
        );
    }

    public function test_a_width_nobody_generates_falls_back_to_the_original(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('variants/400/products/photo.webp', 'webp');

        $this->assertSame(
            asset('storage/products/photo.png'),
            ImageVariants::url('products/photo.png', 123)
        );
    }

    public function test_a_remote_image_is_left_alone(): void
    {
        Storage::fake('public');

        $this->assertSame(
            'https://cdn.example.com/photo.png',
            ImageVariants::url('https://cdn.example.com/photo.png', 400)
        );
    }

    public function test_an_empty_path_resolves_to_nothing(): void
    {
        $this->assertNull(ImageVariants::url(null, 400));
        $this->assertNull(ImageVariants::url('   ', 400));
    }

    public function test_the_command_writes_a_smaller_webp_and_keeps_transparency(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The gd extension is required to generate variants.');
        }

        // Faked, always. Storage::fake points the disk at a scratch directory,
        // so the command reads and writes real files through a real path()
        // without any chance of touching storage/app/public.
        Storage::fake('public');
        $disk = Storage::disk('public');

        $source = imagecreatetruecolor(1200, 900);
        imagealphablending($source, false);
        imagesavealpha($source, true);
        imagefilledrectangle($source, 0, 0, 1199, 899, imagecolorallocatealpha($source, 0, 0, 0, 127));
        imagefilledellipse($source, 600, 450, 600, 600, imagecolorallocate($source, 200, 30, 30));

        ob_start();
        imagepng($source);
        $png = (string) ob_get_clean();
        imagedestroy($source);

        $disk->put('products/cutout.png', $png);

        $this->artisan('images:variants', ['--dir' => ['products']])->assertSuccessful();

        $variant = 'variants/400/products/cutout.webp';
        $this->assertTrue($disk->exists($variant));
        $this->assertLessThan(strlen($png), strlen((string) $disk->get($variant)));

        $image = imagecreatefromwebp($disk->path($variant));
        $this->assertSame(400, imagesx($image));
        $this->assertSame(300, imagesy($image));
        // The cut-out background must not come back as a black box.
        $this->assertSame(127, (imagecolorat($image, 2, 2) >> 24) & 0x7F);
        $this->assertSame(0, (imagecolorat($image, 200, 150) >> 24) & 0x7F);
        imagedestroy($image);
    }

    public function test_an_image_already_smaller_than_the_target_is_left_alone(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The gd extension is required to generate variants.');
        }

        Storage::fake('public');
        $disk = Storage::disk('public');

        $source = imagecreatetruecolor(200, 200);
        ob_start();
        imagepng($source);
        $png = (string) ob_get_clean();
        imagedestroy($source);

        $disk->put('categories/small.png', $png);

        $this->artisan('images:variants', ['--dir' => ['categories']])->assertSuccessful();

        // Re-encoding a 200px file to 400px would only make it heavier.
        $this->assertFalse($disk->exists('variants/400/categories/small.webp'));
    }
}
