<?php

namespace Tests\Feature\Admin;

use App\Mail\OperationalNotificationMail;
use App\Jobs\SendEmailBroadcastJob;
use App\Models\EmailBroadcast;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AdminEmailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_manager_can_open_email_center(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.index'))
            ->assertOk()
            ->assertSee('Email Center')
            ->assertSee('Send Test Email')
            ->assertSee('Readiness Checks');
    }

    public function test_email_center_does_not_use_confirm_password_route(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.index'))
            ->assertOk()
            ->assertSee('Email Center');
    }

    public function test_settings_manager_can_send_test_email(): void
    {
        config([
            'mail.default' => 'array',
            'mail.from.address' => 'support@yallaspare.com',
            'mail.from.name' => 'YallaSpare',
        ]);
        Mail::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.test'), [
                'recipient' => 'owner@example.com',
                'subject' => 'Admin mail test',
                'mailer' => 'array',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(OperationalNotificationMail::class, function (OperationalNotificationMail $mail): bool {
            return $mail->subjectLine === 'Admin mail test'
                && ($mail->context['type'] ?? null) === 'mail_test';
        });
    }

    public function test_settings_manager_can_preview_email_templates(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        foreach (['verify-email', 'order-status', 'security-alert', 'reset-password', 'two-factor-code', 'welcome', 'dealer', 'low-stock', 'support'] as $template) {
            foreach (['en', 'ar', 'ku'] as $locale) {
                $this->actingAs($admin)
                    ->get(route('admin.email.preview', ['template' => $template, 'locale' => $locale]))
                    ->assertOk();
            }
        }
    }

    public function test_email_center_renders_when_email_log_table_is_missing(): void
    {
        Schema::dropIfExists('email_logs');

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.index'))
            ->assertOk()
            ->assertSee('Email Center')
            ->assertSee('Mail log table is not installed yet');
    }

    public function test_failed_test_email_does_not_crash_when_email_log_table_is_missing(): void
    {
        Schema::dropIfExists('email_logs');

        config([
            'mail.default' => 'array',
            'mail.from.address' => 'support@yallaspare.com',
            'mail.from.name' => 'YallaSpare',
        ]);

        Mail::shouldReceive('mailer')
            ->once()
            ->with('array')
            ->andThrow(new RuntimeException('Simulated mail transport failure'));

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.test'), [
                'recipient' => 'owner@example.com',
                'subject' => 'Admin mail test',
                'mailer' => 'array',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('recipient');
    }

    public function test_broadcast_post_does_not_crash_when_broadcast_table_is_missing(): void
    {
        Schema::dropIfExists('email_broadcasts');

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.broadcast'), [
                'audience_type' => EmailBroadcast::AUDIENCE_ALL,
                'purpose' => EmailBroadcast::PURPOSE_PROMOTIONAL,
                'subject' => 'Special offer',
                'message' => 'Offer body.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('broadcast');
    }

    public function test_settings_manager_can_queue_broadcast_for_role_group(): void
    {
        Bus::fake();

        User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
            'email_notifications' => true,
            'marketing_consent' => true,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.broadcast'), [
                'audience_type' => EmailBroadcast::AUDIENCE_ROLE,
                'audience_role' => User::ROLE_USER,
                'purpose' => EmailBroadcast::PURPOSE_PROMOTIONAL,
                'subject' => 'Happy Newroz from YallaSpare',
                'message' => 'Special day offer for our customers.',
                'action_url' => url('/'),
                'action_text' => 'Shop now',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $broadcast = EmailBroadcast::query()->latest('id')->first();

        $this->assertNotNull($broadcast);
        $this->assertSame(User::ROLE_USER, $broadcast->audience_role);
        $this->assertSame(EmailBroadcast::STATUS_QUEUED, $broadcast->status);
        $this->assertSame(1, $broadcast->recipient_count);

        Bus::assertDispatched(SendEmailBroadcastJob::class, fn (SendEmailBroadcastJob $job): bool => $job->broadcastId === $broadcast->id);
    }

    public function test_single_user_broadcast_sends_immediately(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'email_verified_at' => now(),
            'email_notifications' => true,
            'marketing_consent' => true,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.broadcast'), [
                'audience_type' => EmailBroadcast::AUDIENCE_USER,
                'recipient_email' => $user->email,
                'purpose' => EmailBroadcast::PURPOSE_PROMOTIONAL,
                'subject' => 'YallaSpare test email',
                'message' => 'Single user message.',
                'action_url' => url('/'),
                'action_text' => 'Open',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(OperationalNotificationMail::class, 1);

        $broadcast = EmailBroadcast::query()->latest('id')->first();
        $this->assertSame(EmailBroadcast::STATUS_SENT, $broadcast->status);
        $this->assertSame(1, $broadcast->recipient_count);
        $this->assertSame(1, $broadcast->sent_count);
    }

    public function test_broadcast_rejects_external_action_url(): void
    {
        Bus::fake();
        config(['app.url' => 'https://yallaspare.test']);

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.broadcast'), [
                'audience_type' => EmailBroadcast::AUDIENCE_ALL,
                'purpose' => EmailBroadcast::PURPOSE_PROMOTIONAL,
                'subject' => 'Special offer',
                'message' => 'Offer body.',
                'action_url' => 'https://evil.test/phishing',
                'action_text' => 'Open',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('action_url');

        Bus::assertNotDispatched(SendEmailBroadcastJob::class);
    }

    public function test_promotional_broadcast_job_respects_email_and_marketing_consent(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'ok@example.com',
            'email_verified_at' => now(),
            'email_notifications' => true,
            'marketing_consent' => true,
        ]);
        User::factory()->create([
            'email' => 'no-marketing@example.com',
            'email_verified_at' => now(),
            'email_notifications' => true,
            'marketing_consent' => false,
        ]);
        User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'email_notifications' => true,
            'marketing_consent' => true,
        ]);

        $broadcast = EmailBroadcast::create([
            'audience_type' => EmailBroadcast::AUDIENCE_ALL,
            'purpose' => EmailBroadcast::PURPOSE_PROMOTIONAL,
            'subject' => 'Special day',
            'message' => 'Special day message.',
            'action_url' => url('/'),
            'action_text' => 'Shop now',
            'status' => EmailBroadcast::STATUS_QUEUED,
        ]);

        (new SendEmailBroadcastJob($broadcast->id))->handle();

        Mail::assertQueued(OperationalNotificationMail::class, 1);

        $broadcast->refresh();
        $this->assertSame(1, $broadcast->recipient_count);
        $this->assertSame(1, $broadcast->sent_count);
        $this->assertSame(0, $broadcast->failed_count);
        $this->assertSame(EmailBroadcast::STATUS_SENT, $broadcast->status);
    }
}
