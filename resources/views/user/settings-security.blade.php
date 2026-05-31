@extends('layouts.user')

@section('title', __('Security'))
@section('subtitle', __('Manage login protection and session behavior for your account'))
@section('actions')
    <a
        href="{{ route('user.settings.edit') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    >
        {{ __('Settings') }}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            @include('user.partials.settings-nav')
        </aside>

        <div class="space-y-4">
            @if (session('success'))
                <x-ui.alert variant="success" :title="__('Success')">
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
                <div class="flex flex-col gap-5 border-b border-slate-200/80 pb-6 dark:border-slate-800 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Section') }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Security Settings') }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Set baseline protection preferences for sign-in and session handling.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Two-Step') }}</p>
                        <p class="mt-1 text-sm font-semibold text-amber-600 dark:text-amber-400">{{ __('Coming soon') }}</p>
                    </div>
                </div>

                <form action="{{ route('user.settings.security.update') }}" method="POST" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-950">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Two-Step Verification') }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('This feature is disabled until full 2FA enforcement is implemented in the login flow.') }}</p>
                        </div>

                        <div>
                            <label for="session_timeout" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Session Timeout') }}</label>
                            <select id="session_timeout" name="session_timeout" class="mt-2 block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="15" @selected(old('session_timeout', $user->session_timeout ?? '30') === '15')>{{ __('15 minutes') }}</option>
                                <option value="30" @selected(old('session_timeout', $user->session_timeout ?? '30') === '30')>{{ __('30 minutes') }}</option>
                                <option value="60" @selected(old('session_timeout', $user->session_timeout ?? '30') === '60')>{{ __('1 hour') }}</option>
                                <option value="120" @selected(old('session_timeout', $user->session_timeout ?? '30') === '120')>{{ __('2 hours') }}</option>
                            </select>
                            @error('session_timeout')
                                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition duration-200 hover:border-primary/20 hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:hover:border-primary/30 dark:hover:bg-slate-900">
                        <input type="checkbox" name="login_alerts" value="1" @checked(old('login_alerts', $user->login_alerts ?? true)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-slate-900 dark:text-white">{{ __('Login Alerts') }}</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('Notify me when a new sign-in or unusual session activity is detected.') }}</span>
                        </span>
                    </label>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                            {{ __('Save Security') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
