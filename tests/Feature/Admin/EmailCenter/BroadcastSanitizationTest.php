<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\EmailBroadcast;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BroadcastSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_broadcast_body_is_sanitized(): void
    {
        Bus::fake();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        RateLimiter::clear('email-broadcast:' . $admin->id);

        $this->actingAs($admin)->post('/admin/email/broadcasts', [
            'subject' => 'Sanitize me',
            'body_html' => '<p>ok</p><script>alert(1)</script><img src=x onerror=alert(2)>',
            'filters' => [],
        ])->assertRedirect();

        $broadcast = EmailBroadcast::first();
        $this->assertNotNull($broadcast);
        $this->assertStringContainsString('<p>ok</p>', $broadcast->body_html);
        $this->assertStringNotContainsString('<script', $broadcast->body_html);
        $this->assertStringNotContainsString('onerror', $broadcast->body_html);
    }
}
