<x-auth-portal
    :heading="__('Sign In')"
    :form-subtitle="__('Access the YallaSpare Management System using your email, phone, and password.')"
    :panel-title="__('Inventory. Orders. Dealers. Full Control.')"
    :panel-subtitle="__('Access the YallaSpare Management System using your email, phone, and password.')"
    :panel-tag="__('Authorized Users')"
    :panel-button-text="__('Create Account')"
    :panel-button-href="route('register')"
    mode="login"
>
    <x-auth-session-status class="ys-auth-alert ys-auth-alert-success mt-5" :status="session('status')" />

    @if (session('auth_error'))
        <div class="ys-auth-alert ys-auth-alert-error mt-5" role="alert">
            {{ session('auth_error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5" data-auth-form data-loading-button-text="{{ __('Signing in...') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email or phone')" class="ys-auth-label" />
            <input
                id="email"
                class="auth-login-input mt-2 block w-full"
                type="text"
                name="email"
                value="{{ old('email') }}"
                required
                @if (! session('auth_error')) autofocus @endif
                autocomplete="username"
                placeholder="{{ __('you@example.com or +964...') }}"
            >
            <x-input-error :messages="$errors->get('email')" class="ys-auth-error mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="ys-auth-label" />
            <x-password-input
                id="password"
                container-class="mt-2"
                class="block w-full"
                name="password"
                required
                autocomplete="current-password"
                placeholder="{{ __('Enter your password') }}"
            />
            <x-input-error :messages="$errors->get('password')" class="ys-auth-error mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
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
                <a href="{{ route('password.request') }}" class="ys-auth-text-link">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="ys-auth-primary-action"
        >
            <span>{{ __('Sign In') }}</span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" /></svg>
        </button>
    </form>

    @include('auth.partials.social-login')

</x-auth-portal>
