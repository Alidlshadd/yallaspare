@php
    // Only admin routes sit behind password.confirm, so the way out is the
    // panel the sensitive action was started from. Without this the page has
    // no exit at all: no header, no sidebar, and the panel button was off.
    $confirmExit = auth()->user()?->isAdminPanelUser()
        ? route('admin.dashboard')
        : route('account.index');
@endphp

<x-auth-split-layout
    :heading="__('Confirm your password')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Security checkpoint')"
    :panel-subtitle="__('You are about to perform a sensitive action. Please confirm your password to continue.')"
    :panel-tag="__('Identity check')"
    panel-theme="login"
    panel-button-action="navigate"
    :panel-button-text="__('Cancel and go back')"
    :panel-button-href="$confirmExit"
    panel-exit-direction="left"
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
                class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-muted shadow-sm transition duration-200 focus:border-accent focus:ring-accent dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-100"
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
            class="pointer-events-auto touch-manipulation mt-2 inline-flex h-12 w-full items-center justify-center rounded-lg bg-accent px-4 text-sm font-semibold text-navy shadow-lg shadow-navy/25 transition duration-200 hover:bg-accent-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-900"
            data-loading-button
            data-loading-text="{{ __('Confirming...') }}"
        >
            <span data-button-label>{{ __('Confirm') }}</span>
        </button>

        {{-- Repeated under the form because on a narrow screen the panel and
             its button sit above the fold, out of sight from here. --}}
        <p class="text-center text-sm">
            <a href="{{ $confirmExit }}" class="font-semibold text-slate-500 underline-offset-4 hover:text-accent hover:underline dark:text-slate-400">
                {{ __('Cancel and go back') }}
            </a>
        </p>
    </form>
</x-auth-split-layout>
