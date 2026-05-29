<?php

namespace Tests\Feature\Admin;

use App\Mail\OperationalNotificationMail;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmailOutboxTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    public function test_message_sent_event_creates_email_log_with_hashed_recipient(): void
    {
        $recipient = 'customer@example.com';

        Mail::to($recipient)->send(new OperationalNotificationMail(
            'Welcome',
            'Hi there.',
            ['type' => 'test']
        ));

        $log = EmailLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame(EmailLog::STATUS_SENT, $log->status);
        $this->assertSame(hash('sha256', strtolower($recipient)), $log->recipient_hash);
        $this->assertSame('example.com', $log->recipient_domain);
        $this->assertNotNull($log->sent_at);

        $this->assertStringNotContainsString($recipient, (string) $log->subject);
    }

    public function test_outbox_page_renders_for_settings_manager(): void
    {
        $admin = $this->admin();

        EmailLog::create([
            'recipient_hash' => hash('sha256', 'a@b.com'),
            'recipient_domain' => 'b.com',
            'subject' => 'Order shipped',
            'mailer' => 'smtp',
            'status' => EmailLog::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.outbox'))
            ->assertOk()
            ->assertSee('Order shipped')
            ->assertSee('b.com');
    }

    public function test_outbox_filter_by_status(): void
    {
        $admin = $this->admin();

        EmailLog::create([
            'recipient_hash' => hash('sha256', 'a@b.com'),
            'recipient_domain' => 'b.com',
            'subject' => 'Sent one',
            'mailer' => 'smtp',
            'status' => EmailLog::STATUS_SENT,
            'sent_at' => now(),
        ]);
        EmailLog::create([
            'recipient_hash' => hash('sha256', 'c@d.com'),
            'recipient_domain' => 'd.com',
            'subject' => 'Failed one',
            'mailer' => 'smtp',
            'status' => EmailLog::STATUS_FAILED,
            'error_message' => 'SMTP timeout',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.outbox', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('Failed one')
            ->assertDontSee('Sent one');
    }
}
