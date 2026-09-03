<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_ok(): void
    {
        $response = $this->getJson('/up');

        $response->assertOk();
        $this->assertSame('ok', $response->json('status'));
    }

    /**
     * The point of the endpoint is that it is cheap. A probe that starts a
     * session and records a page view is not cheap, and at one probe a minute
     * it quietly becomes the busiest writer on the site.
     */
    public function test_health_endpoint_starts_no_session_and_records_no_analytics(): void
    {
        $this->getJson('/up')->assertOk();

        $this->assertDatabaseCount('analytics_events', 0);
        $this->assertFalse(app('session')->isStarted());
    }
}
