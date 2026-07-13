<x-auth-split-layout
    :heading="__('Enter your verification code')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Verify it is really you')"
    :panel-subtitle="__('Enter the one-time code to finish signing in securely.')"
    :panel-tag="__('Identity check')"
    panel-theme="login"
    panel-button-action="none"
>
    <style>
        .otp-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .5rem; }
        .otp-box { height: 3.5rem; border-radius: .875rem; border: 1px solid #cbd5e1; background: #fff; color: #0f172a; text-align: center; font-size: 1.35rem; font-weight: 700; line-height: 1; outline: none; transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease, background-color 160ms ease; }
        .otp-box:focus { border-color: #dc2626; box-shadow: 0 0 0 4px rgba(239, 68, 68, .18); transform: translateY(-1px); }
        .otp-box:not(:placeholder-shown) { border-color: #94a3b8; background: #f8fafc; }
        .dark .otp-box { border-color: #334155; background: rgba(30, 41, 59, .9); color: #f8fafc; }
        .dark .otp-box:focus { border-color: #ef4444; box-shadow: 0 0 0 4px rgba(239, 68, 68, .2); }
        .dark .otp-box:not(:placeholder-shown) { border-color: #475569; background: rgba(51, 65, 85, .95); }
        .otp-countdown[data-state='warn'] { color: #b45309; }
        .dark .otp-countdown[data-state='warn'] { color: #fbbf24; }
        .otp-countdown[data-state='expired'] { color: #b42318; }
        .dark .otp-countdown[data-state='expired'] { color: #f87171; }
        .verification-method-dialog::backdrop { background: rgba(15, 23, 42, .66); backdrop-filter: blur(3px); }
        @media (max-width: 480px) {
            .otp-grid { gap: .35rem; }
            .otp-box { height: 3rem; border-radius: .75rem; font-size: 1.1rem; }
            .verification-method-dialog { width: 100%; max-width: none; margin: auto 0 0; border-radius: 1.5rem 1.5rem 0 0; }
        }
    </style>

    <x-auth-session-status class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300" :status="session('status')" />

    @if (! $deliveryAvailable)
        <div class="mt-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm font-medium text-rose-700 dark:text-rose-300">
            {{ __('We could not send your verification code. Please try again shortly.') }}
        </div>
    @endif

    @error('code')
        <div class="mt-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</div>
    @enderror

    @error('channel')
        <div class="mt-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</div>
    @enderror

    <div class="mt-4 text-center">
        <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">
            {{ __('Enter the 6-digit code sent via :channel to :destination.', ['channel' => $currentChannelLabel, 'destination' => $maskedDestination]) }}
        </p>
        <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
            {{ __('Sent via :channel', ['channel' => $currentChannelLabel]) }} · <span dir="ltr">{{ $maskedDestination }}</span>
        </span>

        @if ($codeExpiresAt > 0)
            <p class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <span class="otp-countdown" data-otp-countdown data-expires-at="{{ $codeExpiresAt }}">{{ __('Code expires soon') }}</span>
            </p>
        @endif
    </div>

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
            class="pointer-events-auto touch-manipulation mt-5 inline-flex h-12 w-full items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 disabled:cursor-not-allowed disabled:opacity-60"
            data-loading-button
            data-loading-text="{{ __('Verifying...') }}"
        >
            <span data-button-label>{{ __('Verify and continue') }}</span>
        </button>
    </form>

    <div class="mt-4 flex items-center justify-center gap-2 text-sm">
        <span class="text-slate-500 dark:text-slate-400">{{ __('Did not receive the code?') }}</span>
        <form method="POST" action="{{ route('user.two-factor.resend') }}" data-resend-form data-cooldown="{{ (int) $resendCooldownSeconds }}">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center justify-center font-semibold text-red-600 transition hover:text-red-500 hover:underline disabled:cursor-not-allowed disabled:text-slate-400 disabled:no-underline dark:text-red-400 dark:hover:text-red-300 dark:disabled:text-slate-600"
                data-resend-button
                @disabled($resendCooldownSeconds > 0)
            >
                <span data-resend-label>{{ $resendCooldownSeconds > 0 ? __('Resend in :seconds s', ['seconds' => $resendCooldownSeconds]) : __('Resend code') }}</span>
            </button>
        </form>
    </div>

    <div class="my-5 flex items-center gap-3" aria-hidden="true">
        <span class="h-px flex-1 bg-slate-200 dark:bg-white/10"></span>
        <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ __('or') }}</span>
        <span class="h-px flex-1 bg-slate-200 dark:bg-white/10"></span>
    </div>

    <button
        type="button"
        class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
        data-method-dialog-open
    >
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 0-9.201-4.42.75.75 0 0 1-1.154-.96 7 7 0 0 1 11.753 5.14l.02.145.855-.854a.75.75 0 1 1 1.06 1.06l-2.122 2.122a.75.75 0 0 1-1.06 0l-2.121-2.121a.75.75 0 0 1 1.06-1.061l.91.91v.039Zm-10.624-2.8a5.5 5.5 0 0 0 9.201 4.42.75.75 0 0 1 1.154.96 7 7 0 0 1-11.753-5.14l-.02-.145-.855.854a.75.75 0 1 1-1.06-1.06l2.122-2.122a.75.75 0 0 1 1.06 0l2.121 2.121a.75.75 0 0 1-1.06 1.061l-.91-.91v-.039Z" clip-rule="evenodd" />
        </svg>
        {{ __('Use another verification method') }}
    </button>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-xs font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-700 dark:decoration-slate-700 dark:hover:text-slate-300">
            {{ __('Sign out instead') }}
        </button>
    </form>

    <dialog class="verification-method-dialog m-auto w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl dark:border-slate-700 dark:bg-slate-900 dark:text-white" data-method-dialog>
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold">{{ __('Choose verification method') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('We will invalidate the previous code after the new code is sent successfully.') }}</p>
                </div>
                <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-white" data-method-dialog-close aria-label="{{ __('Close') }}">×</button>
            </div>

            <div class="mt-5 space-y-2">
                @foreach ($channelOptions as $channelKey => $option)
                    @php
                        $isCurrent = $currentChannel === $channelKey;
                        $iconPath = match ($channelKey) {
                            'email' => 'M2.94 6.34a2 2 0 0 1 1.72-.98h10.68a2 2 0 0 1 1.72.98L10 10.75 2.94 6.34ZM2 8.14V14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.14l-7.47 4.67a1 1 0 0 1-1.06 0L2 8.14Z',
                            'sms' => 'M2 5.5A2.5 2.5 0 0 1 4.5 3h11A2.5 2.5 0 0 1 18 5.5v6a2.5 2.5 0 0 1-2.5 2.5H9l-4.6 3.45A.875.875 0 0 1 3 16.75V14.5A2.5 2.5 0 0 1 2 12.5v-7Z',
                            default => 'M10 2a7.5 7.5 0 0 0-6.46 11.31L2.5 17.5l4.29-1.02A7.5 7.5 0 1 0 10 2Zm3.87 10.3c-.17.48-.84.9-1.38 1.02-.37.08-.86.15-2.5-.53-2.1-.87-3.45-3.02-3.55-3.16-.1-.14-.85-1.13-.85-2.15s.53-1.52.72-1.73c.19-.2.42-.26.56-.26h.4c.13 0 .3-.05.47.36.17.41.58 1.42.63 1.52.05.1.08.22.02.36-.06.14-.1.22-.2.34-.1.12-.21.26-.3.35-.1.1-.2.2-.09.4.11.2.49.81 1.05 1.31.72.65 1.33.85 1.53.95.2.1.32.08.44-.05.12-.14.51-.6.65-.8.14-.2.27-.17.46-.1.19.07 1.2.57 1.4.67.2.1.34.15.39.24.05.08.05.49-.12.97Z',
                        };
                    @endphp

                    @if ($option['available'])
                        <form method="POST" action="{{ route('user.two-factor.channel') }}" data-channel-form>
                            @csrf
                            <input type="hidden" name="channel" value="{{ $channelKey }}">
                            <button
                                type="submit"
                                class="flex w-full items-center gap-3 rounded-xl border px-3.5 py-3 text-start transition {{ $isCurrent ? 'border-red-300 bg-red-50 dark:border-red-500/30 dark:bg-red-500/10' : 'border-slate-200 hover:border-red-200 hover:bg-slate-50 dark:border-white/10 dark:hover:border-red-500/30 dark:hover:bg-white/5' }}"
                                @disabled($isCurrent)
                                data-channel-submit
                            >
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $isCurrent ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300' }}">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="{{ $iconPath }}" /></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-sm font-semibold">
                                        {{ $option['label'] }}
                                        @if ($channelKey === 'email')
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-500 dark:bg-white/10 dark:text-slate-400">{{ __('Default') }}</span>
                                        @endif
                                    </span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">{{ $option['description'] }} · {{ $option['destination'] }}</span>
                                </span>
                                <span class="text-xs font-semibold {{ $isCurrent ? 'text-red-600 dark:text-red-300' : 'text-slate-400' }}" data-channel-action>
                                    {{ $isCurrent ? __('Active') : __('Use') }}
                                </span>
                            </button>
                        </form>
                    @else
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 opacity-75 dark:border-white/10 dark:bg-white/5">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-500 dark:bg-white/10 dark:text-slate-400">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="{{ $iconPath }}" /></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">{{ $option['label'] }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $option['unavailable_message'] }}</span>
                            </span>
                            @if ($option['action_url'])
                                <a href="{{ $option['action_url'] }}" class="shrink-0 text-xs font-semibold text-red-600 hover:underline dark:text-red-400">{{ __('Add phone number') }}</a>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </dialog>

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
            form.addEventListener('submit', () => {
                button.disabled = true;
                label.textContent = @json(__('Sending...'));
            });
        })();

        (() => {
            const node = document.querySelector('[data-otp-countdown]');
            if (!node) return;
            const expiresAt = Number.parseInt(node.dataset.expiresAt || '0', 10);
            if (!expiresAt) return;
            const baseLabel = @json(__('Code expires in :time'));
            const expiredLabel = @json(__('Code expired. Request a new one.'));
            const render = () => {
                const remaining = expiresAt - Math.floor(Date.now() / 1000);
                if (remaining <= 0) {
                    node.textContent = expiredLabel;
                    node.dataset.state = 'expired';
                    return;
                }
                const formatted = Math.floor(remaining / 60) + ':' + String(remaining % 60).padStart(2, '0');
                node.textContent = baseLabel.replace(':time', formatted);
                node.dataset.state = remaining <= 30 ? 'warn' : 'ok';
                window.setTimeout(render, 1000);
            };
            render();
        })();

        (() => {
            const dialog = document.querySelector('[data-method-dialog]');
            const openButton = document.querySelector('[data-method-dialog-open]');
            const closeButton = dialog?.querySelector('[data-method-dialog-close]');
            if (!dialog || !openButton) return;
            openButton.addEventListener('click', () => dialog.showModal());
            closeButton?.addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
            dialog.querySelectorAll('[data-channel-form]').forEach((form) => {
                form.addEventListener('submit', () => {
                    dialog.querySelectorAll('[data-channel-submit]').forEach((button) => { button.disabled = true; });
                    const action = form.querySelector('[data-channel-action]');
                    if (action) action.textContent = @json(__('Sending...'));
                    window.setTimeout(() => dialog.close(), 80);
                });
            });
            @if ($errors->has('channel'))
                dialog.showModal();
            @endif
        })();
    </script>
</x-auth-split-layout>
