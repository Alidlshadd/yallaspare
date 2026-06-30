<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_request_records_page_view_event(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121 Safari/537.36'])
             ->get('/shop')
             ->assertOk();

        $this->assertSame(1, DB::table('analytics_events')->where('event_type', 'page_view')->count());

        $row = DB::table('analytics_events')->where('event_type', 'page_view')->first();
        $this->assertSame(64, strlen((string) $row->ip_hash));
        $this->assertSame(64, strlen((string) $row->user_agent_hash));
    }

    public function test_bot_user_agent_is_ignored(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Googlebot/2.1'])
             ->get('/shop')
             ->assertOk();

        $this->assertSame(0, DB::table('analytics_events')->count());
    }

    public function test_asset_paths_are_ignored(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
             ->get('/favicon.ico');

        $this->assertSame(0, DB::table('analytics_events')->where('event_type', 'page_view')->count());
    }
}
