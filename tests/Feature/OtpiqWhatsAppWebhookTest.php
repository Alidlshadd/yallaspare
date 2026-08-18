<?php

namespace Tests\Feature;

use App\Jobs\ProcessOtpiqWhatsAppWebhook;
use App\Models\OtpiqWebhookEvent;
use App\Models\Setting;
use App\Models\User;
use App\Services\Otpiq\OtpiqInboundSettings;
use App\Services\Otpiq\OtpiqWhatsAppEventProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OtpiqWhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'feature-webhook-secret-that-must-never-render';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.otpiq.webhook_secret', self::SECRET);
        config()->set('services.otpiq.whatsapp.admin_visible', true);
        config()->set('services.otpiq.whatsapp_enabled', true);
        config()->set('services.otpiq.webhook_max_body_bytes', 1048576);
        Http::preventStrayRequests();
    }

    public function test_hidden_whatsapp_webhook_returns_not_found_without_storing_or_dispatching(): void
    {
        config()->set('services.otpiq.whatsapp.admin_visible', false);
        Queue::fake();

        $this->sendWebhook([], 'event-hidden-whatsapp')->assertNotFound();

        $this->assertDatabaseMissing('otpiq_webhook_events', ['event_id' => 'event-hidden-whatsapp']);
        Queue::assertNothingPushed();
    }

    public function test_valid_hmac_webhook_returns_200(): void
    {
        Queue::fake();

        $response = $this->sendWebhook(['message' => ['text' => 'Hello']], 'event-valid');

        $response->assertOk()->assertJson(['success' => true, 'duplicate' => false]);
    }

    public function test_unconfigured_secret_fails_closed_with_503_without_exposing_a_value(): void
    {
        config()->set('services.otpiq.webhook_secret', null);

        $this->sendWebhook([], 'event-no-secret')
            ->assertStatus(503)
            ->assertExactJson(['success' => false, 'message' => 'Webhook service is not configured.']);
    }

    public function test_old_but_valid_timestamp_is_not_rejected(): void
    {
        Queue::fake();

        $this->sendWebhook([], 'event-old-valid', ['timestamp' => '1'])->assertOk();
    }

    public function test_invalid_signature_returns_401(): void
    {
        Queue::fake();

        $this->sendWebhook([], 'event-bad-signature', ['signature' => 'sha256=invalid'])
            ->assertUnauthorized()
            ->assertExactJson(['success' => false, 'message' => 'Invalid webhook signature.']);
    }

    public function test_missing_timestamp_header_is_rejected(): void
    {
        $this->sendWebhook([], 'event-no-time', ['omit' => ['timestamp']])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Missing required webhook headers.');
    }

    public function test_missing_signature_header_is_rejected(): void
    {
        $this->sendWebhook([], 'event-no-signature', ['omit' => ['signature']])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Missing required webhook headers.');
    }

    public function test_missing_event_id_header_is_rejected(): void
    {
        $this->sendWebhook([], 'event-not-used', ['omit' => ['event_id']])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Missing required webhook headers.');
    }

    public function test_duplicate_event_id_creates_only_one_record(): void
    {
        Queue::fake();
        $payload = ['message' => ['text' => 'Once']];

        $this->sendWebhook($payload, 'event-duplicate-count')->assertOk();
        $this->sendWebhook($payload, 'event-duplicate-count')->assertOk();

        $this->assertSame(1, OtpiqWebhookEvent::query()->where('event_id', 'event-duplicate-count')->count());
    }

    public function test_duplicate_event_returns_successful_duplicate_response(): void
    {
        Queue::fake();

        $this->sendWebhook([], 'event-duplicate-response')->assertOk();
        $this->sendWebhook([], 'event-duplicate-response')
            ->assertOk()
            ->assertExactJson(['success' => true, 'duplicate' => true]);
    }

    public function test_raw_payload_is_stored_without_reconstruction(): void
    {
        Queue::fake();
        $payload = [
            'unknown_future_field' => ['nested' => true, 'number' => 42],
            'message' => ['text' => ['body' => '<b>Hello</b>']],
        ];

        $this->sendWebhook($payload, 'event-raw')->assertOk();

        $this->assertSame($payload, OtpiqWebhookEvent::query()->where('event_id', 'event-raw')->firstOrFail()->raw_payload);
    }

    public function test_valid_event_dispatches_processing_job_only_once(): void
    {
        Queue::fake();

        $this->sendWebhook([], 'event-job-once')->assertOk();
        $this->sendWebhook([], 'event-job-once')->assertOk();

        Queue::assertPushed(ProcessOtpiqWhatsAppWebhook::class, 1);
    }

    public function test_disabled_processing_records_ignored_event_without_dispatching_job(): void
    {
        Queue::fake();
        Setting::setValue(OtpiqInboundSettings::ENABLED_SETTING_KEY, '0');

        $this->sendWebhook([], 'event-ignored')->assertOk();

        $this->assertDatabaseHas('otpiq_webhook_events', [
            'event_id' => 'event-ignored',
            'processing_status' => OtpiqWebhookEvent::STATUS_IGNORED,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_non_admin_user_cannot_access_whatsapp_admin_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get(route('admin.whatsapp.index'))->assertForbidden();
    }

    public function test_admin_can_access_whatsapp_list_and_detail_pages(): void
    {
        $admin = $this->admin();
        $event = $this->event([
            'event_id' => 'event-admin-pages',
            'sender_phone' => '9647701234567',
        ]);

        $this->actingAs($admin)->get(route('admin.whatsapp.index'))
            ->assertOk()
            ->assertSee('9647701234567');
        $this->actingAs($admin)->get(route('admin.whatsapp.events.show', $event))
            ->assertOk()
            ->assertSee('event-admin-pages');
    }

    public function test_webhook_secret_never_appears_in_admin_html(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.whatsapp.index'))
            ->assertOk()
            ->assertDontSee(self::SECRET, false);
    }

    public function test_malformed_json_is_handled_safely(): void
    {
        Queue::fake();
        $rawBody = '{"message":';

        $this->sendRawWebhook($rawBody, 'event-malformed')
            ->assertStatus(400)
            ->assertExactJson(['success' => false, 'message' => 'Invalid JSON payload.']);
        $this->assertDatabaseMissing('otpiq_webhook_events', ['event_id' => 'event-malformed']);
    }

    public function test_job_marks_event_processed_after_success(): void
    {
        $event = $this->event(['event_id' => 'event-job-success']);

        (new ProcessOtpiqWhatsAppWebhook($event->id))->handle(app(OtpiqWhatsAppEventProcessor::class));

        $event->refresh();
        $this->assertSame(OtpiqWebhookEvent::STATUS_PROCESSED, $event->processing_status);
        $this->assertNotNull($event->processed_at);
    }

    public function test_job_marks_event_failed_with_sanitized_error_after_failure(): void
    {
        $event = $this->event(['event_id' => 'event-job-failure']);
        $processor = Mockery::mock(OtpiqWhatsAppEventProcessor::class);
        $processor->shouldReceive('process')->once()->andThrow(new RuntimeException('sensitive provider detail'));

        try {
            (new ProcessOtpiqWhatsAppWebhook($event->id))->handle($processor);
            $this->fail('The processing exception should be rethrown for queue retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Webhook processing failed.', $exception->getMessage());
        }

        $event->refresh();
        $this->assertSame(OtpiqWebhookEvent::STATUS_FAILED, $event->processing_status);
        $this->assertSame('Webhook processing failed.', $event->error_message);
        $this->assertStringNotContainsString('sensitive provider detail', $event->error_message);
    }

    public function test_retry_uses_existing_failed_event_and_dispatches_job(): void
    {
        Queue::fake();
        $admin = $this->admin();
        $event = $this->event([
            'event_id' => 'event-retry',
            'processing_status' => OtpiqWebhookEvent::STATUS_FAILED,
            'error_message' => 'Webhook processing failed.',
        ]);

        $this->actingAs($admin)->post(route('admin.whatsapp.events.retry', $event))
            ->assertRedirect();

        $event->refresh();
        $this->assertSame(OtpiqWebhookEvent::STATUS_RECEIVED, $event->processing_status);
        $this->assertSame(1, OtpiqWebhookEvent::query()->where('event_id', 'event-retry')->count());
        Queue::assertPushed(ProcessOtpiqWhatsAppWebhook::class, fn ($job) => $job->eventId === $event->id);
    }

    public function test_webhook_flow_never_calls_the_real_otpiq_api(): void
    {
        Queue::fake();

        $this->sendWebhook(['message' => ['text' => 'Inbound only']], 'event-no-outbound')->assertOk();

        Http::assertNothingSent();
    }

    public function test_rate_limit_response_is_json_even_without_accept_header(): void
    {
        Queue::fake();

        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->sendWebhook([], 'event-rate-'.$attempt, ['omit_accept' => true])->assertOk();
        }

        $this->sendWebhook([], 'event-rate-61', ['omit_accept' => true])
            ->assertStatus(429)
            ->assertHeader('content-type', 'application/json');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function event(array $overrides = []): OtpiqWebhookEvent
    {
        return OtpiqWebhookEvent::query()->create(array_merge([
            'event_id' => 'event-'.uniqid(),
            'event_type' => 'whatsapp.message.received',
            'attempt_number' => 1,
            'webhook_timestamp' => '1786000000',
            'signature_verified' => true,
            'processing_status' => OtpiqWebhookEvent::STATUS_RECEIVED,
            'raw_payload' => ['message' => ['text' => ['body' => 'Hello']]],
            'received_at' => now(),
        ], $overrides));
    }

    private function sendWebhook(array $payload, string $eventId, array $options = [])
    {
        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $this->sendRawWebhook($rawBody ?: '{}', $eventId, $options);
    }

    private function sendRawWebhook(string $rawBody, string $eventId, array $options = [])
    {
        $timestamp = $options['timestamp'] ?? '1786000000';
        $signature = $options['signature'] ?? 'sha256='.hash_hmac(
            'sha256',
            $timestamp.'.'.$rawBody,
            self::SECRET
        );
        $omit = $options['omit'] ?? [];
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_OTPIQ_WEBHOOK_EVENT' => 'whatsapp.message.received',
            'HTTP_X_OTPIQ_WEBHOOK_ATTEMPT' => '1',
        ];

        if (! ($options['omit_accept'] ?? false)) {
            $server['HTTP_ACCEPT'] = 'application/json';
        }

        if (! in_array('timestamp', $omit, true)) {
            $server['HTTP_X_OTPIQ_WEBHOOK_TIMESTAMP'] = $timestamp;
        }
        if (! in_array('signature', $omit, true)) {
            $server['HTTP_X_OTPIQ_WEBHOOK_SIGNATURE'] = $signature;
        }
        if (! in_array('event_id', $omit, true)) {
            $server['HTTP_X_OTPIQ_WEBHOOK_EVENT_ID'] = $eventId;
        }

        return $this->call('POST', '/api/webhooks/otpiq', [], [], [], $server, $rawBody);
    }
}
