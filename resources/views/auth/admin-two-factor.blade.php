@php
    $adminUser = auth()->user();
    $adminEmail = (string) ($adminUser?->email ?? '');
    if (str_contains($adminEmail, '@')) {
        [$emailName, $emailDomain] = explode('@', $adminEmail, 2);
        $maskedAdminEmail = substr($emailName, 0, 2) . str_repeat('*', max(strlen($emailName) - 2, 1)) . '@' . $emailDomain;
    } else {
        $maskedAdminEmail = $adminEmail !== '' ? $adminEmail : __('your admin email');
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Admin Verification') }} | {{ $systemSettings['site_name'] ?? config('app.name', 'YallaSpare') }}</title>
        @include('partials.brand-head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --admin-auth-bg: #04042a;
                --admin-auth-card: rgba(8, 8, 46, 0.88);
                --admin-auth-card-strong: rgba(13, 13, 58, 0.78);
                --admin-auth-input: #0c0c3d;
                --admin-auth-border: rgba(148, 155, 199, 0.16);
                --admin-auth-border-strong: rgba(180, 186, 222, 0.24);
                --admin-auth-text: #f4f5fb;
                --admin-auth-muted: #c7cade;
                --admin-auth-soft: #8e93b8;
                --admin-auth-accent: #dfa92e;
                --admin-auth-accent-hover: #d09d24;
                --admin-auth-accent-text: #e9c464;
                --admin-auth-accent-soft: rgba(223, 169, 46, 0.08);
                --admin-auth-danger: #ef4444;
                --admin-auth-warning: #f59e0b;
                --admin-auth-success: #22c55e;
            }

            body {
                min-height: 100vh;
                background:
                    radial-gradient(circle at 78% 8%, rgba(223, 169, 46, 0.03), transparent 26rem),
                    linear-gradient(160deg, #030322 0%, var(--admin-auth-bg) 52%, #08083a 100%);
                color: var(--admin-auth-text);
            }

            .admin-verify-shell {
                position: relative;
                min-height: 100vh;
                overflow: hidden;
                isolation: isolate;
            }

            .admin-verify-shell::before {
                content: '';
                position: absolute;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                background-image:
                    linear-gradient(rgba(148, 155, 199, 0.07) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(148, 155, 199, 0.07) 1px, transparent 1px);
                background-size: 42px 42px;
                mask-image: radial-gradient(circle at center, black, transparent 72%);
                opacity: 0.18;
            }

            .admin-verify-shell::after {
                content: '';
                position: absolute;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                background-image: radial-gradient(rgba(248, 250, 252, 0.10) 1px, transparent 1px);
                background-size: 3px 3px;
                opacity: 0.06;
                mix-blend-mode: screen;
            }

            .admin-auth-card {
                position: relative;
                z-index: 2;
                pointer-events: auto;
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.018)),
                    var(--admin-auth-card);
                border: 1px solid var(--admin-auth-border);
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.08) inset,
                    0 38px 96px -46px rgba(0, 0, 0, 0.88);
                backdrop-filter: blur(22px);
                -webkit-backdrop-filter: blur(22px);
            }

            .admin-security-panel {
                position: relative;
                z-index: 2;
                pointer-events: auto;
                background:
                    linear-gradient(180deg, rgba(223, 169, 46, 0.03), rgba(223, 169, 46, 0.008)),
                    rgba(6, 6, 40, 0.60);
                border: 1px solid rgba(148, 155, 199, 0.13);
            }

            .admin-otp-grid {
                display: grid;
                grid-template-columns: repeat(6, minmax(0, 1fr));
                gap: 0.625rem;
            }

            .admin-otp-box {
                pointer-events: auto;
                touch-action: manipulation;
                height: 3.75rem;
                border-radius: 1rem;
                border: 1px solid var(--admin-auth-border);
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.015)),
                    var(--admin-auth-input);
                color: var(--admin-auth-text);
                text-align: center;
                font-size: 1.35rem;
                font-weight: 700;
                line-height: 1;
                outline: none;
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.04) inset;
                transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease, background-color 180ms ease;
            }

            .admin-otp-box:focus {
                border-color: rgba(223, 169, 46, 0.45);
                box-shadow: 0 0 0 3px rgba(223, 169, 46, 0.08);
                transform: translateY(-1px);
            }

            .admin-otp-box:not(:placeholder-shown) {
                border-color: var(--admin-auth-border-strong);
                background-color: #12124a;
            }

            .admin-auth-button {
                pointer-events: auto;
                touch-action: manipulation;
                background: linear-gradient(180deg, var(--admin-auth-accent), var(--admin-auth-accent-hover));
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.18) inset, 0 14px 26px -22px rgba(0, 0, 0, 0.6);
                transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
            }

            .admin-auth-button:hover {
                filter: brightness(1.04);
                transform: translateY(-1px);
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.2) inset, 0 16px 30px -24px rgba(0, 0, 0, 0.65);
            }

            .admin-auth-button:active {
                transform: translateY(0);
                filter: brightness(0.98);
            }

            .admin-auth-button:disabled {
                cursor: wait;
                opacity: 0.72;
                transform: none;
            }

            .admin-otp-box.is-pop {
                animation: admin-otp-pop 200ms ease;
            }

            @keyframes admin-otp-pop {
                50% {
                    transform: scale(1.07);
                }
            }

            .admin-otp-grid.is-shake {
                animation: admin-otp-shake 460ms cubic-bezier(0.36, 0.07, 0.19, 0.97);
            }

            @keyframes admin-otp-shake {
                10%, 90% { transform: translateX(-2px); }
                20%, 80% { transform: translateX(4px); }
                30%, 50%, 70% { transform: translateX(-6px); }
                40%, 60% { transform: translateX(6px); }
            }

            .admin-otp-box.is-error {
                border-color: rgba(239, 68, 68, 0.55);
                background-color: rgba(239, 68, 68, 0.08);
                color: #fecaca;
            }

            .admin-otp-box.is-success {
                border-color: rgba(34, 197, 94, 0.55);
                background-color: rgba(34, 197, 94, 0.10);
                color: #86efac;
                transform: translateY(-2px);
            }

            .admin-auth-button.is-success {
                background: linear-gradient(180deg, #2eb564, #27a457);
                color: #ffffff;
            }

            @media (prefers-reduced-motion: reduce) {
                .admin-otp-box.is-pop,
                .admin-otp-grid.is-shake,
                .admin-auth-reveal {
                    animation: none;
                }

                .admin-otp-box,
                .admin-auth-button {
                    transition: none;
                }
            }

            .admin-auth-reveal {
                opacity: 1;
                animation: admin-auth-reveal 520ms cubic-bezier(0.22, 1, 0.36, 1) both;
            }

            @keyframes admin-auth-reveal {
                from {
                    opacity: 0;
                    transform: translateY(14px) scale(0.985);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            @media (max-width: 480px) {
                .admin-otp-grid {
                    gap: 0.45rem;
                }

                .admin-otp-box {
                    height: 3.25rem;
                    border-radius: 0.85rem;
                    font-size: 1.12rem;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <main class="admin-verify-shell">
            <div class="relative z-10 flex min-h-screen flex-col px-4 py-4 sm:px-6 lg:px-8">
                <header class="flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.04] px-3 py-2 text-white shadow-sm backdrop-blur transition hover:bg-white/[0.07] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-300/30">
                        <x-brand-mark
                            :logo-url="$systemSettings['site_logo_url'] ?? null"
                            :brand="$systemSettings['site_name'] ?? 'YallaSpare'"
                            wrapper-class="inline-flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white text-slate-950"
                            img-class="h-full w-full object-contain"
                            fallback-class="inline-flex h-full w-full items-center justify-center rounded-xl bg-white text-slate-950"
                            fallback-text-class="text-xs font-bold"
                        />
                        <span class="hidden min-[360px]:block">
                            <span class="block text-sm font-semibold leading-none">{{ $systemSettings['site_name'] ?? 'YallaSpare' }}</span>
                            <span class="mt-1 block text-[11px] font-medium text-slate-400">{{ __('Admin access') }}</span>
                        </span>
                    </a>

                    <x-language-switcher variant="dark" />
                </header>

                <section class="flex flex-1 items-center justify-center py-8 sm:py-10">
                    <div class="grid w-full max-w-5xl items-stretch gap-5 lg:grid-cols-[0.92fr_1.08fr]">
                        <aside class="admin-security-panel admin-auth-reveal hidden rounded-[2rem] p-7 lg:flex lg:flex-col lg:justify-between">
                            <div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-xs font-semibold text-slate-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-300/60"></span>
                                    {{ __('Privileged admin session') }}
                                </div>

                                <h2 class="mt-6 max-w-sm text-3xl font-semibold tracking-tight text-white">
                                    {{ __('Confirm control before entering the operations console.') }}
                                </h2>
                                <p class="mt-4 max-w-sm text-sm leading-6 text-slate-300">
                                    {{ __('This checkpoint protects inventory, orders, customers, and financial controls from unauthorized access.') }}
                                </p>
                            </div>

                            <div class="mt-10 space-y-3">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/[0.06] text-slate-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 5.25 6v5.25c0 4.13 2.88 7.98 6.75 8.99 3.87-1.01 6.75-4.86 6.75-8.99V6L12 3.75Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 12 1.5 1.5 3.25-3.25" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-white">{{ __('Admin email challenge') }}</p>
                                            <p class="mt-1 text-xs text-slate-400">{{ __('Codes expire automatically and attempts are rate limited.') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/[0.06] text-slate-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.25h.01" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.3 2.9 17.25A1.5 1.5 0 0 0 4.2 19.5h15.6a1.5 1.5 0 0 0 1.3-2.25L13.7 4.3a1.5 1.5 0 0 0-2.4 0Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-white">{{ __('High-trust area') }}</p>
                                            <p class="mt-1 text-xs text-slate-400">{{ __('Admin access changes live commerce operations.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <section class="admin-auth-card admin-auth-reveal rounded-[1.75rem] p-5 sm:p-7 lg:p-8" style="animation-delay: 80ms">
                            <div class="mx-auto max-w-md">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-amber-300/10 bg-amber-300/[0.05] text-amber-200/70">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V8a4 4 0 0 1 8 0v3" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 11h10.5A1.75 1.75 0 0 1 19 12.75v5.5A1.75 1.75 0 0 1 17.25 20H6.75A1.75 1.75 0 0 1 5 18.25v-5.5A1.75 1.75 0 0 1 6.75 11Z" />
                                        </svg>
                                    </div>
                                    <span class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                        {{ __('Admin 2FA') }}
                                    </span>
                                </div>

                                <div class="mt-7">
                                    <p class="text-sm font-semibold text-amber-200/60">{{ __('YallaSpare admin security') }}</p>
                                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                                        {{ __('Verification required') }}
                                    </h1>
                                    <p class="mt-4 text-sm leading-6 text-slate-300">
                                        {{ __('Enter the 6-digit code sent to :email before opening the admin console.', ['email' => $maskedAdminEmail]) }}
                                    </p>
                                </div>

                                <x-auth-session-status class="mt-6 rounded-2xl border border-emerald-400/25 bg-emerald-400/10 px-4 py-3 text-sm font-medium text-emerald-100" :status="session('status')" />

                                @if (($mailAvailable ?? true) === false)
                                    <div class="mt-6 rounded-2xl border border-amber-400/25 bg-amber-400/10 px-4 py-3 text-sm font-medium text-amber-100">
                                        {{ __('We could not send a verification email. Please try again or contact support if the problem continues.') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="mt-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-4 py-3 text-sm font-medium text-rose-100" data-server-error>
                                        {{ $errors->first() }}
                                    </div>
                                @endif

                                <div class="mt-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-4 py-3 text-sm font-medium text-rose-100" data-client-error hidden></div>

                                <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="mt-7" data-otp-form>
                                    @csrf

                                    <input type="hidden" name="code" data-otp-value>

                                    <div>
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <label for="admin-otp-1" class="text-sm font-semibold text-slate-200">{{ __('Verification code') }}</label>
                                            <span class="text-xs font-medium text-slate-500">{{ __('6 digits') }}</span>
                                        </div>

                                        <div class="admin-otp-grid" dir="ltr" data-otp-grid>
                                            @for ($i = 1; $i <= 6; $i++)
                                                <input
                                                    id="admin-otp-{{ $i }}"
                                                    class="admin-otp-box"
                                                    type="text"
                                                    inputmode="numeric"
                                                    autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}"
                                                    maxlength="1"
                                                    pattern="[0-9]*"
                                                    placeholder=" "
                                                    aria-label="{{ __('Verification digit :number', ['number' => $i]) }}"
                                                    data-otp-box
                                                    @if ($i === 1) autofocus @endif
                                                >
                                            @endfor
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        class="admin-auth-button mt-7 inline-flex h-12 w-full items-center justify-center rounded-2xl px-5 text-sm font-bold text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300/60 focus-visible:ring-offset-2 focus-visible:ring-offset-[#04042a]"
                                        data-loading-button
                                        data-loading-text="{{ __('Verifying...') }}"
                                    >
                                        <span data-button-label>{{ __('Verify admin access') }}</span>
                                    </button>
                                </form>

                                <div class="mt-5 flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <form method="POST" action="{{ route('admin.two-factor.resend') }}" data-resend-form data-cooldown="{{ (int) ($resendCooldownSeconds ?? 0) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-xl px-2 py-1.5 text-sm font-semibold text-amber-200/70 transition hover:bg-amber-300/[0.06] hover:text-amber-100/90 disabled:cursor-not-allowed disabled:text-slate-500 disabled:hover:bg-transparent"
                                            data-resend-button
                                            @disabled(((int) ($resendCooldownSeconds ?? 0)) > 0)
                                        >
                                            <span data-resend-label>{{ ((int) ($resendCooldownSeconds ?? 0)) > 0 ? __('Resend in :seconds s', ['seconds' => (int) $resendCooldownSeconds]) : __('Send a new code') }}</span>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-xl px-2 py-1.5 text-sm font-semibold text-slate-400 transition hover:bg-white/[0.05] hover:text-white"
                                        >
                                            {{ __('Sign out') }}
                                        </button>
                                    </form>
                                </div>

                                <p class="mt-6 text-center text-xs leading-5 text-slate-500">
                                    {{ __('This checkpoint protects privileged YallaSpare admin operations.') }}
                                </p>
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        </main>

        @include('partials.language-switcher-script')

        <script nonce="{{ $cspNonce }}">
            (function () {
                const form = document.querySelector('[data-otp-form]');
                const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
                const hidden = document.querySelector('[data-otp-value]');
                const grid = document.querySelector('[data-otp-grid]');
                const clientError = document.querySelector('[data-client-error]');
                const serverError = document.querySelector('[data-server-error]');

                if (!form || boxes.length === 0 || !hidden) {
                    return;
                }

                const button = form.querySelector('[data-loading-button]');
                const label = form.querySelector('[data-button-label]');
                const idleText = label ? label.textContent : '';
                const verifiedText = @json(__('Verified'));
                const failedText = @json(__('Verification failed. Please try again.'));
                const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                let submitting = false;

                const syncValue = () => {
                    hidden.value = boxes.map((box) => box.value.replace(/\D/g, '').slice(0, 1)).join('');
                };

                const popBox = (box) => {
                    if (reducedMotion) {
                        return;
                    }
                    box.classList.remove('is-pop');
                    void box.offsetWidth;
                    box.classList.add('is-pop');
                };

                const shakeGrid = () => {
                    if (!grid || reducedMotion) {
                        return;
                    }
                    grid.classList.remove('is-shake');
                    void grid.offsetWidth;
                    grid.classList.add('is-shake');
                };

                const clearBoxState = () => {
                    boxes.forEach((box) => box.classList.remove('is-error', 'is-success'));
                };

                const setLoading = (on) => {
                    if (button && label) {
                        button.disabled = on;
                        label.textContent = on ? (button.dataset.loadingText || idleText) : idleText;
                    }
                    boxes.forEach((box) => {
                        box.readOnly = on;
                    });
                };

                const showSuccess = (redirectUrl) => {
                    clearBoxState();
                    boxes.forEach((box, index) => {
                        window.setTimeout(() => box.classList.add('is-success'), reducedMotion ? 0 : index * 70);
                    });
                    if (button && label) {
                        button.classList.add('is-success');
                        label.textContent = '✓ ' + verifiedText;
                    }
                    window.setTimeout(() => {
                        window.location.assign(redirectUrl);
                    }, reducedMotion ? 150 : 900);
                };

                const showFailure = (message) => {
                    setLoading(false);
                    if (clientError) {
                        clientError.textContent = message;
                        clientError.hidden = false;
                    }
                    if (serverError) {
                        serverError.hidden = true;
                    }
                    boxes.forEach((box) => box.classList.add('is-error'));
                    shakeGrid();
                    window.setTimeout(() => {
                        clearBoxState();
                        boxes.forEach((box) => {
                            box.value = '';
                        });
                        syncValue();
                        boxes[0].focus();
                    }, reducedMotion ? 0 : 700);
                };

                const maybeAutoSubmit = () => {
                    if (submitting || hidden.value.length !== boxes.length) {
                        return;
                    }
                    window.setTimeout(() => {
                        if (!submitting && hidden.value.length === boxes.length) {
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        }
                    }, reducedMotion ? 0 : 240);
                };

                const fillFromText = (text) => {
                    const digits = String(text || '').replace(/\D/g, '').slice(0, boxes.length).split('');
                    boxes.forEach((box, index) => {
                        box.value = digits[index] || '';
                        if (digits[index]) {
                            popBox(box);
                        }
                    });
                    syncValue();
                    const next = boxes[Math.min(digits.length, boxes.length - 1)];
                    next?.focus();
                    maybeAutoSubmit();
                };

                boxes.forEach((box, index) => {
                    box.addEventListener('input', (event) => {
                        const value = event.target.value.replace(/\D/g, '');
                        if (value.length > 1) {
                            fillFromText(value);
                            return;
                        }

                        event.target.value = value;
                        syncValue();

                        if (value) {
                            popBox(box);
                            if (boxes[index + 1]) {
                                boxes[index + 1].focus();
                            }
                        }

                        maybeAutoSubmit();
                    });

                    box.addEventListener('keydown', (event) => {
                        if (event.key === 'Backspace' && !box.value && boxes[index - 1]) {
                            boxes[index - 1].focus();
                        }
                    });

                    box.addEventListener('paste', (event) => {
                        event.preventDefault();
                        fillFromText(event.clipboardData?.getData('text') || '');
                    });
                });

                form.addEventListener('submit', (event) => {
                    syncValue();

                    if (hidden.value.length !== boxes.length) {
                        event.preventDefault();
                        const firstEmpty = boxes.find((box) => !box.value) || boxes[0];
                        firstEmpty.focus();
                        return;
                    }

                    if (!window.fetch || submitting) {
                        if (!submitting) {
                            setLoading(true);
                        } else {
                            event.preventDefault();
                        }
                        return;
                    }

                    event.preventDefault();
                    submitting = true;
                    if (clientError) {
                        clientError.hidden = true;
                    }
                    setLoading(true);

                    fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    }).then(async (response) => {
                        if (response.ok && !response.url.includes('/two-factor')) {
                            showSuccess(response.url);
                            return;
                        }

                        if (response.status === 419) {
                            window.location.reload();
                            return;
                        }

                        let message = failedText;
                        try {
                            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                            const parsedError = doc.querySelector('[data-server-error]');
                            if (parsedError && parsedError.textContent.trim()) {
                                message = parsedError.textContent.trim();
                            }
                        } catch (parseError) {
                            // fall back to the generic message
                        }

                        submitting = false;
                        showFailure(message);
                    }).catch(() => {
                        submitting = false;
                        setLoading(true);
                        form.submit();
                    });
                });

                if (serverError) {
                    shakeGrid();
                }
            })();

            (function () {
                const form = document.querySelector('[data-resend-form]');
                const button = form?.querySelector('[data-resend-button]');
                const label = form?.querySelector('[data-resend-label]');

                if (!form || !button || !label) {
                    return;
                }

                let remaining = Number.parseInt(form.dataset.cooldown || '0', 10);
                if (Number.isNaN(remaining) || remaining <= 0) {
                    return;
                }

                const baseText = @json(__('Send a new code'));
                const tick = () => {
                    if (remaining <= 0) {
                        button.disabled = false;
                        label.textContent = baseText;
                        return;
                    }

                    button.disabled = true;
                    label.textContent = @json(__('Resend in :seconds s')).replace(':seconds', String(remaining));
                    remaining -= 1;
                    window.setTimeout(tick, 1000);
                };

                tick();
            })();
        </script>
    </body>
</html>
