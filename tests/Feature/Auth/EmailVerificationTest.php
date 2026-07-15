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

    public function test_account_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create([
            'email' => 'customer@gmail.com',
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertSee('Verify your account');
        $response->assertSee('Verify and continue');
        $response->assertSee('Resend code');
        $response->assertSee('Other options');
        $response->assertSee('cu******@gmail.com');
        $response->assertSee('data-otp-form', false);
        $response->assertSee('data-channel-toggle', false);
        $response->assertSee('data-channel-panel', false);
    }

    public function test_verification_screen_lists_email_sms_and_whatsapp_options(): void
    {
        config([
            'services.otpiq.api_key' => 'sk_dev_test_key',
            'services.otpiq.base_url' => 'https://api.otpiq.test/api',
        ]);

        $user = User::factory()->unverified()->unverifiedPhone()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertSee('Choose how to receive your code');
        $response->assertSee('WhatsApp');
        $response->assertSee('data-channel-form', false);
        $response->assertSee(route('verification.channel'), false);
    }

    public function test_users_with_a_verified_phone_are_redirected_away_from_the_screen(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertRedirect(route('user.shop.home'));
    }

    public function test_resend_verification_email_sends_immediate_notification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->unverifiedPhone()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status');

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

    public function test_completely_unverified_users_cannot_access_verified_customer_routes(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create();

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_one_verified_channel_is_enough_for_customer_routes(): void
    {
        $phoneOnly = User::factory()->unverified()->create();
        $emailOnly = User::factory()->unverifiedPhone()->create();

        $this->actingAs($phoneOnly)->get(route('cart.index'))->assertOk();
        $this->actingAs($emailOnly)->get(route('cart.index'))->assertOk();
    }

    public function test_completely_unverified_mobile_users_cannot_receive_login_tokens(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create();

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

    public function test_mobile_users_with_only_a_verified_phone_can_receive_login_tokens(): void
    {
        $user = User::factory()->unverified()->create();

        $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.email_verified', false)
            ->assertJsonStructure(['token']);
    }

    public function test_mobile_registration_requires_email_verification_before_token_issue(): void
    {
        Event::fake();

        $this->postJson('/api/mobile/register', [
            'name' => 'Mobile User',
            'email' => 'mobile@example.com',
            'phone' => '07704488315',
            'password' => 'YallaTest!2026',
        ])
            ->assertCreated()
            ->assertJson([
                'verification_required' => true,
            ])
            ->assertJsonMissingPath('token');

        Event::assertDispatched(\Illuminate\Auth\Events\Registered::class);
        $this->assertDatabaseHas('users', [
            'email' => 'mobile@example.com',
            'phone' => '+9647704488315',
            'email_verified_at' => null,
        ]);
    }

    public function test_existing_unverified_mobile_tokens_cannot_access_verified_api_routes(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/me')->assertForbidden();
    }

    public function test_email_can_be_verified_with_code(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create();

        Event::fake();
        $code = EmailVerificationCode::generateFor($user);

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => $code,
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('user.shop.home'));
        $response->assertSessionHas('success');
    }

    public function test_email_is_not_verified_with_invalid_code(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create();
        EmailVerificationCode::generateFor($user);

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.verify'), [
                'code' => '000000',
            ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_code_is_invalidated_after_too_many_failed_attempts(): void
    {
        config(['security.email_verification.max_attempts' => 2]);

        $user = User::factory()->unverified()->unverifiedPhone()->create();
        $code = EmailVerificationCode::generateFor($user);

        $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '000000',
        ]);

        $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '111111',
        ]);

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.verify'), [
                'code' => $code,
            ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('code');

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
            ['id' => $user->id, 'hash' => sha1($user->email)]
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
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
