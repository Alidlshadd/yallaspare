<?php

namespace App\Console\Commands;

use App\Mail\OperationalNotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestMail extends Command
{
    protected $signature = 'mail:test
        {to : Recipient email address}
        {--subject=YallaSpare test email : Email subject}
        {--mailer= : Mailer to use, defaults to MAIL_MAILER}
        {--queue : Queue the test email instead of sending it immediately}';

    protected $description = 'Send a test email using the configured default mailer.';

    public function handle(): int
    {
        $recipient = (string) $this->argument('to');
        $subject = (string) $this->option('subject');
        $mailer = (string) ($this->option('mailer') ?: config('mail.default'));
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');
        $mailerConfig = (array) config("mail.mailers.{$mailer}", []);
        $transport = (string) ($mailerConfig['transport'] ?? $mailer);
        $smtpUsername = (string) ($mailerConfig['username'] ?? '');
        $smtpPassword = (string) ($mailerConfig['password'] ?? '');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Recipient must be a valid email address.');

            return self::FAILURE;
        }

        $this->line('Mailer summary:');
        $this->line('  MAIL_MAILER=' . $mailer);
        $this->line('  TRANSPORT=' . $transport);
        $this->line('  MAIL_HOST=' . (string) ($mailerConfig['host'] ?? ''));
        $this->line('  MAIL_PORT=' . (string) ($mailerConfig['port'] ?? ''));
        $this->line('  MAIL_ENCRYPTION=' . (string) ($mailerConfig['encryption'] ?? ''));
        $this->line('  MAIL_USERNAME=' . $this->mask($smtpUsername));
        $this->line('  MAIL_FROM_ADDRESS=' . $fromAddress);
        $this->line('  MAIL_FROM_NAME=' . $fromName);
        $this->line('  QUEUE_CONNECTION=' . (string) config('queue.default'));

        if ($transport === 'smtp' && $this->smtpLooksIncomplete($smtpUsername, $smtpPassword, $fromAddress)) {
            $this->error('SMTP is not configured. Update MAIL_USERNAME, MAIL_PASSWORD, and MAIL_FROM_ADDRESS in .env first.');

            return self::FAILURE;
        }

        try {
            if ($this->option('queue')) {
                Mail::mailer($mailer)->to($recipient)->queue(new OperationalNotificationMail(
                    $subject,
                    "This is a queued YallaSpare test email.\n\nMailer: {$mailer}\nFrom: {$fromAddress}\nQueued at: " . now()->toDateTimeString(),
                    ['type' => 'mail_test']
                ));

                $this->info("Test email queued for {$recipient} using {$mailer}. Make sure a queue worker is running.");

                return self::SUCCESS;
            }

            Mail::mailer($mailer)->raw(
                "This is a YallaSpare test email.\n\nMailer: {$mailer}\nFrom: {$fromAddress}\nSent at: " . now()->toDateTimeString(),
                function ($message) use ($recipient, $subject): void {
                    $message->to($recipient)->subject($subject);
                }
            );

            $this->info("SMTP accepted the test email for {$recipient} using {$mailer}.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('Mail test command failed', [
                'recipient_hash' => hash('sha256', strtolower($recipient)),
                'mailer' => $mailer,
                'transport' => $transport,
                'host' => (string) ($mailerConfig['host'] ?? ''),
                'port' => (string) ($mailerConfig['port'] ?? ''),
                'encryption' => (string) ($mailerConfig['encryption'] ?? ''),
                'from' => $fromAddress,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $this->error('Mail test failed: ' . $e::class);
            $this->line($e->getMessage());
            $this->warn('The exact failure was also written to laravel.log without exposing the SMTP password.');

            return self::FAILURE;
        }
    }

    private function smtpLooksIncomplete(string $username, string $password, string $fromAddress): bool
    {
        $placeholders = [
            'your-gmail-address',
            'your-google-app-password',
            'BURAYA_GOOGLE_APP_PASSWORD',
            'GOOGLE_APP_PASSWORD',
            'PASTE_REAL_APP_PASSWORD_HERE',
            'changeme',
            'password',
        ];

        if ($username === '' || $password === '' || $fromAddress === '' || ! filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        foreach ($placeholders as $placeholder) {
            if (str_contains($username, $placeholder) || str_contains($password, $placeholder)) {
                return true;
            }
        }

        return false;
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
}
