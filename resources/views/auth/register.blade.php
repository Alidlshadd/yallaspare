<x-auth-split-layout
    :heading="__('Request Account')"
    form-position="left"
    enter-direction="left"
    :panel-title="__('Request Access')"
    :panel-subtitle="__('Submit your details to request authorized access to the system.')"
    :panel-tag="__('Access Requests')"
    panel-theme="register"
    :panel-button-text="__('Sign In')"
    panel-button-action="navigate"
    :panel-button-href="route('login')"
    panel-exit-direction="right"
>
    <form method="POST" action="{{ route('register') }}" class="mt-5 space-y-4" data-auth-form>
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" class="text-sm font-medium text-slate-300" />
            <x-text-input
                id="name"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 transition duration-200 focus:border-red-500 focus:ring-red-500"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('Full name') }}"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm text-red-400" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm font-medium text-slate-300" />
            <x-text-input
                id="email"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 transition duration-200 focus:border-red-500 focus:ring-red-500"
                type="email"
                name="email"
                :value="old('email')"
                required
                autocomplete="username"
                placeholder="{{ __('you@example.com') }}"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-400" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" class="text-sm font-medium text-slate-300" />
            <x-text-input
                id="phone"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 transition duration-200 focus:border-red-500 focus:ring-red-500"
                type="text"
                name="phone"
                :value="old('phone')"
                autocomplete="tel"
                placeholder="+964..."
            />
            <x-input-error :messages="$errors->get('phone')" class="mt-2 text-sm text-red-400" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-slate-300" />
            <x-text-input
                id="password"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 transition duration-200 focus:border-red-500 focus:ring-red-500"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="{{ __('Create password') }}"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-400" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-sm font-medium text-slate-300" />
            <x-text-input
                id="password_confirmation"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 transition duration-200 focus:border-red-500 focus:ring-red-500"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="{{ __('Confirm password') }}"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm text-red-400" />
        </div>

        <button
            type="submit"
            class="pointer-events-auto touch-manipulation inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-950/40 transition duration-200 hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
        >
            {{ __('Request Account') }}
        </button>
    </form>

</x-auth-split-layout>
