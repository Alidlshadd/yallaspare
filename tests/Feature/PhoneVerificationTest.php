<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
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

    public function test_personal_info_page_shows_unverified_phone_action(): void
    {
        $user = User::factory()->create(['phone' => '0770 448 8315']);

        $this->actingAs($user)
            ->get(route('user.account.personal'))
            ->assertOk()
            ->assertSee(__('user.unverified'))
            ->assertSee(__('user.send_verification_code'));
    }

    public function test_user_can_send_and_confirm_phone_verification_code(): void
    {
        $user = User::factory()->create(['phone' => '0770 448 8315']);
        $sentCode = null;

        Http::fake(function (HttpRequest $request) use (&$sentCode) {
            $sentCode = (string) $request['verificationCode'];

            return Http::response([
                'message' => 'SMS task created successfully',
                'smsId' => 'sms-1234567890abcdef123456',
                'remainingCredit' => 1000,
                'cost' => 80,
                'canCover' => true,
                'paymentType' => 'prepaid',
            ]);
        });

        $this->actingAs($user)
            ->post(route('user.account.phone-verification.send'))
            ->assertRedirect()
            ->assertSessionHas('phone_verification_sent');

        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $sentCode);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://api.otpiq.test/api/sms'
                && $request->hasHeader('Authorization', 'Bearer sk_dev_test_key')
                && $request['phoneNumber'] === '9647704488315'
                && $request['smsType'] === 'verification'
                && $request['provider'] === 'sms';
        });

        $this->actingAs($user)
            ->post(route('user.account.phone-verification.verify'), [
                'verification_code' => $sentCode,
            ])
            ->assertRedirect()
            ->assertSessionHas('phone_verification_success');

        $this->assertNotNull($user->refresh()->phone_verified_at);
    }

    public function test_invalid_phone_verification_code_is_rejected(): void
    {
        $user = User::factory()->create(['phone' => '+964 770 448 8315']);

        Http::fake([
            'https://api.otpiq.test/api/sms' => Http::response([
                'smsId' => 'sms-1234567890abcdef123456',
            ]),
        ]);

        $this->actingAs($user)->post(route('user.account.phone-verification.send'));

        $this->actingAs($user)
            ->post(route('user.account.phone-verification.verify'), [
                'verification_code' => '000000',
            ])
            ->assertSessionHasErrors('verification_code');

        $this->assertNull($user->refresh()->phone_verified_at);
    }

    public function test_changing_phone_number_clears_verified_status(): void
    {
        $user = User::factory()->create([
            'phone' => '+964 770 448 8315',
            'phone_verified_at' => now(),
        ]);

        $user->phone = '+964 750 123 4567';
        $user->save();

        $this->assertNull($user->refresh()->phone_verified_at);
    }

    public function test_same_normalized_phone_keeps_verified_status(): void
    {
        $user = User::factory()->create([
            'phone' => '+964 770 448 8315',
            'phone_verified_at' => now(),
        ]);

        $user->phone = '9647704488315';
        $user->save();

        $this->assertNotNull($user->refresh()->phone_verified_at);
    }

    public function test_sms_failure_does_not_claim_that_a_code_was_sent(): void
    {
        $user = User::factory()->create(['phone' => '0770 448 8315']);

        Http::fake([
            'https://api.otpiq.test/api/sms' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $this->actingAs($user)
            ->post(route('user.account.phone-verification.send'))
            ->assertSessionHasErrors('phone_verification')
            ->assertSessionMissing('phone_verification_sent');
    }
}
