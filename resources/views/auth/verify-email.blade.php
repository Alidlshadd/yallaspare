<x-auth-split-layout
    :heading="__('Verify Email')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Check Your Inbox')"
    :panel-subtitle="__('Confirm your email to unlock checkout, orders, saved addresses, and account settings.')"
    :panel-tag="__('Email Required')"
    panel-theme="login"
>
    @php
        $verificationEmail = (string) auth()->user()?->email;
        $emailDomain = strtolower((string) str($verificationEmail)->afterLast('@'));
        $isGmailAddress = in_array($emailDomain, ['gmail.com', 'googlemail.com'], true);
        $verificationSent = session('status') == 'verification-code-sent';
        $cooldownSeconds = 60;
    @endphp

    <style>
        @keyframes verifyCheckPulse {
            0% { opacity: 0; transform: scale(0.88); }
            70% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }

        @keyframes verifyToastIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes verifySpin {
            to { transform: rotate(360deg); }
        }

        [data-verify-check] {
            animation: verifyCheckPulse 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        [data-verify-toast] {
            animation: verifyToastIn 220ms ease-out both;
        }

        [data-verify-spinner] {
            animation: verifySpin 760ms linear infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            [data-verify-check],
            [data-verify-toast],
            [data-verify-spinner] {
                animation: none;
            }
        }

        #verify-email-experience :is(a, button):focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.95), 0 0 0 6px rgba(7, 7, 64, 0.38);
        }

        html.dark #verify-email-experience :is(a, button):focus-visible {
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.95), 0 0 0 6px rgba(125, 211, 252, 0.38);
        }

        @media (min-width: 1280px) {
            main > section {
                max-width: 64rem !important;
            }

            #auth-aside {
                padding: 2.35rem !important;
            }

            #auth-panel {
                max-width: 29rem;
                margin-inline: auto;
            }
        }

        @media (max-width: 1023px) {
            main {
                align-items: flex-start !important;
                padding: 4.75rem 1rem 1rem !important;
            }

            main > section {
                border-radius: 1.25rem !important;
            }

            #auth-aside,
            #auth-panel {
                padding: 1.25rem !important;
            }

            #auth-aside h1 {
                font-size: 1.875rem !important;
                line-height: 2.2rem !important;
            }

            #verify-email-experience {
                margin-top: 1rem !important;
            }

            #verify-email-experience [data-resend-button],
            #verify-email-experience [data-open-mail-button],
            #verify-email-experience [data-logout-button] {
                width: 100%;
            }
        }
    </style>

    @if ($verificationSent)
        <div
            class="fixed left-4 right-4 top-20 z-[60] mx-auto max-w-md rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-medium text-emerald-800 shadow-2xl shadow-slate-900/15 dark:border-emerald-500/30 dark:bg-slate-950 dark:text-emerald-200"
            role="status"
            aria-live="polite"
            data-verify-toast
        >
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white" data-verify-check>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.312a1 1 0 0 1-1.42.002L3.29 9.226a1 1 0 1 1 1.42-1.408l4.04 4.075 6.54-6.597a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span>{{ __('A fresh verification code has been sent.') }}</span>
            </div>
        </div>
    @endif

    <section
        id="verify-email-experience"
        class="mt-4 space-y-4"
        data-user-id="{{ auth()->id() }}"
        data-resend-sent="{{ $verificationSent ? '1' : '0' }}"
        data-cooldown-seconds="{{ $cooldownSeconds }}"
    >
        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200 dark:bg-slate-950/50 dark:ring-white/10">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#070740] text-white shadow-lg shadow-slate-950/20">
                    @if ($verificationSent)
                        <svg class="h-6 w-6" data-verify-check viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.312a1 1 0 0 1-1.42.002L3.29 9.226a1 1 0 1 1 1.42-1.408l4.04 4.075 6.54-6.597a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75h14.5v10.5H4.75z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 7.25 6.05 5.1a1.1 1.1 0 0 0 1.4 0l6.05-5.1" />
                        </svg>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Confirm your email address') }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ __('Enter the 6-digit code we sent to finish protecting your YallaSpare account.') }}
                    </p>
                    @if ($verificationEmail !== '')
                        <p class="mt-3 break-words rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-[#070740] dark:border-white/10 dark:bg-slate-900 dark:text-slate-100">
                            {{ $verificationEmail }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="border-y border-slate-200 py-3 dark:border-white/10" aria-label="{{ __('Verification steps') }}">
            <div class="grid gap-3 text-sm sm:grid-cols-3">
                <div class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-[#070740] dark:bg-white/10 dark:text-white">1</span>
                    <span class="font-semibold">{{ __('Open inbox') }}</span>
                </div>
                <div class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-[#070740] dark:bg-white/10 dark:text-white">2</span>
                    <span class="font-semibold">{{ __('Enter code') }}</span>
                </div>
                <div class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-[#070740] dark:bg-white/10 dark:text-white">3</span>
                    <span class="font-semibold">{{ __('Continue shopping') }}</span>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-5 dark:border-white/10">
            <div class="grid gap-3">
                <form method="POST" action="{{ route('verification.verify') }}" class="grid gap-3" data-auth-form>
                    @csrf

                    <div>
                        <label for="verification_code" class="mb-2 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                            {{ __('Verification code') }}
                        </label>
                        <input
                            id="verification_code"
                            name="verification_code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            pattern="[0-9]*"
                            value="{{ old('verification_code') }}"
                            class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-center text-xl font-bold tracking-[0.35em] text-[#070740] shadow-sm transition focus:border-[#070740] focus:outline-none focus:ring-2 focus:ring-[#070740]/20 dark:border-white/10 dark:bg-slate-900 dark:text-white dark:focus:border-sky-300 dark:focus:ring-sky-300/20"
                            required
                        >
                        @error('verification_code')
                            <p class="mt-2 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-lg bg-[#070740] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-950/20 transition duration-200 hover:bg-[#10105c] focus:outline-none"
                    >
                        {{ __('Verify Code') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('verification.send') }}" data-auth-form data-resend-form>
                    @csrf

                    <button
                        type="submit"
                        class="inline-flex min-h-[2.75rem] w-full min-w-[14rem] items-center justify-center gap-2 rounded-lg bg-[#070740] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-950/20 transition duration-200 hover:bg-[#10105c] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-500 disabled:shadow-none sm:w-auto"
                        data-resend-button
                    >
                        <svg class="h-4 w-4 opacity-0" data-verify-spinner viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M12 3a9 9 0 1 1-8.49 6" />
                        </svg>
                        <span data-resend-label>{{ __('Resend Verification Code') }}</span>
                    </button>
                </form>

                <div class="grid gap-3 sm:grid-cols-2">
                    @if ($isGmailAddress)
                        <a
                            href="https://mail.google.com/mail/u/0/#inbox"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition duration-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/10 dark:hover:text-white"
                            data-open-mail-button
                        >
                            {{ __('Open Gmail') }}
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="inline-flex min-h-[2.75rem] w-full items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition duration-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/10 dark:hover:text-white"
                            data-logout-button
                        >
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>
            </div>

            <p class="mt-3 min-h-[1.25rem] text-xs font-medium text-slate-500 dark:text-slate-400" aria-live="polite" data-cooldown-text></p>
        </div>

        <p class="text-xs leading-5 text-slate-500 dark:text-slate-400">
            {{ __('If the email is not visible, check spam or request a fresh code. The newest code is the one you should use.') }}
        </p>
    </section>

    <script>
        (() => {
            const root = document.getElementById('verify-email-experience');
            const form = root?.querySelector('[data-resend-form]');
            const button = root?.querySelector('[data-resend-button]');
            const label = root?.querySelector('[data-resend-label]');
            const spinner = root?.querySelector('[data-verify-spinner]');
            const cooldownText = root?.querySelector('[data-cooldown-text]');

            if (!root || !form || !button || !label || !cooldownText) {
                return;
            }

            const cooldownSeconds = Number(root.dataset.cooldownSeconds || 60);
            const key = `ys-email-verification-resend:${root.dataset.userId || 'guest'}`;
            const sentAt = root.dataset.resendSent === '1';
            let isSubmitting = false;

            if (sentAt) {
                const existingUntil = Number(window.localStorage.getItem(key) || 0);
                const nextUntil = Date.now() + cooldownSeconds * 1000;
                window.localStorage.setItem(key, String(Math.max(existingUntil, nextUntil)));
            }

            const setCooldownState = () => {
                const until = Number(window.localStorage.getItem(key) || 0);
                const remaining = Math.max(0, Math.ceil((until - Date.now()) / 1000));

                if (remaining > 0) {
                    button.disabled = true;
                    if (!isSubmitting) {
                        label.textContent = @json(__('Resend available soon'));
                    }
                    cooldownText.textContent = @json(__('You can resend another code in :seconds seconds.')).replace(':seconds', String(remaining));
                    return;
                }

                isSubmitting = false;
                button.disabled = false;
                label.textContent = @json(__('Resend Verification Code'));
                cooldownText.textContent = '';
                spinner?.classList.add('opacity-0');
                window.localStorage.removeItem(key);
            };

            setCooldownState();
            window.setInterval(setCooldownState, 1000);

            form.addEventListener('submit', () => {
                isSubmitting = true;
                window.localStorage.setItem(key, String(Date.now() + cooldownSeconds * 1000));
                button.disabled = true;
                label.textContent = @json(__('Sending...'));
                spinner?.classList.remove('opacity-0');
            });
        })();
    </script>
</x-auth-split-layout>
