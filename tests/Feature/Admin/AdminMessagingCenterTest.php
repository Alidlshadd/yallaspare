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
            'services.otpiq.whatsapp.enabled' => false,
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

    public function test_whatsapp_card_is_disabled_and_no_api_call_is_made_when_channel_is_off(): void
    {
        Http::fake();
        $admin = $this->settingsManager();

        $this->actingAs($admin)
            ->get(route('admin.messaging.index'))
            ->assertOk()
            ->assertSee(__('Disabled'))
            ->assertSee('WhatsApp · '.__('Disabled'));

        Http::assertNothingSent();
    }

    public function test_whatsapp_card_lists_missing_configuration_when_enabled_but_incomplete(): void
    {
        Http::fake();
        config(['services.otpiq.whatsapp.enabled' => true]);
        $admin = $this->settingsManager();

        $this->actingAs($admin)
            ->get(route('admin.messaging.index'))
            ->assertOk()
            ->assertSee(__('Configuration required'))
            ->assertSee(__('WhatsApp account ID'))
            ->assertSee(__('WhatsApp phone ID'))
            ->assertSee(__('Approved template name'));

        // Local config is incomplete, so the remote resources check is skipped.
        Http::assertNothingSent();
    }

    public function test_whatsapp_shows_ready_when_config_is_complete_and_template_is_approved(): void
    {
        $this->configureWhatsapp();
        Http::fake([
            'https://api.otpiq.test/api/whatsapp/resources*' => Http::response($this->resourcesPayload('APPROVED')),
        ]);
        $admin = $this->settingsManager();

        $this->actingAs($admin)
            ->get(route('admin.messaging.index'))
            ->assertOk()
            ->assertSee('WhatsApp · '.__('Ready'))
            ->assertSee(__('Template approved on OTPiQ'))
            ->assertDontSee(__('An approved WhatsApp verification template is required.'));
    }

    public function test_whatsapp_is_not_ready_and_test_send_is_blocked_when_template_is_not_approved(): void
    {
        $this->configureWhatsapp();
        Http::fake([
            'https://api.otpiq.test/api/whatsapp/resources*' => Http::response($this->resourcesPayload('REJECTED')),
            'https://api.otpiq.test/api/sms' => Http::response(['smsId' => 'should-never-happen']),
        ]);
        $admin = $this->settingsManager();

        $this->actingAs($admin)
            ->get(route('admin.messaging.index'))
            ->assertOk()
            ->assertSee(__('Configuration required'))
            ->assertSee(__('An approved WhatsApp verification template is required.'));

        $this->actingAs($admin)
            ->post(route('admin.messaging.test'), [
                'channel' => 'whatsapp',
                'phone' => '7704488315',
            ])
            ->assertSessionHasErrors('channel');

        Http::assertNotSent(fn (HttpRequest $request): bool => str_ends_with($request->url(), '/sms'));
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
        $this->configureWhatsapp();
        $admin = $this->settingsManager();

        Http::fake([
            'https://api.otpiq.test/api/whatsapp/resources*' => Http::response($this->resourcesPayload('APPROVED')),
            'https://api.otpiq.test/api/sms' => Http::response(['smsId' => 'wa-test-1234567890']),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.messaging.test'), [
                'channel' => 'whatsapp',
                'phone' => '+9647704488315',
            ])
            ->assertSessionHas('success');

        Http::assertSent(fn (HttpRequest $request): bool => str_ends_with($request->url(), '/sms')
            && $request['provider'] === 'whatsapp'
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

    private function configureWhatsapp(): void
    {
        config([
            'services.otpiq.whatsapp.enabled' => true,
            'services.otpiq.whatsapp.account_id' => 'wa-account',
            'services.otpiq.whatsapp.phone_id' => 'wa-phone',
            'services.otpiq.whatsapp.template_name' => 'yallaspare_otp',
            'services.otpiq.whatsapp.template_language' => 'en',
        ]);
    }

    /**
     * Response shape from GET /whatsapp/resources per the official OTPiQ docs.
     *
     * @return array<string, mixed>
     */
    private function resourcesPayload(string $templateStatus): array
    {
        return [
            'success' => true,
            'data' => [
                'project' => ['id' => 'p1', 'slug' => 'yallaspare', 'name' => 'YallaSpare'],
                'summary' => ['businessCount' => 1, 'accountCount' => 1, 'phoneNumberCount' => 1, 'templateCount' => 1],
                'businesses' => [
                    [
                        'id' => 'biz-1',
                        'businessId' => '123456789012345',
                        'name' => 'YallaSpare Business',
                        'whatsappAccounts' => [
                            [
                                'id' => 'wa-account',
                                'whatsappBusinessId' => '987654321098765',
                                'name' => 'YallaSpare WABA',
                                'accountReviewStatus' => 'APPROVED',
                                'phoneNumbers' => [
                                    [
                                        'id' => 'wa-phone',
                                        'phoneNumberId' => '112233445566778',
                                        'displayPhoneNumber' => '+964 000 000 0000',
                                        'status' => 'CONNECTED',
                                    ],
                                ],
                                'templates' => [
                                    [
                                        'name' => 'yallaspare_otp',
                                        'category' => 'AUTHENTICATION',
                                        'language' => 'en',
                                        'status' => $templateStatus,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
