# Admin Email Center Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace `/admin/email` with a 4-tab management center: Settings, Broadcast (queued, filtered, sanitized), History, Template Preview. Spec at `docs/superpowers/specs/2026-05-28-admin-email-center-design.md`.

**Architecture:** Existing `EmailController` keeps Settings + Preview. New `EmailBroadcastController` owns broadcast send + history. A `RecipientFilter` support class translates UI filters into User queries. A `HtmlSanitizer` wraps HTMLPurifier with our allowlist. `BroadcastMail` (ShouldQueue) + `SendBroadcastEmailJob` ride a `Bus::batch` for per-recipient delivery. Frontend: Preline-styled Tailwind shell + Alpine state + TipTap editor.

**Tech Stack:** Laravel 10 · PHP 8.1 · Tailwind 3 + Preline UI · Alpine 3 · TipTap 2 (editor) · HTMLPurifier (sanitizer) · Vite 8.

---

## Phase 1 — Foundation (tab shell, Settings polish, Preview wired, permission)

Goal of phase: visiting `/admin/email` shows 4 Preline-pill tabs; **Settings** still works exactly as today; **Template Preview** lets admin pick a template + locale and see the rendered email in an iframe; **Broadcast** and **History** tabs render "Coming next" placeholders. New `email.broadcast` permission registered so only super_admin + admin will see those tabs in Phase 2.

### Task 1.1: Install Preline UI

**Files:**
- Modify: `package.json` (npm dependency)
- Modify: `tailwind.config.js` (add Preline to content + plugin)
- Modify: `resources/js/app.js` (import Preline after Alpine)

- [ ] **Step 1: Add Preline to npm dependencies**

```bash
npm install preline
```

Expected: `package.json` now has `"preline": "^2.x"` under dependencies (or devDependencies — wherever npm puts it). `node_modules/preline/` exists.

- [ ] **Step 2: Register Preline with Tailwind**

Edit `tailwind.config.js`. Replace whole file with:

```js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import preline from 'preline/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/preline/preline.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, preline],
};
```

- [ ] **Step 3: Import Preline JS in the bundle**

Edit `resources/js/app.js`. Find the line `window.Alpine = Alpine;` and immediately AFTER the existing `Alpine.start();` call near the bottom of the file, add an import for Preline. Preline should be loaded so its `HSStaticMethods.autoInit()` runs.

Specifically, near the TOP of the file (after the Alpine import) add:

```js
import 'preline/preline';
```

The existing file already calls `Alpine.start()` near the bottom — leave that. Preline auto-inits on DOMContentLoaded.

- [ ] **Step 4: Build assets**

```bash
npm run build
```

Expected: `public/build/manifest.json` updates; new `app-<hash>.js` and `app-<hash>.css` appear under `public/build/assets/`. No build errors.

- [ ] **Step 5: Commit**

```bash
git add package.json package-lock.json tailwind.config.js resources/js/app.js
git commit -m "chore(deps): add Preline UI plugin to Tailwind + JS bundle"
```

---

### Task 1.2: Register `email.broadcast` permission

**Files:**
- Modify: `app/Models/User.php` (add const, label, role defaults)

- [ ] **Step 1: Add the constant**

In `app/Models/User.php`, find the block of `public const PERMISSION_*` declarations (around the existing `PERMISSION_ACTIVITY_LOGS_VIEW`). Add at the end of that block:

```php
    public const PERMISSION_EMAIL_BROADCAST = 'email.broadcast';
```

- [ ] **Step 2: Add a translation label**

Find the `allPermissionsLabels()` (or equivalent — it's the array map of permission → label) inside User.php. The grep from exploration showed it lives near line 181. Add the new entry inside the returned array:

```php
                self::PERMISSION_EMAIL_BROADCAST => __('Send broadcast emails to user segments'),
```

Keep alphabetical with the rest if the file is sorted; otherwise append.

- [ ] **Step 3: Grant to super_admin and admin by default**

Find `defaultPermissionsForRole()` (around line 236 per exploration). Two changes:

a) `ROLE_SUPER_ADMIN` already returns `self::allowedPermissions()` (all permissions) — no change there, but verify `allowedPermissions()` includes the new constant. If it's a manually maintained list rather than auto-generated, append `self::PERMISSION_EMAIL_BROADCAST` to it.

b) For `ROLE_ADMIN`'s returned array, append:

```php
                self::PERMISSION_EMAIL_BROADCAST,
```

Other roles (product_manager, order_manager, finance_manager, inventory_manager, settings_manager, dealer, user) do NOT get it.

- [ ] **Step 4: Verify a quick syntax check**

```bash
php -l app/Models/User.php
```

Expected: `No syntax errors detected in app/Models/User.php`.

- [ ] **Step 5: Commit (after Task 1.3 — they go together)**

We'll group this with the next task's commit since the tab shell will need the permission lookup.

---

### Task 1.3: Restructure `/admin/email` into a 4-tab shell

**Files:**
- Modify: `resources/views/admin/email/index.blade.php` (full rewrite)
- Create: `resources/views/admin/email/partials/_settings.blade.php`
- Create: `resources/views/admin/email/partials/_broadcast.blade.php` (placeholder)
- Create: `resources/views/admin/email/partials/_history.blade.php` (placeholder)
- Create: `resources/views/admin/email/partials/_preview.blade.php` (placeholder — Task 1.4 fills it in)
- Modify: `app/Http/Controllers/Admin/EmailController.php` (pass `canBroadcast` flag to view)

- [ ] **Step 1: Extract current settings markup into `_settings.blade.php`**

Read the current `resources/views/admin/email/index.blade.php`. The three `<section>` blocks (Mail Configuration, Send Test Email, Readiness Checks, Email Workflows — keep them all) are the Settings content. Cut them and paste into a new file:

Create `resources/views/admin/email/partials/_settings.blade.php` containing those sections **without** the `<x-app-layout>`, `<x-slot name="header">`, or outer `<div class="py-8">` wrapper — just the inner content (the `<section>` blocks and the `@if(session('success'))` flash block).

So `_settings.blade.php` is:

```blade
@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-900/30 dark:text-red-200">
        {{ $errors->first() }}
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr] mt-4">
    {{-- Mail Configuration card (copy from old index.blade.php) --}}
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Mail Configuration') }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Sensitive values are masked and must be changed from environment configuration.') }}</p>
        </div>
        <div class="grid gap-3 p-6 sm:grid-cols-2">
            @foreach ($summary as $label => $value)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __(str_replace('_', ' ', $label)) }}</p>
                    <p class="mt-2 break-words text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $value !== '' ? $value : '-' }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Send Test Email card (copy from old index.blade.php verbatim) --}}
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Send Test Email') }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Use this after changing SMTP, queue, or sender settings.') }}</p>
        </div>
        <form method="POST" action="{{ route('admin.email.test') }}" class="space-y-4 p-6">
            @csrf
            <div>
                <label for="recipient" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Recipient') }}</label>
                <input id="recipient" type="email" name="recipient" value="{{ old('recipient', auth()->user()?->email) }}" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required>
                @error('recipient')
                    <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="subject" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Subject') }}</label>
                <input id="subject" type="text" name="subject" value="{{ old('subject', 'YallaSpare test email') }}" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required>
            </div>
            <div>
                <label for="mailer" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Mailer') }}</label>
                <select id="mailer" name="mailer" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    @foreach ($mailers as $mailer)
                        <option value="{{ $mailer }}" @selected(old('mailer', $summary['default_mailer'] ?? '') === $mailer)>{{ $mailer }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500">
                <i class="fas fa-paper-plane"></i>
                {{ __('Send Test Email') }}
            </button>
        </form>
    </section>
</div>

{{-- Readiness Checks card --}}
<section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
    <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Readiness Checks') }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('These checks help catch the most common mail delivery problems.') }}</p>
    </div>
    <div class="grid gap-4 p-6 md:grid-cols-2">
        @foreach ($checks as $check)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $check['label'] }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Current:') }} {{ $check['value'] }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' }}">
                        {{ $check['ok'] ? __('OK') : __('Action') }}
                    </span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $check['detail'] }}</p>
            </div>
        @endforeach
    </div>
</section>
```

(Note: the Submit button color changed from `bg-slate-900` to `bg-red-600` — marka kırmızısı ile hizalama, P-prior decision.)

- [ ] **Step 2: Create placeholder partials for Broadcast, History, Preview**

Create `resources/views/admin/email/partials/_broadcast.blade.php`:

```blade
<div class="rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-slate-800 dark:bg-slate-900">
    <i class="fas fa-paper-plane text-3xl text-slate-300 dark:text-slate-700"></i>
    <p class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">{{ __('Broadcast') }}</p>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Coming in Phase 2 — compose and send filtered email broadcasts.') }}</p>
</div>
```

Create `resources/views/admin/email/partials/_history.blade.php`:

```blade
<div class="rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-slate-800 dark:bg-slate-900">
    <i class="fas fa-clock-rotate-left text-3xl text-slate-300 dark:text-slate-700"></i>
    <p class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">{{ __('History') }}</p>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Coming in Phase 3 — broadcast history and per-recipient outcomes.') }}</p>
</div>
```

