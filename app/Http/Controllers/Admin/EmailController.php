<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailBroadcastJob;
use App\Mail\OperationalNotificationMail;
use App\Models\EmailBroadcast;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmailController extends Controller
{
    public function index(Request $request): View
    {
        $checks = $this->readinessChecks();
        $templateCards = $this->templateCards();

        $status = (string) $request->query('status', '');
        if (! in_array($status, ['sent', 'failed', 'pending'], true)) {
            $status = '';
        }
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        return view('admin.email.index', [
            'summary' => $this->mailSummary(),
            'mailers' => $this->availableMailers(),
            'checks' => $checks,
            'emailStats' => $this->emailStats(),
            'health' => $this->healthSummary($checks),
            'recentLogs' => $this->recentLogs(),
            'previewTemplates' => array_keys($this->previewTemplates()),
            'templateCards' => $templateCards,
            'previewShowcase' => array_slice($templateCards, 0, 3),
            'audienceRoles' => $this->broadcastAudienceRoles(),
            'recentBroadcasts' => $this->recentBroadcasts($status, $search),
            'broadcastCounts' => $this->broadcastStatusCounts(),
            'broadcastFilters' => ['status' => $status, 'q' => $search],
            'broadcastsAvailable' => $this->emailBroadcastTableExists(),
        ]);
    }

    public function createBroadcast(): View
    {
        return view('admin.email.broadcasts.create', [
            'audienceRoles' => $this->broadcastAudienceRoles(),
            'broadcastsAvailable' => $this->emailBroadcastTableExists(),
        ]);
    }

    public function outbox(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));
        $domain = trim((string) $request->query('domain', ''));
        $mailLogAvailable = $this->emailLogTableExists();

        if ($mailLogAvailable) {
            $logs = EmailLog::query()
                ->when(in_array($status, [EmailLog::STATUS_SENT, EmailLog::STATUS_FAILED], true),
                    fn ($q) => $q->where('status', $status))
                ->when($domain !== '', fn ($q) => $q->where('recipient_domain', strtolower($domain)))
                ->orderByDesc('id')
                ->paginate(50)
                ->withQueryString();
        } else {
            $logs = new LengthAwarePaginator([], 0, 50);
        }

        $stats = $this->emailLogStats24h();

        return view('admin.email.outbox', [
            'logs' => $logs,
            'stats' => $stats,
            'status' => $status,
            'domain' => $domain,
            'mailLogAvailable' => $mailLogAvailable,
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

    /**
     * @return array<string, mixed>
     */
    private function emailStats(): array
    {
        if (! $this->emailLogTableExists()) {
            return [
                'total_24h' => 0,
                'sent_24h' => 0,
                'failed_24h' => 0,
                'total_7d' => 0,
                'sent_7d' => 0,
                'success_rate_24h' => null,
                'success_rate_7d' => null,
                'last_sent_label' => __('Mail log table is not installed yet'),
            ];
        }

        $lastDay = now()->subDay();
        $lastWeek = now()->subDays(7);

        $total24h = EmailLog::query()->where('created_at', '>=', $lastDay)->count();
        $sent24h = EmailLog::query()
            ->where('status', EmailLog::STATUS_SENT)
            ->where('created_at', '>=', $lastDay)
            ->count();
        $failed24h = EmailLog::query()
            ->where('status', EmailLog::STATUS_FAILED)
            ->where('created_at', '>=', $lastDay)
            ->count();
        $total7d = EmailLog::query()->where('created_at', '>=', $lastWeek)->count();
        $sent7d = EmailLog::query()
            ->where('status', EmailLog::STATUS_SENT)
            ->where('created_at', '>=', $lastWeek)
            ->count();
        $lastSent = EmailLog::query()
            ->where('status', EmailLog::STATUS_SENT)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->first();

        return [
            'total_24h' => $total24h,
            'sent_24h' => $sent24h,
            'failed_24h' => $failed24h,
            'total_7d' => $total7d,
            'sent_7d' => $sent7d,
            'success_rate_24h' => $total24h > 0 ? (int) round(($sent24h / $total24h) * 100) : null,
            'success_rate_7d' => $total7d > 0 ? (int) round(($sent7d / $total7d) * 100) : null,
            'last_sent_label' => $lastSent?->sent_at
                ? $lastSent->sent_at->diffForHumans()
                : __('No sent mail yet'),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, EmailLog>
     */
    private function recentLogs()
    {
        if (! $this->emailLogTableExists()) {
            return collect();
        }

        return EmailLog::query()
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    /**
     * @return array{total_24h:int,sent_24h:int,failed_24h:int}
     */
    private function emailLogStats24h(): array
    {
        if (! $this->emailLogTableExists()) {
            return [
                'total_24h' => 0,
                'sent_24h' => 0,
                'failed_24h' => 0,
            ];
        }

        $lastDay = now()->subDay();

        return [
            'total_24h' => EmailLog::query()->where('created_at', '>=', $lastDay)->count(),
            'sent_24h' => EmailLog::query()
                ->where('status', EmailLog::STATUS_SENT)
                ->where('created_at', '>=', $lastDay)
                ->count(),
            'failed_24h' => EmailLog::query()
                ->where('status', EmailLog::STATUS_FAILED)
                ->where('created_at', '>=', $lastDay)
                ->count(),
        ];
    }

    private function emailLogTableExists(): bool
    {
        try {
            return Schema::hasTable('email_logs');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<int, array{label:string,value:string,ok:bool,detail:string}>  $checks
     * @return array{ok:int,total:int,score:int,label:string,tone:string}
     */
    private function healthSummary(array $checks): array
    {
        $total = count($checks);
        $ok = collect($checks)->where('ok', true)->count();
        $score = $total > 0 ? (int) round(($ok / $total) * 100) : 0;

        return [
            'ok' => $ok,
            'total' => $total,
            'score' => $score,
            'label' => $score >= 100
                ? __('Ready')
                : ($score >= 75 ? __('Needs review') : __('Needs setup')),
            'tone' => $score >= 100 ? 'green' : ($score >= 75 ? 'amber' : 'rose'),
        ];
    }

    /**
     * @return array<int, array{key:string,title:string,description:string,icon:string,tone:string,badges:array<int,string>,sample:array{spec:string,subject:string,body:string,meta:string}}>
     */
    private function templateCards(): array
    {
        return [
            [
                'key' => 'verify-email',
                'title' => __('Email verification'),
                'description' => __('Six-digit customer verification with expiry details.'),
                'icon' => 'fa-shield-halved',
                'tone' => 'blue',
                'badges' => ['Auth', 'Code'],
                'sample' => [
                    'spec' => 'SYS / VERIFY',
                    'subject' => __('Verify your email address'),
                    'body' => __('Enter the code on the verification screen to protect your account.'),
                    'meta' => __('Code 847293 - expires in 60 minutes'),
                ],
            ],
            [
                'key' => 'order-status',
                'title' => __('Order status'),
                'description' => __('Customer order timeline with items, totals, and tracking CTA.'),
                'icon' => 'fa-truck-fast',
                'tone' => 'emerald',
                'badges' => ['Orders', 'Customer'],
                'sample' => [
                    'spec' => 'ORD / STATUS',
                    'subject' => __('Your order is on the way'),
                    'body' => __('Order #YS-104482 has shipped and will arrive in 2-3 business days.'),
                    'meta' => __('3 items - 135,500 IQD'),
                ],
            ],
            [
                'key' => 'security-alert',
                'title' => __('Security alert'),
                'description' => __('Admin sign-in and risk notification with device metadata.'),
                'icon' => 'fa-triangle-exclamation',
                'tone' => 'rose',
                'badges' => ['Admin', 'Security'],
                'sample' => [
                    'spec' => 'SEC / ALERT',
                    'subject' => __('New admin sign-in detected'),
                    'body' => __('A protected admin account signed in from a new device.'),
                    'meta' => __('Chrome 134 - Windows 11'),
                ],
            ],
            [
                'key' => 'reset-password',
                'title' => __('Password reset'),
                'description' => __('Secure reset flow with expiry and protective warning.'),
                'icon' => 'fa-key',
                'tone' => 'amber',
                'badges' => ['Auth', 'Security'],
                'sample' => [
                    'spec' => 'SEC / RESET',
                    'subject' => __('Reset your password'),
                    'body' => __('Choose a new password using the secure reset link.'),
                    'meta' => __('Expires in 60 minutes'),
                ],
            ],
            [
                'key' => 'two-factor-code',
                'title' => __('Admin 2FA'),
                'description' => __('Short-lived admin sign-in code for protected sessions.'),
                'icon' => 'fa-user-lock',
                'tone' => 'violet',
                'badges' => ['Admin', '2FA'],
                'sample' => [
                    'spec' => 'SEC / 2FA',
                    'subject' => __('Admin sign-in code'),
                    'body' => __('Use this one-time code to finish signing in.'),
                    'meta' => __('Code 129 437 - valid for 10 minutes'),
                ],
            ],
            [
                'key' => 'welcome',
                'title' => __('Welcome'),
                'description' => __('New account onboarding with useful next actions.'),
                'icon' => 'fa-star',
                'tone' => 'cyan',
                'badges' => ['Customer', 'Onboarding'],
                'sample' => [
                    'spec' => 'SYS / WELCOME',
                    'subject' => __('Welcome to YallaSpare'),
                    'body' => __('Your account is ready for checkout, tracking, and saved settings.'),
                    'meta' => __('Shop parts - track orders - secure checkout'),
                ],
            ],
            [
                'key' => 'dealer',
                'title' => __('Dealer update'),
                'description' => __('Dealer account status and account action messaging.'),
                'icon' => 'fa-handshake',
                'tone' => 'indigo',
                'badges' => ['Dealer', 'Status'],
                'sample' => [
                    'spec' => 'DLR / STATUS',
                    'subject' => __('Dealer status approved'),
                    'body' => __('Dealer pricing and account tools are now available.'),
                    'meta' => __('Discount tier 8%'),
                ],
            ],
            [
                'key' => 'low-stock',
                'title' => __('Low stock alert'),
                'description' => __('Inventory warning for products below configured threshold.'),
                'icon' => 'fa-boxes-stacked',
                'tone' => 'orange',
                'badges' => ['Inventory', 'Ops'],
                'sample' => [
                    'spec' => 'INV / STOCK',
                    'subject' => __('3 products below threshold'),
                    'body' => __('Replenish products before the next sales cycle.'),
                    'meta' => __('NGK-IX-IZTR5B - 4 units left'),
                ],
            ],
            [
                'key' => 'support',
                'title' => __('Support request'),
                'description' => __('Inbound customer contact details and message summary.'),
                'icon' => 'fa-headset',
                'tone' => 'slate',
                'badges' => ['Support', 'Admin'],
                'sample' => [
                    'spec' => 'SUP / REQUEST',
                    'subject' => __('Wrong part received'),
                    'body' => __('Customer submitted an order issue with contact details.'),
                    'meta' => __('sara@example.com - +964 770 123 4567'),
                ],
            ],
        ];
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:2000'],
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
                trim((string) ($data['body'] ?? '')) !== ''
                    ? trim((string) $data['body'])
                    : "This is a YallaSpare admin test email.\n\nMailer: {$mailer}\nSent at: " . now()->toDateTimeString(),
                [
                    'type' => 'mail_test',
                    'mailer' => $mailer,
                    'admin_id' => $request->user()?->getAuthIdentifier(),
                ]
            ));
        } catch (Throwable $e) {
            $recipient = (string) $data['recipient'];

            $this->recordFailedEmail($recipient, (string) $data['subject'], $mailer, $e);

            Log::error('Admin mail test failed', [
                'recipient_hash' => hash('sha256', strtolower($recipient)),
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

    public function sendBroadcast(Request $request): RedirectResponse
    {
        if (! $this->emailBroadcastTableExists()) {
            return back()
                ->withInput()
                ->withErrors(['broadcast' => 'Email broadcast table is not installed yet. Run the pending migrations first.']);
        }

        $data = $request->validate([
            'audience_type' => ['required', Rule::in([
                EmailBroadcast::AUDIENCE_ALL,
                EmailBroadcast::AUDIENCE_ROLE,
                EmailBroadcast::AUDIENCE_USER,
            ])],
            'audience_role' => ['nullable', 'required_if:audience_type,' . EmailBroadcast::AUDIENCE_ROLE, Rule::in(array_keys($this->broadcastAudienceRoles()))],
            'recipient_email' => ['nullable', 'required_if:audience_type,' . EmailBroadcast::AUDIENCE_USER, 'email:rfc', 'max:255'],
            'purpose' => ['required', Rule::in([EmailBroadcast::PURPOSE_PROMOTIONAL, EmailBroadcast::PURPOSE_OPERATIONAL])],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'action_url' => [
                'nullable',
                'url',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && $value !== '' && ! $this->isAllowedBroadcastUrl($value)) {
                        $fail('Action links must point to the YallaSpare website.');
                    }
                },
            ],
            'action_text' => ['nullable', 'string', 'max:80'],
        ]);

        $actionUrl = trim((string) ($data['action_url'] ?? ''));
        $targetUser = null;
        if ($data['audience_type'] === EmailBroadcast::AUDIENCE_USER) {
            $targetUser = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower((string) $data['recipient_email'])])
                ->first();

            if (! $targetUser) {
                return back()
                    ->withInput()
                    ->withErrors(['recipient_email' => __('No user was found for that email address.')]);
            }
        }

        $recipientCount = $this->broadcastRecipientCount(
            (string) $data['audience_type'],
            $data['audience_type'] === EmailBroadcast::AUDIENCE_ROLE ? (string) $data['audience_role'] : null,
            $targetUser,
            (string) $data['purpose'],
        );

        if ($recipientCount < 1) {
            return back()
                ->withInput()
                ->withErrors(['broadcast' => __('No eligible recipients matched this audience and purpose.')]);
        }

        $broadcast = EmailBroadcast::create([
            'admin_id' => $request->user()?->getAuthIdentifier(),
            'target_user_id' => $targetUser?->getKey(),
            'audience_type' => (string) $data['audience_type'],
            'audience_role' => $data['audience_type'] === EmailBroadcast::AUDIENCE_ROLE ? (string) $data['audience_role'] : null,
            'purpose' => (string) $data['purpose'],
            'subject' => trim((string) $data['subject']),
            'message' => trim((string) $data['message']),
            'action_url' => $actionUrl !== '' ? $actionUrl : null,
            'action_text' => trim((string) ($data['action_text'] ?? '')) ?: null,
            'status' => EmailBroadcast::STATUS_QUEUED,
            'recipient_count' => $recipientCount,
        ]);

        if ($broadcast->audience_type === EmailBroadcast::AUDIENCE_USER) {
            SendEmailBroadcastJob::dispatchSync($broadcast->id);

            return back()->with('success', __('Email sent to the selected user.'));
        }

        SendEmailBroadcastJob::dispatch($broadcast->id);

        return back()->with('success', __('Email broadcast queued successfully. Keep the queue worker running to send group broadcasts.'));
    }

    private function recordFailedEmail(string $recipient, string $subject, string $mailer, Throwable $e): void
    {
        if (! $this->emailLogTableExists()) {
            return;
        }

        try {
            EmailLog::create([
                'recipient_hash' => hash('sha256', strtolower($recipient)),
                'recipient_domain' => str_contains($recipient, '@')
                    ? strtolower(substr($recipient, strpos($recipient, '@') + 1))
                    : null,
                'subject' => mb_substr($subject, 0, 255),
                'mailer' => $mailer,
                'mailable_class' => OperationalNotificationMail::class,
                'status' => EmailLog::STATUS_FAILED,
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
                'sent_at' => null,
            ]);
        } catch (Throwable) {
            // Failed delivery logging must never turn a handled mail failure into a 500.
        }
    }

    /**
     * @return array<string, string>
     */
    private function broadcastAudienceRoles(): array
    {
        return [
            User::ROLE_USER => __('Customers'),
            User::ROLE_DEALER => __('Dealers'),
            User::ROLE_ADMIN => __('Admins'),
            User::ROLE_SETTINGS_MANAGER => __('Settings managers'),
        ];
    }

    private function broadcastRecipientCount(string $audienceType, ?string $audienceRole, ?User $targetUser, string $purpose): int
    {
        $query = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('email_verified_at')
            ->where(function ($q): void {
                $q->where('email_notifications', true)
                    ->orWhereNull('email_notifications');
            });

        if ($purpose === EmailBroadcast::PURPOSE_PROMOTIONAL) {
            $query->where('marketing_consent', true);
        }

        if ($audienceType === EmailBroadcast::AUDIENCE_ROLE && $audienceRole) {
            $query->where('role', $audienceRole);
        }

        if ($audienceType === EmailBroadcast::AUDIENCE_USER) {
            if (! $targetUser) {
                return 0;
            }

            $query->whereKey($targetUser->getKey());
        }

        return $query->count();
    }

    private function recentBroadcasts(string $status = '', string $search = '')
    {
        if (! $this->emailBroadcastTableExists()) {
            return collect();
        }

        return EmailBroadcast::query()
            ->with(['admin:id,name', 'targetUser:id,name,email'])
            ->when($status === 'sent', fn ($q) => $q->where('status', EmailBroadcast::STATUS_SENT))
            ->when($status === 'failed', fn ($q) => $q->where('status', EmailBroadcast::STATUS_FAILED))
            ->when($status === 'pending', fn ($q) => $q->whereIn('status', [
                EmailBroadcast::STATUS_QUEUED,
                EmailBroadcast::STATUS_SENDING,
            ]))
            ->when($search !== '', function ($q) use ($search) {
                $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
                $q->where('subject', 'like', $needle);
            })
            ->latest('id')
            ->limit(10)
            ->get();
    }

    /**
     * @return array{all:int,sent:int,failed:int,pending:int}
     */
    private function broadcastStatusCounts(): array
    {
        if (! $this->emailBroadcastTableExists()) {
            return ['all' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0];
        }

        return [
            'all' => EmailBroadcast::query()->count(),
            'sent' => EmailBroadcast::query()->where('status', EmailBroadcast::STATUS_SENT)->count(),
            'failed' => EmailBroadcast::query()->where('status', EmailBroadcast::STATUS_FAILED)->count(),
            'pending' => EmailBroadcast::query()->whereIn('status', [
                EmailBroadcast::STATUS_QUEUED,
                EmailBroadcast::STATUS_SENDING,
            ])->count(),
        ];
    }

    private function emailBroadcastTableExists(): bool
    {
        try {
            return Schema::hasTable('email_broadcasts');
        } catch (Throwable) {
            return false;
        }
    }

    private function isAllowedBroadcastUrl(string $url): bool
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        if (! is_string($urlHost) || $urlHost === '') {
            return false;
        }

        $allowedHosts = collect([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            request()->getHost(),
        ])
            ->filter(fn ($host) => is_string($host) && $host !== '')
            ->map(fn (string $host) => strtolower($host))
            ->unique()
            ->all();

        return in_array(strtolower($urlHost), $allowedHosts, true);
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
