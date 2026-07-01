<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $registry = $this->registry();
        $overrides = collect();

        if (EmailTemplate::tableExists()) {
            $overrides = EmailTemplate::query()
                ->with('editor:id,name')
                ->get()
                ->keyBy(fn (EmailTemplate $t) => $t->template_key . '|' . $t->locale);
        }

        $rows = [];
        foreach ($registry as $key => $entry) {
            $rowLocales = [];
            foreach (EmailTemplate::LOCALES as $locale) {
                $override = $overrides->get($key . '|' . $locale);
                $rowLocales[$locale] = [
                    'has_override' => $override !== null,
                    'updated_at' => $override?->updated_at,
                    'updated_by' => $override?->editor?->name,
                ];
            }
            $rows[$key] = [
                'title' => $entry['title'],
                'icon' => $entry['icon'],
                'tone' => $entry['tone'],
                'description' => $entry['description'],
                'locales' => $rowLocales,
            ];
        }

        return view('admin.email.templates.index', [
            'rows' => $rows,
            'templateAvailable' => EmailTemplate::tableExists(),
        ]);
    }

    public function edit(string $key, string $locale): View
    {
        $this->guardKey($key);
        $this->guardLocale($locale);

        $registry = $this->registry();
        $defaults = $this->defaults($key, $locale);
        $override = EmailTemplate::findOverride($key, $locale);

        return view('admin.email.templates.edit', [
            'templateKey' => $key,
            'locale' => $locale,
            'meta' => $registry[$key],
            'defaults' => $defaults,
            'override' => $override,
            'subject' => old('subject', $override?->subject ?? $defaults['subject']),
            'body_html' => old('body_html', $override?->body_html ?? $defaults['body_html']),
            'tableExists' => EmailTemplate::tableExists(),
        ]);
    }

    public function update(Request $request, string $key, string $locale): RedirectResponse
    {
        $this->guardKey($key);
        $this->guardLocale($locale);

        if (! EmailTemplate::tableExists()) {
            return back()->withInput()->withErrors([
                'body_html' => __('Email templates table is not installed yet. Run the pending migrations first.'),
            ]);
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string', 'max:65000'],
        ]);

        EmailTemplate::query()->updateOrCreate(
            ['template_key' => $key, 'locale' => $locale],
            [
                'subject' => trim($data['subject']),
                'body_html' => $this->sanitize($data['body_html']),
                'updated_by' => $request->user()?->getAuthIdentifier(),
            ],
        );

        return redirect()
            ->route('admin.email.templates.edit', ['key' => $key, 'locale' => $locale])
            ->with('success', __('Template saved.'));
    }

    public function preview(Request $request, string $key, string $locale): View
    {
        $this->guardKey($key);
        $this->guardLocale($locale);

        $subject = (string) $request->query('subject', '');
        $body = (string) $request->query('body_html', '');

        if ($subject === '' && $body === '') {
            $override = EmailTemplate::findOverride($key, $locale);
            if ($override) {
                $subject = $override->subject;
                $body = $override->body_html;
            } else {
                $defaults = $this->defaults($key, $locale);
                $subject = $defaults['subject'];
                $body = $defaults['body_html'];
            }
        }

        $body = $this->sanitize($body);

        app()->setLocale(in_array($locale, ['en', 'ar', 'ku'], true) ? $locale : 'en');

        $registry = $this->registry();
        $meta = $registry[$key];

        return view('admin.email.templates.preview', [
            'templateKey' => $key,
            'locale' => $locale,
            'subject' => $subject,
            'body_html' => $this->interpolate($body, $this->sampleVars($key)),
            'meta' => $meta,
            'sampleVars' => $this->sampleVars($key),
        ]);
    }

    /**
     * Strip dangerous tags before persisting or rendering admin content.
     * Only allows a curated tag list. Rejects <script>, <iframe>, event handlers, etc.
     */
    private function sanitize(string $html): string
    {
        $stripBlocks = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'meta', 'link', 'base'];
        foreach ($stripBlocks as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $html) ?? $html;
            $html = preg_replace('/<' . $tag . '\b[^>]*\/?>/is', '', $html) ?? $html;
        }

        $allowed = '<p><br><strong><b><em><i><u><s><a><ul><ol><li><h1><h2><h3><h4><blockquote><hr><span><div>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $clean) ?? $clean;
        $clean = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $clean) ?? $clean;
        $clean = preg_replace('/javascript:/i', '', $clean) ?? $clean;

        return trim($clean);
    }

    /**
     * Replace {name}, {code}, {url} style tokens with sample data for preview.
     *
     * @param  array<string, string>  $vars
     */
    private function interpolate(string $html, array $vars): string
    {
        foreach ($vars as $token => $value) {
            $html = str_replace('{' . $token . '}', e((string) $value), $html);
        }

        return $html;
    }

    /**
     * @return array<string, string>
     */
    private function sampleVars(string $key): array
    {
        $shared = [
            'brand' => 'YallaSpare',
            'name' => 'Ahmed Al-Khalidi',
            'email' => 'customer@example.com',
        ];

        return match ($key) {
            'verify-email' => $shared + ['code' => '847293', 'expires' => '60'],
            'reset-password' => $shared + ['url' => url('/reset-password/sample-token'), 'expires' => '60'],
            'two-factor-code' => $shared + ['code' => '129 437', 'expires' => '10'],
            'welcome' => $shared + ['url' => url('/')],
            'order-status' => $shared + ['order' => 'YS-104482', 'tracking' => 'AR-9837-4471-IQ', 'url' => url('/account/orders')],
            'dealer' => $shared + ['status' => 'approved', 'tier' => '8%', 'url' => url('/')],
            'security-alert' => $shared + ['device' => 'Chrome 134 / Windows 11', 'ip' => '93.184.216.34'],
            'low-stock' => $shared + ['count' => '3', 'url' => url('/')],
            'support' => $shared + ['subject' => 'Wrong part received', 'topic' => 'Order issue'],
            default => $shared,
        };
    }

    /**
     * Registry of admin-editable templates with metadata (title, icon, tone, description).
     * Kept in sync with EmailController::templateCards() and previewTemplates().
     *
     * @return array<string, array{title:string,icon:string,tone:string,description:string}>
     */
    private function registry(): array
    {
        return [
            'verify-email' => [
                'title' => __('Email verification'),
                'icon' => 'fa-shield-halved',
                'tone' => 'blue',
                'description' => __('Six-digit customer verification with expiry details.'),
            ],
            'order-status' => [
                'title' => __('Order status'),
                'icon' => 'fa-truck-fast',
                'tone' => 'emerald',
                'description' => __('Customer order timeline with items, totals, and tracking CTA.'),
            ],
            'security-alert' => [
                'title' => __('Security alert'),
                'icon' => 'fa-triangle-exclamation',
                'tone' => 'rose',
                'description' => __('Admin sign-in and risk notification with device metadata.'),
            ],
            'reset-password' => [
                'title' => __('Password reset'),
                'icon' => 'fa-key',
                'tone' => 'amber',
                'description' => __('Secure reset flow with expiry and protective warning.'),
            ],
            'two-factor-code' => [
                'title' => __('Admin 2FA'),
                'icon' => 'fa-user-lock',
                'tone' => 'violet',
                'description' => __('Short-lived admin sign-in code for protected sessions.'),
            ],
            'welcome' => [
                'title' => __('Welcome'),
                'icon' => 'fa-star',
                'tone' => 'cyan',
                'description' => __('New account onboarding with useful next actions.'),
            ],
            'dealer' => [
                'title' => __('Dealer update'),
                'icon' => 'fa-handshake',
                'tone' => 'indigo',
                'description' => __('Dealer account status and account action messaging.'),
            ],
            'low-stock' => [
                'title' => __('Low stock alert'),
                'icon' => 'fa-boxes-stacked',
                'tone' => 'orange',
                'description' => __('Inventory warning for products below configured threshold.'),
            ],
            'support' => [
                'title' => __('Support request'),
                'icon' => 'fa-headset',
                'tone' => 'slate',
                'description' => __('Inbound customer contact details and message summary.'),
            ],
        ];
    }

    /**
     * Default content pre-filled into the edit form when no override exists yet.
     * These match the copy that ships in the current transactional blade views.
     *
     * @return array{subject:string, body_html:string}
     */
    private function defaults(string $key, string $locale): array
    {
        $registry = [
            'verify-email' => [
                'subject_key' => 'Verify your email address',
                'body' => '<p>' . __('Enter this verification code on the YallaSpare verification screen to protect your account and unlock checkout, order tracking, and account settings.') . '</p>'
                    . '<p><strong>' . __('Verification code: :code', ['code' => '{code}']) . '</strong></p>'
                    . '<p>' . __('This one-time code expires automatically. Do not share it with anyone, including YallaSpare support.') . '</p>',
            ],
            'reset-password' => [
                'subject_key' => 'Reset your password',
                'body' => '<p>' . __('Choose a new password using the secure reset link below.') . '</p>'
                    . '<p><a href="{url}">' . __('Reset password') . '</a></p>'
                    . '<p>' . __('If you did not request this, you can safely ignore this email.') . '</p>',
            ],
            'two-factor-code' => [
                'subject_key' => 'Admin sign-in code',
                'body' => '<p>' . __('Use this one-time code to finish signing in to your admin account.') . '</p>'
                    . '<p><strong>' . __('Code: :code', ['code' => '{code}']) . '</strong></p>'
                    . '<p>' . __('The code is valid for :count minutes.', ['count' => '{expires}']) . '</p>',
            ],
            'welcome' => [
                'subject_key' => 'Welcome to YallaSpare',
                'body' => '<p>' . __('Welcome, :name!', ['name' => '{name}']) . '</p>'
                    . '<p>' . __('Your account is ready for checkout, order tracking, and saved settings.') . '</p>'
                    . '<p><a href="{url}">' . __('Open Your Account') . '</a></p>',
            ],
            'order-status' => [
                'subject_key' => 'Your order is on the way',
                'body' => '<p>' . __('Your YallaSpare order #:order has shipped and will arrive in 2-3 business days.', ['order' => '{order}']) . '</p>'
                    . '<p><a href="{url}">' . __('Track your order') . '</a></p>',
            ],
            'dealer' => [
                'subject_key' => 'Dealer status approved',
                'body' => '<p>' . __('Your YallaSpare dealer application has been approved. You can now access dealer pricing, order tools, and inventory management.') . '</p>'
                    . '<p><a href="{url}">' . __('View dealer dashboard') . '</a></p>',
            ],
            'security-alert' => [
                'subject_key' => 'New admin sign-in detected',
                'body' => '<p>' . __('A YallaSpare admin account was just signed in from a new device.') . '</p>'
                    . '<p>' . __('If this was you, no further action is needed.') . '</p>',
            ],
            'low-stock' => [
                'subject_key' => 'Products below threshold',
                'body' => '<p>' . __(':count products dropped below the low-stock threshold. Replenish them before the next sales cycle to avoid lost orders.', ['count' => '{count}']) . '</p>'
                    . '<p><a href="{url}">' . __('Manage inventory') . '</a></p>',
            ],
            'support' => [
                'subject_key' => 'New support request',
                'body' => '<p>' . __('A customer submitted a support request.') . '</p>'
                    . '<p><strong>' . __('Topic: :topic', ['topic' => '{topic}']) . '</strong></p>',
            ],
        ];

        $entry = $registry[$key];
        app()->setLocale(in_array($locale, ['en', 'ar', 'ku'], true) ? $locale : 'en');

        return [
            'subject' => __($entry['subject_key']),
            'body_html' => $entry['body'],
        ];
    }

    private function guardKey(string $key): void
    {
        abort_unless(in_array($key, EmailTemplate::KEYS, true), 404);
    }

    private function guardLocale(string $locale): void
    {
        abort_unless(in_array($locale, EmailTemplate::LOCALES, true), 404);
    }
}