Create `resources/views/admin/email/partials/_preview.blade.php` — Task 1.4 fills this in. For now, a stub:

```blade
<div class="rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-slate-800 dark:bg-slate-900">
    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Loading preview…') }}</p>
</div>
```

- [ ] **Step 3: Rewrite `resources/views/admin/email/index.blade.php` as the tabbed shell**

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('Email Center') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Configure, broadcast, audit, and preview every email the system sends.') }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                <i class="fas fa-envelope-open-text text-red-500"></i>
                {{ __('Admin mail tools') }}
            </span>
        </div>
    </x-slot>

    <div class="py-8" x-data="emailCenter()" x-init="init()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Pill tab bar (Preline-styled) --}}
            <nav role="tablist" class="inline-flex gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-900 mb-6">
                @php
                    $tabs = [
                        ['key' => 'settings',  'label' => __('Settings'),         'icon' => 'fa-gear',           'visible' => true],
                        ['key' => 'broadcast', 'label' => __('Broadcast'),        'icon' => 'fa-paper-plane',    'visible' => $canBroadcast],
                        ['key' => 'history',   'label' => __('History'),          'icon' => 'fa-clock-rotate-left','visible' => $canBroadcast],
                        ['key' => 'preview',   'label' => __('Template Preview'), 'icon' => 'fa-eye',            'visible' => true],
                    ];
                @endphp
                @foreach ($tabs as $tab)
                    @if ($tab['visible'])
                        <button type="button"
                                @click="setTab('{{ $tab['key'] }}')"
                                :class="tab === '{{ $tab['key'] }}'
                                    ? 'bg-red-600 text-white shadow shadow-red-900/20'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition">
                            <i class="fas {{ $tab['icon'] }}"></i>
                            {{ $tab['label'] }}
                        </button>
                    @endif
                @endforeach
            </nav>

            {{-- Tab panels --}}
            <div x-show="tab === 'settings'"  x-cloak role="tabpanel">
                @include('admin.email.partials._settings')
            </div>
            <div x-show="tab === 'broadcast'" x-cloak role="tabpanel">
                @include('admin.email.partials._broadcast')
            </div>
            <div x-show="tab === 'history'"   x-cloak role="tabpanel">
                @include('admin.email.partials._history')
            </div>
            <div x-show="tab === 'preview'"   x-cloak role="tabpanel">
                @include('admin.email.partials._preview')
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function emailCenter() {
            return {
                tab: 'settings',
                validTabs: @json(collect($tabs)->where('visible', true)->pluck('key')->values()),
                init() {
                    const hash = window.location.hash.replace('#', '');
                    if (this.validTabs.includes(hash)) {
                        this.tab = hash;
                    }
                },
                setTab(name) {
                    if (!this.validTabs.includes(name)) return;
                    this.tab = name;
                    history.replaceState(null, '', '#' + name);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
```

(Note: the `@push('scripts')` requires `@stack('scripts')` in `x-app-layout`. If not present, replace `@push` block with an inline `<script>` at end of file outside `<x-app-layout>` — but typically Laravel admin layouts do include the stack. Verify before commit.)

- [ ] **Step 4: Update `EmailController::index()` to pass the canBroadcast flag**

In `app/Http/Controllers/Admin/EmailController.php`, modify the `index` method:

```php
    public function index(\Illuminate\Http\Request $request): View
    {
        $user = $request->user();

        return view('admin.email.index', [
            'summary' => $this->mailSummary(),
            'mailers' => $this->availableMailers(),
            'checks' => $this->readinessChecks(),
            'previewTemplates' => array_keys($this->previewTemplates()),
            'canBroadcast' => $user?->hasPermission(\App\Models\User::PERMISSION_EMAIL_BROADCAST) ?? false,
        ]);
    }
```

- [ ] **Step 5: View cache + manual eyeball**

```bash
php artisan view:clear
```

Then admin sign-in → `/admin/email` → expect: 4 pill tabs (or 2 if you're testing a role without `email.broadcast`). Clicking each cycles content. URL hash updates. Settings tab renders identically to the old page.

- [ ] **Step 6: Commit Phase 1 (without the preview wiring yet)**

```bash
git add app/Models/User.php \
        app/Http/Controllers/Admin/EmailController.php \
        resources/views/admin/email/index.blade.php \
        resources/views/admin/email/partials/_settings.blade.php \
        resources/views/admin/email/partials/_broadcast.blade.php \
        resources/views/admin/email/partials/_history.blade.php \
        resources/views/admin/email/partials/_preview.blade.php
git commit -m "feat(admin): convert /admin/email into 4-tab shell with email.broadcast permission"
```

---

### Task 1.4: Wire the Template Preview tab to the existing preview route

**Files:**
- Modify: `resources/views/admin/email/partials/_preview.blade.php`

The preview route `/admin/email/preview/{template}` already exists from a prior commit and returns the rendered email HTML directly. The partial just needs to drive an iframe.

- [ ] **Step 1: Replace the stub partial with the real preview UI**

```blade
<div x-data="{
        template: 'verify-email',
        locale: '{{ app()->getLocale() }}',
        templates: @json($previewTemplates ?? []),
        get url() {
            return '{{ url('/admin/email/preview') }}/' + this.template + '?locale=' + this.locale;
        },
     }" class="space-y-4">

    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-4 sm:grid-cols-[1fr_180px]">
            <div>
                <label for="preview-template" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Template') }}</label>
                <select id="preview-template" x-model="template" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <template x-for="t in templates" :key="t">
                        <option :value="t" x-text="t"></option>
                    </template>
                </select>
            </div>
            <div>
                <label for="preview-locale" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Locale') }}</label>
                <select id="preview-locale" x-model="locale" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="en">English</option>
                    <option value="ar">العربية (RTL)</option>
                    <option value="ku">کوردی (RTL)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 dark:border-slate-800 dark:bg-slate-950">
        <iframe :src="url" :key="url"
                class="h-[820px] w-full rounded-xl border-0 bg-white"
                title="Email preview"></iframe>
    </div>
</div>
```

- [ ] **Step 2: Manual verify**

```bash
php artisan view:clear
```

Navigate to `/admin/email#preview`. Expect: template + locale selectors above an iframe rendering the chosen email. Change either select → iframe reloads. Pick `ku` → iframe content shows `dir="rtl"`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/email/partials/_preview.blade.php
git commit -m "feat(admin/email): wire Template Preview tab to /admin/email/preview iframe"
```

---

### Task 1.5: Phase 1 verification — feature test for the shell

**Files:**
- Create: `tests/Feature/Admin/EmailCenter/EmailCenterShellTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\User;
use Tests\TestCase;

class EmailCenterShellTest extends TestCase
{
    public function test_super_admin_sees_all_four_tabs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($admin)->get('/admin/email');

        $response->assertOk();
        $response->assertSee('Email Center');
        $response->assertSee('Settings', false);
        $response->assertSee('Broadcast', false);
        $response->assertSee('History', false);
        $response->assertSee('Template Preview', false);
    }

    public function test_settings_manager_without_email_broadcast_sees_only_settings_and_preview(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SETTINGS_MANAGER]);

        $response = $this->actingAs($user)->get('/admin/email');

        $response->assertOk();
        $response->assertSee('Settings', false);
        $response->assertSee('Template Preview', false);
        $response->assertDontSee('Broadcast');
        $response->assertDontSee('History');
    }

    public function test_admin_role_can_broadcast(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($admin->hasPermission(User::PERMISSION_EMAIL_BROADCAST));
    }

    public function test_settings_manager_cannot_broadcast(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SETTINGS_MANAGER]);

        $this->assertFalse($user->hasPermission(User::PERMISSION_EMAIL_BROADCAST));
    }
}
```

- [ ] **Step 2: Run the tests to verify they pass (or fail and pinpoint the gap)**

```bash
mkdir -p tests/Feature/Admin/EmailCenter
php artisan test --filter EmailCenterShellTest
```

Expected: all 4 tests PASS. If any fail, the most likely cause is the role default not granting the permission (Task 1.2 step 3). Fix and re-run.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Admin/EmailCenter/EmailCenterShellTest.php
git commit -m "test(admin/email): feature test for tab visibility per permission"
```

---

### Task 1.6: Build & deploy Phase 1

- [ ] **Step 1: Rebuild assets, clear caches**

```bash
npm run build
php artisan optimize:clear
```

- [ ] **Step 2: Run the full test suite to catch any regression**

```bash
php artisan test
```

Expected: all tests still pass (97 baseline + 4 new from Task 1.5 = 101+).

- [ ] **Step 3: Push Phase 1 to GitHub**

```bash
git push origin main
```

**Phase 1 ships:** tabs render, Settings unchanged, Preview works, permission registered. No broadcast surface yet — UI placeholder shows "Coming in Phase 2".

---

## Phase 2 — Broadcast (schema, models, sanitizer, filter, mailable, job, UI, tests)

Goal of phase: admin in Broadcast tab can filter users, compose a sanitized rich-text email with attachments, preview, and send a queued batch. Tests cover the security-critical paths (auth, sanitization, rate limit, attachment rejection).

