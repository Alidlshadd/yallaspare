<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const APPLE_CLIENT_ID = 'com.yallaspare.signin';
    private const APPLE_STATE_CACHE_PREFIX = 'social:apple:state:';

    public function test_social_login_buttons_reflect_provider_configuration(): void
    {
        $this->configureGoogle();

        $this->get('/login')
            ->assertOk()
            ->assertSee(route('auth.google.redirect'), false)
            ->assertSee('Continue with Google')
            ->assertDontSee('Continue with Apple')
            ->assertDontSee('Coming soon')
            ->assertDontSee('/auth/apple', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee(route('auth.google.redirect'), false)
            ->assertDontSee('/auth/apple', false);
    }

    public function test_social_login_section_is_fully_hidden_by_default(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Continue with Google')
            ->assertDontSee('Continue with Apple')
            ->assertDontSee('Coming soon')
            ->assertDontSee('/auth/google', false)
            ->assertDontSee('/auth/apple', false);
    }

    public function test_feature_flags_hide_buttons_even_with_complete_credentials(): void
    {
        $this->configureGoogle();
        config(['services.social_login.visible' => false]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Continue with Google')
            ->assertDontSee('/auth/google', false);

        config(['services.social_login.visible' => true, 'services.google.enabled' => false]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Continue with Google')
            ->assertDontSee('/auth/google', false);
    }

    public function test_google_redirect_is_gated_by_feature_flags(): void
    {
        $this->configureGoogle();
        config(['services.google.enabled' => false]);

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error', 'Google sign-in is not configured yet.');
    }

    public function test_configured_apple_button_renders_link(): void
    {
        $this->configureApple();

        $this->get('/login')
            ->assertOk()
            ->assertSee(route('auth.apple.redirect'), false)
            ->assertSee('Continue with Apple');
    }

    public function test_apple_button_stays_hidden_when_private_key_is_unreadable(): void
    {
        config([
            'services.social_login.visible' => true,
            'services.apple.enabled' => true,
            'services.apple.client_id' => self::APPLE_CLIENT_ID,
            'services.apple.client_secret' => null,
            'services.apple.team_id' => 'TEAM123456',
            'services.apple.key_id' => 'KEY1234567',
            'services.apple.private_key' => 'C:/nonexistent/apple/AuthKey_MISSING.p8',
            'services.apple.redirect' => 'https://yallaspare.test/auth/apple/callback',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Continue with Apple')
            ->assertDontSee('/auth/apple', false);
    }

    public function test_google_redirect_requires_provider_configuration_without_email_validation_error(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.redirect' => null,
        ]);

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error', 'Google sign-in is not configured yet.');
        $response->assertSessionDoesntHaveErrors(['email']);
    }

    public function test_google_redirect_uses_session_state(): void
    {
        $this->configureGoogle();

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertNotEmpty($query['state'] ?? null);
        $response->assertSessionHas('state', $query['state']);
    }

    public function test_password_login_still_works_after_social_login_changes(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('user.shop.home'));
    }

    public function test_google_callback_creates_verified_user_with_default_role(): void
    {
        $this->mockGoogleUser([
            'id' => 'google-123',
            'name' => 'Buyer Account',
            'email' => 'Buyer@Example.com',
            'email_verified' => true,
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'buyer@example.com')->firstOrFail();

        $this->assertSame('Buyer Account', $user->name);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('https://example.com/avatar.jpg', $user->avatar);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertFalse($user->isAdminPanelUser());
    }

    public function test_google_callback_links_existing_email_without_duplicate_user(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'buyer@example.com',
        ]);

        $this->mockGoogleUser([
            'id' => 'google-456',
            'name' => 'Buyer Account',
            'email' => 'buyer@example.com',
            'email_verified' => true,
            'avatar' => 'https://example.com/buyer.jpg',
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'buyer@example.com')->count());

        $freshUser = $user->fresh();
        $this->assertSame('google-456', $freshUser->google_id);
        $this->assertSame('https://example.com/buyer.jpg', $freshUser->avatar);
        $this->assertNotNull($freshUser->email_verified_at);
    }

    public function test_google_callback_binds_google_id_to_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'google_id' => null,
            'avatar' => null,
        ]);

        $this->mockGoogleUser([
            'id' => 'google-linked',
            'name' => 'Linked User',
            'email' => 'linked@example.com',
            'email_verified' => true,
            'avatar' => 'https://example.com/linked.jpg',
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-linked', $user->fresh()->google_id);
        $this->assertSame('https://example.com/linked.jpg', $user->fresh()->avatar);
    }

    public function test_admin_google_login_redirects_to_admin_dashboard(): void
    {
        config(['security.admin_two_factor.enabled' => false]);

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->mockGoogleUser([
            'id' => 'google-admin',
            'name' => 'Admin Account',
            'email' => 'admin@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->assertSame('google-admin', $admin->fresh()->google_id);
    }

    public function test_user_google_login_redirects_to_user_home(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $this->mockGoogleUser([
            'id' => 'google-user',
            'name' => 'Customer Account',
            'email' => 'customer@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-user', $user->fresh()->google_id);
    }

    public function test_google_callback_rejects_missing_email(): void
    {
        $this->mockGoogleUser([
            'id' => 'google-no-email',
            'name' => 'No Email',
            'email' => null,
            'email_verified' => true,
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $response->assertSessionDoesntHaveErrors(['email']);
        $this->assertGuest();
    }

    public function test_google_callback_rejects_mismatched_google_id_on_same_email(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@example.com',
        ]);
        $user->forceFill(['google_id' => 'google-original'])->save();

        $this->mockGoogleUser([
            'id' => 'google-imposter',
            'name' => 'Imposter',
            'email' => 'victim@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
        $this->assertSame('google-original', $user->fresh()->google_id);
    }

    public function test_banned_user_cannot_sign_in_with_google(): void
    {
        $user = User::factory()->create([
            'email' => 'banned@example.com',
        ]);
        $user->forceFill(['banned_at' => now(), 'ban_reason' => 'Fraud'])->save();

        $this->mockGoogleUser([
            'id' => 'google-banned',
            'name' => 'Banned User',
            'email' => 'banned@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
    }

    public function test_apple_redirect_issues_single_use_state_and_nonce(): void
    {
        $this->configureApple();

        $response = $this->get(route('auth.apple.redirect'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $this->assertStringStartsWith('https://appleid.apple.com/auth/authorize', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertNotEmpty($query['state'] ?? null);
        $this->assertNotEmpty($query['nonce'] ?? null);
        $this->assertSame('form_post', $query['response_mode'] ?? null);
        $this->assertSame(self::APPLE_CLIENT_ID, $query['client_id'] ?? null);
        $this->assertTrue(Cache::has(self::APPLE_STATE_CACHE_PREFIX.hash('sha256', $query['state'])));
    }

    public function test_apple_full_flow_from_redirect_to_callback_creates_user_with_default_role(): void
    {
        $this->configureApple();

        $redirect = $this->get(route('auth.apple.redirect'));
        parse_str((string) parse_url((string) $redirect->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->mockAppleUser([
            'id' => 'apple-123',
            'name' => 'Apple Buyer',
            'email' => 'Apple.Buyer@Example.com',
            'nonce' => $query['nonce'],
            'aud' => self::APPLE_CLIENT_ID,
        ]);

        $response = $this->post(route('auth.apple.callback'), [
            'code' => 'fake-code',
            'state' => $query['state'],
        ]);

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'apple.buyer@example.com')->firstOrFail();

        $this->assertSame('Apple Buyer', $user->name);
        $this->assertSame('apple-123', $user->apple_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertFalse($user->isAdminPanelUser());
    }

    public function test_apple_callback_rejects_get_requests(): void
    {
        $this->configureApple();

        $this->get('/auth/apple/callback?code=fake-code&state=whatever')
            ->assertMethodNotAllowed();
    }

    public function test_apple_callback_rejects_missing_state(): void
    {
        $this->configureApple();

        $response = $this->post(route('auth.apple.callback'), ['code' => 'fake-code']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
    }

    public function test_apple_callback_rejects_unknown_state(): void
    {
        $this->configureApple();

        $response = $this->post(route('auth.apple.callback'), [
            'code' => 'fake-code',
            'state' => Str::random(40),
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
    }

    public function test_apple_callback_rejects_expired_state(): void
    {
        $this->configureApple();
        [$state] = $this->seedAppleState();

        $this->travel(11)->minutes();

        $response = $this->post(route('auth.apple.callback'), [
            'code' => 'fake-code',
            'state' => $state,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
    }

    public function test_apple_callback_state_cannot_be_replayed(): void
    {
        $this->configureApple();
        [$state, $nonce] = $this->seedAppleState();

        $this->mockAppleUser([
            'id' => 'apple-replay',
            'name' => 'Replay Target',
            'email' => 'replay@example.com',
            'nonce' => $nonce,
            'aud' => self::APPLE_CLIENT_ID,
        ]);

        $this->post(route('auth.apple.callback'), ['code' => 'fake-code', 'state' => $state])
            ->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticated();

        Auth::guard('web')->logout();

        $replay = $this->post(route('auth.apple.callback'), ['code' => 'fake-code', 'state' => $state]);

        $replay->assertRedirect(route('login'));
        $replay->assertSessionHas('auth_error');
        $this->assertGuest();
    }

    public function test_apple_callback_rejects_nonce_mismatch(): void
    {
        $this->configureApple();
        [$state] = $this->seedAppleState();

        $this->mockAppleUser([
            'id' => 'apple-nonce',
            'name' => 'Nonce Mismatch',
            'email' => 'nonce@example.com',
            'nonce' => 'a-completely-different-nonce',
            'aud' => self::APPLE_CLIENT_ID,
        ]);

        $response = $this->post(route('auth.apple.callback'), [
            'code' => 'fake-code',
            'state' => $state,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'nonce@example.com']);
    }

    public function test_apple_callback_rejects_wrong_audience(): void
    {
        $this->configureApple();
        [$state, $nonce] = $this->seedAppleState();

        $this->mockAppleUser([
            'id' => 'apple-aud',
            'name' => 'Wrong Audience',
            'email' => 'aud@example.com',
            'nonce' => $nonce,
            'aud' => 'com.some-other-app.signin',
        ]);

        $response = $this->post(route('auth.apple.callback'), [
            'code' => 'fake-code',
            'state' => $state,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'aud@example.com']);
    }

    public function test_apple_callback_links_existing_email_without_duplicate_user(): void
    {
        $this->configureApple();
        [$state, $nonce] = $this->seedAppleState();

        $user = User::factory()->create([
            'email' => 'buyer@example.com',
        ]);

        $this->mockAppleUser([
            'id' => 'apple-456',
            'name' => null,
            'email' => 'buyer@example.com',
            'nonce' => $nonce,
            'aud' => self::APPLE_CLIENT_ID,
        ]);

        $response = $this->post(route('auth.apple.callback'), ['code' => 'fake-code', 'state' => $state]);

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'buyer@example.com')->count());
        $this->assertSame('apple-456', $user->fresh()->apple_id);
    }

    public function test_apple_login_with_null_email_and_name_keeps_existing_profile(): void
    {
        $this->configureApple();
        [$state, $nonce] = $this->seedAppleState();

        $user = User::factory()->create([
            'name' => 'Existing Name',
            'email' => 'existing@example.com',
        ]);
        $user->forceFill(['apple_id' => 'apple-repeat'])->save();

        $this->mockAppleUser([
            'id' => 'apple-repeat',
            'name' => null,
            'email' => null,
            'nonce' => $nonce,
            'aud' => self::APPLE_CLIENT_ID,
        ]);

        $response = $this->post(route('auth.apple.callback'), ['code' => 'fake-code', 'state' => $state]);

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticatedAs($user);

        $fresh = $user->fresh();
        $this->assertSame('Existing Name', $fresh->name);
        $this->assertSame('existing@example.com', $fresh->email);
    }

    public function test_apple_callback_rejects_mismatched_apple_id_on_same_email(): void
    {
        $this->configureApple();
        [$state, $nonce] = $this->seedAppleState();

        $user = User::factory()->create([
            'email' => 'victim@example.com',
        ]);
        $user->forceFill(['apple_id' => 'apple-original'])->save();

        $this->mockAppleUser([
            'id' => 'apple-imposter',
            'name' => 'Imposter',
            'email' => 'victim@example.com',
            'nonce' => $nonce,
            'aud' => self::APPLE_CLIENT_ID,
        ]);

        $response = $this->post(route('auth.apple.callback'), ['code' => 'fake-code', 'state' => $state]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
        $this->assertSame('apple-original', $user->fresh()->apple_id);
    }

    public function test_apple_callback_rejects_unverified_email(): void
    {
        $this->configureApple();
        [$state, $nonce] = $this->seedAppleState();

        $this->mockAppleUser([
            'id' => 'apple-unverified',
            'name' => 'Shady Account',
            'email' => 'shady@example.com',
            'email_verified' => 'false',
            'nonce' => $nonce,
            'aud' => self::APPLE_CLIENT_ID,
        ]);

        $response = $this->post(route('auth.apple.callback'), ['code' => 'fake-code', 'state' => $state]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'shady@example.com']);
    }

    public function test_banned_user_cannot_sign_in_with_apple(): void
    {
        $this->configureApple();
        [$state, $nonce] = $this->seedAppleState();

        $user = User::factory()->create([
            'email' => 'banned-apple@example.com',
        ]);
        $user->forceFill(['apple_id' => 'apple-banned', 'banned_at' => now()])->save();

        $this->mockAppleUser([
            'id' => 'apple-banned',
            'name' => null,
            'email' => null,
            'nonce' => $nonce,
            'aud' => self::APPLE_CLIENT_ID,
        ]);

        $response = $this->post(route('auth.apple.callback'), ['code' => 'fake-code', 'state' => $state]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');
        $this->assertGuest();
    }

    public function test_apple_redirect_requires_provider_configuration(): void
    {
        $response = $this->get(route('auth.apple.redirect'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error', 'Apple sign-in is not configured yet.');
    }

    public function test_csrf_exception_only_covers_the_apple_callback(): void
    {
        $middleware = app(VerifyCsrfToken::class);
        $except = (new ReflectionProperty($middleware, 'except'))->getValue($middleware);

        $this->assertSame(['csp-report', 'auth/apple/callback'], $except);
    }

    public function test_social_identity_columns_are_not_mass_assignable(): void
    {
        $user = User::factory()->create();
        $user->update(['google_id' => 'injected', 'apple_id' => 'injected', 'avatar' => 'https://evil.example/x.png']);

        $fresh = $user->fresh();
        $this->assertNull($fresh->google_id);
        $this->assertNull($fresh->apple_id);
        $this->assertNull($fresh->avatar);
    }

    private function configureGoogle(): void
    {
        config([
            'services.social_login.visible' => true,
            'services.google.enabled' => true,
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://yallaspare.test/auth/google/callback',
        ]);
    }

    private function configureApple(): void
    {
        config([
            'services.social_login.visible' => true,
            'services.apple.enabled' => true,
            'services.apple.client_id' => self::APPLE_CLIENT_ID,
            'services.apple.client_secret' => 'fake-jwt-secret',
            'services.apple.redirect' => 'https://yallaspare.test/auth/apple/callback',
        ]);
    }

    /**
     * Seed the single-use state entry exactly as redirectToApple() would.
     *
     * @return array{0: string, 1: string} [state, nonce]
     */
    private function seedAppleState(): array
    {
        $state = Str::random(40);
        $nonce = Str::random(40);

        Cache::put(
            self::APPLE_STATE_CACHE_PREFIX.hash('sha256', $state),
            hash('sha256', $nonce),
            now()->addMinutes(10)
        );

        return [$state, $nonce];
    }

    private function mockGoogleUser(array $attributes): void
    {
        $providerMock = Mockery::mock();
        $providerMock->shouldReceive('user')
            ->once()
            ->andReturn(SocialiteUser::fake($attributes));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($providerMock);
    }

    private function mockAppleUser(array $attributes): void
    {
        $providerMock = Mockery::mock();
        $providerMock->shouldReceive('stateless')
            ->once()
            ->andReturnSelf();
        $providerMock->shouldReceive('user')
            ->once()
            ->andReturn(SocialiteUser::fake($attributes));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('apple')
            ->andReturn($providerMock);
    }
}
