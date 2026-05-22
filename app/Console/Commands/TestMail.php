<?php

namespace App\Console\Commands;

use App\Mail\OperationalNotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'mail:test
        {to : Recipient email address}
        {--subject=YallaSpare test email : Email subject}
        {--queue : Queue the test email instead of sending it immediately}';

    protected $description = 'Send a test email using the configured default mailer.';

    public function handle(): int
    {
        $recipient = (string) $this->argument('to');
        $subject = (string) $this->option('subject');
        $fromAddress = (string) config('mail.from.address');
        $mailer = (string) config('mail.default');
        $smtpUsername = (string) config('mail.mailers.smtp.username');
        $smtpPassword = (string) config('mail.mailers.smtp.password');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Recipient must be a valid email address.');

            return self::FAILURE;
        }

        if ($mailer === 'smtp' && (
            $smtpUsername === ''
            || $smtpPassword === ''
            || str_contains($smtpUsername, 'your-gmail-address')
            || str_contains($smtpPassword, 'your-google-app-password')
            || str_contains($smtpPassword, 'BURAYA_GOOGLE_APP_PASSWORD')
            || str_contains($smtpPassword, 'GOOGLE_APP_PASSWORD')
            || str_contains($smtpPassword, 'PASTE_REAL_APP_PASSWORD_HERE')
        )) {
            $this->error('Gmail SMTP is not configured. Update MAIL_USERNAME, MAIL_PASSWORD, and MAIL_FROM_ADDRESS in .env first.');

            return self::FAILURE;
        }

        if ($this->option('queue')) {
            Mail::to($recipient)->queue(new OperationalNotificationMail(
                $subject,
                "This is a queued YallaSpare test email.\n\nMailer: {$mailer}\nFrom: {$fromAddress}\nQueued at: " . now()->toDateTimeString(),
                ['type' => 'mail_test']
            ));

            $this->info("Test email queued for {$recipient} using {$mailer}.");

            return self::SUCCESS;
        }

        Mail::raw(
            "This is a YallaSpare test email.\n\nMailer: {$mailer}\nFrom: {$fromAddress}\nSent at: " . now()->toDateTimeString(),
            function ($message) use ($recipient, $subject): void {
                $message->to($recipient)->subject($subject);
            }
        );

        $this->info("Test email sent to {$recipient} using {$mailer}.");

        return self::SUCCESS;
    }
}