### Task 2.1: Install HTMLPurifier + TipTap

**Files:**
- Modify: `composer.json` (require)
- Modify: `package.json` (deps)
- Modify: `resources/js/app.js` (import TipTap factory)

- [ ] **Step 1: Composer require HTMLPurifier**

```bash
composer require ezyang/htmlpurifier
```

Expected: `composer.json` now lists `ezyang/htmlpurifier` under `require`. `vendor/ezyang/htmlpurifier/` exists.

- [ ] **Step 2: Configure HTMLPurifier cache directory**

HTMLPurifier writes a definition cache. Ensure the storage path exists:

```bash
mkdir -p storage/app/htmlpurifier
```

The directory will be empty for now; the Sanitizer class will configure HTMLPurifier to use it.

- [ ] **Step 3: Install TipTap**

```bash
npm install @tiptap/core @tiptap/starter-kit @tiptap/extension-link @tiptap/extension-image
```

- [ ] **Step 4: Don't import TipTap globally yet**

We'll create a dedicated module `resources/js/email-broadcast.js` in Task 2.10 and import it from `app.js` lazily. Leave `app.js` alone for now.

- [ ] **Step 5: Commit dependencies**

```bash
git add composer.json composer.lock package.json package-lock.json
git commit -m "chore(deps): add HTMLPurifier + TipTap for broadcast composer"
```

---

### Task 2.2: HtmlSanitizer (TDD)

**Files:**
- Create: `tests/Unit/Support/HtmlSanitizerTest.php`
- Create: `app/Support/HtmlSanitizer.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Support;

use App\Support\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new HtmlSanitizer();
    }

    public function test_strips_script_tags(): void
    {
        $dirty = '<p>Hello</p><script>alert(1)</script>';
        $this->assertStringNotContainsString('<script', $this->sanitizer->clean($dirty));
        $this->assertStringContainsString('Hello', $this->sanitizer->clean($dirty));
    }

    public function test_strips_inline_event_handlers(): void
    {
        $dirty = '<img src="x" onerror="alert(1)">';
        $result = $this->sanitizer->clean($dirty);
        $this->assertStringNotContainsString('onerror', $result);
    }

    public function test_strips_iframe(): void
    {
        $dirty = '<iframe src="https://evil.test"></iframe>';
        $this->assertStringNotContainsString('iframe', $this->sanitizer->clean($dirty));
    }

    public function test_keeps_basic_formatting(): void
    {
        $dirty = '<p><strong>Bold</strong> and <em>italic</em></p>';
        $clean = $this->sanitizer->clean($dirty);
        $this->assertStringContainsString('<strong>Bold</strong>', $clean);
        $this->assertStringContainsString('<em>italic</em>', $clean);
    }

    public function test_keeps_safe_anchors_with_rel_and_target(): void
    {
        $dirty = '<a href="https://yallaspare.com" target="_blank" rel="noopener">link</a>';
        $clean = $this->sanitizer->clean($dirty);
        $this->assertStringContainsString('href="https://yallaspare.com"', $clean);
        $this->assertStringContainsString('target="_blank"', $clean);
    }

    public function test_strips_javascript_protocol_in_href(): void
    {
        $dirty = '<a href="javascript:alert(1)">click</a>';
        $clean = $this->sanitizer->clean($dirty);
        $this->assertStringNotContainsString('javascript:', $clean);
    }

    public function test_keeps_lists_and_headings(): void
    {
        $dirty = '<h2>Title</h2><ul><li>A</li><li>B</li></ul>';
        $clean = $this->sanitizer->clean($dirty);
        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<li>A</li>', $clean);
    }
}
```

- [ ] **Step 2: Run it to confirm it fails (class does not exist)**

```bash
mkdir -p tests/Unit/Support
php artisan test --filter HtmlSanitizerTest
```

Expected: `Error: Class "App\Support\HtmlSanitizer" not found`.

- [ ] **Step 3: Write the minimal implementation**

```php
<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        $config->set('Cache.SerializerPath', storage_path('app/htmlpurifier'));
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed',
            'p,br,strong,em,u,s,'
            .'a[href|title|target|rel],'
            .'ul,ol,li,'
            .'h1,h2,h3,'
            .'img[src|alt],'
            .'blockquote,'
            .'span[style]'
        );
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align']);
        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        // Force noopener on target=_blank links (XSS / tabnabbing defense)
        $config->set('HTML.TargetNoopener', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(string $html): string
    {
        return $this->purifier->purify($html);
    }
}
```

- [ ] **Step 4: Run the tests, watch them pass**

```bash
php artisan test --filter HtmlSanitizerTest
```

