<x-auth-split-layout
    :heading="__('Add your phone number')"
    form-position="right"
    enter-direction="right"
    :panel-title="__('Secure your account')"
    :panel-subtitle="__('Add an Iraqi mobile number before continuing. You can then receive verification codes by email, SMS, or WhatsApp when available.')"
    :panel-tag="__('Phone required')"
    panel-theme="login"
    panel-button-action="none"
>
    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
        {{ __('Your number is stored in international format and is never exposed to the browser after submission.') }}
    </p>

    <form method="POST" action="{{ route('user.phone.store') }}" class="mt-5 space-y-5" data-auth-form>
        @csrf

        <div>
            <x-input-label for="phone" :value="__('Phone number')" class="text-sm font-medium text-slate-700 dark:text-slate-300" />
            <div class="mt-2 grid grid-cols-[7.5rem_minmax(0,1fr)] gap-2" dir="ltr">
                <label class="sr-only" for="country_code">{{ __('Country code') }}</label>
                <select
                    id="country_code"
                    name="country_code"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                    <option value="+964" selected>🇮🇶 +964</option>
                </select>
                <input
                    id="phone"
                    name="phone"
                    type="tel"
                    inputmode="tel"
                    autocomplete="tel-national"
                    value="{{ old('phone') }}"
                    required
                    placeholder="0770 000 0000"
                    class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                >
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Accepted: 07700000000, 7700000000, or +9647700000000.') }}</p>
            <x-input-error :messages="$errors->get('country_code')" class="mt-2 text-sm text-red-500" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2 text-sm text-red-500" />
        </div>

        <button
            type="submit"
            class="inline-flex h-12 w-full items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-semibold text-white shadow-lg shadow-red-950/30 transition hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 disabled:cursor-not-allowed disabled:opacity-60"
            data-loading-button
            data-loading-text="{{ __('Saving...') }}"
        >
            <span data-button-label>{{ __('Save and continue') }}</span>
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-xs font-medium text-slate-500 underline underline-offset-4 hover:text-slate-700 dark:hover:text-slate-300">
            {{ __('Sign out instead') }}
        </button>
    </form>
</x-auth-split-layout>
