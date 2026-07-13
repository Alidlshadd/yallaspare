<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\UserTwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
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
        ]);
    }

    public function test_email_is_the_default_channel_and_other_options_are_shown(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'two_factor_preference' => 'email',
            'phone' => '0770 448 8315',
            'phone_verified_at' => now(),
        ]);

        $this->loginAndOpenChallenge($user)
            ->assertOk()
            ->assertSee(__('Change delivery method'))
            ->assertSeeInOrder([__('Email'), __('SMS'), __('WhatsApp')]);

        $this->assertSame('email', session('user_2fa.challenge.channel'));
        Notification::assertSentTo($user, UserTwoFactorCode::class);
        Http::assertNothingSent();
    }

    public function test_user_can_change_two_factor_delivery_to_sms_and_verify_the_new_code(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'two_factor_preference' => 'email',
            'phone' => '0770 448 8315',
            'phone_verified_at' => now(),
        ]);
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

        $this->assertSame($user->id, session('user_2fa.verified_user_id'));
    }

    public function test_user_can_change_two_factor_delivery_to_whatsapp(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'two_factor_preference' => 'email',
            'phone' => '+964 770 448 8315',
            'phone_verified_at' => now(),
        ]);

        Http::fake([
            'https://api.otpiq.test/api/sms' => Http::response([
                'smsId' => 'sms-1234567890abcdef123456',
            ]),
        ]);

        $this->loginAndOpenChallenge($user);

        $this->post(route('user.two-factor.channel'), ['channel' => 'whatsapp'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('whatsapp', session('user_2fa.challenge.channel'));
        Http::assertSent(fn (HttpRequest $request): bool => $request['provider'] === 'whatsapp');
    }

    public function test_sms_and_whatsapp_cannot_be_selected_without_a_verified_phone(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'two_factor_preference' => 'email',
            'phone' => '0770 448 8315',
            'phone_verified_at' => null,
        ]);

        Http::fake();

        $this->loginAndOpenChallenge($user)
            ->assertSee(__('Verify phone first'));

        $this->post(route('user.two-factor.channel'), ['channel' => 'sms'])
            ->assertSessionHasErrors('channel');

        $this->assertSame('email', session('user_2fa.challenge.channel'));
        Http::assertNothingSent();
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