Expected: 7/7 PASS. If any test fails, inspect the purifier config — `HTML.Allowed` is the most likely culprit (e.g., missing element).

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Support/HtmlSanitizerTest.php app/Support/HtmlSanitizer.php
git commit -m "feat(support): HtmlSanitizer wrapping HTMLPurifier with allowlist"
```

---

### Task 2.3: Schema — `email_broadcasts` migration

**Files:**
- Create: `database/migrations/2026_05_28_000010_create_email_broadcasts_table.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 255);
            $table->longText('body_html'); // already-purified HTML
            $table->json('attachments')->nullable(); // [{path, original_name, mime, size}, ...]
            $table->json('filters_snapshot')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->enum('status', ['queued', 'sending', 'completed', 'failed'])->default('queued');
            $table->string('batch_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['admin_user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_broadcasts');
    }
};
```

- [ ] **Step 2: Run migration locally**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_05_28_000010_create_email_broadcasts_table` → `Migrated`. Test sqlite (PHPUnit) — re-run will be automatic on test.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_28_000010_create_email_broadcasts_table.php
git commit -m "feat(db): create email_broadcasts table"
```

---

### Task 2.4: Schema — `email_broadcast_recipients` migration

**Files:**
- Create: `database/migrations/2026_05_28_000020_create_email_broadcast_recipients_table.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('email_broadcasts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 255); // denormalized so audit survives user deletion
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['broadcast_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_broadcast_recipients');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_28_000020_create_email_broadcast_recipients_table.php
git commit -m "feat(db): create email_broadcast_recipients table"
```

---

### Task 2.5: Models — `EmailBroadcast` + `EmailBroadcastRecipient`

**Files:**
- Create: `app/Models/EmailBroadcast.php`
- Create: `app/Models/EmailBroadcastRecipient.php`

- [ ] **Step 1: Write `EmailBroadcast` model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailBroadcast extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'admin_user_id',
        'subject',
        'body_html',
        'attachments',
        'filters_snapshot',
        'recipient_count',
        'batch_id',
    ];

    /**
     * Mutable state — set only via forceFill()->save() in the controller / job.
     * Mirrors the P1 mass-assignment guard pattern used on Order/User.
     */
    protected $guarded = [
        'status',
        'sent_count',
        'failed_count',
        'sent_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'filters_snapshot' => 'array',
        'sent_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailBroadcastRecipient::class, 'broadcast_id');
    }
}
```

- [ ] **Step 2: Write `EmailBroadcastRecipient` model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailBroadcastRecipient extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'broadcast_id',
        'user_id',
        'email',
    ];

    protected $guarded = [
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(EmailBroadcast::class, 'broadcast_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 3: Lint**

```bash
php -l app/Models/EmailBroadcast.php
php -l app/Models/EmailBroadcastRecipient.php
```

Expected: `No syntax errors detected` on each.

- [ ] **Step 4: Commit**

```bash
git add app/Models/EmailBroadcast.php app/Models/EmailBroadcastRecipient.php
git commit -m "feat(models): EmailBroadcast + EmailBroadcastRecipient with guarded state fields"
```

---

### Task 2.6: RecipientFilter (TDD)

**Files:**
- Create: `tests/Feature/Admin/EmailCenter/RecipientFilterTest.php`
- Create: `app/Support/RecipientFilter.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\Order;
use App\Models\User;
use App\Support\RecipientFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipientFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_filter_returns_only_matching_roles(): void
    {
        User::factory()->create(['role' => User::ROLE_DEALER]);
        User::factory()->create(['role' => User::ROLE_USER]);
        User::factory()->create(['role' => User::ROLE_USER]);

        $filter = new RecipientFilter(['roles' => ['user']]);
        $this->assertSame(2, $filter->query()->count());
    }

    public function test_dealer_status_only_applies_when_dealer_role_selected(): void
    {
        User::factory()->create(['role' => User::ROLE_DEALER, 'dealer_status' => User::DEALER_STATUS_ACTIVE]);
        User::factory()->create(['role' => User::ROLE_DEALER, 'dealer_status' => User::DEALER_STATUS_INACTIVE]);
        User::factory()->create(['role' => User::ROLE_USER]); // non-dealer, should be ignored by dealer_statuses

        $filter = new RecipientFilter(['roles' => ['dealer'], 'dealer_statuses' => ['active']]);
        $this->assertSame(1, $filter->query()->count());
    }

    public function test_locale_filter_limits_to_users_with_preferred_locale(): void
    {
        User::factory()->create(['locale_preference' => 'ku']);
        User::factory()->create(['locale_preference' => 'en']);
        User::factory()->create(['locale_preference' => 'ar']);

        $filter = new RecipientFilter(['locales' => ['ku', 'ar']]);
        $this->assertSame(2, $filter->query()->count());
    }

    public function test_email_verified_filter(): void
    {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->unverified()->create();

        $filter = new RecipientFilter(['email_verified' => 'verified']);
        $this->assertSame(1, $filter->query()->count());

        $filterUnv = new RecipientFilter(['email_verified' => 'unverified']);
        $this->assertSame(1, $filterUnv->query()->count());
    }

    public function test_manual_include_and_exclude_apply_after_filters(): void
    {
        $a = User::factory()->create(['role' => User::ROLE_USER]);
        $b = User::factory()->create(['role' => User::ROLE_USER]);
        $excluded = User::factory()->create(['role' => User::ROLE_USER]);
        $extra = User::factory()->create(['role' => User::ROLE_DEALER]);

        $filter = new RecipientFilter([
            'roles' => ['user'],
            'manual_include' => [$extra->id],
            'manual_exclude' => [$excluded->id],
        ]);

        $ids = $filter->query()->pluck('id')->all();

        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
        $this->assertContains($extra->id, $ids);
        $this->assertNotContains($excluded->id, $ids);
    }

    public function test_empty_filter_returns_all_users(): void
    {
        User::factory()->count(3)->create();

        $filter = new RecipientFilter([]);
        $this->assertSame(3, $filter->query()->count());
    }
}
```

- [ ] **Step 2: Run, watch fail**

```bash
php artisan test --filter RecipientFilterTest
```

Expected: class not found.

- [ ] **Step 3: Implement `RecipientFilter`**

```php
<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RecipientFilter
{
    /**
     * @param array{
     *   roles?: array<int,string>,
     *   dealer_statuses?: array<int,string>,
     *   order_state?: string,
     *   locales?: array<int,string>,
     *   email_verified?: string,
     *   manual_include?: array<int,int>,
     *   manual_exclude?: array<int,int>,
     * } $filters
     */
    public function __construct(private array $filters)
    {
    }

    public function query(): Builder
    {
        $query = User::query();

        $roles = $this->arrayFilter('roles');
        if ($roles !== []) {
            $query->whereIn('role', $roles);
        }

        $dealerStatuses = $this->arrayFilter('dealer_statuses');
        if ($dealerStatuses !== [] && in_array('dealer', $roles, true)) {
            $query->whereIn('dealer_status', $dealerStatuses);
        }

        $orderState = (string) ($this->filters['order_state'] ?? 'any');
        if ($orderState === 'none') {
            $query->whereDoesntHave('orders');
        } elseif ($orderState === 'active') {
            $query->whereHas('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(90)));
        } elseif ($orderState === 'old') {
            $query->whereHas('orders', fn ($q) => $q->where('created_at', '<', now()->subDays(90)));
        }

        $locales = $this->arrayFilter('locales');
        if ($locales !== []) {
            $query->whereIn('locale_preference', $locales);
        }

        $verified = (string) ($this->filters['email_verified'] ?? 'any');
        if ($verified === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($verified === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        $excludeIds = array_map('intval', $this->arrayFilter('manual_exclude'));
        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        $includeIds = array_map('intval', $this->arrayFilter('manual_include'));
        if ($includeIds !== []) {
            // OR-ed with the rest of the where chain so the manual list is additive.
            $query = User::query()->where(function ($outer) use ($query, $includeIds) {
                $outer->whereIn('id', $query->select('id'))
                      ->orWhereIn('id', $includeIds);
            });
        }

        return $query;
    }

    public function normalize(): array
    {
        return [
            'roles' => $this->arrayFilter('roles'),
            'dealer_statuses' => $this->arrayFilter('dealer_statuses'),
            'order_state' => (string) ($this->filters['order_state'] ?? 'any'),
            'locales' => $this->arrayFilter('locales'),
            'email_verified' => (string) ($this->filters['email_verified'] ?? 'any'),
            'manual_include' => array_values(array_map('intval', $this->arrayFilter('manual_include'))),
            'manual_exclude' => array_values(array_map('intval', $this->arrayFilter('manual_exclude'))),
        ];
    }

    private function arrayFilter(string $key): array
    {
        $value = $this->filters[$key] ?? [];

        return is_array($value) ? array_values($value) : [];
    }
}
```

- [ ] **Step 4: Run tests, expect green**

```bash
php artisan test --filter RecipientFilterTest
```

Expected: all 6 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Admin/EmailCenter/RecipientFilterTest.php app/Support/RecipientFilter.php
git commit -m "feat(support): RecipientFilter translates UI filters into User query"
```

---

### Task 2.7: SecureImageStorage attachment helper (TDD for SVG reject)

**Files:**
- Modify: `app/Support/SecureImageStorage.php` (add `storeAttachment`)
- Create: `tests/Feature/Admin/EmailCenter/AttachmentValidationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\User;
use App\Support\SecureImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AttachmentValidationTest extends TestCase
{
    public function test_jpeg_is_accepted(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->image('photo.jpg', 600, 400);

        $path = SecureImageStorage::storeAttachment($file, 'email-attachments');

        $this->assertStringStartsWith('email-attachments/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_svg_is_rejected(): void
    {
        Storage::fake('local');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>';
        $file = UploadedFile::fake()->createWithContent('a.svg', $svg);

        $this->expectException(HttpException::class);
        SecureImageStorage::storeAttachment($file, 'email-attachments');
    }

    public function test_pdf_with_valid_content_is_accepted(): void
    {
        Storage::fake('local');
        // %PDF- magic bytes
        $pdf = "%PDF-1.4\n%test\n";
        $file = UploadedFile::fake()->createWithContent('doc.pdf', $pdf);

        $path = SecureImageStorage::storeAttachment($file, 'email-attachments');

        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_fake_pdf_with_wrong_magic_bytes_is_rejected(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('fake.pdf', 'this is not pdf');

        $this->expectException(HttpException::class);
        SecureImageStorage::storeAttachment($file, 'email-attachments');
    }
}
```

- [ ] **Step 2: Run to confirm fail**

```bash
php artisan test --filter AttachmentValidationTest
```

Expected: `Error: Method storeAttachment does not exist`.

- [ ] **Step 3: Add `storeAttachment` to `SecureImageStorage`**

In `app/Support/SecureImageStorage.php`, add a new method (the existing `store()` method stays untouched):

```php
    /**
     * Store an email-attachment-class upload. Accepts jpeg/png/webp via the
     * existing image flow OR application/pdf with a magic-byte check. SVG and
     * anything else is rejected with HTTP 422.
     *
     * @return string Relative storage path (e.g. "email-attachments/abc.pdf")
     */
    public static function storeAttachment(UploadedFile $file, string $directory, string $disk = 'local'): string
    {
        $realPath = $file->getRealPath();

        if ($realPath === false || ! is_file($realPath)) {
            abort(422, 'Unable to read uploaded file.');
        }

        $imageInfo = @getimagesize($realPath);
        $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            // Re-use the hardened image path (which itself rejects SVG/unverifiable MIME).
            return self::store($file, $directory, $disk);
        }

        // PDF path — verify magic bytes.
        $head = (string) @file_get_contents($realPath, false, null, 0, 5);
        if ($head !== '%PDF-') {
            abort(422, 'Unsupported or unverifiable attachment format.');
        }

        $filename = trim($directory, '/') . '/' . (string) \Illuminate\Support\Str::uuid() . '.pdf';
        \Illuminate\Support\Facades\Storage::disk($disk)->putFileAs(
            trim($directory, '/'),
            $file,
            basename($filename)
        );

        return $filename;
    }
```

- [ ] **Step 4: Run tests, expect green**

```bash
php artisan test --filter AttachmentValidationTest
```

Expected: 4/4 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Support/SecureImageStorage.php tests/Feature/Admin/EmailCenter/AttachmentValidationTest.php
git commit -m "feat(support): SecureImageStorage::storeAttachment with PDF magic-byte verify, SVG reject"
```

---

### Task 2.8: BroadcastMail + Mailable view

**Files:**
- Create: `app/Mail/BroadcastMail.php`
- Create: `resources/views/emails/broadcast.blade.php`
- Create: `tests/Feature/Admin/EmailCenter/BroadcastMailRenderTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Mail\BroadcastMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BroadcastMailRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_renders_subject_and_body(): void
    {
        $user = User::factory()->create(['locale_preference' => 'en']);

        $mailable = new BroadcastMail(
            subject: 'Test Broadcast',
            bodyHtml: '<p>Hello <strong>world</strong></p>',
            attachments: [],
        );

        $mailable->assertHasSubject('Test Broadcast');
        $mailable->assertSeeInHtml('Hello');
        $mailable->assertSeeInHtml('<strong>world</strong>', false);
    }

    public function test_mail_includes_attachments(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('email-attachments/sample.pdf', '%PDF-1.4');

        $mailable = new BroadcastMail(
            subject: 'With File',
            bodyHtml: '<p>See attached</p>',
            attachments: [['path' => 'email-attachments/sample.pdf', 'original_name' => 'doc.pdf', 'mime' => 'application/pdf', 'size' => 8]],
        );

        $mailable->assertHasAttachmentFromStorageDisk('local', 'email-attachments/sample.pdf', ['as' => 'doc.pdf', 'mime' => 'application/pdf']);
    }
}
```

- [ ] **Step 2: Run, expect fail**

```bash
php artisan test --filter BroadcastMailRenderTest
```

Expected: `Class "App\Mail\BroadcastMail" not found`.

- [ ] **Step 3: Implement `BroadcastMail`**

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BroadcastMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $subject,
        public readonly string $bodyHtml,
        public readonly array $attachments = [],
    ) {
        $this->onQueue('mail-broadcast');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
            with: [
                'bodyHtml' => $this->bodyHtml,
                'subjectLine' => $this->subject,
                'preheader' => mb_substr(strip_tags($this->bodyHtml), 0, 120),
                'title' => $this->subject,
            ],
        );
    }

    public function attachments(): array
    {
        return collect($this->attachments)
            ->map(fn ($a) => Attachment::fromStorageDisk('local', $a['path'])
                ->as($a['original_name'] ?? basename($a['path']))
                ->withMime($a['mime'] ?? 'application/octet-stream'))
            ->all();
    }
}
```

- [ ] **Step 4: Create the broadcast view (re-uses shared header/footer chrome)**

```blade
@extends('emails.layouts.base', [
    'preheader' => $preheader ?? '',
])

@section('content')
    {{-- Body HTML is already sanitized by HtmlSanitizer at write time;
         rendering with {!! !!} here is the right escape boundary. --}}
    <div style="color:#0f172a; font-size:15px; line-height:24px;">
        {!! $bodyHtml !!}
    </div>
@endsection
```

- [ ] **Step 5: Run tests, expect green**

```bash
php artisan test --filter BroadcastMailRenderTest
```

Expected: 2/2 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Mail/BroadcastMail.php resources/views/emails/broadcast.blade.php tests/Feature/Admin/EmailCenter/BroadcastMailRenderTest.php
git commit -m "feat(mail): BroadcastMail mailable + email view re-using shared chrome"
```

---

### Task 2.9: SendBroadcastEmailJob

**Files:**
- Create: `app/Jobs/SendBroadcastEmailJob.php`

(The job's E2E behaviour is exercised in Task 2.13's end-to-end test rather than a separate unit test — it's a thin coordinator.)

- [ ] **Step 1: Implement the job**

```php
<?php

namespace App\Jobs;

use App\Mail\BroadcastMail;
use App\Models\EmailBroadcast;
use App\Models\EmailBroadcastRecipient;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        public readonly int $broadcastId,
        public readonly int $recipientRowId,
    ) {
        $this->onQueue('mail-broadcast');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $broadcast = EmailBroadcast::find($this->broadcastId);
        $row = EmailBroadcastRecipient::with('user')->find($this->recipientRowId);

        if (! $broadcast || ! $row) {
            return;
        }

        try {
            $mail = new BroadcastMail(
                subject: $broadcast->subject,
                bodyHtml: $broadcast->body_html,
                attachments: $broadcast->attachments ?? [],
            );

            // Route via the user model when available — HasLocalePreference auto-sets locale.
            if ($row->user) {
                Mail::to($row->user)->send($mail);
            } else {
                Mail::to($row->email)->send($mail);
            }

            $row->forceFill([
                'status' => EmailBroadcastRecipient::STATUS_SENT,
                'sent_at' => now(),
            ])->save();

            $broadcast->increment('sent_count');
        } catch (Throwable $e) {
            $row->forceFill([
                'status' => EmailBroadcastRecipient::STATUS_FAILED,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ])->save();

            $broadcast->increment('failed_count');

            throw $e; // allow batch to track failure
        }
    }
}
```

- [ ] **Step 2: Lint**

```bash
php -l app/Jobs/SendBroadcastEmailJob.php
```

- [ ] **Step 3: Commit**

```bash
git add app/Jobs/SendBroadcastEmailJob.php
git commit -m "feat(jobs): SendBroadcastEmailJob per-recipient delivery with status tracking"
```

---

### Task 2.10: Rate limiter registration

**Files:**
- Modify: `app/Providers/RouteServiceProvider.php` (add `email-broadcast` limiter)

- [ ] **Step 1: Find the existing limiters and append**

Locate the `boot()` method in `RouteServiceProvider.php`. Existing throttle definitions (e.g. `admin-write`, `commerce-write`) live there. Append a new one:

```php
        \Illuminate\Cache\RateLimiting\Limit::perMinutes(5, 3);

        \Illuminate\Support\Facades\RateLimiter::for('email-broadcast', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinutes(5, 3)
                ->by($request->user()?->id ?: $request->ip());
        });
```

Insert ONLY the `RateLimiter::for(...)` block (delete the first stray statement — that was just for type context). Place it next to the existing throttles, keeping style.

- [ ] **Step 2: Verify by registering a temporary route inline (no commit yet — just sanity check)**

```bash
php artisan route:list | grep -i 'admin' | head
```

(Just to confirm route list still loads — i.e., RouteServiceProvider boots without errors.)

- [ ] **Step 3: Commit**

```bash
git add app/Providers/RouteServiceProvider.php
git commit -m "feat(rate-limit): register email-broadcast limiter (3 per 5min per admin)"
```

---

### Task 2.11: EmailBroadcastController + routes

**Files:**
- Create: `app/Http/Controllers/Admin/EmailBroadcastController.php`
- Modify: `routes/web.php` (add routes inside the admin group)

- [ ] **Step 1: Implement the controller**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBroadcastEmailJob;
use App\Mail\BroadcastMail;
use App\Models\EmailBroadcast;
use App\Models\EmailBroadcastRecipient;
use App\Models\User;
use App\Support\HtmlSanitizer;
use App\Support\RecipientFilter;
use App\Support\SecureImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

class EmailBroadcastController extends Controller
{
    public function __construct(private readonly HtmlSanitizer $sanitizer)
    {
    }

    public function previewRecipients(Request $request): JsonResponse
    {
        $filter = new RecipientFilter($request->input('filters', []));
        $query = $filter->query();

        return response()->json([
            'count' => $query->count(),
            'first10' => $query->limit(10)->get(['id', 'name', 'email', 'role'])->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
            ])->all(),
            'filters_normalized' => $filter->normalize(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'filters' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'], // 10MB per file
        ]);

        // Total attachment size guard: 25MB.
        $total = collect($request->file('attachments', []))
            ->sum(fn ($f) => $f->getSize());
        abort_if($total > 25 * 1024 * 1024, 422, 'Total attachment size exceeds 25MB.');

        $sanitized = $this->sanitizer->clean($data['body_html']);

        $attachments = collect($request->file('attachments', []))
            ->map(fn ($file) => [
                'path' => SecureImageStorage::storeAttachment($file, 'email-attachments'),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ])
            ->all();

        $filter = new RecipientFilter($data['filters'] ?? []);
        $users = $filter->query()->get(['id', 'email']);

        $broadcast = new EmailBroadcast();
        $broadcast->forceFill([
            'admin_user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'body_html' => $sanitized,
            'attachments' => $attachments,
            'filters_snapshot' => $filter->normalize(),
            'recipient_count' => $users->count(),
            'status' => EmailBroadcast::STATUS_QUEUED,
        ])->save();

        $rows = $users->map(fn ($u) => [
            'broadcast_id' => $broadcast->id,
            'user_id' => $u->id,
            'email' => $u->email,
            'status' => EmailBroadcastRecipient::STATUS_QUEUED,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();
        EmailBroadcastRecipient::insert($rows);

        $jobs = EmailBroadcastRecipient::where('broadcast_id', $broadcast->id)
            ->pluck('id')
            ->map(fn ($id) => new SendBroadcastEmailJob($broadcast->id, $id))
            ->all();

        $batch = Bus::batch($jobs)
            ->onQueue('mail-broadcast')
            ->name('email-broadcast-' . $broadcast->id)
            ->then(fn ($b) => EmailBroadcast::where('id', $broadcast->id)->update(['status' => EmailBroadcast::STATUS_COMPLETED, 'sent_at' => now()]))
            ->catch(fn ($b, $e) => EmailBroadcast::where('id', $broadcast->id)->update(['status' => EmailBroadcast::STATUS_FAILED]))
            ->dispatch();

        $broadcast->forceFill(['batch_id' => $batch->id, 'status' => EmailBroadcast::STATUS_SENDING])->save();

        return redirect()
            ->route('admin.email.index')
            ->with('success', __('Broadcast queued: :count recipients.', ['count' => $users->count()]));
    }

    public function sendTestToSelf(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
        ]);

        Mail::to($request->user())->send(new BroadcastMail(
            subject: $data['subject'],
            bodyHtml: $this->sanitizer->clean($data['body_html']),
        ));

        return redirect()
            ->route('admin.email.index')
            ->with('success', __('Test broadcast sent to :email.', ['email' => $request->user()->email]));
    }
}
```

- [ ] **Step 2: Register the routes**

In `routes/web.php`, inside the admin group, add:

```php
        Route::post('/email/broadcasts', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'store'])
            ->middleware(['can:' . User::PERMISSION_EMAIL_BROADCAST, 'throttle:email-broadcast'])
            ->name('email.broadcasts.store');

        Route::post('/email/broadcasts/test', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'sendTestToSelf'])
            ->middleware(['can:' . User::PERMISSION_EMAIL_BROADCAST, 'throttle:admin-write'])
            ->name('email.broadcasts.test');

        Route::post('/email/broadcasts/recipients-preview', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'previewRecipients'])
            ->middleware(['can:' . User::PERMISSION_EMAIL_BROADCAST, 'throttle:admin-write'])
            ->name('email.broadcasts.recipients-preview');
