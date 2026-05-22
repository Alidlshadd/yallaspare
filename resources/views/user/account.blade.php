@extends('layouts.user')

@php
    $avatarInitial = strtoupper(substr((string) ($firstName ?: $user->name ?: 'U'), 0, 1));
@endphp

@section('title', __('Account'))
@section('subtitle', __('A dedicated account hub with separate pages for each major section'))
@section('actions')
    <a
        href="{{ route('user.shop.home') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    >
        {{ __('Home') }}
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

        <div class="space-y-4">
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
                <div class="border-b border-slate-200/80 pb-6 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        @if (!empty($user->profile_photo_path))
                            <img
                                src="{{ asset('storage/' . ltrim((string) $user->profile_photo_path, '/')) }}"
                                alt="{{ $user->name }} profile photo"
                                class="h-16 w-16 rounded-full object-cover border border-slate-200 dark:border-slate-700"
                            >
                        @else
                            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-[#070740] text-xl font-semibold text-white">
                                {{ $avatarInitial }}
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Account Hub') }}</p>
                            <h2 class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ $user->name }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach ([
                        ['title' => __('Personal Info'), 'description' => __('Name, email, phone, and primary delivery data.'), 'route' => 'user.account.personal'],
                        ['title' => __('Address Book'), 'description' => __('Manage all saved addresses and defaults.'), 'route' => 'user.account.addresses'],
                        ['title' => __('Security'), 'description' => __('Password changes and account protection status.'), 'route' => 'user.account.security'],
                        ['title' => __('Activity'), 'description' => __('Recent orders and account history at a glance.'), 'route' => 'user.account.activity'],
                        ['title' => __('Account Actions'), 'description' => __('Freeze requests, data export, and deletion.'), 'route' => 'user.account.actions'],
                        ['title' => __('Settings'), 'description' => __('Theme, language, and account preferences.'), 'route' => 'user.settings.edit'],
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

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="rounded-3xl border border-slate-200/80 bg-white px-4 py-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Orders') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $totalOrders }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white px-4 py-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Pending') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $pendingOrders }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white px-4 py-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Delivered') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $deliveredOrders }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white px-4 py-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Addresses') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $addresses->count() }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
