<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('data-header-dropdown-trigger', false);
        $response->assertSee('languageDropdownReady', false);
        $response->assertSee('password-input-toggle', false);
        $response->assertSee('x-data="passwordInput(', false);
        $response->assertSee('Show password');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('user.shop.home'));
    }

    public function test_remember_me_creates_a_persistent_login_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }

    public function test_remember_me_selection_is_restored_after_a_failed_login(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'remember' => '1',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasInput('remember', '1');

        $this->get('/login')
            ->assertOk()
            ->assertSee('id="remember_me"', false)
            ->assertSee('value="1"', false)
            ->assertSee('checked', false);
    }

    public function test_users_can_authenticate_with_normalized_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+964 750 123 4567',
        ]);

        $response = $this->post('/login', [
            'email' => '9647501234567',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('user.shop.home'));
    }

    public function test_shared_phone_login_selects_the_account_matching_the_password(): void
    {
        User::factory()->create([
            'email' => 'first-admin@example.com',
            'phone' => '+964 750 123 4567',
            'password' => 'FirstAdmin!2026',
            'role' => User::ROLE_ADMIN,
        ]);
        $secondAdmin = User::factory()->create([
            'email' => 'second-admin@example.com',
            'phone' => '+964 750 123 4567',
            'password' => 'SecondAdmin!2026',
            'role' => User::ROLE_PRODUCT_MANAGER,
        ]);

        $this->post('/login', [
            'email' => '0750 123 4567',
            'password' => 'SecondAdmin!2026',
        ]);

        $this->assertAuthenticatedAs($secondAdmin);
    }

    public function test_shared_phone_login_requires_email_when_password_is_also_shared(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_ORDER_MANAGER] as $index => $role) {
            User::factory()->create([
                'email' => "same-password-admin-{$index}@example.com",
                'phone' => '+964 750 123 4567',
                'password' => 'SharedAdmin!2026',
                'role' => $role,
            ]);
        }

        $this->post('/login', [
            'email' => '0750 123 4567',
            'password' => 'SharedAdmin!2026',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_mobile_shared_phone_login_selects_the_account_matching_the_password(): void
    {
        User::factory()->create([
            'email' => 'mobile-first-admin@example.com',
            'phone' => '+964 750 123 4567',
            'password' => 'MobileFirst!2026',
            'role' => User::ROLE_ADMIN,
        ]);
        $secondAdmin = User::factory()->create([
            'email' => 'mobile-second-admin@example.com',
            'phone' => '+964 750 123 4567',
            'password' => 'MobileSecond!2026',
            'role' => User::ROLE_FINANCE_MANAGER,
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => '0750 123 4567',
            'password' => 'MobileSecond!2026',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $secondAdmin->id);
    }

    public function test_completely_unverified_users_are_sent_to_account_verification_after_login(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_users_with_only_a_verified_phone_can_sign_in_normally(): void
    {
        $user = User::factory()->unverified()->create([
            'two_factor_preference' => 'off',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('user.shop.home'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_users_cannot_logout_with_get_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/logout')
            ->assertStatus(405);

        $this->assertAuthenticated();
    }
}
