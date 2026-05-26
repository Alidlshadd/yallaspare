<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ImmediateVerifyEmail;
use App\Providers\RouteServiceProvider;
use App\Support\EmailVerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@gmail.com',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertSee('Verify Email');
        $response->assertSee('Verification code');
        $response->assertSee('Verify Code');
        $response->assertSee('Resend Verification Code');
        $response->assertSee('Open Gmail');
        $response->assertSee($user->email);
        $response->assertDontSee('Verification Required');
        $response->assertSee('data-resend-button', false);
        $response->assertSee('data-cooldown-text', false);
        $response->assertSee('data-cooldown-seconds="60"', false);
    }

    public function test_verification_screen_hides_gmail_button_for_other_email_domains(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertDontSee('Open Gmail');
    }

    public function test_verification_screen_shows_resend_success_state(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['status' => 'verification-code-sent'])
            ->get('/verify-email');

        $response->assertStatus(200);
        $response->assertSee('A fresh verification code has been sent.');
        $response->assertSee('data-verify-toast', false);
        $response->assertSee('data-resend-sent="1"', false);
        $response->assertSee('You can resend another code in :seconds seconds.');
    }

    public function test_resend_verification_email_sends_immediate_notification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, ImmediateVerifyEmail::class);
        $this->assertFalse(new ImmediateVerifyEmail() instanceof ShouldQueue);
    }

    public function test_verification_email_has_branded_copy(): void
    {
        $user = User::factory()->unverified()->create();
        $mail = (new ImmediateVerifyEmail('123456'))->toMail($user);

        $this->assertSame('Verify your YallaSpare email address', $mail->subject);
        $this->assertSame('Welcome to YallaSpare', $mail->greeting);
        $this->assertNull($mail->actionText);
        $this->assertContains('Enter this verification code on the YallaSpare verification screen to protect your account and unlock checkout, orders, saved addresses, and account settings.', $mail->introLines);
        $this->assertContains('Your verification code is 123456.', $mail->introLines);
        $this->assertContains('This verification code expires in 60 minutes.', $mail->introLines);
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

    public function test_email_can_be_verified_with_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();
        $code = EmailVerificationCode::generateFor($user);

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'verification_code' => $code,
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        EmailVerificationCode::generateFor($user);

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.verify'), [
                'verification_code' => '000000',
            ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('verification_code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_code_is_invalidated_after_too_many_failed_attempts(): void
    {
        config(['security.email_verification.max_attempts' => 2]);

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $code = EmailVerificationCode::generateFor($user);

        $this->actingAs($user)->post(route('verification.verify'), [
            'verification_code' => '000000',
        ]);

        $this->actingAs($user)->post(route('verification.verify'), [
            'verification_code' => '111111',
        ]);

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.verify'), [
                'verification_code' => $code,
            ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('verification_code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_legacy_signed_email_link_still_verifies_email(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify-link',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            false
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_legacy_hash(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify-link',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
            false
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
