<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DdosRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_mobile_lookup_endpoints_are_rate_limited(): void
    {
        $client = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.44']);

        for ($i = 0; $i < 20; $i++) {
            $client->postJson('/api/mobile/vin/decode', [
                'vin' => '1HGCM82633A004352',
            ])->assertOk();
        }

        $client->postJson('/api/mobile/vin/decode', [
            'vin' => '1HGCM82633A004352',
        ])->assertTooManyRequests();
    }
}
