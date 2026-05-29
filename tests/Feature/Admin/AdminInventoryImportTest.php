<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminInventoryImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCsv(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'inv_import_') . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, 'inventory.csv', 'text/csv', null, true);
    }

    private function makeAdminAndProducts(): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'name_en' => 'Filters',
            'name_ar' => 'فلاتر',
            'name_ku' => 'پاڵاوەر',
            'slug' => 'filters',
        ]);

        $p1 = Product::create([
            'category_id' => $category->id,
            'name_en' => 'Oil Filter A',
            'name_ar' => 'Filter A',
            'name_ku' => 'Filter A',
            'price' => 25.00,
            'stock_quantity' => 10,
            'sku' => 'SKU-A',
        ]);

        $p2 = Product::create([
            'category_id' => $category->id,
            'name_en' => 'Oil Filter B',
            'name_ar' => 'Filter B',
            'name_ku' => 'Filter B',
            'price' => 30.00,
            'stock_quantity' => 5,
            'sku' => 'SKU-B',
        ]);

        return [$admin, $p1, $p2];
    }

    public function test_bulk_inventory_import_adds_stock_for_valid_rows(): void
    {
        [$admin, $p1, $p2] = $this->makeAdminAndProducts();

        $csv = "product_sku,type,quantity,reference,note\n"
            . "SKU-A,in,20,PO-1,Restock\n"
            . "SKU-B,in,15,PO-1,Restock\n";

        $this->actingAs($admin)
            ->post(route('admin.inventory.import'), [
                'import_file' => $this->makeCsv($csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(30, $p1->fresh()->stock_quantity);
        $this->assertSame(20, $p2->fresh()->stock_quantity);
        $this->assertSame(2, InventoryMovement::count());
    }

    public function test_bulk_import_skips_invalid_rows_and_reports_them(): void
    {
        [$admin, $p1] = $this->makeAdminAndProducts();

        $csv = "product_sku,type,quantity\n"
            . "SKU-A,in,5\n"
            . "SKU-MISSING,in,3\n"
            . "SKU-A,bogus,2\n"
            . "SKU-A,out,9999\n";

        $response = $this->actingAs($admin)
            ->post(route('admin.inventory.import'), [
                'import_file' => $this->makeCsv($csv),
            ]);

        $response->assertRedirect()->assertSessionHas('error');

        $errorMsg = (string) (session('error') ?? '');
        $this->assertStringContainsString('1 imported', $errorMsg, "Got: {$errorMsg}");
        $this->assertSame(15, $p1->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::count());
    }

    public function test_bulk_import_rejects_missing_required_columns(): void
    {
        [$admin] = $this->makeAdminAndProducts();

        $csv = "sku,qty\nSKU-A,5\n";

        $this->actingAs($admin)
            ->post(route('admin.inventory.import'), [
                'import_file' => $this->makeCsv($csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, InventoryMovement::count());
    }
}
