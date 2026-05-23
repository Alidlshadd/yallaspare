@extends('layouts.user')

@section('title', __('Appearance'))
@section('subtitle', __('Control how your account looks across the dashboard and shopping experience'))
@section('actions')
    <a
        href="{{ route('user.settings.edit') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    >
        {{ __('Settings') }}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </a>
@endsection

@section('content')
    @php
        $selectedThemePreference = old('theme_preference', $user->theme_preference ?? 'light');
        $selectedThemePreference = in_array($selectedThemePreference, ['light', 'dark'], true) ? $selectedThemePreference : 'light';
    @endphp

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

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8" x-data="{
                themePreference: @js($selectedThemePreference),
                init() {
                    const normalizeTheme = (value) => ['light', 'dark'].includes(value) ? value : null;
                    const storedTheme = localStorage.getItem('user-theme');
                    const normalizedStoredTheme = normalizeTheme(storedTheme);

                    if (normalizedStoredTheme) {
                        this.themePreference = normalizedStoredTheme;
                    } else if (storedTheme !== null) {
                        localStorage.setItem('user-theme', 'light');
                    }
                },
                applyTheme(value) {
                    const selectedTheme = ['light', 'dark'].includes(value) ? value : 'light';

                    this.themePreference = selectedTheme;
                    document.documentElement.classList.toggle('dark', selectedTheme === 'dark');
                    localStorage.setItem('user-theme', selectedTheme);
                }
            }">
                <div class="flex flex-col gap-5 border-b border-slate-200/80 pb-6 dark:border-slate-800 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Section') }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Appearance') }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Choose a theme that stays consistent across your account hub and store pages.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Current Theme') }}</p>
                        <p class="mt-1 text-sm font-semibold capitalize text-slate-950 dark:text-white" x-text="themePreference"></p>
                    </div>
                </div>

                <form action="{{ route('user.settings.appearance.update') }}" method="POST" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="theme_preference" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Theme Mode') }}</label>
                        <select
                            id="theme_preference"
                            name="theme_preference"
                            x-model="themePreference"
                            @change="applyTheme($event.target.value)"
                            class="mt-2 block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/20 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                        >
                            <option value="light">{{ __('Light mode') }}</option>
                            <option value="dark">{{ __('Dark mode') }}</option>
                        </select>
                        @error('theme_preference')
                            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Preview behavior') }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('Changing the selection updates the theme immediately and saves your preference when you submit.') }}</p>
                    </div>

                    <div class="flex items-center justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-[#070740] px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2"
                        >
                            {{ __('Save Appearance') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
