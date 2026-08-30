<?php

namespace Tests\Feature\Ops;

use App\Mail\OperationalNotificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class FailedJobAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::flush();

        config([
            'ops.alerts.email' => 'ops@yallaspare.test',
            'ops.alerts.failed_job_threshold' => 1,
        ]);
    }

    public function test_a_failure_is_reported_to_the_configured_address(): void
    {
        $this->recordFailure('App\\Jobs\\SendEmailBroadcastJob', 'SMTP connect() failed');

        $this->artisan('queue:alert-failed')->assertSuccessful();

        Mail::assertSent(OperationalNotificationMail::class, function (OperationalNotificationMail $mail): bool {
            return $mail->hasTo('ops@yallaspare.test')
                && str_contains($mail->bodyText, 'SendEmailBroadcastJob')
                && str_contains($mail->bodyText, 'SMTP connect() failed');
        });
    }

    public function test_the_alert_is_sent_immediately_rather_than_queued(): void
    {
        $this->recordFailure();

        $this->artisan('queue:alert-failed')->assertSuccessful();

        // The message says the queue is broken. Handing it to the queue would
        // give the news to the thing the news is about.
        Mail::assertSent(OperationalNotificationMail::class);
        Mail::assertNotQueued(OperationalNotificationMail::class);
    }

    public function test_a_quiet_queue_produces_no_mail(): void
    {
        $this->artisan('queue:alert-failed')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_failures_already_reported_are_not_reported_again(): void
    {
        $this->recordFailure();

        $this->artisan('queue:alert-failed')->assertSuccessful();
        Mail::assertSentCount(1);

        $this->artisan('queue:alert-failed')->assertSuccessful();
        Mail::assertSentCount(1);
    }

    public function test_a_shop_below_its_threshold_is_left_in_peace(): void
    {
        config(['ops.alerts.failed_job_threshold' => 5]);

        for ($i = 0; $i < 4; $i++) {
            $this->recordFailure();
        }

        $this->artisan('queue:alert-failed')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_super_admins_hear_about_it_when_no_address_is_configured(): void
    {
        config(['ops.alerts.email' => '']);

        $admin = User::factory()->create([
            'email' => 'boss@yallaspare.test',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['role' => User::ROLE_SUPER_ADMIN])->save();

        $this->recordFailure();

        $this->artisan('queue:alert-failed')->assertSuccessful();

        Mail::assertSent(OperationalNotificationMail::class, fn (OperationalNotificationMail $mail): bool => $mail->hasTo('boss@yallaspare.test'));
    }

    private function recordFailure(string $job = 'App\\Jobs\\ExampleJob', string $exception = 'RuntimeException: boom'): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => $job, 'job' => $job]),
            'exception' => $exception,
            'failed_at' => now(),
        ]);
    }
}