```

- [ ] **Step 3: Lint**

```bash
php -l app/Http/Controllers/Admin/EmailBroadcastController.php
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/EmailBroadcastController.php routes/web.php
git commit -m "feat(admin/email): EmailBroadcastController with store/test/preview + routes"
```

---

### Task 2.12: Authorization + rate-limit feature tests

**Files:**
- Create: `tests/Feature/Admin/EmailCenter/BroadcastAuthorizationTest.php`
- Create: `tests/Feature/Admin/EmailCenter/BroadcastRateLimitTest.php`

- [ ] **Step 1: Authorization test**

```php
<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_permitted_role_gets_403_on_store(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->post('/admin/email/broadcasts', [
            'subject' => 'Hi', 'body_html' => '<p>x</p>',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_role_can_reach_recipients_preview(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/admin/email/broadcasts/recipients-preview', [
            'filters' => [],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['count', 'first10', 'filters_normalized']);
    }
}
```

- [ ] **Step 2: Rate-limit test**

```php
<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BroadcastRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_throttled_after_three_broadcasts_in_five_minutes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        RateLimiter::clear('email-broadcast:' . $admin->id);

        $payload = ['subject' => 'Tick', 'body_html' => '<p>x</p>', 'filters' => []];

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin)->post('/admin/email/broadcasts', $payload)->assertRedirect();
        }

        $this->actingAs($admin)->post('/admin/email/broadcasts', $payload)->assertStatus(429);
    }
}
```

- [ ] **Step 3: Run, expect green**

```bash
php artisan test --filter "BroadcastAuthorizationTest|BroadcastRateLimitTest"
```

Expected: 3/3 PASS.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Admin/EmailCenter/BroadcastAuthorizationTest.php tests/Feature/Admin/EmailCenter/BroadcastRateLimitTest.php
git commit -m "test(admin/email): broadcast authorization + rate limit coverage"
```

