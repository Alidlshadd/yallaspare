<?php

namespace Tests\Feature\Analytics;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchTrackingTest extends TestCase
{
    use RefreshDatabase;

    private string $browserUserAgent = 'Mozilla/5.0 Chrome/121 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['id' => 1]);
    }

    public function test_search_query_creates_normalized_keyword_row(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->get(route('shop.index', ['search' => 'BraKe  pad ']))
            ->assertOk();

        $this->assertSame(1, DB::table('search_analytics')->count());
        $this->assertSame(1, (int) DB::table('search_analytics')
            ->where('keyword', 'brake pad')
            ->value('search_count'));
        $this->assertSame(1, DB::table('analytics_events')
            ->where('event_type', 'search')
            ->count());
    }

    public function test_second_identical_search_increments(): void
    {
        $url = route('shop.index', ['search' => 'oil filter']);

        for ($i = 0; $i < 2; $i++) {
            $this->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
                ->get($url);
        }

        $this->assertSame(2, (int) DB::table('search_analytics')
            ->where('keyword', 'oil filter')
            ->value('search_count'));
    }

    public function test_q_alias_also_tracks(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->get(route('shop.index', ['q' => 'spark plug']))
            ->assertOk();

        $this->assertSame(1, (int) DB::table('search_analytics')
            ->where('keyword', 'spark plug')
            ->value('search_count'));
    }

    public function test_empty_search_is_ignored(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->get(route('shop.index', ['search' => ' ']))
            ->assertOk();

        $this->assertSame(0, DB::table('search_analytics')->count());
    }

    public function test_symbol_only_search_is_ignored(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => $this->browserUserAgent])
            ->get(route('shop.index', ['search' => '???']))
            ->assertOk();

        $this->assertSame(0, DB::table('search_analytics')->count());
    }

    public function test_bot_search_is_ignored(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Googlebot/2.1'])
            ->get(route('shop.index', ['search' => 'brake pad']))
            ->assertOk();

        $this->assertSame(0, DB::table('search_analytics')->count());
    }
}
