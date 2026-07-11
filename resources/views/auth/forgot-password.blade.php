<x-auth-split-layout
    :heading="__('Forgot Password')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Reset Your Password')"
    :panel-subtitle="__('Enter your email to receive a secure password reset link.')"
    :panel-tag="__('Account Recovery')"
    panel-theme="login"
    :panel-button-text="__('Back to Sign In')"
    panel-button-action="navigate"
    :panel-button-href="route('login')"
    panel-exit-direction="left"
>
    <x-auth-session-status class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-4" data-auth-form data-loading-button-text="Sending...">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm font-medium text-slate-300" />
            <x-text-input
                id="email"
                class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition duration-200 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-100 dark:placeholder:text-slate-500"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="{{ __('you@example.com') }}"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-400" />
        </div>

        <button
            type="submit"
            class="pointer-events-auto touch-manipulation inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition duration-200 hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
        >
            {{ __('Email Password Reset Link') }}
        </button>
    </form>
</x-auth-split-layout>