---

### Task 2.13: End-to-end sanitization test

**Files:**
- Create: `tests/Feature/Admin/EmailCenter/BroadcastSanitizationTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\EmailBroadcast;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BroadcastSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_broadcast_body_is_sanitized(): void
    {
        Bus::fake();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        RateLimiter::clear('email-broadcast:' . $admin->id);

        $this->actingAs($admin)->post('/admin/email/broadcasts', [
            'subject' => 'Sanitize me',
            'body_html' => '<p>ok</p><script>alert(1)</script><img src=x onerror=alert(2)>',
            'filters' => [],
        ])->assertRedirect();

        $broadcast = EmailBroadcast::first();
        $this->assertNotNull($broadcast);
        $this->assertStringContainsString('<p>ok</p>', $broadcast->body_html);
        $this->assertStringNotContainsString('<script', $broadcast->body_html);
        $this->assertStringNotContainsString('onerror', $broadcast->body_html);
    }
}
```

- [ ] **Step 2: Run**

```bash
php artisan test --filter BroadcastSanitizationTest
```

Expected: 1/1 PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Admin/EmailCenter/BroadcastSanitizationTest.php
git commit -m "test(admin/email): end-to-end sanitization removes script/onerror from stored body"
```

---

### Task 2.14: TipTap editor wiring + canned templates from lang

**Files:**
- Create: `resources/js/email-broadcast.js`
- Modify: `resources/js/app.js` (import the broadcast module)
- Modify: `lang/en.json`, `lang/ar.json`, `lang/ku.json` (4 canned templates each)

- [ ] **Step 1: Add canned templates to lang JSON**

For each locale, append (before the closing `}`) — using the en.json append pattern from earlier sessions. EN values are identity; AR/KU translated. The keys are deliberately specific so they sort cleanly and don't collide.

```json
    "broadcast.template.campaign.title": "Campaign / Promotion",
    "broadcast.template.campaign.body": "<h2>Special offer</h2><p>Dear customer,</p><p>We have a limited-time offer for you. Use code <strong>SAVE10</strong> at checkout.</p>",
    "broadcast.template.announcement.title": "General Announcement",
    "broadcast.template.announcement.body": "<h2>Announcement</h2><p>Hello,</p><p>We want to share an important update with you.</p>",
    "broadcast.template.dealer.title": "Dealer-Only",
    "broadcast.template.dealer.body": "<h2>Dealer update</h2><p>Hello partner,</p><p>This message is for our authorised dealers only.</p>",
    "broadcast.template.thanks.title": "Thanks",
    "broadcast.template.thanks.body": "<h2>Thank you</h2><p>We appreciate your continued trust in YallaSpare.</p>"
```

(For `ar.json` and `ku.json`, translate the title/body strings; keep the keys identical.)

- [ ] **Step 2: Create the editor module**

`resources/js/email-broadcast.js`:

```js
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';

export function initEmailBroadcast() {
    document.querySelectorAll('[data-tiptap-mount]').forEach((mount) => {
        if (mount.dataset.tiptapReady === '1') return;
        mount.dataset.tiptapReady = '1';

        const hidden = mount.parentElement.querySelector('input[type="hidden"][name="body_html"]');
        const editor = new Editor({
            element: mount,
            extensions: [
                StarterKit,
                Link.configure({ openOnClick: false, HTMLAttributes: { rel: 'noopener', target: '_blank' } }),
                Image,
            ],
            content: hidden?.value ?? '<p></p>',
            onUpdate: ({ editor }) => {
                if (hidden) hidden.value = editor.getHTML();
            },
            editorProps: {
                attributes: {
                    class: 'prose prose-sm max-w-none min-h-[260px] rounded-lg border border-slate-300 bg-white p-3 text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100',
                },
            },
        });

        mount._tiptap = editor;
    });
}

if (typeof window !== 'undefined') {
    window.addEventListener('DOMContentLoaded', initEmailBroadcast);
}
```

- [ ] **Step 3: Pull the module into the bundle**

In `resources/js/app.js`, near the top imports, add:

```js
import './email-broadcast';
```

- [ ] **Step 4: Rebuild**

```bash
npm run build
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/email-broadcast.js resources/js/app.js lang/en.json lang/ar.json lang/ku.json
git commit -m "feat(broadcast): TipTap editor module + canned templates in lang JSON"
```

---

### Task 2.15: Build the Broadcast tab UI

**Files:**
- Modify: `resources/views/admin/email/partials/_broadcast.blade.php` (replace placeholder)

- [ ] **Step 1: Write the broadcast partial**

Replace the entire content of `_broadcast.blade.php` with:

```blade
@php
    $allRoles = [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_PRODUCT_MANAGER, User::ROLE_ORDER_MANAGER, User::ROLE_FINANCE_MANAGER, User::ROLE_INVENTORY_MANAGER, User::ROLE_SETTINGS_MANAGER, User::ROLE_DEALER, User::ROLE_USER];
    $cannedTemplates = ['campaign', 'announcement', 'dealer', 'thanks'];
@endphp

