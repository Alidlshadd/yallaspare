<x-auth-portal
    :heading="__('Request Account')"
    :form-subtitle="__('Submit your details to request authorized access to the system.')"
    :panel-title="__('Request Access')"
    :panel-subtitle="__('Submit your details to request authorized access to the system.')"
    :panel-tag="__('Access Requests')"
    :panel-button-text="__('Sign In')"
    :panel-button-href="route('login')"
    mode="register"
>
    <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-5" data-auth-form data-loading-button-text="{{ __('Processing...') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" class="ys-auth-label" />
            <x-text-input
                id="name"
                class="mt-2 block w-full"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('Full name') }}"
            />
            <x-input-error :messages="$errors->get('name')" class="ys-auth-error mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="ys-auth-label" />
            <x-text-input
                id="email"
                class="mt-2 block w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autocomplete="username"
                placeholder="{{ __('you@example.com') }}"
            />
            <x-input-error :messages="$errors->get('email')" class="ys-auth-error mt-2" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" class="ys-auth-label" />
            <div class="mt-2 grid grid-cols-[7.5rem_minmax(0,1fr)] gap-2" dir="ltr">
                <label class="sr-only" for="country_code">{{ __('Country code') }}</label>
                <select
                    id="country_code"
                    name="country_code"
                    class="font-semibold"
                    required
                >
                    <option value="+964" @selected(old('country_code', '+964') === '+964')>🇮🇶 +964</option>
                </select>
                <input
                    id="phone"
                    class="block w-full"
                    type="tel"
                    inputmode="tel"
                    name="phone"
                    value="{{ old('phone') }}"
                    required
                    autocomplete="tel-national"
                    placeholder="0770 000 0000"
                >
            </div>
            <p class="ys-auth-hint mt-2">{{ __('Iraq mobile number. Accepted: 07700000000, 7700000000, or +9647700000000.') }}</p>
            <x-input-error :messages="$errors->get('country_code')" class="ys-auth-error mt-2" />
            <x-input-error :messages="$errors->get('phone')" class="ys-auth-error mt-2" />
        </div>

        <x-password-create-field
            :placeholder="__('Create password')"
            :confirm-placeholder="__('Confirm password')"
        />

        <button
            type="submit"
            class="ys-auth-primary-action"
        >
            <span>{{ __('Request Account') }}</span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" /></svg>
        </button>
    </form>

    @include('auth.partials.social-login')

</x-auth-portal>
