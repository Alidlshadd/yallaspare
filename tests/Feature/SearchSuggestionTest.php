<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use App\Support\Search\SearchQuery;
use App\Support\Search\SearchSuggestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Nothing is not an answer. A shopper who types "rextn" should be offered
 * "Rexton" — but only ever a name the catalogue actually holds, and never by
 * quietly folding approximate matches into the results.
 */
class SearchSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private VehicleModel $rexton;

    protected function setUp(): void
    {
        parent::setUp();

        SearchSuggestions::flush();

        Category::factory()->create([
            'id' => 1,
            'name_en' => 'Filters',
            'name_ar' => 'Filters',
            'name_ku' => 'Filters',
            'slug' => 'filters',
        ]);

        $brand = VehicleBrand::query()->create(['name' => 'SsangYong', 'slug' => 'ssangyong']);
        $family = VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Rexton',
            'slug' => 'rexton',
        ]);
        $this->rexton = VehicleModel::query()->create([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_family_id' => $family->id,
            'name' => 'Rexton',
            'name_en' => 'Rexton',
            'slug' => 'rexton-2022',
            'production_start_year' => 2022,
            'production_end_year' => 2026,
        ]);

        $product = Product::factory()->create(['name_en' => 'Engine Oil Filter']);
        ProductVehicleFitment::query()->create([
            'product_id' => $product->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $this->rexton->id,
        ]);
    }

    public function test_a_one_letter_slip_is_offered_the_right_model(): void
    {
        $suggestion = SearchSuggestions::forQuery(SearchQuery::parse('rextn'));

        $this->assertNotNull($suggestion);
        $this->assertSame('Rexton', $suggestion['suggestion']);
        $this->assertSame('rextn', $suggestion['word']);
    }

    public function test_a_misspelled_marque_is_offered_too(): void
    {
        $suggestion = SearchSuggestions::forQuery(SearchQuery::parse('ssanyong'));

        $this->assertNotNull($suggestion);
        $this->assertSame('SsangYong', $suggestion['suggestion']);
    }

    public function test_only_the_wrong_word_is_replaced_in_the_query(): void
    {
        $suggestion = SearchSuggestions::forQuery(SearchQuery::parse('rextn oil filter'));

        $this->assertNotNull($suggestion);

        // The suggestion keeps the catalogue's own spelling, which is what the
        // shopper should see offered back; matching is case-insensitive either
        // way, and the rest of the query is untouched.
        $this->assertSame('Rexton oil filter', $suggestion['query']);
    }

    public function test_a_word_that_is_already_right_is_not_corrected(): void
    {
        $this->assertNull(SearchSuggestions::forQuery(SearchQuery::parse('rexton')));
    }

    public function test_a_word_nothing_resembles_gets_no_suggestion(): void
    {
        $this->assertNull(SearchSuggestions::forQuery(SearchQuery::parse('helicopter')));
    }

    public function test_a_very_short_word_is_left_alone(): void
    {
        // Two letters away from everything and from nothing; guessing here
        // would produce noise rather than help.
        $this->assertNull(SearchSuggestions::forQuery(SearchQuery::parse('rex')));
    }

    public function test_the_suggestion_only_appears_when_there_are_no_results(): void
    {
        // A real query returns products, and no suggestion beside them.
        $this->get(route('shop.index', ['search' => 'rexton']))
            ->assertOk()
            ->assertSee('Engine Oil Filter')
            ->assertDontSee('Did you mean');

        // A typo returns nothing, and the offer appears.
        $this->get(route('shop.index', ['search' => 'rextn']))
            ->assertOk()
            ->assertSee('Did you mean')
            ->assertSee('Rexton');
    }

    public function test_a_fuzzy_match_never_reaches_the_results(): void
    {
        // "rextn" matches nothing exactly, and must not quietly return the
        // Rexton parts as though it had.
        $this->get(route('shop.index', ['search' => 'rextn']))
            ->assertOk()
            ->assertDontSee('Engine Oil Filter');
    }

    public function test_the_empty_state_offers_a_way_out(): void
    {
        $this->get(route('shop.index', ['search' => 'rextn']))
            ->assertOk()
            ->assertSee('No products found for &quot;rextn&quot;', false)
            ->assertSee('Clear search')
            ->assertSee('Browse all parts');
    }

    public function test_the_suggested_query_actually_returns_products(): void
    {
        $suggestion = SearchSuggestions::forQuery(SearchQuery::parse('rextn oil'));
        $this->assertNotNull($suggestion);

        $this->get(route('shop.index', ['search' => $suggestion['query']]))
            ->assertOk()
            ->assertSee('Engine Oil Filter');
    }

    public function test_the_dictionary_is_read_once_and_cached(): void
    {
        SearchSuggestions::flush();

        $reads = 0;
        DB::listen(function ($query) use (&$reads): void {
            if (str_contains($query->sql, 'vehicle_model_families')
                || str_contains($query->sql, 'vehicle_brands')) {
                $reads++;
            }
        });

        SearchSuggestions::forQuery(SearchQuery::parse('rextn'));
        $afterFirst = $reads;

        SearchSuggestions::forQuery(SearchQuery::parse('tivli'));
        SearchSuggestions::forQuery(SearchQuery::parse('korndo'));

        $this->assertGreaterThan(0, $afterFirst, 'The dictionary was never read.');
        $this->assertSame($afterFirst, $reads, 'The dictionary was read again instead of being cached.');
    }

    public function test_the_dictionary_never_touches_the_products_table(): void
    {
        SearchSuggestions::flush();

        $productReads = 0;
        DB::listen(function ($query) use (&$productReads): void {
            if (str_contains($query->sql, 'from "products"')) {
                $productReads++;
            }
        });

        SearchSuggestions::forQuery(SearchQuery::parse('rextn'));

        // Levenshtein over every product row is exactly what this must not do.
        $this->assertSame(0, $productReads);
    }
}