<div x-data="broadcastForm()" x-init="init()" class="grid gap-6 lg:grid-cols-[280px_1fr_280px]">

    {{-- Column 1: filters --}}
    <aside class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Filters') }}</p>

            <div class="mt-3">
                <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">{{ __('Role') }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach ($allRoles as $r)
                        <button type="button" @click="toggle('roles', '{{ $r }}')"
                                :class="filters.roles.includes('{{ $r }}') ? 'bg-red-600 text-white border-red-600' : 'border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-300'"
                                class="rounded-full border px-2.5 py-0.5 text-xs font-semibold transition">{{ $r }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3" x-show="filters.roles.includes('dealer')" x-cloak>
                <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">{{ __('Dealer status') }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach (['active', 'inactive', 'suspended'] as $s)
                        <button type="button" @click="toggle('dealer_statuses', '{{ $s }}')"
                                :class="filters.dealer_statuses.includes('{{ $s }}') ? 'bg-red-600 text-white border-red-600' : 'border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-300'"
                                class="rounded-full border px-2.5 py-0.5 text-xs font-semibold transition">{{ $s }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3">
                <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">{{ __('Language') }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach (['en', 'ar', 'ku'] as $loc)
                        <button type="button" @click="toggle('locales', '{{ $loc }}')"
                                :class="filters.locales.includes('{{ $loc }}') ? 'bg-red-600 text-white border-red-600' : 'border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-300'"
                                class="rounded-full border px-2.5 py-0.5 text-xs font-semibold transition">{{ strtoupper($loc) }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3">
                <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">{{ __('Order activity') }}</p>
                <select x-model="filters.order_state" class="w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="any">{{ __('Any') }}</option>
                    <option value="active">{{ __('Active (90 days)') }}</option>
                    <option value="old">{{ __('Older than 90 days') }}</option>
                    <option value="none">{{ __('Never ordered') }}</option>
                </select>
            </div>

            <div class="mt-3">
                <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">{{ __('Email status') }}</p>
                <select x-model="filters.email_verified" class="w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="any">{{ __('Any') }}</option>
                    <option value="verified">{{ __('Verified') }}</option>
                    <option value="unverified">{{ __('Unverified') }}</option>
                </select>
            </div>
        </div>
    </aside>

    {{-- Column 2: editor --}}
    <section class="space-y-4">
        <form method="POST" action="{{ route('admin.email.broadcasts.store') }}" enctype="multipart/form-data" id="broadcast-form" class="space-y-4">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <label class="text-xs uppercase tracking-wider text-slate-500">{{ __('Subject') }}</label>
                <input type="text" name="subject" required x-model="subject" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs uppercase tracking-wider text-slate-500">{{ __('Body') }}</label>
                    <select @change="loadTemplate($event.target.value); $event.target.value=''"
                            class="rounded-lg border-slate-300 bg-white text-xs dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">{{ __('Insert template…') }}</option>
                        @foreach ($cannedTemplates as $key)
                            <option value="{{ $key }}">{{ __('broadcast.template.' . $key . '.title') }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-tiptap-mount></div>
                <input type="hidden" name="body_html" value="<p></p>">
                @php
                    // Build a locale-aware map for canned templates as a small JS object.
                    $templatesPayload = collect($cannedTemplates)->mapWithKeys(fn ($k) => [$k => __('broadcast.template.' . $k . '.body')])->toArray();
                @endphp
                <script type="application/json" id="broadcast-templates">@json($templatesPayload)</script>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <label class="text-xs uppercase tracking-wider text-slate-500">{{ __('Attachments') }}</label>
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                       class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 dark:text-slate-300 dark:file:bg-slate-800 dark:file:text-slate-200">
                <p class="mt-1 text-xs text-slate-500">{{ __('Max 10MB per file, 25MB total. jpg/png/webp/pdf only.') }}</p>
            </div>
        </form>
    </section>

    {{-- Column 3: preview/send --}}
    <aside class="space-y-3">
        <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-4 text-center dark:border-blue-900/40 dark:bg-blue-950/40">
            <p class="text-3xl font-bold text-slate-900 dark:text-white" x-text="recipientCount"></p>
            <p class="mt-1 text-xs uppercase tracking-wider text-blue-700 dark:text-blue-300">{{ __('recipients selected') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">{{ __('First 10 recipients') }}</p>
            <ul class="space-y-1 text-xs text-slate-700 dark:text-slate-300">
                <template x-for="r in firstTen" :key="r.id">
                    <li x-text="r.name + ' · ' + r.email"></li>
                </template>
                <li x-show="firstTen.length === 0" class="italic text-slate-400">{{ __('No recipients yet') }}</li>
            </ul>
        </div>

        <button type="button" @click="sendTest" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Send test to me') }}</button>
        <button type="button" @click="confirmAndSend" :disabled="recipientCount === 0" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow shadow-red-900/20 transition hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed">{{ __('Send broadcast') }}</button>
    </aside>
</div>

<script>
function broadcastForm() {
    return {
        subject: '',
        filters: { roles: [], dealer_statuses: [], locales: [], order_state: 'any', email_verified: 'any', manual_include: [], manual_exclude: [] },
        recipientCount: 0,
        firstTen: [],
        previewToken: null,
        debounce: null,
        init() {
            this.refreshRecipients();
            this.$watch('filters', () => this.scheduleRefresh(), { deep: true });
        },
        toggle(key, value) {
            const arr = this.filters[key];
            const idx = arr.indexOf(value);
            if (idx === -1) arr.push(value); else arr.splice(idx, 1);
        },
        scheduleRefresh() {
            clearTimeout(this.debounce);
            this.debounce = setTimeout(() => this.refreshRecipients(), 300);
        },
        async refreshRecipients() {
            const token = ++this.previewToken;
            const res = await fetch('{{ route('admin.email.broadcasts.recipients-preview') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ filters: this.filters }),
            });
            if (this.previewToken !== token) return; // stale
            if (!res.ok) return;
            const data = await res.json();
            this.recipientCount = data.count;
            this.firstTen = data.first10;
        },
        loadTemplate(key) {
            if (!key) return;
            const payload = JSON.parse(document.getElementById('broadcast-templates').textContent);
            const html = payload[key];
            if (!html) return;
            const mount = document.querySelector('[data-tiptap-mount]');
            if (mount && mount._tiptap) {
                mount._tiptap.commands.setContent(html);
                document.querySelector('input[name=body_html]').value = html;
            }
        },
        async sendTest() {
            const form = document.getElementById('broadcast-form');
            const fd = new FormData(form);
            fd.delete('attachments[]'); // test-to-self skips attachments for now
            await fetch('{{ route('admin.email.broadcasts.test') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: fd,
            });
            window.location.href = '{{ route('admin.email.index') }}#broadcast';
        },
        confirmAndSend() {
            if (this.recipientCount === 0) return;
            if (this.recipientCount > 100) {
                if (!confirm(`{{ __('Send to') }} ${this.recipientCount} {{ __('recipients?') }}`)) return;
            }
            // Sync filters into a hidden field then submit.
            const form = document.getElementById('broadcast-form');
            let hidden = form.querySelector('input[name=filters]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'filters';
                form.appendChild(hidden);
            }
            hidden.value = JSON.stringify(this.filters);
            form.submit();
        },
    };
}
</script>
```

- [ ] **Step 2: Rebuild assets, clear view cache, eyeball**

```bash
npm run build
php artisan view:clear
```

Admin → `/admin/email#broadcast` → toggle filter chips → see counter update → pick a template → see editor populate → click "Send test to me" → verify mail in storage/logs/laravel.log (or your mail inbox if SMTP configured).

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/email/partials/_broadcast.blade.php
git commit -m "feat(admin/email): Broadcast tab UI — filters chips, TipTap mount, live counter, send flow"
```

---

### Task 2.16: Phase 2 final test + push

- [ ] **Step 1: Full test run**

```bash
php artisan test
```

Expected: ALL tests pass — baseline 101 (after Phase 1) + new tests = ~120+.

- [ ] **Step 2: Push**

```bash
git push origin main
```

**Phase 2 ships:** broadcast end-to-end works behind the admin permission, sanitized, throttled, attachment-safe, queued. History tab still placeholder.

---

## Phase 3 — History card list + drawer + activity log

Goal of phase: History tab shows the card list per the visual decision (recipient counts, sender, filter summary, status badge), with a click-into drawer showing per-recipient outcomes. Broadcast creations also flow into the existing Spatie ActivityLog.

### Task 3.1: Activity log on broadcast creation

**Files:**
- Modify: `app/Http/Controllers/Admin/EmailBroadcastController.php` (one extra line after save)
- Modify: `app/Models/EmailBroadcast.php` (use `LogsActivity` trait from Spatie)

- [ ] **Step 1: Add Spatie LogsActivity to the EmailBroadcast model**

In `app/Models/EmailBroadcast.php`, add at top:

```php
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
```

Then add a `use LogsActivity;` next to the existing traits and a method:

```php
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['subject', 'recipient_count', 'admin_user_id'])
            ->useLogName('email-broadcast');
    }
```

(This emits an activity_log row on every save / update — the controller's create call now auto-logs.)

- [ ] **Step 2: Lint + commit**

```bash
php -l app/Models/EmailBroadcast.php
git add app/Models/EmailBroadcast.php
git commit -m "feat(broadcast): emit Spatie activity log on broadcast create"
```

---

### Task 3.2: History endpoints in the controller

**Files:**
- Modify: `app/Http/Controllers/Admin/EmailBroadcastController.php` (add `history` + `show`)
- Modify: `routes/web.php` (add routes)

- [ ] **Step 1: Add controller methods**

Append to `EmailBroadcastController`:

```php
    public function history(): \Illuminate\View\View
    {
        $broadcasts = EmailBroadcast::query()
            ->with('admin:id,name,email')
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.email.partials._history-data', compact('broadcasts'));
    }

    public function show(int $id): JsonResponse
    {
        $broadcast = EmailBroadcast::with(['admin:id,name,email', 'recipients' => fn ($q) => $q->latest()->limit(100)])->findOrFail($id);

        return response()->json([
            'broadcast' => [
                'id' => $broadcast->id,
                'subject' => $broadcast->subject,
                'admin' => $broadcast->admin?->only(['name', 'email']),
                'status' => $broadcast->status,
                'recipient_count' => $broadcast->recipient_count,
                'sent_count' => $broadcast->sent_count,
                'failed_count' => $broadcast->failed_count,
                'filters_snapshot' => $broadcast->filters_snapshot,
                'created_at' => $broadcast->created_at?->toIso8601String(),
                'recipients_preview' => $broadcast->recipients->map(fn ($r) => [
                    'email' => $r->email,
                    'status' => $r->status,
                    'sent_at' => $r->sent_at?->toIso8601String(),
                    'error_message' => $r->error_message,
                ])->all(),
            ],
        ]);
    }
```

- [ ] **Step 2: Add the routes**

In `routes/web.php`, inside the admin group:

```php
        Route::get('/email/broadcasts/history', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'history'])
            ->middleware('can:' . User::PERMISSION_EMAIL_BROADCAST)
            ->name('email.broadcasts.history');

        Route::get('/email/broadcasts/{broadcast}', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'show'])
            ->middleware('can:' . User::PERMISSION_EMAIL_BROADCAST)
            ->whereNumber('broadcast')
            ->name('email.broadcasts.show');
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/EmailBroadcastController.php routes/web.php
git commit -m "feat(admin/email): history endpoints (list partial + JSON detail)"
```

---

### Task 3.3: History tab UI — card list + drawer

**Files:**
- Modify: `resources/views/admin/email/partials/_history.blade.php` (replace placeholder)

- [ ] **Step 1: Replace partial**

```blade
<div x-data="historyTab()" x-init="init()">
    <div x-show="loading" x-cloak class="rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm text-slate-500">{{ __('Loading…') }}</p>
    </div>

    <div x-show="!loading && items.length === 0" x-cloak class="rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-slate-800 dark:bg-slate-900">
        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('No broadcasts yet') }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ __('Send your first broadcast from the Broadcast tab.') }}</p>
    </div>

    <div x-show="!loading && items.length > 0" class="space-y-2" x-cloak>
        <template x-for="b in items" :key="b.id">
            <div @click="openDetail(b.id)" class="cursor-pointer rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white" x-text="b.subject"></p>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="b.admin_email + ' · ' + b.created_at_human + ' · ' + b.filter_summary"></p>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <div><span class="block text-sm font-bold text-slate-900 dark:text-white" x-text="b.recipient_count"></span><span class="text-slate-500">{{ __('total') }}</span></div>
                        <div><span class="block text-sm font-bold text-emerald-600 dark:text-emerald-300" x-text="b.sent_count"></span><span class="text-slate-500">{{ __('sent') }}</span></div>
                        <div><span class="block text-sm font-bold text-rose-600 dark:text-rose-400" x-text="b.failed_count"></span><span class="text-slate-500">{{ __('failed') }}</span></div>
                        <span :class="badgeClasses(b.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase" x-text="b.status"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Drawer --}}
    <div x-show="detail" x-cloak class="fixed inset-0 z-50 flex">
        <div class="flex-1 bg-slate-900/40 backdrop-blur-sm" @click="detail = null"></div>
        <aside class="w-full max-w-md overflow-y-auto bg-white p-6 shadow-2xl dark:bg-slate-950">
            <button @click="detail = null" class="text-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">&larr; {{ __('Close') }}</button>
            <template x-if="detail">
                <div class="mt-4 space-y-4">
                    <div>
                        <p class="text-lg font-bold text-slate-900 dark:text-white" x-text="detail.subject"></p>
                        <p class="text-xs text-slate-500" x-text="detail.admin?.email + ' · ' + new Date(detail.created_at).toLocaleString()"></p>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-lg bg-slate-100 p-3 dark:bg-slate-900"><p class="text-xl font-bold" x-text="detail.recipient_count"></p><p class="text-xs text-slate-500">{{ __('total') }}</p></div>
                        <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-950/40"><p class="text-xl font-bold text-emerald-700 dark:text-emerald-300" x-text="detail.sent_count"></p><p class="text-xs text-slate-500">{{ __('sent') }}</p></div>
                        <div class="rounded-lg bg-rose-50 p-3 dark:bg-rose-950/40"><p class="text-xl font-bold text-rose-700 dark:text-rose-400" x-text="detail.failed_count"></p><p class="text-xs text-slate-500">{{ __('failed') }}</p></div>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">{{ __('Recipients (first 100)') }}</p>
                        <ul class="space-y-1 text-xs">
                            <template x-for="r in detail.recipients_preview" :key="r.email + r.sent_at">
                                <li class="flex justify-between gap-2">
                                    <span class="truncate text-slate-700 dark:text-slate-300" x-text="r.email"></span>
                                    <span :class="r.status === 'sent' ? 'text-emerald-600' : (r.status === 'failed' ? 'text-rose-600' : 'text-slate-500')" x-text="r.status"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>
        </aside>
    </div>
