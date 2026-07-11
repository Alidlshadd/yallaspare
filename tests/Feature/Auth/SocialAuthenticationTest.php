<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_login_buttons_render_google_link_and_disabled_apple_button(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('auth.google.redirect'), false)
            ->assertSee('Continue with Google')
            ->assertSee('Continue with Apple')
            ->assertSee('Coming soon')
            ->assertSee('disabled', false)
            ->assertDontSee('/auth/apple', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee(route('auth.google.redirect'), false)
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

    public function test_google_callback_creates_verified_user(): void
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
}
