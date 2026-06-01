<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ __('Two-factor verification') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
            {{ __('Enter the 6-digit code sent to :email to continue.', ['email' => $maskedEmail]) }}
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if (! $mailAvailable)
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ __('We could not send your verification code. Please try again shortly.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('user.two-factor.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Verification Code') }}</label>
            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                autofocus
                class="mt-2 block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-center text-lg font-semibold tracking-[0.4em] text-slate-900 outline-none transition duration-200 focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white"
            >
            @error('code')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-primary px-4 py-3 text-sm font-semibold text-white transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
            {{ __('Verify') }}
        </button>
    </form>

    <div class="mt-4 flex items-center justify-between gap-3 text-sm">
        <form method="POST" action="{{ route('user.two-factor.resend') }}">
            @csrf
            <button type="submit" class="font-semibold text-primary hover:underline" @disabled($resendCooldownSeconds > 0)>
                {{ $resendCooldownSeconds > 0 ? __('Resend in :seconds s', ['seconds' => $resendCooldownSeconds]) : __('Resend code') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                {{ __('Sign out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
