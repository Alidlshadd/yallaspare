<x-auth-portal
    mode="register"
    :kicker="__('New account')"
    :heading="__('Create your account')"
    :form-subtitle="__('A few details and your workspace is ready. We verify the account before it goes live.')"
    :panel-eyebrow="__('Join the network')"
    :panel-title="__('Join YallaSpare. Grow your business.')"
    :panel-title-accent="__('Stay in control.')"
    :panel-subtitle="__('Create an account to track stock, follow every order end to end, and work with the dealer network from a single place.')"
    :switch-text="__('Already have an account?')"
    :switch-label="__('Sign in')"
    :switch-href="route('login')"
    :loading-message="__('Setting up your account')"
>
    <form method="POST" action="{{ route('register') }}" class="space-y-4" data-auth-form data-loading-button-text="{{ __('Processing...') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Full name')" class="ys-auth-label" />
            <x-text-input
                id="name"
                class="mt-2"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('Full name') }}"
            />
            <x-input-error :messages="$errors->get('name')" class="ys-auth-error" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="ys-auth-label" />
            <x-text-input
                id="email"
                class="mt-2"
                type="email"
                name="email"
                :value="old('email')"
                required
                autocomplete="username"
                placeholder="{{ __('you@example.com') }}"
            />
            <x-input-error :messages="$errors->get('email')" class="ys-auth-error" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" class="ys-auth-label" />
            {{-- The dial code stays LTR even in Arabic and Kurdish: a phone
                 number reversed around its "+" is unreadable in any locale. --}}
            <div class="mt-2 grid grid-cols-[7.5rem_minmax(0,1fr)] gap-2" dir="ltr">
                <label class="sr-only" for="country_code">{{ __('Country code') }}</label>
                <select id="country_code" name="country_code" class="font-semibold" required>
                    <option value="+964" @selected(old('country_code', '+964') === '+964')>🇮🇶 +964</option>
                </select>
                <input
                    id="phone"
                    type="tel"
                    inputmode="tel"
                    name="phone"
                    value="{{ old('phone') }}"
                    required
                    autocomplete="tel-national"
                    placeholder="0770 000 0000"
                >
            </div>
            <p class="ys-auth-hint">{{ __('Iraq mobile number. Accepted: 07700000000, 7700000000, or +9647700000000.') }}</p>
            <x-input-error :messages="$errors->get('country_code')" class="ys-auth-error" />
            <x-input-error :messages="$errors->get('phone')" class="ys-auth-error" />
        </div>

        <x-password-create-field
            :placeholder="__('Create password')"
            :confirm-placeholder="__('Confirm password')"
        />

        <button type="submit" class="ys-auth-cta">
            <span>{{ __('Create account') }}</span>
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" /></svg>
        </button>

        <p class="ys-auth-terms">
            {!! __('By creating an account you agree to the :terms and the :privacy.', [
                'terms' => '<a href="' . e(route('legal.terms')) . '">' . e(__('Terms of Service')) . '</a>',
                'privacy' => '<a href="' . e(route('legal.privacy')) . '">' . e(__('Privacy Policy')) . '</a>',
            ]) !!}
        </p>
    </form>

    @include('auth.partials.social-login')
</x-auth-portal>
