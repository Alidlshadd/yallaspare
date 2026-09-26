<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

        $client->get('/user/shop?q='.urlencode("' OR 1=1 --"))
            ->assertOk();

        $client->get('/user/shop?q='.urlencode("' UNION SELECT password FROM users --"))
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

    /**
     * Each of these used to score 3, so a shopper resubmitting a form three
     * times blocked their whole address for the block window.
     */
    public function test_ordinary_customer_text_is_not_scored(): void
    {
        $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.12']);

        foreach (range(1, 4) as $attempt) {
            $response = $client->post('/contact', [
                'name' => 'Ali',
                'address_line1' => 'House #12, Street 5',
                'notes' => 'Call before arriving -- thanks',
                'message' => 'Please select a brake pad from your list for my car',
            ]);
            $this->assertNotSame(429, $response->status());

            $client->get('/user/shop?q='.urlencode('part #12 -- front'))
                ->assertOk();
        }

        $client->get('/user/shop')->assertOk();
    }

    public function test_provider_callbacks_are_never_scored(): void
    {
        $client = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.13']);

        foreach (range(1, 4) as $attempt) {
            $response = $client->postJson('/api/webhooks/otpiq', [
                'message' => ['text' => "' UNION SELECT password FROM users --"],
            ]);
            $this->assertNotSame(429, $response->status());
        }

        $this->assertFalse(Cache::has('ips:block:'.sha1('203.0.113.13')));
    }
}
