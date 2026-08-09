<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BannedUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_permanently_banned_user_is_logged_out_of_the_web_session(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subDay(),
            'banned_until' => null,
            'ban_reason' => 'Confirmed account fraud',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_temporarily_banned_user_sees_the_expiry_date(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subDay(),
            'banned_until' => now()->addDays(7),
            'ban_reason' => 'Repeated payment abuse',
        ]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));

        $errors = session('errors')->get('email');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Repeated payment abuse', $errors[0]);
        // The dotted lang keys used here previously rendered as an empty string.
        $this->assertNotSame('', trim($errors[0]));
    }

    public function test_user_whose_ban_has_expired_is_allowed_through(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subMonth(),
            'banned_until' => now()->subDay(),
        ]);

        // /dashboard sits behind the phone-verification chain, so a plain
        // factory user is redirected there regardless. What matters here is
        // that the ban middleware did not bounce them to login or log them out.
        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertNotSame(route('login'), $response->headers->get('Location'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_unbanned_user_is_not_bounced_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertNotSame(route('login'), $response->headers->get('Location'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_banned_user_cannot_use_a_sanctum_token(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subDay(),
            'banned_until' => null,
        ]);

        Sanctum::actingAs($user, ['mobile:read', 'mobile:write']);

        $this->getJson('/api/mobile/me')->assertForbidden();
    }

    public function test_banned_user_without_an_accept_header_still_gets_json_not_a_session_error(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subDay(),
            'banned_until' => null,
        ]);

        Sanctum::actingAs($user, ['mobile:read', 'mobile:write']);

        // The api stack has no session; the middleware must not reach
        // $request->session() on this path.
        $this->get('/api/mobile/me', ['Accept' => '*/*'])->assertForbidden();
    }

    public function test_guest_requests_are_untouched(): void
    {
        $this->get('/login')->assertOk();
        $this->assertGuest();
    }

    public function test_banned_user_cannot_log_in_through_the_web_form(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subDay(),
            'banned_until' => null,
            'ban_reason' => 'Confirmed account fraud',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Confirmed account fraud', $errors[0]);
    }

    public function test_web_login_does_not_reveal_the_ban_to_a_wrong_password(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subDay(),
            'banned_until' => null,
            'ban_reason' => 'Confirmed account fraud',
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'not-the-password',
        ]);

        $errors = session('errors')->get('email');
        $this->assertSame(trans('auth.failed'), $errors[0]);
        $this->assertStringNotContainsString('Confirmed account fraud', $errors[0]);
        $this->assertGuest();
    }

    public function test_banned_user_gets_no_token_from_the_mobile_login(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subDay(),
            'banned_until' => now()->addDays(3),
            'ban_reason' => 'Repeated payment abuse',
        ]);

        $response = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('Repeated payment abuse', $response->json('message'));
        $this->assertNull($response->json('token'));
        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_mobile_login_does_not_reveal_the_ban_to_a_wrong_password(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subDay(),
            'banned_until' => null,
            'ban_reason' => 'Confirmed account fraud',
        ]);

        $response = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'not-the-password',
        ]);

        $response->assertStatus(422);
        $this->assertStringNotContainsString('Confirmed account fraud', (string) $response->json('message'));
    }

    public function test_unbanned_user_can_still_log_in(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_banned_user_is_blocked_before_reaching_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'banned_at' => now()->subDay(),
            'banned_until' => null,
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
    }
}
