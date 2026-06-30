<?php

namespace Tests\Feature\Analytics;

use App\Models\Category;
use App\Models\Product;
use App\Services\Analytics\AnalyticsRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsRecorderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ProductFactory hardcodes category_id => 1; seed a category so FK passes.
        Category::factory()->create(['id' => 1]);
    }

    public function test_record_inserts_event_and_increments_product_views(): void
    {
        $product = Product::factory()->create();
        app(AnalyticsRecorder::class)->record('product_view', ['product_id' => $product->id]);

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'product_view',
            'product_id' => $product->id,
        ]);
        $this->assertSame(1, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('views_count'));
    }

    public function test_record_increments_add_to_cart_counter(): void
    {
        $product = Product::factory()->create();
        app(AnalyticsRecorder::class)->record('add_to_cart', ['product_id' => $product->id, 'qty' => 2]);
        app(AnalyticsRecorder::class)->record('add_to_cart', ['product_id' => $product->id, 'qty' => 1]);

        $this->assertSame(2, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('add_to_cart_count'));
    }

    public function test_record_increments_wishlist_counter(): void
    {
        $product = Product::factory()->create();
        app(AnalyticsRecorder::class)->record('wishlist_click', ['product_id' => $product->id]);

        $this->assertSame(1, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('wishlist_count'));
    }

    public function test_record_search_upserts_keyword(): void
    {
        $recorder = app(AnalyticsRecorder::class);
        $recorder->recordSearch('BraKe  pad ', 5);
        $recorder->recordSearch('brake pad', 7);

        $this->assertSame(1, DB::table('search_analytics')->count());
        $this->assertSame(2, (int) DB::table('search_analytics')->where('keyword', 'brake pad')->value('search_count'));
    }

    public function test_record_search_ignores_invalid_input(): void
    {
        app(AnalyticsRecorder::class)->recordSearch('???', 0);
        $this->assertSame(0, DB::table('search_analytics')->count());
        $this->assertSame(0, DB::table('analytics_events')->where('event_type', 'search')->count());
    }
}
