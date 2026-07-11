<x-auth-split-layout
    :heading="__('Confirm your password')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Security checkpoint')"
    :panel-subtitle="__('You are about to perform a sensitive action. Please confirm your password to continue.')"
    :panel-tag="__('Identity check')"
    panel-theme="login"
    panel-button-action="none"
>
    <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-5 space-y-4" data-auth-form data-loading-button-text="{{ __('Confirming...') }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-slate-300" />
            <x-password-input
                id="password"
                container-class="mt-2"
                class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition duration-200 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-100 dark:placeholder:text-slate-500"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                placeholder="{{ __('Enter your password') }}"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-400" />
        </div>

        <button
            type="submit"
            class="pointer-events-auto touch-manipulation mt-2 inline-flex h-12 w-full items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition duration-200 hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-900"
            data-loading-button
            data-loading-text="{{ __('Confirming...') }}"
        >
            <span data-button-label>{{ __('Confirm') }}</span>
        </button>
    </form>
</x-auth-split-layout>
