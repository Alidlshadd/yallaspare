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

    public function test_social_login_buttons_are_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('social.redirect', ['provider' => 'google']), false)
            ->assertSee(route('social.redirect', ['provider' => 'apple']), false)
            ->assertSee('Continue with Google')
            ->assertSee('Continue with Apple');

        $this->get('/register')
            ->assertOk()
            ->assertSee(route('social.redirect', ['provider' => 'google']), false)
            ->assertSee(route('social.redirect', ['provider' => 'apple']), false);
    }

    public function test_google_callback_creates_verified_user_and_social_account(): void
    {
        $this->mockSocialiteUser('google', [
            'id' => 'google-123',
            'name' => 'Buyer Account',
            'email' => 'Buyer@Example.com',
            'email_verified' => true,
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'buyer@example.com')->firstOrFail();

        $this->assertSame('Buyer Account', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-123',
            'email' => 'buyer@example.com',
        ]);
    }

    public function test_verified_social_email_links_existing_account(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'buyer@example.com',
        ]);

        $this->mockSocialiteUser('google', [
            'id' => 'google-456',
            'name' => 'Buyer Account',
            'email' => 'buyer@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('user.shop.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-456',
        ]);
    }

    public function test_unverified_social_email_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'buyer@example.com',
        ]);

        $this->mockSocialiteUser('google', [
            'id' => 'google-789',
            'name' => 'Buyer Account',
            'email' => 'buyer@example.com',
            'email_verified' => false,
        ]);

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-789',
        ]);
    }

    public function test_social_email_does_not_auto_link_admin_panel_accounts(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
        $user->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->mockSocialiteUser('google', [
            'id' => 'google-admin',
            'name' => 'Admin Account',
            'email' => 'admin@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-admin',
        ]);
    }

    public function test_redirect_requires_provider_configuration(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $response = $this->get(route('social.redirect', ['provider' => 'google']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    private function mockSocialiteUser(string $provider, array $attributes): void
    {
        $providerMock = Mockery::mock();
        $providerMock->shouldReceive('user')
            ->once()
            ->andReturn(SocialiteUser::fake($attributes));

        Socialite::shouldReceive('driver')
            ->once()
            ->with($provider)
            ->andReturn($providerMock);
    }
}
