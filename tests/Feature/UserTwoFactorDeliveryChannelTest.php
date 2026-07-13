<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\UserTwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserTwoFactorDeliveryChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.otpiq.api_key' => 'sk_dev_test_key',
            'services.otpiq.base_url' => 'https://api.otpiq.test/api',
            'services.otpiq.whatsapp.enabled' => false,
        ]);
    }

    public function test_email_is_the_default_channel_and_methods_are_in_the_secondary_dialog(): void
    {
        Notification::fake();
        $user = $this->twoFactorUser();

        $this->loginAndOpenChallenge($user)
            ->assertOk()
            ->assertSee(__('Enter your verification code'))
            ->assertSee(__('Use another verification method'))
            ->assertSeeInOrder([__('Email'), __('SMS'), __('WhatsApp')])
            ->assertDontSee(__('Delivery Method'))
            ->assertDontSee(__('Verify phone first'));

        $this->assertSame('email', session('user_2fa.challenge.channel'));
        Notification::assertSentTo($user, UserTwoFactorCode::class);
        Http::assertNothingSent();
    }

    public function test_unverified_phone_can_receive_sms_and_successful_otp_verifies_it(): void
    {
        Notification::fake();
        $user = $this->twoFactorUser(['phone_verified_at' => null]);
        $sentCode = null;

        Http::fake(function (HttpRequest $request) use (&$sentCode) {
            $sentCode = (string) $request['verificationCode'];

            return Http::response(['smsId' => 'sms-1234567890abcdef123456']);
        });

        $this->loginAndOpenChallenge($user);

        $this->post(route('user.two-factor.channel'), ['channel' => 'sms'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('sms', session('user_2fa.challenge.channel'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $sentCode);
        Http::assertSent(fn (HttpRequest $request): bool => $request['provider'] === 'sms'
            && $request['phoneNumber'] === '9647704488315');

        $this->post(route('user.two-factor.verify'), ['code' => $sentCode])
            ->assertRedirect(route('user.shop.home'));

        $this->assertNotNull($user->refresh()->phone_verified_at);
        $this->assertSame($user->id, session('user_2fa.verified_user_id'));
        $this->assertNull(session('user_2fa.challenge'));
    }

    public function test_whatsapp_is_available_only_when_template_configuration_is_complete(): void
    {
        Notification::fake();
        $user = $this->twoFactorUser(['phone_verified_at' => null]);

        $this->loginAndOpenChallenge($user)
            ->assertSee(__('WhatsApp verification is currently unavailable.'));

        $this->post(route('user.two-factor.channel'), ['channel' => 'whatsapp'])
            ->assertSessionHasErrors('channel');

        $this->assertSame('email', session('user_2fa.challenge.channel'));
        Http::assertNothingSent();

        config([
            'services.otpiq.whatsapp.enabled' => true,
            'services.otpiq.whatsapp.account_id' => 'wa-account',
            'services.otpiq.whatsapp.phone_id' => 'wa-phone',
            'services.otpiq.whatsapp.template_name' => 'yallaspare_otp',
        ]);
        Http::fake([
            'https://api.otpiq.test/api/sms' => Http::response(['smsId' => 'wa-1234567890abcdef123456']),
        ]);

        $this->post(route('user.two-factor.channel'), ['channel' => 'whatsapp'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('whatsapp', session('user_2fa.challenge.channel'));
        Http::assertSent(fn (HttpRequest $request): bool => $request['provider'] === 'whatsapp'
            && $request['whatsappAccountId'] === 'wa-account'
            && $request['whatsappPhoneId'] === 'wa-phone'
            && $request['templateName'] === 'yallaspare_otp');
    }

    public function test_whatsapp_otp_verifies_the_phone_number(): void
    {
        Notification::fake();
        config([
            'services.otpiq.whatsapp.enabled' => true,
            'services.otpiq.whatsapp.account_id' => 'wa-account',
            'services.otpiq.whatsapp.phone_id' => 'wa-phone',
            'services.otpiq.whatsapp.template_name' => 'yallaspare_otp',
        ]);
        $user = $this->twoFactorUser(['phone_verified_at' => null]);
        $sentCode = null;

        Http::fake(function (HttpRequest $request) use (&$sentCode) {
            $sentCode = (string) $request['verificationCode'];

            return Http::response(['smsId' => 'wa-1234567890abcdef123456']);
        });

        $this->loginAndOpenChallenge($user);

        $this->post(route('user.two-factor.channel'), ['channel' => 'whatsapp'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('whatsapp', session('user_2fa.challenge.channel'));
        Http::assertSent(fn (HttpRequest $request): bool => $request['provider'] === 'whatsapp'
            && $request['templateName'] === 'yallaspare_otp');

        $this->post(route('user.two-factor.verify'), ['code' => $sentCode])
            ->assertRedirect(route('user.shop.home'));

        $this->assertNotNull($user->refresh()->phone_verified_at);
        $this->assertNull(session('user_2fa.challenge'));

        // A used OTP cannot be replayed: the challenge was consumed.
        $this->post(route('user.two-factor.verify'), ['code' => $sentCode])
            ->assertSessionHasErrors('code');
    }

    public function test_changing_method_invalidates_the_previous_otp(): void
    {
        Notification::fake();
        $user = $this->twoFactorUser(['phone_verified_at' => null]);
        $emailCode = null;
        $smsCode = null;

        Notification::assertNothingSent();
        Http::fake(function (HttpRequest $request) use (&$smsCode) {
            $smsCode = (string) $request['verificationCode'];

            return Http::response(['smsId' => 'sms-1234567890abcdef123456']);
        });

        $this->loginAndOpenChallenge($user);
        Notification::assertSentTo($user, UserTwoFactorCode::class, function (UserTwoFactorCode $notification) use (&$emailCode) {
            $emailCode = $notification->code;

            return true;
        });

        $this->post(route('user.two-factor.channel'), ['channel' => 'sms'])->assertSessionHas('status');

        $this->assertFalse(Hash::check((string) $emailCode, (string) session('user_2fa.challenge.hash')));
        $this->assertTrue(Hash::check((string) $smsCode, (string) session('user_2fa.challenge.hash')));
        $this->post(route('user.two-factor.verify'), ['code' => $emailCode])
            ->assertSessionHasErrors('code');
    }

    public function test_api_failure_keeps_the_existing_method_and_challenge(): void
    {
        Notification::fake();
        $user = $this->twoFactorUser();
        Http::fake(['*' => Http::response(['message' => 'provider failed'], 500)]);

        $this->loginAndOpenChallenge($user);
        $oldChallenge = session('user_2fa.challenge');

        $this->post(route('user.two-factor.channel'), ['channel' => 'sms'])
            ->assertSessionHasErrors('channel');

        $this->assertSame('email', session('user_2fa.challenge.channel'));
        $this->assertSame($oldChallenge['hash'], session('user_2fa.challenge.hash'));
    }

    public function test_resend_is_rate_limited_for_sixty_seconds(): void
    {
        Notification::fake();
        $user = $this->twoFactorUser();

        $this->loginAndOpenChallenge($user);
        Notification::assertSentToTimes($user, UserTwoFactorCode::class, 1);

        $this->post(route('user.two-factor.resend'))
            ->assertSessionHasErrors('code');

        Notification::assertSentToTimes($user, UserTwoFactorCode::class, 1);
    }

    private function twoFactorUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'two_factor_preference' => 'email',
            'phone' => '0770 448 8315',
            'phone_verified_at' => now(),
        ], $attributes));
    }

    private function loginAndOpenChallenge(User $user)
    {
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('user.two-factor.challenge'));

        return $this->get(route('user.two-factor.challenge'));
    }
}
