@extends('layouts.user')

@php
    $lastLogin = optional($user->last_login_at)->translatedFormat('M d, Y h:i A');
@endphp

@section('title', __('Security'))
@section('subtitle', __('Password, session awareness, and account protection controls'))
@section('actions')
    <a
        href="{{ route('user.account.edit') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    >
        {{ __('Account') }}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            @include('user.partials.account-nav')
        </aside>

        <div class="space-y-6">
            @if (session('password_success'))
                <x-ui.alert variant="success" :title="__('user.success')">
                    {{ session('password_success') }}
                </x-ui.alert>
            @endif

            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="text-base font-semibold text-app">{{ __('user.change_password') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('user.password_hint') }}</p>
                    </div>
                </x-slot>

                <form method="POST" action="{{ route('user.account.password') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui.input name="current_password" type="password" :label="__('user.current_password')" />
                        <div class="hidden md:block"></div>
                        <x-ui.input name="password" type="password" :label="__('user.new_password')" :hint="__('user.password_rules')" />
                        <x-ui.input name="password_confirmation" type="password" :label="__('user.confirm_password')" />
                    </div>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="secondary">
                            {{ __('user.update_password') }}
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-ui.card>
                    <x-slot name="header">
                        <h2 class="text-base font-semibold text-app">{{ __('Session Status') }}</h2>
                    </x-slot>
                    <div class="space-y-3">
                        @if ($lastLogin)
                            <div class="rounded-app border border-app bg-surface-1 px-4 py-3">
                                <p class="text-xs font-medium uppercase tracking-[0.14em] text-muted">{{ __('Last Login') }}</p>
                                <p class="mt-1 text-sm text-app">{{ $lastLogin }}</p>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" class="w-full justify-center">
                                {{ __('Sign Out Current Session') }}
                            </x-ui.button>
                        </form>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <x-slot name="header">
                        <h2 class="text-base font-semibold text-app">{{ __('Advanced Protection') }}</h2>
                    </x-slot>
                    <div class="space-y-3">
                        <div class="rounded-app border border-app bg-surface-1 px-4 py-3">
                            <p class="text-sm font-semibold text-app">{{ __('Two-Factor Authentication') }}</p>
                            <p class="mt-1 text-sm text-muted">
                                {{ ($user->two_factor_preference ?? 'off') === 'email' ? __('Email code verification is active for your next sign-in.') : __('Email code verification is available in security settings.') }}
                            </p>
                            <a href="{{ route('user.settings.security') }}" class="mt-3 inline-flex text-sm font-semibold text-primary hover:underline">
                                {{ __('Manage 2FA') }}
                            </a>
                        </div>
                        <div class="rounded-app border border-app bg-surface-1 px-4 py-3">
                            <p class="text-sm font-semibold text-app">{{ __('Global Sign-out') }}</p>
                            <p class="mt-1 text-sm text-muted">{{ __('Sign out other browser sessions and revoke mobile API tokens from security settings.') }}</p>
                            <a href="{{ route('user.settings.security') }}" class="mt-3 inline-flex text-sm font-semibold text-primary hover:underline">
                                {{ __('Open security settings') }}
                            </a>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection
