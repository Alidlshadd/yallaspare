<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OperationalNotificationMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EmailController extends Controller
{
    public function index(): View
    {
        return view('admin.email.index', [
            'summary' => $this->mailSummary(),
            'mailers' => $this->availableMailers(),
            'checks' => $this->readinessChecks(),
        ]);
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'mailer' => ['nullable', 'string', 'max:80'],
        ]);

        $mailer = (string) ($data['mailer'] ?: config('mail.default'));
        $mailerConfig = (array) config("mail.mailers.{$mailer}", []);

        if ($mailerConfig === []) {
            return back()
                ->withInput()
                ->withErrors(['mailer' => __('The selected mailer is not configured.')]);
        }

        if ($this->smtpLooksIncomplete($mailerConfig)) {
            return back()
                ->withInput()
                ->withErrors(['recipient' => __('SMTP is not fully configured. Check username, password, host, and from address first.')]);
        }

        try {
            Mail::mailer($mailer)->to($data['recipient'])->send(new OperationalNotificationMail(
                (string) $data['subject'],
                "This is a YallaSpare admin test email.\n\nMailer: {$mailer}\nSent at: " . now()->toDateTimeString(),
                [
                    'type' => 'mail_test',
                    'mailer' => $mailer,
                    'admin_id' => $request->user()?->getAuthIdentifier(),
                ]
            ));
        } catch (Throwable $e) {
            Log::error('Admin mail test failed', [
                'recipient_hash' => hash('sha256', strtolower((string) $data['recipient'])),
                'mailer' => $mailer,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['recipient' => __('The test email could not be sent. Check the mail logs for details.')]);
        }

        return back()->with('success', __('Test email sent successfully.'));
    }

    /**
     * @return array<string, string>
     */
    private function mailSummary(): array
    {
        $mailer = (string) config('mail.default');
        $mailerConfig = (array) config("mail.mailers.{$mailer}", []);

        return [
            'default_mailer' => $mailer,
            'transport' => (string) ($mailerConfig['transport'] ?? $mailer),
            'host' => (string) ($mailerConfig['host'] ?? '-'),
            'port' => (string) ($mailerConfig['port'] ?? '-'),
            'encryption' => (string) ($mailerConfig['encryption'] ?? '-'),
            'username' => $this->mask((string) ($mailerConfig['username'] ?? '')),
            'from_address' => (string) config('mail.from.address', ''),
            'from_name' => (string) config('mail.from.name', ''),
            'queue' => (string) config('queue.default', ''),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availableMailers(): array
    {
        return collect((array) config('mail.mailers', []))
            ->keys()
            ->map(fn ($mailer) => (string) $mailer)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label:string,value:string,ok:bool,detail:string}>
     */
    private function readinessChecks(): array
    {
        $summary = $this->mailSummary();
        $mailerConfig = (array) config("mail.mailers.{$summary['default_mailer']}", []);
        $transport = (string) ($mailerConfig['transport'] ?? $summary['default_mailer']);

        return [
            [
                'label' => 'MAIL_MAILER',
                'value' => $summary['default_mailer'],
                'ok' => $summary['default_mailer'] !== 'log',
                'detail' => __('Use smtp, gmail, hostinger, mailgun, or another real transport in production.'),
            ],
            [
                'label' => 'MAIL_FROM_ADDRESS',
                'value' => $summary['from_address'] ?: '-',
                'ok' => filter_var($summary['from_address'], FILTER_VALIDATE_EMAIL) !== false,
                'detail' => __('The sender address must be a valid email address.'),
            ],
            [
                'label' => 'SMTP credentials',
                'value' => $transport === 'smtp' ? $summary['username'] : $transport,
                'ok' => ! $this->smtpLooksIncomplete($mailerConfig),
                'detail' => __('SMTP transports need a real host, username, password, and sender address.'),
            ],
            [
                'label' => 'QUEUE_CONNECTION',
                'value' => $summary['queue'] ?: '-',
                'ok' => $summary['queue'] !== 'sync',
                'detail' => __('Queued mail is recommended for checkout, order, and support workflows.'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $mailerConfig
     */
    private function smtpLooksIncomplete(array $mailerConfig): bool
    {
        $transport = (string) ($mailerConfig['transport'] ?? '');

        if ($transport !== 'smtp') {
            return false;
        }

        $username = (string) ($mailerConfig['username'] ?? '');
        $password = (string) ($mailerConfig['password'] ?? '');
        $host = (string) ($mailerConfig['host'] ?? '');
        $fromAddress = (string) config('mail.from.address', '');
        $placeholders = [
            'your-gmail-address',
            'your-google-app-password',
            'BURAYA_GOOGLE_APP_PASSWORD',
            'GOOGLE_APP_PASSWORD',
            'PASTE_REAL_APP_PASSWORD_HERE',
            'changeme',
            'password',
        ];

        if ($username === '' || $password === '' || $host === '' || ! filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
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
