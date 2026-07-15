<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ImmediateVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Registration verification: the code goes out by email first, and the user
 * can switch delivery to SMS or WhatsApp from the "Other options" panel.
 * Confirming any one channel activates the account.
 */
class RegistrationPhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.otpiq.api_key' => 'sk_dev_test_key',
            'services.otpiq.base_url' => 'https://api.otpiq.test/api',
            'services.otpiq.provider' => 'sms',
            'services.otpiq.default_country_code' => '964',
            'services.otpiq.verification_ttl' => 10,
        ]);
    }

    public function test_successful_registration_emails_the_code_and_redirects_to_account_verification(): void
    {
        Notification::fake();
        Http::fake();

        $this->register()->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'phone' => '+9647700000001',
        ]);

        Notification::assertSentTo(
            User::where('email', 'new-user@example.com')->first(),
            ImmediateVerifyEmail::class
        );

        // No SMS goes out at registration anymore.
        Http::assertNothingSent();
    }

    public function test_account_is_kept_when_the_verification_email_fails_during_registration(): void
    {
        // Point the mailer at a closed local port so the sync send throws.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 1,
        ]);

        $this->register()
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('code');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new-user@example.com']);
        $this->assertNull(User::where('email', 'new-user@example.com')->first()->email_verified_at);
    }

    public function test_registration_with_an_already_registered_phone_is_rejected_with_a_clear_message(): void
    {
        User::factory()->create(['phone' => '+9647700000001']);

        $response = $this->register();

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'phone' => __('This phone number is already registered. Please sign in or use another number.'),
        ]);
    }

    public function test_switching_the_channel_to_sms_sends_the_code_via_otpiq(): void
    {
        Notification::fake();
        $this->fakeSuccessfulSms();
        $this->register();

        $this->post(route('verification.channel'), ['channel' => 'sms'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://api.otpiq.test/api/sms'
                && $request['smsType'] === 'verification'
                && $request['provider'] === 'sms'
                && $request['phoneNumber'] === '9647700000001'
                && preg_match('/^\d{6}$/', (string) $request['verificationCode']) === 1;
        });
    }

    public function test_switching_the_channel_to_whatsapp_sends_the_code_via_otpiq(): void
    {
        config([
            'services.otpiq.whatsapp.enabled' => true,
            'services.otpiq.whatsapp.account_id' => 'wa-account',
            'services.otpiq.whatsapp.phone_id' => 'wa-phone',
            'services.otpiq.whatsapp.template_name' => 'yallaspare_otp',
        ]);

        Notification::fake();
        $this->fakeSuccessfulSms();
        $this->register();

        $this->post(route('verification.channel'), ['channel' => 'whatsapp'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Http::assertSent(fn (HttpRequest $request): bool => $request['provider'] === 'whatsapp');
    }

    public function test_a_correct_sms_code_verifies_the_phone_and_activates_the_account(): void
    {
        $sentCode = $this->registerAndSwitchToSms();

        $this->post(route('verification.verify'), ['code' => $sentCode])
            ->assertRedirect(route('user.shop.home'))
            ->assertSessionHas('success');

        $user = User::where('email', 'new-user@example.com')->first();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->email_verified_at);

        // The account now passes the customer verification gates.
        $this->get(route('cart.index'))->assertOk();
    }

    public function test_a_wrong_sms_code_is_rejected(): void
    {
        $sentCode = $this->registerAndSwitchToSms();

        $wrongCode = $sentCode === '111111' ? '222222' : '111111';

        $this->post(route('verification.verify'), ['code' => $wrongCode])
            ->assertSessionHasErrors('code');

        $this->assertNull(User::where('email', 'new-user@example.com')->first()->phone_verified_at);
    }

    public function test_an_expired_sms_code_is_rejected(): void
    {
        $sentCode = $this->registerAndSwitchToSms();

        $this->travel(11)->minutes();

        $this->post(route('verification.verify'), ['code' => $sentCode])
            ->assertSessionHasErrors('code');

        $this->assertNull(User::where('email', 'new-user@example.com')->first()->phone_verified_at);
    }

    public function test_resend_is_blocked_by_the_sixty_second_cooldown(): void
    {
        Notification::fake();
        $this->register();

        $this->post(route('verification.send'))
            ->assertSessionHasErrors('code');

        // Only the registration email went out; the cooldown blocked the resend.
        Notification::assertSentToTimes(
            User::where('email', 'new-user@example.com')->first(),
            ImmediateVerifyEmail::class,
            1
        );
    }

    public function test_resend_works_again_after_the_cooldown_and_is_rate_limited(): void
    {
        Notification::fake();
        $this->register();

        $this->travel(61)->seconds();
        $this->post(route('verification.send'))
            ->assertSessionHas('status');

        Notification::assertSentToTimes(
            User::where('email', 'new-user@example.com')->first(),
            ImmediateVerifyEmail::class,
            2
        );

        // The route throttle allows 3 requests per 10 minutes; the 4th hit is rejected.
        $this->travel(61)->seconds();
        $this->post(route('verification.send'));
        $this->post(route('verification.send'));
        $this->post(route('verification.send'))->assertStatus(429);
    }

    public function test_unavailable_channels_cannot_be_selected(): void
    {
        Notification::fake();
        // No OTPiq key => SMS and WhatsApp are unavailable.
        config(['services.otpiq.api_key' => '']);
        $this->register();

        $this->post(route('verification.channel'), ['channel' => 'sms'])
            ->assertSessionHasErrors('channel');
    }

    public function test_customers_with_one_unverified_channel_still_pass_the_gates(): void
    {
        $phoneOnly = User::factory()->unverified()->create();
        $emailOnly = User::factory()->unverifiedPhone()->create();

        $this->actingAs($phoneOnly)->get(route('user.account.edit'))->assertOk();
        $this->actingAs($emailOnly)->get(route('user.account.edit'))->assertOk();
    }

    public function test_completely_unverified_customers_are_gated_to_account_verification(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create();

        $this->actingAs($user)
            ->get(route('user.account.edit'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_login_redirects_completely_unverified_users_to_account_verification(): void
    {
        $user = User::factory()->unverified()->unverifiedPhone()->create([
            'two_factor_preference' => 'off',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('verification.notice'));
    }

    public function test_admin_users_are_not_locked_by_the_phone_verification_gate(): void
    {
        config(['security.admin_two_factor.enabled' => false]);

        $admin = User::factory()->unverifiedPhone()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->actingAs($admin)
            ->get(route('phone.verify'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_phone_verify_page_is_still_available_for_users_with_an_unverified_phone(): void
    {
        // The standalone phone verification page remains for the change-phone
        // flow; it is no longer part of the registration path. A user with a
        // verified email but unverified phone can still use it.
        $this->fakeSuccessfulSms();
        $user = User::factory()->unverifiedPhone()->create();

        $this->actingAs($user)
            ->get(route('phone.verify'))
            ->assertOk()
            ->assertSee(__('Verify your phone number'));
    }

    public function test_user_without_a_phone_is_sent_to_phone_setup_instead(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)
            ->get(route('phone.verify'))
            ->assertRedirect(route('user.phone.setup'));
    }

    public function test_verified_phone_user_visiting_the_page_is_redirected_away(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('phone.verify'))
            ->assertRedirect(route('user.shop.home'));
    }

    public function test_user_with_unverified_phone_can_change_the_number_before_verifying(): void
    {
        $this->fakeSuccessfulSms();
        $user = User::factory()->unverifiedPhone()->create(['phone' => '+9647700000009']);

        $this->actingAs($user)
            ->get(route('user.phone.setup'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('user.phone.store'), [
                'country_code' => '+964',
                'phone' => '07700000008',
            ])
            ->assertRedirect(route('phone.verify'));

        $this->assertSame('+9647700000008', $user->refresh()->phone);
        Http::assertSent(fn (HttpRequest $request): bool => $request['phoneNumber'] === '9647700000008');
    }

    private function register(string $phone = '07700000001')
    {
        return $this->post('/register', [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'country_code' => '+964',
            'phone' => $phone,
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ]);
    }

    private function registerAndSwitchToSms(): string
    {
        Notification::fake();

        $sentCode = null;

        Http::fake(function (HttpRequest $request) use (&$sentCode) {
            $sentCode = (string) $request['verificationCode'];

            return Http::response(['smsId' => 'sms-1234567890abcdef123456']);
        });

        $this->register()->assertRedirect(route('verification.notice'));

        $this->post(route('verification.channel'), ['channel' => 'sms'])
            ->assertSessionHas('status');

        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $sentCode);

        return (string) $sentCode;
    }

    private function fakeSuccessfulSms(): void
    {
        Http::fake([
            'https://api.otpiq.test/api/sms' => Http::response(['smsId' => 'sms-1234567890abcdef123456']),
        ]);
    }
}