</div>

<script>
function historyTab() {
    return {
        loading: true,
        items: [],
        detail: null,
        async init() {
            const res = await fetch('{{ route('admin.email.broadcasts.history') }}', { headers: { 'Accept': 'text/html' } });
            const html = await res.text();
            this.items = JSON.parse(new DOMParser().parseFromString(html, 'text/html').querySelector('script#history-data').textContent);
            this.loading = false;
        },
        async openDetail(id) {
            const res = await fetch('/admin/email/broadcasts/' + id, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.detail = data.broadcast;
        },
        badgeClasses(status) {
            const map = {
                queued: 'bg-amber-100 text-amber-700',
                sending: 'bg-blue-100 text-blue-700',
                completed: 'bg-emerald-100 text-emerald-700',
                failed: 'bg-rose-100 text-rose-700',
            };
            return map[status] ?? 'bg-slate-100 text-slate-700';
        },
    };
}
</script>
```

- [ ] **Step 2: Build the `_history-data.blade.php` helper (returns JSON-in-html for the fetch above)**

Create `resources/views/admin/email/partials/_history-data.blade.php`:

```blade
@php
    $rows = $broadcasts->map(function ($b) {
        $fs = $b->filters_snapshot ?? [];
        $summary = collect([
            $fs['roles'] ?? [],
            $fs['locales'] ?? [],
            $fs['email_verified'] ?? null,
        ])->flatten()->filter()->implode(' · ');
        return [
            'id' => $b->id,
            'subject' => $b->subject,
            'admin_email' => $b->admin?->email ?? __('Unknown'),
            'created_at_human' => $b->created_at?->diffForHumans(),
            'filter_summary' => $summary ?: __('All users'),
            'recipient_count' => $b->recipient_count,
            'sent_count' => $b->sent_count,
            'failed_count' => $b->failed_count,
            'status' => $b->status,
        ];
    })->values();
@endphp
<script id="history-data" type="application/json">@json($rows)</script>
```

- [ ] **Step 3: Eyeball + commit**

```bash
php artisan view:clear
```

Open History tab — expect card list (or empty state). Click any card → drawer slides in with the per-recipient breakdown.

```bash
git add resources/views/admin/email/partials/_history.blade.php resources/views/admin/email/partials/_history-data.blade.php
git commit -m "feat(admin/email): History tab card list + drawer with per-recipient detail"
```

---

### Task 3.4: Final regression + push

- [ ] **Step 1: Full test run**

```bash
php artisan test
```

Expected: full green. Build assets one last time:

```bash
npm run build
php artisan optimize:clear
```

- [ ] **Step 2: Push**

```bash
git push origin main
```

**Phase 3 ships:** History tab functional, activity log entries emitted, full feature available to super_admin + admin.

---

## Self-Review Summary

**Spec coverage check:**
- Settings tab preservation → Task 1.3 step 1 (extract markup verbatim into partial).
- Broadcast filters/editor/send → Tasks 2.6, 2.11, 2.14, 2.15.
- History card list + drawer → Tasks 3.2, 3.3.
- Template Preview tab → Task 1.4.
- HTMLPurifier sanitization → Task 2.2 + 2.13.
- SecureImageStorage attachments incl. SVG reject → Task 2.7.
- TipTap editor → Task 2.14.
- Bus::batch send → Task 2.11.
- Rate limit 3/5min → Tasks 2.10 + 2.12.
- `email.broadcast` permission → Task 1.2 + tests in 1.5 + 2.12.
- Lang-based canned templates → Task 2.14 step 1.
- RTL preview → Task 1.4 (iframe carries the locale param, base layout sets `dir="rtl"`).
- Activity log → Task 3.1.
- All TDD tests from spec section "Tests" → mapped 1:1 onto Tasks 2.2, 2.6, 2.7, 2.8, 2.12, 2.13.

**Placeholder scan:** none — every step has concrete code, file paths, commands. No TBDs.

**Type consistency:** `EmailBroadcast::STATUS_*` constants defined once (Task 2.5), referenced in Tasks 2.9, 2.11. `EmailBroadcastRecipient::STATUS_*` same. `RecipientFilter` constructor signature defined Task 2.6 step 3, called same way in 2.11 step 1. `HtmlSanitizer::clean()` signature one-arg string, used same way everywhere. `SecureImageStorage::storeAttachment(UploadedFile $file, string $directory, string $disk = 'local')` used same way in 2.7 and 2.11.

**Scope:** Three-phase plan. Each phase deployable independently. Phase 1 alone reverts the page improvement cleanly without leaving dead code. Phase 2 introduces backend + UI in one bundle. Phase 3 adds history view + activity log on top of Phase 2's data.

No issues found — plan is implementation-ready.
