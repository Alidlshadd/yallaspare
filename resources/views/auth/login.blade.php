<x-auth-portal
    mode="login"
    :kicker="__('Authorized access')"
    :heading="__('Sign in')"
    :form-subtitle="__('Use your email or phone number to reach your workspace.')"
    :panel-eyebrow="__('Secure access')"
    :panel-title="__('Inventory. Orders. Dealers.')"
    :panel-title-accent="__('All in control.')"
    :panel-subtitle="__('YallaSpare keeps stock, orders and dealer operations on one system, so every part, price and delivery is accounted for.')"
    :switch-text="__('New to YallaSpare?')"
    :switch-label="__('Create an account')"
    :switch-href="route('register')"
    :loading-message="__('Preparing your dashboard')"
>
    <x-auth-session-status class="ys-auth-alert ys-auth-alert-success mb-5" :status="session('status')" />

    @if (session('auth_error'))
        <div class="ys-auth-alert ys-auth-alert-error mb-5" role="alert">
            {{ session('auth_error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4" data-auth-form data-loading-button-text="{{ __('Signing in...') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email or phone')" class="ys-auth-label" />
            <input
                id="email"
                class="auth-login-input mt-2"
                type="text"
                name="email"
                value="{{ old('email') }}"
                required
                @if (! session('auth_error')) autofocus @endif
                autocomplete="username"
                placeholder="{{ __('you@example.com or +964...') }}"
            >
            <x-input-error :messages="$errors->get('email')" class="ys-auth-error" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="ys-auth-label" />
            <x-password-input
                id="password"
                container-class="mt-2"
                name="password"
                required
                autocomplete="current-password"
                placeholder="{{ __('Enter your password') }}"
            />
            <x-input-error :messages="$errors->get('password')" class="ys-auth-error" />
        </div>

        <div class="ys-auth-row">
            <label for="remember_me" class="ys-auth-remember">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    value="1"
                    @checked(old('remember'))
                    class="ys-auth-checkbox"
                >
                <span>{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="ys-auth-link">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="ys-auth-cta">
            <span>{{ __('Sign in') }}</span>
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" /></svg>
        </button>
    </form>

    @include('auth.partials.social-login')
</x-auth-portal>
