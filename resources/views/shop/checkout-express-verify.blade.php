@extends('layouts.user')

@section('title', __('Confirm your order'))

@section('checkout_back', route('checkout.express'))
@section('checkout_back_label', __('Back to Delivery Details'))

@section('content')
    <style>
        .otp-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .5rem; }
        .otp-box { height: 3.5rem; border-radius: .875rem; border: 1px solid var(--border); background: var(--surface); color: var(--text); text-align: center; font-size: 1.35rem; font-weight: 700; line-height: 1; outline: none; transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease, background-color 160ms ease; }
        .otp-box:focus { border-color: var(--brand-orange); box-shadow: 0 0 0 4px var(--focus); transform: translateY(-1px); }
        .otp-box:not(:placeholder-shown) { border-color: var(--text-muted); background: var(--surface-sunk); }
        .dark .otp-box { background: rgb(var(--surface-rgb) / 0.9); }
        .dark .otp-box:not(:placeholder-shown) { background: rgb(var(--surface-sunk-rgb) / 0.95); }
        @media (max-width: 480px) {
            .otp-grid { gap: .35rem; }
            .otp-box { height: 3rem; border-radius: .75rem; font-size: 1.1rem; }
        }
    </style>

    <div class="space-y-5 pb-16">
        @if (session('error'))
            <x-ui.alert variant="danger" :title="__('Please review')">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        @include('shop.partials.checkout-steps', [
            'current' => 3,
            'steps' => [
                1 => ['label' => __('Cart'), 'icon' => 'cart'],
                2 => ['label' => __('Delivery'), 'icon' => 'truck'],
                3 => ['label' => __('Confirm'), 'icon' => 'check'],
            ],
        ])

        <div class="mx-auto w-full max-w-lg">
            <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-8">
                <h1 class="text-center text-xl font-semibold tracking-[-0.02em] text-slate-950">{{ __('Confirm your phone number') }}</h1>

                <p class="mt-3 text-center text-sm leading-6 text-slate-600 dark:text-slate-400">
                    {{ __('Enter the 6-digit code we sent by SMS to :phone.', ['phone' => $maskedPhone]) }}
                </p>
                <p class="mt-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">
                    {{ __('The code expires in :minutes minutes.', ['minutes' => $expiresInMinutes]) }}
                    {{ __('Your order is placed as soon as the code is confirmed.') }}
                </p>

                @if (session('status'))
                    <div class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                @error('code')
                    <div class="mt-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('checkout.express.verify.store') }}" class="mt-5" data-otp-form>
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
                        class="font-display mt-5 inline-flex h-12 w-full items-center justify-center rounded-xl bg-accent px-4 text-sm font-bold text-navy transition duration-200 hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent disabled:cursor-not-allowed disabled:opacity-60"
                        data-loading-button
                        data-loading-text="{{ __('Placing your order...') }}"
                    >
                        <span data-button-label>{{ __('Confirm and place order') }}</span>
                    </button>
                </form>

                <div class="mt-4 flex items-center justify-center gap-2 text-sm">
                    <span class="text-slate-500 dark:text-slate-400">{{ __('Did not receive the code?') }}</span>
                    <form method="POST" action="{{ route('checkout.express.resend') }}" data-resend-form data-cooldown="{{ (int) $resendCooldownSeconds }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center font-semibold text-primary transition hover:underline disabled:cursor-not-allowed disabled:text-slate-400 disabled:no-underline dark:text-white dark:disabled:text-slate-600"
                            data-resend-button
                            @disabled($resendCooldownSeconds > 0)
                        >
                            <span data-resend-label>{{ $resendCooldownSeconds > 0 ? __('Resend in :seconds s', ['seconds' => $resendCooldownSeconds]) : __('user.send_verification_code') }}</span>
                        </button>
                    </form>
                </div>

                <div class="mt-4 text-center">
                    <a href="{{ route('checkout.express') }}" class="text-sm font-medium text-slate-500 underline underline-offset-4 hover:text-slate-700 dark:hover:text-slate-300">
                        {{ __('Wrong number? Change phone number') }}
                    </a>
                </div>
            </section>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        (() => {
            const form = document.querySelector('[data-otp-form]');
            const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
            const hidden = document.querySelector('[data-otp-value]');
            if (!form || boxes.length === 0 || !hidden) return;

            let submitting = false;
            const syncValue = () => { hidden.value = boxes.map((box) => box.value.replace(/\D/g, '').slice(0, 1)).join(''); };
            const maybeAutoSubmit = () => {
                if (submitting || hidden.value.length !== boxes.length) return;
                submitting = true;
                typeof form.requestSubmit === 'function' ? form.requestSubmit() : form.submit();
            };
            const fillFromText = (text) => {
                const digits = String(text || '').replace(/\D/g, '').slice(0, boxes.length).split('');
                boxes.forEach((box, index) => { box.value = digits[index] || ''; });
                syncValue();
                boxes[Math.min(digits.length, boxes.length - 1)]?.focus();
                maybeAutoSubmit();
            };
            boxes.forEach((box, index) => {
                box.addEventListener('input', (event) => {
                    const value = event.target.value.replace(/\D/g, '');
                    if (value.length > 1) return fillFromText(value);
                    event.target.value = value;
                    syncValue();
                    if (value && boxes[index + 1]) boxes[index + 1].focus();
                    maybeAutoSubmit();
                });
                box.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && !box.value && boxes[index - 1]) boxes[index - 1].focus();
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
                    (boxes.find((box) => !box.value) || boxes[0]).focus();
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

        (() => {
            const form = document.querySelector('[data-resend-form]');
            const button = form?.querySelector('[data-resend-button]');
            const label = form?.querySelector('[data-resend-label]');
            if (!form || !button || !label) return;
            let remaining = Number.parseInt(form.dataset.cooldown || '0', 10);
            if (Number.isNaN(remaining)) remaining = 0;
            const baseText = @json(__('user.send_verification_code'));
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
            form.addEventListener('submit', () => {
                button.disabled = true;
                label.textContent = @json(__('Sending...'));
            });
        })();
    </script>
@endsection
