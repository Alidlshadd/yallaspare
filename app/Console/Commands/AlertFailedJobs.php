<?php

namespace App\Console\Commands;

use App\Mail\OperationalNotificationMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Say out loud when queued work has been failing.
 *
 * Queued work fails silently by design: the job goes in a table and the
 * request that queued it succeeded long ago. An order confirmation that never
 * left looks exactly like one that did, and a misconfigured mailer can go
 * unnoticed for weeks. This is the thing that notices.
 */
class AlertFailedJobs extends Command
{
    /**
     * The last row already reported.
     *
     * A row id rather than a timestamp on purpose. `failed_at` is only
     * accurate to the second, so a boundary held as a time either reports the
     * failures on that second twice or misses them entirely, depending on
     * which comparison you pick. An id has neither problem, and ids are never
     * reused — pruning old rows does not roll the counter back.
     */
    private const LAST_REPORTED_KEY = 'ops.failed_jobs.last_reported_id';

    protected $signature = 'queue:alert-failed';

    protected $description = 'Email an alert when queued jobs have been failing';

    public function handle(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            $this->warn('There is no failed_jobs table. Nothing to watch.');

            return self::SUCCESS;
        }

        $lastReported = $this->lastReportedId();

        $failures = DB::table('failed_jobs')
            ->where('id', '>', $lastReported)
            ->orderByDesc('failed_at')
            ->get(['id', 'payload', 'exception', 'failed_at']);

        if ($failures->isEmpty()) {
            $this->info('No new job failures.');

            return self::SUCCESS;
        }

        $highestSeen = (int) $failures->max('id');
        $threshold = max(1, (int) config('ops.alerts.failed_job_threshold', 1));

        if ($failures->count() < $threshold) {
            // Below the threshold, but still seen: leaving the mark where it
            // was would re-count these every hour until something tipped it
            // over, and then report a number that was never news.
            $this->rememberReported($highestSeen);
            $this->info($failures->count().' job(s) failed, under the threshold of '.$threshold.'.');

            return self::SUCCESS;
        }

        $recipients = $this->recipients();

        if ($recipients === []) {
            $this->warn('Jobs are failing but there is nobody to tell. Set OPS_ALERT_EMAIL.');

            return self::SUCCESS;
        }

        $this->notify($recipients, $failures);
        $this->rememberReported($highestSeen);

        $this->warn($failures->count().' job failure(s) reported to '.implode(', ', $recipients).'.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $recipients
     * @param  Collection<int, object>  $failures
     */
    private function notify(array $recipients, Collection $failures): void
    {
        $total = $failures->count();
        $oldest = $failures->min('failed_at');

        $byJob = $failures
            ->groupBy(fn (object $row): string => $this->jobName((string) ($row->payload ?? '')))
            ->map->count()
            ->sortDesc()
            ->take(5);

        $lines = [
            __(':count queued job(s) have failed since :since.', [
                'count' => $total,
                'since' => (string) ($oldest ? Carbon::parse($oldest)->toDateTimeString() : ''),
            ]),
            '',
        ];

        foreach ($byJob as $job => $count) {
            $lines[] = "- {$job}: {$count}";
        }

        $lines[] = '';
        $lines[] = __('Most recent error:');
        $lines[] = Str::limit((string) ($failures->first()->exception ?? ''), 600);
        $lines[] = '';
        $lines[] = __('Inspect them with `php artisan queue:failed`, and retry with `php artisan queue:retry all`.');

        $subject = __('Queued jobs are failing on :app', ['app' => config('app.name', 'YallaSpare')]);
        $body = implode(PHP_EOL, $lines);

        foreach ($recipients as $recipient) {
            try {
                // sendNow, not queue: this message reports that the queue is
                // not working. Queuing it would hand the news to the thing
                // the news is about.
                Mail::to($recipient)->sendNow(new OperationalNotificationMail($subject, $body, [
                    'type' => 'system_alert',
                    'locale' => 'en',
                    'failed_jobs' => $total,
                ]));
            } catch (\Throwable $exception) {
                $this->error('Could not send the alert: '.$exception->getMessage());
            }
        }
    }

    /**
     * Who hears about it: the configured address, or every verified super
     * admin when none is set.
     *
     * @return array<int, string>
     */
    private function recipients(): array
    {
        $configured = trim((string) config('ops.alerts.email', ''));

        if ($configured !== '') {
            return array_values(array_filter(
                array_map('trim', explode(',', $configured)),
                static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            ));
        }

        return User::query()
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('email_verified_at')
            ->pluck('email')
            ->map(static fn ($email): string => (string) $email)
            ->all();
    }

    /**
     * Where to start reading.
     *
     * With nothing remembered — a fresh deployment, a cleared cache — anything
     * already more than an hour old is treated as read. Otherwise the first
     * run reports every failure the table has ever held as though it just
     * happened.
     */
    private function lastReportedId(): int
    {
        $stored = Cache::get(self::LAST_REPORTED_KEY);

        if (is_numeric($stored)) {
            return (int) $stored;
        }

        return (int) DB::table('failed_jobs')
            ->where('failed_at', '<', Carbon::now()->subHour())
            ->max('id');
    }

    private function rememberReported(int $id): void
    {
        Cache::put(self::LAST_REPORTED_KEY, $id, Carbon::now()->addDays(30));
    }

    private function jobName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return 'unknown';
        }

        $name = $decoded['displayName'] ?? ($decoded['job'] ?? 'unknown');

        return class_basename((string) $name);
    }
}
