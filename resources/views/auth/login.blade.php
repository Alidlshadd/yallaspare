<x-auth-split-layout
    :heading="__('Sign In')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Welcome Back')"
    :panel-subtitle="__('Access the YallaSpare Management System using your email, phone, and password.')"
    :panel-tag="__('Authorized Users')"
    panel-theme="login"
    :panel-button-text="__('Create Account')"
    panel-button-action="navigate"
    :panel-button-href="route('register')"
    panel-exit-direction="left"
>
    <x-auth-session-status class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4" data-auth-form>
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email or phone')" class="text-sm font-medium text-slate-300" />
            <x-text-input
                id="email"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 transition duration-200 focus:border-red-500 focus:ring-red-500"
                type="text"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="{{ __('you@example.com or +964...') }}"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-400" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-slate-300" />
            <div class="relative mt-2">
                <x-text-input
                    id="password"
                    class="block w-full rounded-lg border border-slate-700 bg-slate-800/90 px-3 py-2.5 pr-12 text-sm text-slate-100 placeholder:text-slate-500 transition duration-200 focus:border-red-500 focus:ring-red-500"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Enter your password') }}"
                    data-password-input
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-2 inline-flex w-9 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-700/70 hover:text-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                    data-password-toggle
                    data-show-label="{{ __('Show password') }}"
                    data-hide-label="{{ __('Hide password') }}"
                    aria-label="{{ __('Show password') }}"
                    title="{{ __('Show password') }}"
                >
                    <i class="fas fa-eye" aria-hidden="true" data-password-toggle-icon></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-400" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-slate-600 bg-slate-800 text-red-600 focus:ring-2 focus:ring-red-500 focus:ring-offset-0"
                >
                <span>{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="rounded-sm text-sm text-slate-400 underline decoration-slate-600 underline-offset-4 transition hover:text-red-300 hover:decoration-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="pointer-events-auto touch-manipulation inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition duration-200 hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
        >
            {{ __('Sign In') }}
        </button>
    </form>

</x-auth-split-layout>
