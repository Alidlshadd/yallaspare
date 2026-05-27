<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TestPasswordResetMail extends Command
{
    protected $signature = 'auth:test-password-reset-mail
        {email : Account email address to check}
        {--send : Send a real password reset link after diagnostics}
        {--mailer= : Mailer to use, defaults to MAIL_MAILER}';

    protected $description = 'Audit password reset mail configuration and optionally send a real reset link.';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $mailer = (string) ($this->option('mailer') ?: config('mail.default'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email must be a valid address.');

            return self::FAILURE;
        }

        config(['mail.default' => $mailer]);

        $this->printMailSummary($mailer);
        $this->printQueueSummary();

        try {
            $user = User::query()
                ->where('email', $email)
                ->select(['id', 'email', 'email_verified_at'])
                ->first();
        } catch (Throwable $exception) {
            Log::error('Password reset mail diagnostic failed while reading user.', [
                'email_hash' => hash('sha256', $email),
                'email_domain' => $this->emailDomain($email),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            $this->error('Could not query users table: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->line('Password broker: ' . config('auth.defaults.passwords'));
        $this->line('Reset token table: ' . config('auth.passwords.'.config('auth.defaults.passwords').'.table'));
        $this->line('Account exists: ' . ($user ? 'yes (id ' . $user->id . ')' : 'no'));

        if (! $this->option('send')) {
            $this->warn('Dry run only. Re-run with --send to send a real reset link.');

            return $user ? self::SUCCESS : self::FAILURE;
        }

        if (! $user) {
            $this->warn('No reset email was sent because no account exists for that address.');

            return self::FAILURE;
        }

        try {
            $status = Password::sendResetLink(['email' => $email]);

            Log::info('Password reset mail diagnostic send completed.', [
                'email_hash' => hash('sha256', $email),
                'email_domain' => $this->emailDomain($email),
                'user_id' => $user->id,
                'broker' => config('auth.defaults.passwords'),
                'mailer' => $mailer,
                'queue_connection' => config('queue.default'),
                'status' => $status,
            ]);

            if ($status !== Password::RESET_LINK_SENT) {
                $this->warn('Broker returned status: ' . $status);

                return self::FAILURE;
            }

            $this->info('Password reset email was handed to SMTP immediately.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Password reset mail diagnostic send failed.', [
                'email_hash' => hash('sha256', $email),
                'email_domain' => $this->emailDomain($email),
                'user_id' => $user->id,
                'broker' => config('auth.defaults.passwords'),
                'mailer' => $mailer,
                'queue_connection' => config('queue.default'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            $this->error('Password reset email failed: ' . $exception::class);
            $this->line($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function printMailSummary(string $mailer): void
    {
        $mailerConfig = (array) config("mail.mailers.{$mailer}", []);

        $this->line('Mail summary:');
        $this->line('  MAIL_MAILER=' . $mailer);
        $this->line('  TRANSPORT=' . (string) ($mailerConfig['transport'] ?? $mailer));
        $this->line('  MAIL_HOST=' . (string) ($mailerConfig['host'] ?? ''));
        $this->line('  MAIL_PORT=' . (string) ($mailerConfig['port'] ?? ''));
        $this->line('  MAIL_ENCRYPTION=' . (string) ($mailerConfig['encryption'] ?? ''));
        $this->line('  MAIL_USERNAME=' . $this->mask((string) ($mailerConfig['username'] ?? '')));
        $this->line('  MAIL_FROM_ADDRESS=' . (string) config('mail.from.address'));
    }

    private function printQueueSummary(): void
    {
        $this->line('Queue summary:');
        $this->line('  QUEUE_CONNECTION=' . (string) config('queue.default'));
        $this->line('  password reset notification=immediate');

        try {
            if (Schema::hasTable('jobs')) {
                $this->line('  pending jobs=' . DB::table('jobs')->count());
            }

            if (Schema::hasTable('failed_jobs')) {
                $this->line('  failed jobs=' . DB::table('failed_jobs')->count());
            }
        } catch (Throwable $exception) {
            $this->warn('  queue table counts unavailable: ' . $exception->getMessage());
        }
    }

    private function mask(string $value): string
    {
        if ($value === '') {
            return '(empty)';
        }

        if (str_contains($value, '@')) {
            [$name, $domain] = explode('@', $value, 2);

            return mb_substr($name, 0, 2) . str_repeat('*', max(3, mb_strlen($name) - 2)) . '@' . $domain;
        }

        return mb_substr($value, 0, 2) . str_repeat('*', max(3, mb_strlen($value) - 2));
    }

    private function emailDomain(string $email): string
    {
        $domain = strrchr($email, '@');

        return $domain === false ? '' : strtolower(substr($domain, 1));
    }
}
