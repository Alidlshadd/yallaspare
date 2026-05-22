@extends('layouts.user')

@section('title', __('Settings'))
@section('subtitle', __('Choose a section to manage your account preferences'))
@section('actions')
    <a
        href="{{ route('user.account.edit') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    >
        {{ __('Profile') }}
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
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Settings Hub') }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Manage each section separately') }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Each preference group now has its own dedicated page, so changes stay focused and easier to review.') }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach ([
                        ['title' => __('Appearance'), 'description' => __('Theme mode and visual presentation.'), 'route' => 'user.settings.appearance'],
                        ['title' => __('Language'), 'description' => __('Choose the interface language used across your account.'), 'route' => 'user.settings.language'],
                        ['title' => __('Notifications'), 'description' => __('Control which updates and alerts should reach you.'), 'route' => 'user.settings.notifications'],
                        ['title' => __('Security'), 'description' => __('Manage login protection, two-step preferences, and session timeout rules.'), 'route' => 'user.settings.security'],
                        ['title' => __('Communication'), 'description' => __('Choose how email, SMS, and WhatsApp updates should reach you.'), 'route' => 'user.settings.communication'],
                        ['title' => __('Checkout'), 'description' => __('Define your default contact method and quick checkout behavior.'), 'route' => 'user.settings.checkout'],
                        ['title' => __('Accessibility'), 'description' => __('Adjust font size, motion, and contrast for a more comfortable interface.'), 'route' => 'user.settings.accessibility'],
                    ] as $section)
                        <a
                            href="{{ route($section['route']) }}"
                            class="group block w-full rounded-3xl border border-slate-200/80 bg-slate-50 p-5 transition duration-200 hover:-translate-y-0.5 hover:border-[#070740]/20 hover:bg-white hover:shadow-sm hover:shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-950 dark:hover:border-[#070740]/30 dark:hover:bg-slate-900 dark:hover:shadow-black/20"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-slate-950 dark:text-white">{{ $section['title'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $section['description'] }}</p>
                                </div>
                                <span class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 group-hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:group-hover:bg-slate-800">
                                    {{ __('Manage') }}
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection
