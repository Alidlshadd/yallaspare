<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

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

    public function test_successful_registration_sends_a_verification_sms_and_redirects_to_phone_verify(): void
    {
        $this->fakeSuccessfulSms();

        $this->register()->assertRedirect(route('phone.verify'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'phone' => '+9647700000001',
        ]);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://api.otpiq.test/api/sms'
                && $request['smsType'] === 'verification'
                && $request['provider'] === 'sms'
                && $request['phoneNumber'] === '9647700000001'
                && preg_match('/^\d{6}$/', (string) $request['verificationCode']) === 1;
        });
    }

    public function test_account_is_kept_when_the_sms_api_fails_during_registration(): void
    {
        Http::fake([
            'https://api.otpiq.test/api/sms' => Http::response(['message' => 'provider down'], 500),
        ]);

        $this->register()
            ->assertRedirect(route('phone.verify'))
            ->assertSessionHasErrors('code');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new-user@example.com']);
        $this->assertNull(User::where('email', 'new-user@example.com')->first()->phone_verified_at);
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

    public function test_correct_otp_verifies_the_phone_and_continues_to_email_verification(): void
    {
        $sentCode = $this->registerAndCaptureCode();

        $this->post(route('phone.verify.confirm'), ['code' => $sentCode])
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'new-user@example.com')->first();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->email_verified_at);
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $sentCode = $this->registerAndCaptureCode();

        $wrongCode = $sentCode === '111111' ? '222222' : '111111';

        $this->post(route('phone.verify.confirm'), ['code' => $wrongCode])
            ->assertSessionHasErrors('code');

        $this->assertNull(User::where('email', 'new-user@example.com')->first()->phone_verified_at);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $sentCode = $this->registerAndCaptureCode();

        $this->travel(11)->minutes();

        $this->post(route('phone.verify.confirm'), ['code' => $sentCode])
            ->assertSessionHasErrors('code');

        $this->assertNull(User::where('email', 'new-user@example.com')->first()->phone_verified_at);
    }

    public function test_resend_is_blocked_by_the_sixty_second_cooldown(): void
    {
        $this->fakeSuccessfulSms();
        $this->register();

        $this->post(route('phone.verify.resend'))
            ->assertSessionHasErrors('code');

        // Only the registration SMS went out; the cooldown blocked the resend.
        Http::assertSentCount(1);
    }

    public function test_resend_works_again_after_the_cooldown_and_is_rate_limited(): void
    {
        $this->fakeSuccessfulSms();
        $this->register();

        $this->travel(61)->seconds();
        $this->post(route('phone.verify.resend'))
            ->assertSessionHas('status');

        Http::assertSentCount(2);

        // The route throttle allows 3 requests per 10 minutes; the 4th hit is rejected.
        $this->travel(61)->seconds();
        $this->post(route('phone.verify.resend'));
        $this->travel(61)->seconds();
        $this->post(route('phone.verify.resend'));
        $this->post(route('phone.verify.resend'))->assertStatus(429);
    }

    public function test_phone_verify_page_is_shown_to_users_with_an_unverified_phone(): void
    {
        $user = User::factory()->unverifiedPhone()->create();

        $this->actingAs($user)
            ->get(route('phone.verify'))
            ->assertOk()
            ->assertSee(__('Verify your phone number'))
            ->assertSee(__('user.send_verification_code'));
    }

    public function test_user_without_a_phone_is_sent_to_phone_setup_instead(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)
            ->get(route('phone.verify'))
            ->assertRedirect(route('user.phone.setup'));
    }

    public function test_customer_with_unverified_phone_cannot_reach_gated_customer_routes(): void
    {
        $user = User::factory()->unverifiedPhone()->create();

        $this->actingAs($user)
            ->get(route('user.account.edit'))
            ->assertRedirect(route('phone.verify'));

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertRedirect(route('phone.verify'));
    }

    public function test_login_redirects_users_with_unverified_phone_to_the_verification_page(): void
    {
        $user = User::factory()->unverifiedPhone()->create([
            'two_factor_preference' => 'off',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('phone.verify'));
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

    private function registerAndCaptureCode(): string
    {
        $sentCode = null;

        Http::fake(function (HttpRequest $request) use (&$sentCode) {
            $sentCode = (string) $request['verificationCode'];

            return Http::response(['smsId' => 'sms-1234567890abcdef123456']);
        });

        $this->register()->assertRedirect(route('phone.verify'));

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
