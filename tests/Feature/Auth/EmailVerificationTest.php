<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertSee('Verify Email');
        $response->assertSee('Resend Verification Email');
        $response->assertSee($user->email);
    }

    public function test_unverified_users_cannot_access_verified_customer_routes(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_mobile_users_cannot_receive_login_tokens(): void
    {
        $user = User::factory()->unverified()->create();

        $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertForbidden()
            ->assertJson([
                'verification_required' => true,
            ])
            ->assertJsonMissingPath('token');
    }

    public function test_mobile_registration_requires_email_verification_before_token_issue(): void
    {
        Event::fake();

        $this->postJson('/api/mobile/register', [
            'name' => 'Mobile User',
            'email' => 'mobile@example.com',
            'password' => 'password',
        ])
            ->assertCreated()
            ->assertJson([
                'verification_required' => true,
            ])
            ->assertJsonMissingPath('token');

        Event::assertDispatched(\Illuminate\Auth\Events\Registered::class);
        $this->assertDatabaseHas('users', [
            'email' => 'mobile@example.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_existing_unverified_mobile_tokens_cannot_access_verified_api_routes(): void
    {
        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/me')->assertForbidden();
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
