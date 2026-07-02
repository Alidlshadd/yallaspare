<x-auth-split-layout
    :heading="__('Enter your verification code')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Verify it is really you')"
    :panel-subtitle="__('A 6-digit code is on its way to :email. Enter it below to continue.', ['email' => $maskedEmail])"
    :panel-tag="__('Identity check')"
    panel-theme="login"
    panel-button-action="none"
>
    <style>
        .otp-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.5rem;
        }

        .otp-box {
            height: 3.5rem;
            border-radius: 0.875rem;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #0f172a;
            text-align: center;
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1;
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease, background-color 160ms ease;
        }

        .otp-box:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.18);
            transform: translateY(-1px);
        }

        .otp-box:not(:placeholder-shown) {
            border-color: #94a3b8;
            background-color: #f8fafc;
        }

        .dark .otp-box {
            border-color: #334155;
            background-color: rgba(30, 41, 59, 0.9);
            color: #f8fafc;
        }

        .dark .otp-box:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
        }

        .dark .otp-box:not(:placeholder-shown) {
            border-color: #475569;
            background-color: rgba(51, 65, 85, 0.95);
        }

        @media (max-width: 480px) {
            .otp-grid {
                gap: 0.35rem;
            }

            .otp-box {
                height: 3rem;
                border-radius: 0.75rem;
                font-size: 1.1rem;
            }
        }

        .otp-countdown[data-state='warn'] {
            color: #b45309;
        }

        .dark .otp-countdown[data-state='warn'] {
            color: #fbbf24;
        }

        .otp-countdown[data-state='expired'] {
            color: #b42318;
        }

        .dark .otp-countdown[data-state='expired'] {
            color: #f87171;
        }
    </style>

    <x-auth-session-status class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300" :status="session('status')" />

    @if (! $mailAvailable)
        <div class="mt-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm font-medium text-rose-700 dark:text-rose-300">
            {{ __('We could not send your verification code. Please try again shortly.') }}
        </div>
    @endif

    @error('code')
        <div class="mt-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm font-medium text-rose-700 dark:text-rose-300">
            {{ $message }}
        </div>
    @enderror

    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
        {{ __('Enter the 6-digit code we sent to :email.', ['email' => $maskedEmail]) }}
    </p>

    @if ($codeExpiresAt > 0)
        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
            <span class="otp-countdown" data-otp-countdown data-expires-at="{{ $codeExpiresAt }}">
                {{ __('Code expires soon') }}
            </span>
        </p>
    @endif

    <form method="POST" action="{{ route('user.two-factor.verify') }}" class="mt-5" data-auth-form data-otp-form>
        @csrf

        <input type="hidden" name="code" data-otp-value>

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

        <button
            type="submit"
            class="pointer-events-auto touch-manipulation mt-5 inline-flex h-12 w-full items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition duration-200 hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-900"
            data-loading-button
            data-loading-text="{{ __('Verifying...') }}"
        >
            <span data-button-label>{{ __('Verify and continue') }}</span>
        </button>
    </form>

    <div class="mt-5 flex flex-col gap-2 border-t border-slate-200 pt-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('user.two-factor.resend') }}" data-resend-form data-cooldown="{{ (int) $resendCooldownSeconds }}">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-md px-1.5 py-1 text-sm font-semibold text-red-600 transition hover:text-red-500 hover:underline disabled:cursor-not-allowed disabled:text-slate-400 disabled:no-underline disabled:hover:text-slate-400 dark:text-red-400 dark:hover:text-red-300 dark:disabled:text-slate-600"
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
                class="inline-flex items-center justify-center rounded-md px-1.5 py-1 text-xs font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-700 hover:decoration-slate-500 dark:text-slate-500 dark:decoration-slate-700 dark:hover:text-slate-300"
            >
                {{ __('Sign out instead') }}
            </button>
        </form>
    </div>

    <script nonce="{{ $cspNonce }}">
        (function () {
            const form = document.querySelector('[data-otp-form]');
            const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
            const hidden = document.querySelector('[data-otp-value]');

            if (!form || boxes.length === 0 || !hidden) {
                return;
            }

            let submitting = false;

            const syncValue = () => {
                hidden.value = boxes.map((box) => box.value.replace(/\D/g, '').slice(0, 1)).join('');
            };

            const maybeAutoSubmit = () => {
                if (submitting) return;
                if (hidden.value.length !== boxes.length) return;
                submitting = true;
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            };

            const fillFromText = (text) => {
                const digits = String(text || '').replace(/\D/g, '').slice(0, boxes.length).split('');
                boxes.forEach((box, index) => {
                    box.value = digits[index] || '';
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

                    if (value && boxes[index + 1]) {
                        boxes[index + 1].focus();
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
                    submitting = false;
                    const firstEmpty = boxes.find((box) => !box.value) || boxes[0];
                    firstEmpty.focus();
                    return;
                }

                submitting = true;
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

        (function () {
            const node = document.querySelector('[data-otp-countdown]');
            if (!node) {
                return;
            }

            const expiresAt = Number.parseInt(node.dataset.expiresAt || '0', 10);
            if (!expiresAt) {
                return;
            }

            const baseLabel = @json(__('Code expires in :time'));
            const expiredLabel = @json(__('Code expired. Request a new one.'));

            const render = () => {
                const remaining = expiresAt - Math.floor(Date.now() / 1000);

                if (remaining <= 0) {
                    node.textContent = expiredLabel;
                    node.dataset.state = 'expired';
                    return;
                }

                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                const formatted = minutes + ':' + String(seconds).padStart(2, '0');
                node.textContent = baseLabel.replace(':time', formatted);
                node.dataset.state = remaining <= 30 ? 'warn' : 'ok';
                window.setTimeout(render, 1000);
            };

            render();
        })();
    </script>
</x-auth-split-layout>
