<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Two-factor verification') }} | {{ $systemSettings['site_name'] ?? config('app.name', 'YallaSpare') }}</title>
        @include('partials.brand-head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --auth-bg: #0f172a;
                --auth-panel: rgba(17, 24, 39, 0.84);
                --auth-panel-strong: rgba(30, 41, 59, 0.78);
                --auth-input: #1e293b;
                --auth-border: rgba(148, 163, 184, 0.18);
                --auth-border-strong: rgba(203, 213, 225, 0.26);
                --auth-text: #f8fafc;
                --auth-muted: #cbd5e1;
                --auth-soft: #94a3b8;
                --auth-accent: #06b6d4;
                --auth-accent-hover: #0891b2;
                --auth-accent-soft: rgba(6, 182, 212, 0.14);
                --auth-success: #22c55e;
                --auth-danger: #ef4444;
                --auth-warning: #f59e0b;
            }

            body {
                min-height: 100vh;
                background:
                    radial-gradient(circle at 18% 12%, rgba(6, 182, 212, 0.18), transparent 30rem),
                    radial-gradient(circle at 82% 18%, rgba(139, 92, 246, 0.14), transparent 28rem),
                    linear-gradient(145deg, #0b1220 0%, var(--auth-bg) 48%, #111827 100%);
                color: var(--auth-text);
            }

            .two-factor-shell {
                position: relative;
                min-height: 100vh;
                overflow: hidden;
            }

            .two-factor-shell::before {
                content: '';
                position: absolute;
                inset: 0;
                pointer-events: none;
                background-image:
                    linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
                background-size: 44px 44px;
                mask-image: radial-gradient(circle at center, black, transparent 72%);
                opacity: 0.22;
            }

            .two-factor-shell::after {
                content: '';
                position: absolute;
                inset: 0;
                pointer-events: none;
                background-image: radial-gradient(rgba(248, 250, 252, 0.12) 1px, transparent 1px);
                background-size: 3px 3px;
                opacity: 0.11;
                mix-blend-mode: screen;
            }

            .auth-card {
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.018)),
                    var(--auth-panel);
                border: 1px solid var(--auth-border);
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.08) inset,
                    0 36px 90px -44px rgba(0, 0, 0, 0.86);
                backdrop-filter: blur(22px);
                -webkit-backdrop-filter: blur(22px);
            }

            .auth-side-panel {
                background:
                    linear-gradient(180deg, rgba(6, 182, 212, 0.12), rgba(6, 182, 212, 0.035)),
                    rgba(15, 23, 42, 0.58);
                border: 1px solid rgba(148, 163, 184, 0.14);
            }

            .otp-grid {
                display: grid;
                grid-template-columns: repeat(6, minmax(0, 1fr));
                gap: 0.625rem;
            }

            .otp-box {
                height: 3.75rem;
                border-radius: 1rem;
                border: 1px solid var(--auth-border);
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.015)),
                    var(--auth-input);
                color: var(--auth-text);
                text-align: center;
                font-size: 1.35rem;
                font-weight: 700;
                line-height: 1;
                outline: none;
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.04) inset;
                transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease, background-color 180ms ease;
            }

            .otp-box:focus {
                border-color: var(--auth-accent);
                box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.16), 0 16px 34px -28px rgba(6, 182, 212, 0.7);
                transform: translateY(-1px);
            }

            .otp-box:not(:placeholder-shown) {
                border-color: var(--auth-border-strong);
                background-color: #243147;
            }

            .auth-primary-button {
                background: linear-gradient(180deg, var(--auth-accent), var(--auth-accent-hover));
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.18) inset, 0 20px 38px -26px rgba(6, 182, 212, 0.8);
                transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
            }

            .auth-primary-button:hover {
                filter: brightness(1.04);
                transform: translateY(-1px);
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.2) inset, 0 24px 44px -28px rgba(6, 182, 212, 0.88);
            }

            .auth-primary-button:active {
                transform: translateY(0);
                filter: brightness(0.98);
            }

            .auth-primary-button:disabled {
                cursor: wait;
                opacity: 0.72;
                transform: none;
            }

            .auth-reveal {
                animation: auth-reveal 520ms cubic-bezier(0.22, 1, 0.36, 1) both;
            }

            @keyframes auth-reveal {
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
                .otp-grid {
                    gap: 0.45rem;
                }

                .otp-box {
                    height: 3.25rem;
                    border-radius: 0.85rem;
                    font-size: 1.12rem;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <main class="two-factor-shell">
            <div class="relative z-10 flex min-h-screen flex-col px-4 py-4 sm:px-6 lg:px-8">
                <header class="flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.04] px-3 py-2 text-white shadow-sm backdrop-blur transition hover:bg-white/[0.07] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300/35">
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
                            <span class="mt-1 block text-[11px] font-medium text-slate-400">{{ __('Secure access') }}</span>
                        </span>
                    </a>

                    <x-language-switcher variant="dark" />
                </header>

                <section class="flex flex-1 items-center justify-center py-8 sm:py-10">
                    <div class="grid w-full max-w-5xl items-stretch gap-5 lg:grid-cols-[0.92fr_1.08fr]">
                        <aside class="auth-side-panel auth-reveal hidden rounded-[2rem] p-7 lg:flex lg:flex-col lg:justify-between">
                            <div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-cyan-300/15 bg-cyan-300/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-300 shadow-[0_0_16px_rgba(103,232,249,0.9)]"></span>
                                    {{ __('Protected checkout session') }}
                                </div>

                                <h2 class="mt-6 max-w-sm text-3xl font-semibold tracking-tight text-white">
                                    {{ __('One more check before we open your account.') }}
                                </h2>
                                <p class="mt-4 max-w-sm text-sm leading-6 text-slate-300">
                                    {{ __('We verify this sign-in to keep orders, addresses, and payment history protected.') }}
                                </p>
                            </div>

                            <div class="mt-10 space-y-3">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-400/10 text-cyan-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 5.25 6v5.25c0 4.13 2.88 7.98 6.75 8.99 3.87-1.01 6.75-4.86 6.75-8.99V6L12 3.75Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 12 1.5 1.5 3.25-3.25" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-white">{{ __('Email code verification') }}</p>
                                            <p class="mt-1 text-xs text-slate-400">{{ __('Codes expire automatically for your safety.') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/5 text-slate-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V8a4 4 0 0 1 8 0v3" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 11h10.5A1.75 1.75 0 0 1 19 12.75v5.5A1.75 1.75 0 0 1 17.25 20H6.75A1.75 1.75 0 0 1 5 18.25v-5.5A1.75 1.75 0 0 1 6.75 11Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-white">{{ __('Session locked') }}</p>
                                            <p class="mt-1 text-xs text-slate-400">{{ __('Your session continues only after successful verification.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <section class="auth-card auth-reveal rounded-[1.75rem] p-5 sm:p-7 lg:p-8" style="animation-delay: 80ms">
                            <div class="mx-auto max-w-md">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-300/10 text-cyan-100 shadow-[0_18px_40px_-28px_rgba(6,182,212,0.8)]">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 5.25 6v5.25c0 4.13 2.88 7.98 6.75 8.99 3.87-1.01 6.75-4.86 6.75-8.99V6L12 3.75Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 12.25 11.25 14 14.75 10" />
                                        </svg>
                                    </div>
                                    <span class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                        {{ __('2FA') }}
                                    </span>
                                </div>

                                <div class="mt-7">
                                    <p class="text-sm font-semibold text-cyan-200">{{ __('YallaSpare identity check') }}</p>
                                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                                        {{ __('Verify it is you') }}
                                    </h1>
                                    <p class="mt-4 text-sm leading-6 text-slate-300">
                                        {{ __('Enter the 6-digit code sent to :email to continue.', ['email' => $maskedEmail]) }}
                                    </p>
                                </div>

                                @if (session('status'))
                                    <div class="mt-6 rounded-2xl border border-emerald-400/25 bg-emerald-400/10 px-4 py-3 text-sm font-medium text-emerald-100">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if (! $mailAvailable)
                                    <div class="mt-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-4 py-3 text-sm font-medium text-rose-100">
                                        {{ __('We could not send your verification code. Please try again shortly.') }}
                                    </div>
                                @endif

                                @error('code')
                                    <div class="mt-6 rounded-2xl border border-rose-400/25 bg-rose-400/10 px-4 py-3 text-sm font-medium text-rose-100">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <form method="POST" action="{{ route('user.two-factor.verify') }}" class="mt-7" data-otp-form>
                                    @csrf

                                    <input type="hidden" name="code" data-otp-value>

                                    <div>
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <label for="otp-1" class="text-sm font-semibold text-slate-200">{{ __('Verification code') }}</label>
                                            <span class="text-xs font-medium text-slate-500">{{ __('6 digits') }}</span>
                                        </div>

                                        <div class="otp-grid" dir="ltr" data-otp-grid>
                                            @for ($i = 1; $i <= 6; $i++)
                                                <input
                                                    id="otp-{{ $i }}"
                                                    class="otp-box"
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
                                        class="auth-primary-button mt-7 inline-flex h-12 w-full items-center justify-center rounded-2xl px-5 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                                        data-loading-button
                                        data-loading-text="{{ __('Verifying...') }}"
                                    >
                                        <span data-button-label>{{ __('Verify and continue') }}</span>
                                    </button>
                                </form>

                                <div class="mt-5 flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <form method="POST" action="{{ route('user.two-factor.resend') }}" data-resend-form data-cooldown="{{ (int) $resendCooldownSeconds }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-xl px-2 py-1.5 text-sm font-semibold text-cyan-200 transition hover:bg-cyan-300/10 hover:text-cyan-100 disabled:cursor-not-allowed disabled:text-slate-600 disabled:hover:bg-transparent"
                                            data-resend-button
                                            @disabled($resendCooldownSeconds > 0)
                                        >
                                            <span data-resend-label>{{ $resendCooldownSeconds > 0 ? __('Resend in :seconds s', ['seconds' => $resendCooldownSeconds]) : __('Resend code') }}</span>
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
                                    {{ __('This extra step helps protect your garage, orders, and account settings.') }}
                                </p>
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        </main>

        @include('partials.language-switcher-script')

        <script>
            (function () {
                const form = document.querySelector('[data-otp-form]');
                const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
                const hidden = document.querySelector('[data-otp-value]');

                if (!form || boxes.length === 0 || !hidden) {
                    return;
                }

                const syncValue = () => {
                    hidden.value = boxes.map((box) => box.value.replace(/\D/g, '').slice(0, 1)).join('');
                };

                const fillFromText = (text) => {
                    const digits = String(text || '').replace(/\D/g, '').slice(0, boxes.length).split('');
                    boxes.forEach((box, index) => {
                        box.value = digits[index] || '';
                    });
                    syncValue();
                    const next = boxes[Math.min(digits.length, boxes.length - 1)];
                    next?.focus();
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

                        if (value && boxes[index + 1]) {
                            boxes[index + 1].focus();
                        }
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

                    const button = form.querySelector('[data-loading-button]');
                    const label = form.querySelector('[data-button-label]');
                    if (button && label) {
                        button.disabled = true;
                        label.textContent = button.dataset.loadingText || label.textContent;
                    }
                });
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

                const baseText = @json(__('Resend code'));
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
