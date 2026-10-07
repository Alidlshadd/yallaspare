<?php

namespace Tests\Feature\Admin;

use App\Exports\ProductsExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\ExchangeRateService;
use App\Support\ExportThumbnail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ProductsExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * The writer keeps every embedded picture in memory, so a full-size photo
     * per product is what took the export down with a 500. What goes into the
     * sheet has to be a thumbnail, however large the upload was.
     */
    public function test_a_large_product_photo_is_embedded_as_a_small_thumbnail(): void
    {
        Storage::fake('public');
        ExportThumbnail::resetBudget();

        $photo = imagecreatetruecolor(2400, 1800);
        for ($i = 0; $i < 4000; $i++) {
            imagesetpixel($photo, random_int(0, 2399), random_int(0, 1799), random_int(0, 0xFFFFFF));
        }
        ob_start();
        imagepng($photo, null, 0);
        $bytes = (string) ob_get_clean();
        imagedestroy($photo);
        Storage::disk('public')->put('products/big-photo.png', $bytes);

        $this->assertGreaterThan(5_000_000, strlen($bytes));

        Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'image' => 'products/big-photo.png',
        ]);
        Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'image' => 'products/missing.png',
        ]);

        $thumbnail = ExportThumbnail::pathFor('products/big-photo.png');
        $this->assertNotNull($thumbnail);
        $this->assertLessThan(20_000, filesize($thumbnail));
        [$width, $height] = getimagesize($thumbnail);
        $this->assertSame([96, 72], [$width, $height]);
        $this->assertNull(ExportThumbnail::pathFor('products/missing.png'));

        $sheet = Excel::raw(new ProductsExport, \Maatwebsite\Excel\Excel::XLSX);

        $this->assertLessThan(100_000, strlen($sheet));

        $this->actingAs($this->admin())
            ->get(route('admin.products.export-excel'))
            ->assertOk()
            ->assertDownload('products.xlsx');

        @unlink($thumbnail);
    }

    /**
     * The real file, built and read back — not a faked download. A faked one
     * passes while the sheet itself cannot be written.
     */
    public function test_the_products_sheet_is_built_and_downloads(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        app(ExchangeRateService::class)->setRate('150000', $admin);

        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'IQD-EXPORT-1',
            'price' => 20000,
            'cost_price' => 14000,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'USD-EXPORT-1',
            'price_currency' => 'USD',
            'price_usd' => '10.00',
            'dealer_price_usd' => '8.00',
            'cost_price_usd' => '6.00',
            'price' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.export-excel'))
            ->assertOk()
            ->assertDownload('products.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'products-export-').'.xlsx';
        file_put_contents($path, Excel::raw(new ProductsExport, \Maatwebsite\Excel\Excel::XLSX));
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        @unlink($path);

        $header = $rows[0];
        $bySku = collect(array_slice($rows, 1))->keyBy(fn (array $row) => $row[array_search('sku', $header, true)]);
        $cell = fn (string $sku, string $column) => $bySku[$sku][array_search($column, $header, true)];

        $this->assertSame('IQD', $cell('IQD-EXPORT-1', 'price_currency'));
        $this->assertEquals(20000, (float) str_replace(',', '', (string) $cell('IQD-EXPORT-1', 'price')));
        $this->assertEquals(14000, (float) str_replace(',', '', (string) $cell('IQD-EXPORT-1', 'cost_price')));

        $this->assertSame('USD', $cell('USD-EXPORT-1', 'price_currency'));
        $this->assertEquals(15000, (float) str_replace(',', '', (string) $cell('USD-EXPORT-1', 'price')));
        $this->assertEquals(10, (float) $cell('USD-EXPORT-1', 'price_usd'));
        $this->assertEquals(8, (float) $cell('USD-EXPORT-1', 'dealer_price_usd'));
        $this->assertEquals(6, (float) $cell('USD-EXPORT-1', 'cost_price_usd'));
    }
}
