<x-auth-portal
    mode="recover"
    :kicker="__('Password reset')"
    :heading="__('Reset your password')"
    :form-subtitle="__('Enter the email or phone number on your account and we will send a secure reset link.')"
    :panel-eyebrow="__('Account recovery')"
    :panel-title="__('Locked out of your workspace?')"
    :panel-title-accent="__('We will get you back in.')"
    :panel-subtitle="__('Reset links are single use and expire on their own, so recovering access never leaves the account open behind you.')"
    :switch-text="__('Remembered your password?')"
    :switch-label="__('Back to sign in')"
    :switch-href="route('login')"
    :loading-message="__('Sending your reset link')"
>
    <x-auth-session-status class="ys-auth-alert ys-auth-alert-success mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4" data-auth-form data-loading-button-text="{{ __('Sending...') }}">
        @csrf

        <div>
            <x-input-label for="login" :value="__('Email or phone')" class="ys-auth-label" />
            <x-text-input
                id="login"
                class="mt-2"
                type="text"
                name="login"
                :value="old('login')"
                required
                autofocus
                autocomplete="username"
                placeholder="{{ __('you@example.com or +964...') }}"
            />
            <x-input-error :messages="$errors->get('login')" class="ys-auth-error" />
        </div>

        <button type="submit" class="ys-auth-cta">
            <span>{{ __('Send reset link') }}</span>
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" /></svg>
        </button>

        <p class="ys-auth-hint">
            {{ __('The link works once and expires shortly after it is sent. Check your spam folder if it does not arrive.') }}
        </p>
    </form>
</x-auth-portal>
