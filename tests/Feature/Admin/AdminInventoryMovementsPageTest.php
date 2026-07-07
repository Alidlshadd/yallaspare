<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryMovementsPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function seedMovements(User $admin): Product
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name_en' => 'Dock Test Radiator',
            'sku' => 'RAD-9001',
            'stock_quantity' => 100,
        ]);

        $actingAdmin = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin);

        $actingAdmin->post(route('admin.inventory.store'), [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 40,
            'reference' => 'PO-7001',
        ])->assertSessionHasNoErrors();

        $actingAdmin->post(route('admin.inventory.store'), [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 15,
            'reference' => 'ORD-7002',
        ])->assertSessionHasNoErrors();

        return $product;
    }

    public function test_movements_page_renders_dock_lanes(): void
    {
        $admin = $this->makeAdmin();
        $this->seedMovements($admin);

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee('Gate A');
        $response->assertSee('Inbound');
        $response->assertSee('Outbound');
        $response->assertSee('Dock Test Radiator');
        $response->assertSee('PO-7001');
        $response->assertSee('Import CSV');
    }

    public function test_type_filter_shows_single_lane(): void
    {
        $admin = $this->makeAdmin();
        $this->seedMovements($admin);

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.inventory.index', ['type' => 'in']));

        $response->assertOk();
        $response->assertSee('Inbound');
        $response->assertDontSee('Outbound');
    }

    public function test_export_streams_filtered_csv(): void
    {
        $admin = $this->makeAdmin();
        $this->seedMovements($admin);

        $response = $this
            ->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.inventory.export', ['type' => 'out']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Dock Test Radiator', $csv);
        $this->assertStringContainsString('ORD-7002', $csv);
        $this->assertStringNotContainsString('PO-7001', $csv);
    }
}
