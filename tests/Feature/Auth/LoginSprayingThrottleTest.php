<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\LoginFailureThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One password tried against many accounts from one address.
 */
class LoginSprayingThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_login_stops_an_address_that_keeps_failing_across_accounts(): void
    {
        $victim = User::factory()->create(['email' => 'victim@example.test']);

        foreach (range(1, LoginFailureThrottle::MAX_FAILURES) as $i) {
            $this->post('/login', ['email' => "user{$i}@example.test", 'password' => 'Summer2026!']);
        }

        $this->post('/login', ['email' => 'victim@example.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Another address is untouched.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->post('/login', ['email' => 'victim@example.test', 'password' => 'password']);
        $this->assertAuthenticatedAs($victim);
    }

    public function test_mobile_login_stops_an_address_that_keeps_failing_across_accounts(): void
    {
        User::factory()->create(['email' => 'victim@example.test']);

        foreach (range(1, LoginFailureThrottle::MAX_FAILURES) as $i) {
            $this->postJson('/api/mobile/login', ['email' => "user{$i}@example.test", 'password' => 'Summer2026!'])
                ->assertStatus(422);
        }

        $this->postJson('/api/mobile/login', ['email' => 'victim@example.test', 'password' => 'password'])
            ->assertStatus(429)
            ->assertJsonMissingPath('token');
    }

    public function test_successful_sign_ins_from_a_shared_address_are_not_counted(): void
    {
        // 40 sign-ins, above the failure ceiling, and under the separate
        // 5-per-account-per-minute route limit.
        $users = User::factory()->count(10)->create();

        foreach (range(1, 4) as $round) {
            foreach ($users as $user) {
                $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])
                    ->assertOk();
            }
        }

        $this->assertFalse(LoginFailureThrottle::tooMany('127.0.0.1'));
    }
}
