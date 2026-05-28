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
            'previewTemplates' => array_keys($this->previewTemplates()),
        ]);
    }

    /**
     * Render any of the transactional email templates with realistic sample data.
     * Admin-only; the route is already behind the admin middleware + 2FA. The
     * template key is matched against a static allowlist — no view paths from
     * user input ever reach the renderer.
     */
    public function preview(string $template, Request $request): View
    {
        $registry = $this->previewTemplates();
        abort_unless(isset($registry[$template]), 404);

        $locale = (string) $request->query('locale', app()->getLocale());
        if (! in_array($locale, ['en', 'ar', 'ku'], true)) {
            $locale = 'en';
        }
        app()->setLocale($locale);

        $entry = $registry[$template];

        return view($entry['view'], array_merge($entry['data'], ['locale' => $locale]));
    }

    /**
     * Allowlist of preview-able templates with realistic sample data.
     *
     * @return array<string, array{view:string, data:array<string,mixed>}>
     */
    private function previewTemplates(): array
    {
        $sampleOrderRows = [
            ['name' => 'Bosch Front Brake Pads (Set)', 'sku' => 'BP-9043-FR', 'quantity' => 2, 'subtotal' => '85,000 IQD'],
            ['name' => 'NGK Iridium Spark Plug', 'sku' => 'NGK-IX-IZTR5B', 'quantity' => 4, 'subtotal' => '42,000 IQD'],
            ['name' => 'Mann Oil Filter W712/75', 'sku' => 'MN-W712-75', 'quantity' => 1, 'subtotal' => '13,500 IQD'],
        ];
        $sampleTotals = [
            ['label' => 'Subtotal', 'value' => '140,500 IQD'],
            ['label' => 'Shipping', 'value' => '5,000 IQD'],
            ['label' => 'Discount', 'value' => '-10,000 IQD'],
            ['label' => 'Grand total', 'value' => '135,500 IQD'],
        ];

        return [
            'verify-email' => [
                'view' => 'emails.auth.verify-email',
                'data' => [
                    'verificationCode' => '847293',
                    'email' => 'customer@example.com',
                    'expiresIn' => 60,
                ],
            ],
            'reset-password' => [
                'view' => 'emails.auth.reset-password',
                'data' => [
                    'actionUrl' => url('/reset-password/sample-token'),
                    'email' => 'customer@example.com',
                    'expiresIn' => 60,
                ],
            ],
            'two-factor-code' => [
                'view' => 'emails.admin.two-factor-code',
                'data' => [
                    'code' => '129 437',
                    'email' => 'admin@yallaspare.com',
                    'ttlMinutes' => 10,
                ],
            ],
            'welcome' => [
                'view' => 'emails.auth.welcome',
                'data' => [
                    'email' => 'newuser@example.com',
                    'name' => 'Ahmed Al-Khalidi',
                    'actionUrl' => url('/'),
                    'actionText' => __('Open Your Account'),
                ],
            ],
            'order-status' => [
                'view' => 'emails.orders.status',
                'data' => [
                    'eyebrow' => __('Order shipped'),
                    'title' => __('Your order is on the way'),
                    'intro' => __('Your YallaSpare order #YS-104482 has shipped and will arrive in 2-3 business days.'),
                    'orderStatus' => 'shipped',
                    'recipientName' => 'Ahmed Al-Khalidi',
                    'recipientEmail' => 'customer@example.com',
                    'metaItems' => [
                        ['label' => __('Order number'), 'value' => 'YS-104482'],
                        ['label' => __('Tracking'), 'value' => 'AR-9837-4471-IQ'],
                        ['label' => __('Placed on'), 'value' => '12 Mar 2026, 14:22'],
                    ],
                    'orderRows' => $sampleOrderRows,
                    'totals' => $sampleTotals,
                    'shippingAddress' => 'Hay Al-Jihad, Baghdad — +964 770 000 0000',
                    'actionUrl' => url('/account/orders'),
                    'actionText' => __('Track your order'),
                ],
            ],
            'dealer' => [
                'view' => 'emails.dealer.notification',
                'data' => [
                    'title' => __('Dealer status approved'),
                    'bodyText' => __('Your YallaSpare dealer application has been approved. You can now access dealer pricing, order tools, and inventory management.'),
                    'dealerStatus' => 'approved',
                    'metaItems' => [
                        ['label' => __('Dealer account'), 'value' => 'dealer@example.com'],
                        ['label' => __('Discount tier'), 'value' => '8%'],
                        ['label' => __('Status updated'), 'value' => '12 Mar 2026'],
                    ],
                    'actionUrl' => url('/'),
                    'actionText' => __('View dealer dashboard'),
                ],
            ],
            'support' => [
                'view' => 'emails.support.contact-request',
                'data' => [
                    'name' => 'Sara Mohammed',
                    'email' => 'sara@example.com',
                    'phone' => '+964 770 123 4567',
                    'topic' => 'Order issue',
                    'requestSubject' => 'Wrong part received',
                    'messageText' => "I ordered front brake pads for a 2017 Toyota Camry but received pads for a different model.\n\nOrder number: YS-104399",
                ],
            ],
            'low-stock' => [
                'view' => 'emails.inventory.low-stock-alert',
                'data' => [
                    'title' => __('3 products below threshold'),
                    'bodyText' => __('The following products dropped below the low-stock threshold. Replenish them before the next sales cycle to avoid lost orders.'),
                    'metaItems' => [
                        ['label' => 'NGK-IX-IZTR5B', 'value' => __('4 units left (threshold 10)')],
                        ['label' => 'BP-9043-FR', 'value' => __('2 units left (threshold 5)')],
                        ['label' => 'MN-W712-75', 'value' => __('1 unit left (threshold 5)')],
                    ],
                    'actionUrl' => url('/'),
                    'actionText' => __('Manage inventory'),
                ],
            ],
            'security-alert' => [
                'view' => 'emails.admin.security-alert',
                'data' => [
                    'title' => __('New admin sign-in detected'),
                    'bodyText' => __("A YallaSpare admin account was just signed in from a new device.\n\nIf this was you, no further action is needed."),
                    'email' => 'admin@yallaspare.com',
                    'metaItems' => [
                        ['label' => __('Account'), 'value' => 'admin@yallaspare.com'],
                        ['label' => __('IP address'), 'value' => '93.184.216.34'],
                        ['label' => __('Device'), 'value' => 'Chrome 134 — Windows 11'],
                        ['label' => __('Signed in at'), 'value' => '12 Mar 2026, 14:22'],
                    ],
                    'actionUrl' => url('/'),
                    'actionText' => __('Review account security'),
                ],
            ],
        ];
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
