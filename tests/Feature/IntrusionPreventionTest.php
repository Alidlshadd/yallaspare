<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntrusionPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'security.intrusion_prevention.enabled' => true,
            'security.intrusion_prevention.max_score' => 8,
            'security.intrusion_prevention.window_minutes' => 10,
            'security.intrusion_prevention.block_minutes' => 30,
        ]);

        Cache::flush();
    }

    public function test_ips_blocks_repeated_attack_signatures_from_same_ip(): void
    {
        $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        $client->get('/user/shop?q=' . urlencode("' OR 1=1 --"))
            ->assertOk();

        $client->get('/user/shop?q=' . urlencode("' UNION SELECT password FROM users --"))
            ->assertTooManyRequests();

        $client->get('/user/shop')
            ->assertTooManyRequests();
    }

    public function test_ips_does_not_block_normal_requests(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->get('/user/shop?q=brake')
            ->assertOk();
    }
}
