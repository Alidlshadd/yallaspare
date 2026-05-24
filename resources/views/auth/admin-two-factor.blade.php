<x-auth-split-layout
    :heading="__('Admin Verification')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Verification Required')"
    :panel-subtitle="__('Enter the 6-digit code sent to your admin email address.')"
    :panel-tag="__('Admin Security')"
    panel-theme="login"
>
    <x-auth-session-status class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-300" :status="session('status')" />

    @if (($mailAvailable ?? true) === false)
        <div class="mt-4 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-200">
            {{ __('We could not send a verification email. Please try again or contact support if the problem continues.') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="mt-5 space-y-4" data-loading-form>
        @csrf

        <div>
            <x-input-label for="code" :value="__('Verification code')" class="text-sm font-medium text-slate-300" />
            <x-text-input
                id="code"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 transition duration-200 focus:border-red-500 focus:ring-red-500"
                type="text"
                name="code"
                required
                autofocus
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                placeholder="000000"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2 text-sm text-red-400" />
        </div>

        <button
            type="submit"
            data-loading-button
            data-loading-text="{{ __('Verifying...') }}"
            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition duration-200 hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
        >
            {{ __('Verify') }}
        </button>
    </form>

    <form method="POST" action="{{ route('admin.two-factor.resend') }}" class="mt-3" data-loading-form data-resend-form data-cooldown="{{ (int) ($resendCooldownSeconds ?? 0) }}">
        @csrf
        <button type="submit" data-loading-button data-loading-text="{{ __('Sending...') }}" class="text-sm text-slate-400 underline decoration-slate-600 underline-offset-4 transition hover:text-red-300 hover:decoration-red-400 disabled:cursor-not-allowed disabled:text-slate-600 disabled:no-underline" data-resend-button>
            <span data-resend-label>{{ __('Send a new code') }}</span>
        </button>
    </form>

    <script>
        document.querySelectorAll('[data-loading-form]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('[data-loading-button]');
                if (!button) return;

                button.disabled = true;
                button.dataset.originalText = button.textContent.trim();
                button.textContent = button.dataset.loadingText || button.dataset.originalText;
            });
        });

        document.querySelectorAll('[data-resend-form]').forEach((form) => {
            const button = form.querySelector('[data-resend-button]');
            const label = form.querySelector('[data-resend-label]');
            let remaining = Number.parseInt(form.dataset.cooldown || '0', 10);

            if (!button || !label || Number.isNaN(remaining) || remaining <= 0) {
                return;
            }

            const original = label.textContent.trim();
            const tick = () => {
                if (remaining <= 0) {
                    button.disabled = false;
                    label.textContent = original;
                    return;
                }

                button.disabled = true;
                label.textContent = `${original} (${remaining}s)`;
                remaining -= 1;
                window.setTimeout(tick, 1000);
            };

            tick();
        });
    </script>
</x-auth-split-layout>
