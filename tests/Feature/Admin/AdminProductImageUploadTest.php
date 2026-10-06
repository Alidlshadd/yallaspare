<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\ProductImageUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AdminProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
        $this->category = Category::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Front Brake Disc',
            'name_ar' => 'Front Brake Disc',
            'name_ku' => 'Front Brake Disc',
            'price' => 45000,
            'stock_quantity' => 7,
            'sku' => 'SKU-UPLOAD-01',
            'category_id' => $this->category->id,
            'is_active' => 1,
        ], $overrides);
    }

    private function tooLargeMessage(): string
    {
        return 'The image is too large. The maximum size is '.ProductImageUpload::maxMegabytes().' MB per image.';
    }

    private function oversizedImage(): UploadedFile
    {
        return UploadedFile::fake()->image('huge.jpg')->size(ProductImageUpload::maxKilobytes() + 1);
    }

    /** A file PHP refused before the application saw it. */
    private function failedUpload(int $error): UploadedFile
    {
        return new UploadedFile(tempnam(sys_get_temp_dir(), 'upl'), 'photo.jpg', 'image/jpeg', $error, true);
    }

    /** Sniffs as a JPEG, so it passes validation, but cannot be decoded. */
    private function corruptJpeg(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('broken.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00".str_repeat('x', 64));
    }

    private function productWithImage(): Product
    {
        Storage::disk('public')->put('products/old-main.jpg', 'old');

        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'sku' => 'SKU-UPLOAD-EXISTING',
            'image' => 'products/old-main.jpg',
        ]);
        $product->images()->create([
            'path' => 'products/old-main.jpg',
            'disk' => 'public',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        return $product;
    }

    public function test_validation_messages_are_whole_sentences_not_the_last_word_of_a_key(): void
    {
        // The regression behind the bare "File" error: each of these used to
        // come back as "Required", "File", "Image".
        $this->assertSame('validation.custom.image.max.file', __('validation.custom.image.max.file'));

        $errors = Validator::make(
            ['name' => '', 'photo' => UploadedFile::fake()->image('p.jpg')->size(300)],
            ['name' => 'required', 'photo' => 'image|max:100']
        )->errors();

        $this->assertSame('The name field is required.', $errors->first('name'));
        $this->assertSame('The photo field must not be greater than 100 kilobytes.', $errors->first('photo'));
    }

    public function test_a_product_is_created_with_its_image(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->payload([
                'image' => UploadedFile::fake()->image('disc.jpg', 600, 600),
                'gallery_images' => [UploadedFile::fake()->image('side.png', 400, 400)],
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product = Product::query()->where('sku', 'SKU-UPLOAD-01')->firstOrFail();

        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
        $this->assertSame(2, $product->images()->count());
        $this->assertSame($product->image, $product->images()->where('is_primary', true)->value('path'));
        $this->assertCount(2, Storage::disk('public')->allFiles('products'));
    }

    public function test_an_oversized_image_is_refused_with_the_limit_and_the_form_is_kept(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->payload(['image' => $this->oversizedImage()]));

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors(['image' => $this->tooLargeMessage()]);
        $response->assertSessionHasInput('name_en', 'Front Brake Disc');
        $response->assertSessionHasInput('sku', 'SKU-UPLOAD-01');

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-UPLOAD-01']);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->actingAs($this->admin)
            ->get(route('admin.products.create'))
            ->assertSee($this->tooLargeMessage())
            ->assertSee('value="Front Brake Disc"', false);
    }

    public function test_an_unsupported_format_is_named_as_such(): void
    {
        $unsupported = 'Unsupported image format. Please upload a JPG, PNG or WEBP file.';

        foreach ([
            UploadedFile::fake()->image('animated.gif'),
            UploadedFile::fake()->create('manual.pdf', 20, 'application/pdf'),
        ] as $file) {
            $this->actingAs($this->admin)
                ->post(route('admin.products.store'), $this->payload(['image' => $file]))
                ->assertSessionHasErrors(['image' => $unsupported]);
        }

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->payload([
                'gallery_images' => [UploadedFile::fake()->image('animated.gif')],
            ]))
            ->assertSessionHasErrors(['gallery_images.0' => $unsupported]);

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-UPLOAD-01']);
    }

    public function test_an_upload_php_refused_is_explained_without_server_detail(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->payload(['image' => $this->failedUpload(UPLOAD_ERR_INI_SIZE)]))
            ->assertSessionHasErrors(['image' => $this->tooLargeMessage()]);

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->payload(['image' => $this->failedUpload(UPLOAD_ERR_CANT_WRITE)]))
            ->assertSessionHasErrors(['image' => 'The image could not be uploaded. Please try again.']);

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-UPLOAD-01']);
    }

    public function test_upload_errors_are_translated(): void
    {
        foreach (['ar', 'ku'] as $locale) {
            $this->app->setLocale($locale);

            $response = $this->actingAs($this->admin)
                ->post(route('admin.products.store'), $this->payload(['image' => $this->oversizedImage()]));

            $message = session('errors')->first('image');

            $response->assertSessionHasErrors('image');
            $this->assertStringContainsString(ProductImageUpload::maxMegabytes(), $message);
            $this->assertStringNotContainsString('The image is too large', $message);
        }
    }

    public function test_a_failed_gallery_image_leaves_no_product_and_no_files_behind(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->payload([
                'image' => UploadedFile::fake()->image('disc.jpg'),
                'gallery_images' => [$this->corruptJpeg()],
            ]));

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors(['image' => 'The image could not be read. Please upload a valid JPG, PNG or WEBP file.']);
        $response->assertSessionHasInput('name_en', 'Front Brake Disc');

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-UPLOAD-01']);
        $this->assertSame(0, ProductImage::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_replacing_the_image_swaps_the_file_and_keeps_the_new_one_as_main(): void
    {
        $product = $this->productWithImage();
        $oldRowId = $product->images()->value('id');

        // What the edit form really sends: the new file, plus the radio that
        // is still checked on the picture being replaced.
        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $this->payload([
                'sku' => 'SKU-UPLOAD-EXISTING',
                'image' => UploadedFile::fake()->image('new.jpg', 500, 500),
                'primary_image_id' => $oldRowId,
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertNotSame('products/old-main.jpg', $product->image);
        Storage::disk('public')->assertExists($product->image);
        Storage::disk('public')->assertMissing('products/old-main.jpg');
        $this->assertSame([$product->image], $product->images()->pluck('path')->all());
        $this->assertTrue((bool) $product->images()->value('is_primary'));
    }

    public function test_a_rejected_replacement_keeps_the_existing_image(): void
    {
        $product = $this->productWithImage();

        foreach ([$this->oversizedImage(), $this->corruptJpeg()] as $file) {
            $this->actingAs($this->admin)
                ->from(route('admin.products.edit', $product))
                ->put(route('admin.products.update', $product), $this->payload([
                    'sku' => 'SKU-UPLOAD-EXISTING',
                    'name_en' => 'Renamed During Failed Upload',
                    'image' => $file,
                ]))
                ->assertRedirect(route('admin.products.edit', $product))
                ->assertSessionHasErrors('image')
                ->assertSessionHasInput('name_en', 'Renamed During Failed Upload');

            $product->refresh();

            $this->assertSame('products/old-main.jpg', $product->image);
            $this->assertNotSame('Renamed During Failed Upload', $product->name_en);
            $this->assertSame(['products/old-main.jpg'], $product->images()->pluck('path')->all());
            $this->assertSame(['products/old-main.jpg'], Storage::disk('public')->allFiles());
        }
    }

    public function test_editing_without_touching_the_image_leaves_it_alone(): void
    {
        $product = $this->productWithImage();

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $this->payload([
                'sku' => 'SKU-UPLOAD-EXISTING',
                'name_en' => 'Renamed Only',
                'primary_image_id' => $product->images()->value('id'),
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertSame('Renamed Only', $product->name_en);
        $this->assertSame('products/old-main.jpg', $product->image);
        Storage::disk('public')->assertExists('products/old-main.jpg');
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
    }

    public function test_removing_the_image_deletes_the_file_only_once_the_save_succeeds(): void
    {
        $product = $this->productWithImage();

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $this->payload([
                'sku' => 'SKU-UPLOAD-EXISTING',
                'remove_image' => 1,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertNull($product->refresh()->image);
        $this->assertSame(0, $product->images()->count());
        Storage::disk('public')->assertMissing('products/old-main.jpg');
    }

    public function test_the_forms_state_the_same_limit_the_server_enforces(): void
    {
        $hint = 'JPG, PNG or WEBP up to '.ProductImageUpload::maxMegabytes().' MB';
        $accept = 'accept="'.ProductImageUpload::acceptAttribute().'"';

        foreach ([
            route('admin.products.create'),
            route('admin.products.edit', $this->productWithImage()),
        ] as $url) {
            $this->actingAs($this->admin)
                ->get($url)
                ->assertOk()
                ->assertSee($hint)
                ->assertSee($accept, false)
                ->assertDontSee('accept="image/*"', false);
        }
    }

    public function test_a_request_over_post_max_size_gets_a_product_message_not_the_video_one(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.products.create'))
            ->call('POST', route('admin.products.store'), [], [], [], ['CONTENT_LENGTH' => PHP_INT_MAX]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors([
            'image' => 'The upload is too large for the server to accept. Please use fewer or smaller images.',
        ]);
    }
}
