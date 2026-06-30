<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsPruneCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_deletes_old_events_only(): void
    {
        DB::table('analytics_events')->insert([
            ['event_type' => 'page_view', 'created_at' => Carbon::now()->subDays(366)],
            ['event_type' => 'page_view', 'created_at' => Carbon::now()->subDays(1)],
        ]);

        $this->artisan('analytics:prune', ['--days' => 365])
            ->assertSuccessful();

        $this->assertSame(1, DB::table('analytics_events')->count());

        $remaining = DB::table('analytics_events')->first();
        $this->assertTrue(Carbon::parse($remaining->created_at)->isAfter(Carbon::now()->subDays(2)));
    }

    public function test_prune_respects_custom_days_option(): void
    {
        DB::table('analytics_events')->insert([
            ['event_type' => 'page_view', 'created_at' => Carbon::now()->subDays(40)],
            ['event_type' => 'page_view', 'created_at' => Carbon::now()->subDays(20)],
            ['event_type' => 'page_view', 'created_at' => Carbon::now()->subDays(5)],
        ]);

        $this->artisan('analytics:prune', ['--days' => 30])
            ->assertSuccessful();

        $this->assertSame(2, DB::table('analytics_events')->count());
    }
}
