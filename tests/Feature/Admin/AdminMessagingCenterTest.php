<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminMessagingCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.otpiq.api_key' => 'test-api-key',
            'services.otpiq.base_url' => 'https://api.otpiq.test/api',
            'services.otpiq.whatsapp_enabled' => false,
            'security.admin_two_factor.enabled' => false,
        ]);
    }

    public function test_settings_manager_can_open_sms_and_whatsapp_center(): void
    {
        $admin = $this->settingsManager();
        User::factory()->create([
            'phone' => '+9647704488315',
            'phone_verified_at' => now(),
            'sms_notifications' => true,
            'whatsapp_notifications' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.messaging.index'))
            ->assertOk()
            ->assertSee('SMS &amp; WhatsApp Center', false)
            ->assertSee('Provider health')
            ->assertSee('Send test OTP')
            ->assertSee('Verified phones')
            ->assertDontSee('test-api-key');
    }

    public function test_user_without_settings_permission_cannot_open_messaging_center(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('admin.messaging.index'))
            ->assertForbidden();
    }

    public function test_settings_manager_can_send_sms_test_with_e164_payload(): void
    {
        $admin = $this->settingsManager();

        Http::fake([
            'https://api.otpiq.test/api/sms' => Http::response(['smsId' => 'sms-test-1234567890']),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.messaging.test'), [
                'channel' => 'sms',
                'phone' => '07704488315',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn (HttpRequest $request): bool => $request['provider'] === 'sms'
            && $request['phoneNumber'] === '9647704488315'
            && preg_match('/^\d{6}$/', (string) $request['verificationCode']) === 1
            && ! str_contains((string) $request->header('Authorization')[0], 'sk_live_'));
    }

    public function test_whatsapp_test_is_blocked_until_template_configuration_is_complete(): void
    {
        $admin = $this->settingsManager();
        Http::fake();

        $this->actingAs($admin)
            ->post(route('admin.messaging.test'), [
                'channel' => 'whatsapp',
                'phone' => '7704488315',
            ])
            ->assertSessionHasErrors('channel');

        Http::assertNothingSent();
    }

    public function test_configured_whatsapp_test_uses_approved_template_fields(): void
    {
        config([
            'services.otpiq.whatsapp_enabled' => true,
            'services.otpiq.whatsapp_account_id' => 'wa-account',
            'services.otpiq.whatsapp_phone_id' => 'wa-phone',
            'services.otpiq.whatsapp_template_name' => 'yallaspare_otp',
        ]);
        $admin = $this->settingsManager();

        Http::fake([
            'https://api.otpiq.test/api/sms' => Http::response(['smsId' => 'wa-test-1234567890']),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.messaging.test'), [
                'channel' => 'whatsapp',
                'phone' => '+9647704488315',
            ])
            ->assertSessionHas('success');

        Http::assertSent(fn (HttpRequest $request): bool => $request['provider'] === 'whatsapp'
            && $request['whatsappAccountId'] === 'wa-account'
            && $request['whatsappPhoneId'] === 'wa-phone'
            && $request['templateName'] === 'yallaspare_otp');
    }

    public function test_provider_failure_returns_safe_error(): void
    {
        $admin = $this->settingsManager();
        Http::fake(['*' => Http::response(['provider_error' => 'secret detail'], 500)]);

        $this->actingAs($admin)
            ->post(route('admin.messaging.test'), [
                'channel' => 'sms',
                'phone' => '7704488315',
            ])
            ->assertSessionHasErrors('phone')
            ->assertSessionDoesntHaveErrors('provider_error');
    }

    private function settingsManager(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);
    }
}
