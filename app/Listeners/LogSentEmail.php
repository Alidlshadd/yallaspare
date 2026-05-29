<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Str;
use Throwable;

class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;
            $to = method_exists($message, 'getTo') ? ($message->getTo()[0] ?? null) : null;
            $address = $to && method_exists($to, 'getAddress') ? (string) $to->getAddress() : '';
            $subject = method_exists($message, 'getSubject') ? (string) ($message->getSubject() ?? '') : '';
            $data = (array) ($event->data ?? []);

            EmailLog::create([
                'recipient_hash' => $address !== '' ? hash('sha256', strtolower($address)) : '',
                'recipient_domain' => $address !== '' && str_contains($address, '@')
                    ? Str::lower(Str::after($address, '@'))
                    : null,
                'subject' => mb_substr($subject, 0, 255),
                'mailer' => (string) ($data['__laravel_mailer'] ?? config('mail.default')),
                'mailable_class' => $data['__mailable_class'] ?? null,
                'status' => EmailLog::STATUS_SENT,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Never let log persistence break the mail pipeline.
        }
    }
}
