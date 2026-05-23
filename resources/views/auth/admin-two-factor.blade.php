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

    <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="mt-5 space-y-4">
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
            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition duration-200 hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
        >
            {{ __('Verify') }}
        </button>
    </form>

    <form method="POST" action="{{ route('admin.two-factor.resend') }}" class="mt-3">
        @csrf
        <button type="submit" class="text-sm text-slate-400 underline decoration-slate-600 underline-offset-4 transition hover:text-red-300 hover:decoration-red-400">
            {{ __('Send a new code') }}
        </button>
    </form>
</x-auth-split-layout>
