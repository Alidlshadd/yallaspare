<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BroadcastRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_throttled_after_three_broadcasts_in_five_minutes(): void
    {
        Bus::fake();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        RateLimiter::clear('email-broadcast:' . $admin->id);

        $payload = ['subject' => 'Tick', 'body_html' => '<p>x</p>', 'filters' => []];

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin)->post('/admin/email/broadcasts', $payload)->assertRedirect();
        }

        $this->actingAs($admin)->post('/admin/email/broadcasts', $payload)->assertStatus(429);
    }
}
