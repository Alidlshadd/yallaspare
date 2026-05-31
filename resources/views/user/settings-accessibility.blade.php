@extends('layouts.user')

@section('title', __('Accessibility'))
@section('subtitle', __('Improve readability and comfort with accessibility controls'))
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
                <div class="border-b border-slate-200/80 pb-6 dark:border-slate-800">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Section') }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Accessibility') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Make the interface easier to read and more comfortable during long sessions.') }}</p>
                </div>

                <form action="{{ route('user.settings.accessibility.update') }}" method="POST" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="font_size_preference" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Font Size') }}</label>
                        <select id="font_size_preference" name="font_size_preference" class="mt-2 block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            <option value="default" @selected(old('font_size_preference', $user->font_size_preference ?? 'default') === 'default')>{{ __('Default') }}</option>
                            <option value="large" @selected(old('font_size_preference', $user->font_size_preference ?? 'default') === 'large')>{{ __('Large') }}</option>
                            <option value="xl" @selected(old('font_size_preference', $user->font_size_preference ?? 'default') === 'xl')>{{ __('Extra large') }}</option>
                        </select>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition duration-200 hover:border-primary/20 hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:hover:border-primary/30 dark:hover:bg-slate-900">
                        <input type="checkbox" name="reduced_motion" value="1" @checked(old('reduced_motion', $user->reduced_motion ?? false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-slate-900 dark:text-white">{{ __('Reduced Motion') }}</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('Limit animation and movement-heavy transitions.') }}</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition duration-200 hover:border-primary/20 hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:hover:border-primary/30 dark:hover:bg-slate-900">
                        <input type="checkbox" name="high_contrast_mode" value="1" @checked(old('high_contrast_mode', $user->high_contrast_mode ?? false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-slate-900 dark:text-white">{{ __('High Contrast Mode') }}</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('Use stronger contrast for text, controls, and card surfaces.') }}</span>
                        </span>
                    </label>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                            {{ __('Save Accessibility') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
