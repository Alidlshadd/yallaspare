<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The guest auth POST routes each send mail or create an account, so none of
 * them may be replayable without limit. Login is covered separately by
 * LoginRequest::ensureIsNotRateLimited().
 */
class AuthThrottleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rejected attempts have to count too, otherwise anyone can keep the door
     * open just by submitting a payload that fails validation.
     *
     * @return array<string, mixed>
     */
    private function invalidRegistration(): array
    {
        return [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'country_code' => '+964',
            'phone' => '07704488315',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', $this->invalidRegistration())
                ->assertStatus(302);
        }

        $this->post('/register', $this->invalidRegistration())
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_registration_limit_lifts_after_the_window(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', $this->invalidRegistration());
        }

        $this->post('/register', $this->invalidRegistration())->assertStatus(429);

        $this->travel(61)->seconds();

        $this->post('/register', $this->invalidRegistration())->assertStatus(302);
    }

    public function test_password_reset_links_are_rate_limited_per_address(): void
    {
        Notification::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => 'victim@example.com'])
                ->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'victim@example.com'])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_a_blocked_address_does_not_block_everyone_else(): void
    {
        Notification::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => 'victim@example.com']);
        }

        $this->post('/forgot-password', ['email' => 'victim@example.com'])
            ->assertStatus(429);

        // That address is spent, but the limit is keyed per address, so a
        // different one from the same IP still has room under the wider ceiling.
        $this->post('/forgot-password', ['email' => 'someone-else@example.com'])
            ->assertStatus(302);
    }

    public function test_spraying_many_addresses_still_hits_the_ip_ceiling(): void
    {
        Notification::fake();

        // One request per address stays under the per-address limit every time,
        // so only the per-IP limit can stop this.
        for ($i = 0; $i < 10; $i++) {
            $this->post('/forgot-password', ['email' => "target{$i}@example.com"])
                ->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'target10@example.com'])
            ->assertStatus(429);
    }

    public function test_new_password_submissions_are_rate_limited(): void
    {
        $payload = [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->post('/reset-password', $payload)->assertStatus(302);
        }

        $this->post('/reset-password', $payload)
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}
