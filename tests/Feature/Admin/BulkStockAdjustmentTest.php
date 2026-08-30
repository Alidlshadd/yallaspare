<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\BulkStockParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BulkStockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pasted_list_is_reviewed_before_anything_changes(): void
    {
        $pad = $this->product('BRK-1001', 14);
        $filter = $this->product('FLT-2002', 3);

        $response = $this->review("BRK-1001 +20\nFLT-2002 +50");

        $response->assertOk()
            ->assertSee('BRK-1001', false)
            ->assertSee('Will apply', false);

        // Reviewing reads; it does not write.
        $this->assertSame(14, (int) $pad->fresh()->stock_quantity);
        $this->assertSame(3, (int) $filter->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_applying_writes_every_row_and_records_each_one(): void
    {
        $pad = $this->product('BRK-1001', 14);
        $filter = $this->product('FLT-2002', 3);

        $this->review("BRK-1001 +20\nFLT-2002 -3")->assertOk();
        $this->apply()->assertRedirect();

        $this->assertSame(34, (int) $pad->fresh()->stock_quantity);
        $this->assertSame(0, (int) $filter->fresh()->stock_quantity);

        $movement = InventoryMovement::query()->where('product_id', $pad->id)->firstOrFail();
        $this->assertSame(InventoryMovement::TYPE_IN, $movement->type);
        $this->assertSame(20, (int) $movement->quantity);
        $this->assertSame(14, (int) $movement->stock_before);
        $this->assertSame(34, (int) $movement->stock_after);
        $this->assertSame($this->admin->id, (int) $movement->user_id);
        $this->assertStringContainsString('Stock count', (string) $movement->note);
        $this->assertStringStartsWith('BULK-', (string) $movement->reference);
        $this->assertNotNull($movement->performed_at);

        // One run, one reference, so it can be read back as a single act.
        $this->assertSame(1, InventoryMovement::query()->distinct()->count('reference'));
    }

    public function test_an_unknown_code_stops_the_whole_run(): void
    {
        $pad = $this->product('BRK-1001', 14);

        $this->review("BRK-1001 +20\nNOPE-9999 +5")
            ->assertOk()
            ->assertSee('No product carries this code.', false)
            ->assertDontSee('Apply 2 change', false);

        $this->apply()->assertSessionHas('error');

        $this->assertSame(14, (int) $pad->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_a_row_that_would_go_below_zero_stops_the_whole_run(): void
    {
        $pad = $this->product('BRK-1001', 14);
        $clutch = $this->product('CLT-3003', 4);

        $this->review("BRK-1001 +20\nCLT-3003 -9")
            ->assertOk()
            ->assertSee('Only 4 in stock.', false);

        $this->apply()->assertSessionHas('error');

        $this->assertSame(14, (int) $pad->fresh()->stock_quantity);
        $this->assertSame(4, (int) $clutch->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_the_same_code_twice_is_added_up_rather_than_one_winning(): void
    {
        $pad = $this->product('BRK-1001', 10);

        $this->review("BRK-1001 +20\nBRK-1001 -5")
            ->assertOk()
            ->assertSee('2 lines merged', false);

        $this->apply()->assertRedirect();

        $this->assertSame(25, (int) $pad->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->count());
    }

    public function test_a_run_is_abandoned_when_the_stock_moved_while_it_was_reviewed(): void
    {
        $pad = $this->product('BRK-1001', 14);

        $this->review('BRK-1001 -10')->assertOk();

        // A customer buys some of it between the review and the confirmation.
        $pad->forceFill(['stock_quantity' => 6])->save();

        $this->apply()->assertSessionHas('error');

        $this->assertSame(6, (int) $pad->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_a_code_matching_two_products_is_refused_rather_than_guessed(): void
    {
        // part_number is not unique. The old importer took whichever row came
        // back first and adjusted a product nobody had chosen.
        $this->product('AAA-1', 5, ['part_number' => 'SHARED-9']);
        $this->product('BBB-2', 5, ['part_number' => 'SHARED-9']);

        $this->review('SHARED-9 +10')
            ->assertOk()
            ->assertSee('More than one product carries this code.', false);

        $this->apply()->assertSessionHas('error');
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_a_part_number_matching_exactly_one_product_still_works(): void
    {
        $pad = $this->product('BRK-1001', 14, ['part_number' => 'PN-4471']);

        $this->review('PN-4471 +6')->assertOk();
        $this->apply()->assertRedirect();

        $this->assertSame(20, (int) $pad->fresh()->stock_quantity);
    }

    public function test_a_reason_is_required(): void
    {
        $this->product('BRK-1001', 14);

        $this->actingAs($this->adminUser())
            ->post(route('admin.inventory.bulk-stock.preview'), ['rows' => 'BRK-1001 +5'])
            ->assertSessionHasErrors('reason');
    }

    public function test_an_unreadable_line_blocks_the_run_even_when_the_others_resolve(): void
    {
        $pad = $this->product('BRK-1001', 14);

        $this->review("BRK-1001 +20\nthis is not a row")
            ->assertOk()
            ->assertSee('could not be read', false);

        $this->apply()->assertSessionHas('error');
        $this->assertSame(14, (int) $pad->fresh()->stock_quantity);
    }

    public function test_a_csv_in_the_new_shape_is_accepted(): void
    {
        $pad = $this->product('BRK-1001', 14);

        $this->reviewFile("sku,quantity_change,note\nBRK-1001,7,delivery\n")->assertOk();
        $this->apply()->assertRedirect();

        $this->assertSame(21, (int) $pad->fresh()->stock_quantity);
        $this->assertStringContainsString('delivery', (string) InventoryMovement::query()->firstOrFail()->note);
    }

    public function test_a_csv_written_for_the_older_importer_still_works(): void
    {
        $pad = $this->product('BRK-1001', 14);
        $filter = $this->product('FLT-2002', 9);

        $this->reviewFile("product_sku,type,quantity\nBRK-1001,in,6\nFLT-2002,out,4\n")->assertOk();
        $this->apply()->assertRedirect();

        $this->assertSame(20, (int) $pad->fresh()->stock_quantity);
        $this->assertSame(5, (int) $filter->fresh()->stock_quantity);
    }

    public function test_more_rows_than_allowed_are_refused(): void
    {
        $this->product('BRK-1001', 5);

        $lines = [];
        for ($i = 0; $i <= BulkStockParser::MAX_ROWS + 5; $i++) {
            $lines[] = 'BRK-1001 +1';
        }

        $this->review(implode("\n", $lines))
            ->assertOk()
            ->assertSee('rows can be adjusted at once', false);

        $this->apply()->assertSessionHas('error');
    }

    public function test_an_administrator_without_the_stock_permission_cannot_reach_it(): void
    {
        $staff = User::factory()->create(['email_verified_at' => now()]);
        $staff->forceFill(['role' => User::ROLE_ADMIN, 'permissions' => []])->save();

        $this->actingAs($staff)
            ->withSession(['admin_2fa.verified_user_id' => $staff->id])
            ->get(route('admin.inventory.bulk-stock'))
            ->assertForbidden();
    }

    public function test_a_stale_review_cannot_be_applied(): void
    {
        $pad = $this->product('BRK-1001', 14);
        $this->review('BRK-1001 +5')->assertOk();

        $this->actingAs($this->adminUser())
            ->withSession(['admin_2fa.verified_user_id' => $this->admin->id])
            ->post(route('admin.inventory.bulk-stock.apply'), ['token' => 'not-the-token'])
            ->assertRedirect(route('admin.inventory.bulk-stock'))
            ->assertSessionHas('error');

        $this->assertSame(14, (int) $pad->fresh()->stock_quantity);
    }

    // ---------------------------------------------------------------- helpers

    private ?User $admin = null;

    private function review(string $rows, string $reason = 'Stock count')
    {
        return $this->actingAs($this->adminUser())
            ->withSession(['admin_2fa.verified_user_id' => $this->adminUser()->id])
            ->post(route('admin.inventory.bulk-stock.preview'), [
                'reason' => $reason,
                'rows' => $rows,
            ]);
    }

    private function reviewFile(string $contents, string $reason = 'Stock count')
    {
        return $this->actingAs($this->adminUser())
            ->withSession(['admin_2fa.verified_user_id' => $this->adminUser()->id])
            ->post(route('admin.inventory.bulk-stock.preview'), [
                'reason' => $reason,
                'file' => UploadedFile::fake()->createWithContent('stock.csv', $contents),
            ]);
    }

    private function apply()
    {
        $held = session('bulk_stock.preview');

        return $this->actingAs($this->adminUser())
            ->withSession(['admin_2fa.verified_user_id' => $this->adminUser()->id])
            ->post(route('admin.inventory.bulk-stock.apply'), [
                'token' => $held['token'] ?? 'missing',
            ]);
    }

    private function adminUser(): User
    {
        if ($this->admin === null) {
            $this->admin = User::factory()->create(['email_verified_at' => now()]);
            $this->admin->forceFill(['role' => User::ROLE_SUPER_ADMIN])->save();
        }

        return $this->admin;
    }

    private function product(string $sku, int $stock, array $attributes = []): Product
    {
        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1]);
        }

        return Product::factory()->create(array_merge([
            'category_id' => 1,
            'is_active' => true,
            'sku' => $sku,
            'stock_quantity' => $stock,
        ], $attributes));
    }
}
