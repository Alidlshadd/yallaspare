@extends('layouts.user')

@section('title', __('Language'))
@section('subtitle', __('Choose the language used across your account pages and interface labels'))
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
                        <h2 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Language') }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Pick the language used for labels, menus, and account messages.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Current Locale') }}</p>
                        <p class="mt-1 text-sm font-semibold uppercase text-slate-950 dark:text-white">{{ old('locale_preference', $user->locale_preference ?? app()->getLocale()) }}</p>
                    </div>
                </div>

                <form action="{{ route('user.settings.language.update') }}" method="POST" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="locale_preference" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Interface Language') }}</label>
                        <select
                            id="locale_preference"
                            name="locale_preference"
                            class="mt-2 block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/20 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                        >
                            <option value="en" @selected(old('locale_preference', $user->locale_preference ?? 'en') === 'en')>{{ __('English') }}</option>
                            <option value="ar" @selected(old('locale_preference', $user->locale_preference ?? 'en') === 'ar')>{{ __('Arabic') }}</option>
                            <option value="ku" @selected(old('locale_preference', $user->locale_preference ?? 'en') === 'ku')>{{ __('Kurdish') }}</option>
                        </select>
                        @error('locale_preference')
                            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Language scope') }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('This updates interface language for your signed-in experience and related account notices.') }}</p>
                    </div>

                    <div class="flex items-center justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-[#070740] px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2"
                        >
                            {{ __('Save Language') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
