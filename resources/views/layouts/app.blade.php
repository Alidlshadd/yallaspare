<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $systemSettings['site_name'] ?? config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            html, body {
                margin: 0;
                padding: 0;
            }
            .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }
        </style>

        @if(request()->routeIs('admin.*'))
            <script>
                (function () {
                    try {
                        const lightDefaultResetKey = 'admin-theme-light-default-20260523';
                        let storedTheme = localStorage.getItem('admin-theme');

                        if (storedTheme === 'dark' && localStorage.getItem(lightDefaultResetKey) !== '1') {
                            storedTheme = 'light';
                            localStorage.setItem('admin-theme', 'light');
                        }

                        localStorage.setItem(lightDefaultResetKey, '1');

                        const selectedTheme = storedTheme === 'dark' ? 'dark' : 'light';

                        if (storedTheme !== null && !['light', 'dark'].includes(storedTheme)) {
                            localStorage.setItem('admin-theme', 'light');
                        }

                        document.documentElement.classList.toggle('dark', selectedTheme === 'dark');
                    } catch (error) {
                        document.documentElement.classList.remove('dark');
                    }
                })();
            </script>
        @endif
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        @if(request()->routeIs('admin.*'))
            @php
                $isRtl = in_array(app()->getLocale(), ['ar', 'ku'], true);
                $adminUser = auth()->user();
                $adminAvatarInitial = strtoupper(substr((string) ($adminUser?->name ?: 'A'), 0, 1));
                $adminProfilePhotoUrl = !empty($adminUser?->profile_photo_path)
                    ? asset('storage/' . ltrim((string) $adminUser->profile_photo_path, '/'))
                    : null;
            @endphp
            <div x-data="{ sidebarOpen: false }" class="min-h-screen admin-shell">
                <!-- Mobile Overlay -->
                <div
                    x-show="sidebarOpen"
                    class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
                    @click="sidebarOpen = false"
                    x-transition.opacity
                ></div>

                <!-- Sidebar -->
                <aside
                    class="fixed inset-y-0 {{ $isRtl ? 'right-0' : 'left-0' }} z-40 w-64 bg-slate-900 text-slate-100 transform transition-transform duration-200 lg:translate-x-0 dark:bg-slate-900 h-screen overflow-y-auto overflow-x-hidden overscroll-contain scrollbar-hide"
                    :class="sidebarOpen ? 'translate-x-0' : '{{ $isRtl ? 'translate-x-full' : '-translate-x-full' }}'"
                >
                    <div class="header-logo-area border-b border-white/10 px-4">
                        <a href="{{ route('admin.dashboard') }}" class="app-logo app-logo-dark w-full justify-between focus:outline-none focus-visible:ring-2 focus-visible:ring-white/25">
                            <span class="inline-flex items-center gap-3 min-w-0">
                                <x-brand-mark
                                    :logo-url="$systemSettings['site_logo_url'] ?? null"
                                    :brand="$systemSettings['site_name'] ?? 'YallaSpare'"
                                    wrapper-class="app-logo-mark logo-remove-white-bg"
                                    img-class="h-full w-auto object-contain"
                                    fallback-class="inline-flex h-full w-full items-center justify-center rounded-lg bg-slate-800"
                                    fallback-text-class="text-sm font-semibold text-white"
                                />
                                <span class="app-logo-text truncate">{{ $systemSettings['site_name'] ?? 'YallaSpare' }}</span>
                            </span>
                            <span class="text-[11px] uppercase tracking-widest text-slate-400">{{ __('Admin') }}</span>
                        </a>
                    </div>
                    <a href="{{ route('admin.profile.edit') }}" class="px-4 py-4 border-b border-white/10 flex items-center gap-3 transition hover:bg-slate-800/60">
                        @if ($adminProfilePhotoUrl)
                            <img src="{{ $adminProfilePhotoUrl }}" alt="{{ $adminUser->name }} profile photo" class="h-10 w-10 shrink-0 rounded-full border border-white/20 object-cover">
                        @else
                            <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold shrink-0">
                                {{ $adminAvatarInitial }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-100">{{ $adminUser->name }}</p>
                            <p class="text-xs text-slate-400">{{ __('Profile') }}</p>
                        </div>
                    </a>

                    <nav class="px-4 py-6 space-y-2">
                        @php
                            $navItem = function (bool $active) {
                                return $active
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60';
                            };
                        @endphp
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.dashboard')) }}"
                        >
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                <i class="fas fa-chart-line"></i>
                            </span>
                            <span>{{ __('Dashboard') }}</span>
                        </a>
                        @can(\App\Models\User::PERMISSION_FINANCE_VIEW)
                            <a
                                href="{{ route('admin.revenue.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.revenue.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-sack-dollar"></i>
                                </span>
                                <span>{{ __('Revenue') }}</span>
                            </a>
                        @endcan
                        @can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE)
                            <a
                                href="{{ route('admin.products.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.products.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-box"></i>
                                </span>
                                <span>{{ __('Products') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.categories.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.categories.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-layer-group"></i>
                                </span>
                                <span>{{ __('Categories') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.vehicle-fitments.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.vehicle-fitments.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-car-side"></i>
                                </span>
                                <span>{{ __('Vehicle Finder') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.reviews.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.reviews.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-star"></i>
                                </span>
                                <span>{{ __('Customer Reviews') }}</span>
                            </a>
                        @endcan
                        @can(\App\Models\User::PERMISSION_STOCK_MANAGE)
                            <a
                                href="{{ route('admin.inventory.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.inventory.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-warehouse"></i>
                                </span>
                                <span>{{ __('Inventory') }}</span>
                            </a>
                        @endcan
                        @can(\App\Models\User::PERMISSION_ORDERS_MANAGE)
                            <a
                                href="{{ route('admin.orders.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.orders.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-receipt"></i>
                                </span>
                                <span>{{ __('Orders Management') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.returns.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.returns.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-rotate-left"></i>
                                </span>
                                <span>{{ __('Returns & Refunds') }}</span>
                            </a>
                        @endcan
                        @can('manage-dealers')
                            <a
                                href="{{ route('admin.dealers.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.dealers.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-handshake"></i>
                                </span>
                                <span>{{ __('Dealers') }}</span>
                            </a>
                        @endcan
                        @can('viewAny', \App\Models\User::class)
                            <a
                                href="{{ route('admin.users.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.users.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-users"></i>
                                </span>
                                <span>{{ __('Users') }}</span>
                            </a>
                        @endcan
                        @can(\App\Models\User::PERMISSION_FINANCE_MANAGE)
                            <a
                                href="{{ route('admin.discounts.edit') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.discounts.edit') || request()->routeIs('admin.discounts.coupons.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-tags"></i>
                                </span>
                                <span>{{ __('Coupon Management') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.discounts.rules') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.discounts.rules')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-percent"></i>
                                </span>
                                <span>{{ __('Discount Rules') }}</span>
                            </a>
                        @endcan
                        @can(\App\Models\User::PERMISSION_SETTINGS_MANAGE)
                            <a
                                href="{{ route('admin.settings.edit') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.settings.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-gear"></i>
                                </span>
                                <span>{{ __('Settings') }}</span>
                            </a>
                        @endcan
                        @can(\App\Models\User::PERMISSION_ACTIVITY_LOGS_VIEW)
                            <a
                                href="{{ route('admin.activity-logs.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.activity-logs.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-clipboard-list"></i>
                                </span>
                                <span>{{ __('Activity Logs') }}</span>
                            </a>
                        @endcan
                    </nav>
                </aside>

                <!-- Main Content -->
                <div class="{{ $isRtl ? 'lg:pr-64' : 'lg:pl-64' }} min-h-screen flex flex-col">
                    <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-slate-200 dark:bg-slate-900/80 dark:border-slate-800">
                        <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
                            <div class="flex items-center gap-3">
                                <button
                                    class="lg:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                    @click="sidebarOpen = true"
                                >
                                    <i class="fas fa-bars"></i>
                                </button>
                                <div class="hidden sm:block">
                                    @if (isset($header))
                                        <div class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $header }}
                                        </div>
                                    @else
                                        <div class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Admin Panel') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-language-switcher variant="light" />

                                <button
                                    id="adminThemeToggle"
                                    type="button"
                                    class="inline-flex items-center justify-center h-10 w-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                    aria-label="{{ __('Toggle dark mode') }}"
                                >
                                    <i id="adminThemeIcon" class="fas fa-moon"></i>
                                </button>
                                <div class="relative">
                                    <button
                                        id="adminNotificationsButton"
                                        type="button"
                                        class="relative inline-flex items-center justify-center h-10 w-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                        aria-label="{{ __('Notifications') }}"
                                    >
                                        <i class="fas fa-bell"></i>
                                        <span
                                            id="adminNotificationsBadge"
                                            class="hidden absolute -top-1 {{ $isRtl ? '-left-1' : '-right-1' }} min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-semibold items-center justify-center"
                                        >
                                            0
                                        </span>
                                    </button>

                                    <div
                                        id="adminNotificationsDropdown"
                                        class="hidden absolute {{ $isRtl ? 'left-0' : 'right-0' }} mt-2 w-[360px] max-w-[92vw] bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden z-30 dark:bg-slate-900 dark:border-slate-800 dark:shadow-black/30"
                                    >
                                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between dark:border-slate-800">
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Notifications') }}</p>
                                            <div class="flex items-center gap-3">
                                                <button
                                                    id="adminNotificationsMarkAll"
                                                    type="button"
                                                    class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700"
                                                >
                                                    {{ __('Mark all read') }}
                                                </button>
                                                <span id="adminNotificationsUpdatedAt" class="text-[11px] text-slate-400 dark:text-slate-500">--</span>
                                            </div>
                                        </div>

                                        <div class="max-h-[420px] overflow-y-auto">
                                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">{{ __('Out Of Stock') }}</p>
                                                    <span id="adminOutOfStockCount" class="text-xs font-semibold text-rose-700">0</span>
                                                </div>
                                                <div id="adminOutOfStockList" class="space-y-2"></div>
                                            </div>

                                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ __('Low Stock') }}</p>
                                                    <span id="adminLowStockCount" class="text-xs font-semibold text-amber-700">0</span>
                                                </div>
                                                <div id="adminLowStockList" class="space-y-2"></div>
                                            </div>

                                            <div class="px-4 py-3">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ __('Dealer Requests') }}</p>
                                                    <span id="adminDealerRequestCount" class="text-xs font-semibold text-indigo-700">0</span>
                                                </div>
                                                <div id="adminDealerRequestList" class="space-y-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a
                                    href="{{ route('admin.profile.edit') }}"
                                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    @if ($adminProfilePhotoUrl)
                                        <img src="{{ $adminProfilePhotoUrl }}" alt="{{ $adminUser->name }} profile photo" class="h-6 w-6 rounded-full object-cover">
                                    @else
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">
                                            {{ $adminAvatarInitial }}
                                        </span>
                                    @endif
                                    <span class="hidden md:inline">{{ __('Profile') }}</span>
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-md border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                    >
                                        <i class="fas fa-right-from-bracket"></i>
                                        <span class="hidden sm:inline">{{ __('Log Out') }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </header>

                    <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6">
                        {{ $slot }}
                    </main>

                    <div id="adminDangerModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
                        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 id="adminDangerTitle" class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Delete Coupon') }}</h3>
                                    <p id="adminDangerDescription" class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('This action is permanent and cannot be undone.') }}</p>
                                </div>
                            </div>
                            <div class="mt-5 flex justify-end gap-2">
                                <button type="button" id="adminDangerCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Cancel') }}</button>
                                <button type="button" id="adminDangerConfirm" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">{{ __('Confirm Delete') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="min-h-screen bg-gray-100">
                @unless ($hideNavigation)
                    @include('layouts.navigation')
                @endunless

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>

                @include('partials.site-footer')
                @include('partials.language-switcher-script')
            </div>
        @endif

        @if(request()->routeIs('admin.*'))
            <script>
                (function () {
                    const themeToggle = document.getElementById('adminThemeToggle');
                    const themeIcon = document.getElementById('adminThemeIcon');
                    const themeStorageKey = 'admin-theme';
                    const lightDefaultResetKey = 'admin-theme-light-default-20260523';
                    const applyTheme = (isDark) => {
                        document.documentElement.classList.toggle('dark', isDark);
                        if (themeIcon) {
                            themeIcon.classList.toggle('fa-moon', !isDark);
                            themeIcon.classList.toggle('fa-sun', isDark);
                        }
                    };
                    let storedTheme = localStorage.getItem(themeStorageKey);

                    if (storedTheme === 'dark' && localStorage.getItem(lightDefaultResetKey) !== '1') {
                        storedTheme = 'light';
                        localStorage.setItem(themeStorageKey, 'light');
                    }

                    localStorage.setItem(lightDefaultResetKey, '1');

                    const selectedTheme = storedTheme === 'dark' ? 'dark' : 'light';

                    if (storedTheme !== null && !['light', 'dark'].includes(storedTheme)) {
                        localStorage.setItem(themeStorageKey, 'light');
                    }

                    applyTheme(selectedTheme === 'dark');

                    if (themeToggle) {
                        themeToggle.addEventListener('click', () => {
                            const isDark = !document.documentElement.classList.contains('dark');
                            applyTheme(isDark);
                            localStorage.setItem(themeStorageKey, isDark ? 'dark' : 'light');
                        });
                    }

                    const languageDropdowns = Array.from(document.querySelectorAll('[data-header-dropdown]'));
                    const closeLanguageDropdowns = (except = null) => {
                        languageDropdowns.forEach((root) => {
                            if (root === except) {
                                return;
                            }

                            const menu = root.querySelector('[data-header-dropdown-menu]');
                            const trigger = root.querySelector('[data-header-dropdown-trigger]');
                            const icon = root.querySelector('[data-header-dropdown-icon]');
                            menu?.classList.add('hidden');
                            trigger?.setAttribute('aria-expanded', 'false');
                            icon?.classList.remove('rotate-180');
                        });
                    };

                    languageDropdowns.forEach((root) => {
                        const trigger = root.querySelector('[data-header-dropdown-trigger]');
                        const menu = root.querySelector('[data-header-dropdown-menu]');
                        const icon = root.querySelector('[data-header-dropdown-icon]');

                        if (!trigger || !menu) {
                            return;
                        }

                        trigger.addEventListener('click', (event) => {
                            event.stopPropagation();
                            const willOpen = menu.classList.contains('hidden');
                            closeLanguageDropdowns(root);
                            menu.classList.toggle('hidden', !willOpen);
                            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                            icon?.classList.toggle('rotate-180', willOpen);
                        });
                    });

                    document.addEventListener('click', (event) => {
                        const target = event.target instanceof Node ? event.target : null;
                        if (!target || !languageDropdowns.some((root) => root.contains(target))) {
                            closeLanguageDropdowns();
                        }
                    });

                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') {
                            closeLanguageDropdowns();
                        }
                    });

                    const button = document.getElementById('adminNotificationsButton');
                    const dropdown = document.getElementById('adminNotificationsDropdown');
                    const badge = document.getElementById('adminNotificationsBadge');
                    const updatedAt = document.getElementById('adminNotificationsUpdatedAt');
                    const outCount = document.getElementById('adminOutOfStockCount');
                    const lowCount = document.getElementById('adminLowStockCount');
                    const dealerCount = document.getElementById('adminDealerRequestCount');
                    const outList = document.getElementById('adminOutOfStockList');
                    const lowList = document.getElementById('adminLowStockList');
                    const dealerList = document.getElementById('adminDealerRequestList');
                    const markAllButton = document.getElementById('adminNotificationsMarkAll');
                    const endpoint = "{{ route('admin.notifications.index') }}";
                    const readEndpoint = "{{ route('admin.notifications.read') }}";
                    const readAllEndpoint = "{{ route('admin.notifications.read-all') }}";
                    const csrfToken = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '';
                    const markReadLabel = @json(__('Mark read'));
                    let currentKeys = [];

                    if (!button || !dropdown) {
                        return;
                    }

                    const renderEmpty = (container, message) => {
                        container.innerHTML = `<p class="text-xs text-slate-400 dark:text-slate-500">${message}</p>`;
                    };

                    const renderItems = (container, items) => {
                        if (!items || items.length === 0) {
                            return false;
                        }

                        container.innerHTML = items.map(item => `
                            <div class="block rounded-lg px-2 py-2 ${item.read ? 'bg-white dark:bg-slate-900/60' : 'bg-indigo-50/50 dark:bg-indigo-500/10'} hover:bg-slate-50 dark:hover:bg-slate-800/80 transition">
                                <a href="${item.url}" class="block">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">${item.title}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">${item.subtitle}</p>
                                <div class="flex items-center justify-between mt-1">
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">${item.meta ?? ''}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">${item.time ?? ''}</p>
                                </div>
                                </a>
                                ${item.read ? '' : `<button type="button" data-key="${item.key}" class="admin-mark-read mt-2 text-[11px] font-semibold text-indigo-600 hover:text-indigo-700">${markReadLabel}</button>`}
                            </div>
                        `).join('');
                        return true;
                    };

                    const postJson = async (url, payload) => {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(payload)
                        });

                        return response.ok;
                    };

                    const fetchNotifications = async () => {
                        try {
                            const response = await fetch(endpoint, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (!response.ok) {
                                return;
                            }

                            const data = await response.json();
                            const counts = data.counts || {};
                            const total = Number(counts.unread_total ?? counts.total ?? 0);

                            if (total > 0) {
                                badge.textContent = total > 99 ? '99+' : String(total);
                                badge.classList.remove('hidden');
                                badge.classList.add('inline-flex');
                            } else {
                                badge.classList.add('hidden');
                                badge.classList.remove('inline-flex');
                            }

                            outCount.textContent = String(counts.out_of_stock || 0);
                            lowCount.textContent = String(counts.low_stock || 0);
                            dealerCount.textContent = String(counts.dealer_requests || 0);

                            const outItems = data.items?.out_of_stock || [];
                            const lowItems = data.items?.low_stock || [];
                            const dealerItems = data.items?.dealer_requests || [];
                            currentKeys = [...outItems, ...lowItems, ...dealerItems].map(item => item.key).filter(Boolean);

                            const outHas = renderItems(outList, outItems);
                            const lowHas = renderItems(lowList, lowItems);
                            const dealerHas = renderItems(dealerList, dealerItems);

                            if (!outHas) renderEmpty(outList, 'No out-of-stock products');
                            if (!lowHas) renderEmpty(lowList, 'No low-stock alerts');
                            if (!dealerHas) renderEmpty(dealerList, 'No pending dealer requests');

                            const fetchedAt = data.fetched_at ? new Date(data.fetched_at) : null;
                            updatedAt.textContent = fetchedAt ? `Updated ${fetchedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}` : '--';
                        } catch (error) {
                            // Silently ignore polling errors.
                        }
                    };

                    const bindMarkReadActions = () => {
                        dropdown.querySelectorAll('.admin-mark-read').forEach((buttonEl) => {
                            buttonEl.addEventListener('click', async () => {
                                const key = buttonEl.getAttribute('data-key');
                                if (!key) return;
                                const ok = await postJson(readEndpoint, { notification_key: key });
                                if (ok) {
                                    fetchNotifications();
                                }
                            });
                        });
                    };

                    button.addEventListener('click', () => {
                        dropdown.classList.toggle('hidden');
                        if (!dropdown.classList.contains('hidden')) {
                            fetchNotifications().then(bindMarkReadActions);
                        }
                    });

                    document.addEventListener('click', (event) => {
                        if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                            dropdown.classList.add('hidden');
                        }
                    });

                    if (markAllButton) {
                        markAllButton.addEventListener('click', async () => {
                            if (currentKeys.length === 0) {
                                return;
                            }
                            const ok = await postJson(readAllEndpoint, { notification_keys: currentKeys });
                            if (ok) {
                                fetchNotifications().then(bindMarkReadActions);
                            }
                        });
                    }

                    fetchNotifications().then(bindMarkReadActions);
                    setInterval(() => {
                        fetchNotifications().then(bindMarkReadActions);
                    }, 20000);
                })();

                (function () {
                    const modal = document.getElementById('adminDangerModal');
                    const titleEl = document.getElementById('adminDangerTitle');
                    const descriptionEl = document.getElementById('adminDangerDescription');
                    const cancelBtn = document.getElementById('adminDangerCancel');
                    const confirmBtn = document.getElementById('adminDangerConfirm');

                    if (!modal || !titleEl || !descriptionEl || !cancelBtn || !confirmBtn) {
                        return;
                    }

                    let resolver = null;

                    const setVisible = (visible) => {
                        modal.classList.toggle('hidden', !visible);
                        modal.classList.toggle('flex', visible);
                    };

                    const resolveAndClose = (value) => {
                        setVisible(false);
                        if (resolver) {
                            resolver(value);
                            resolver = null;
                        }
                    };

                    window.adminDangerConfirm = ({ title, description } = {}) => {
                        titleEl.textContent = title || 'Delete Coupon';
                        descriptionEl.textContent = description || 'This action is permanent and cannot be undone.';
                        setVisible(true);

                        return new Promise((resolve) => {
                            resolver = resolve;
                        });
                    };

                    cancelBtn.addEventListener('click', () => resolveAndClose(false));
                    confirmBtn.addEventListener('click', () => resolveAndClose(true));
                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            resolveAndClose(false);
                        }
                    });

                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                            resolveAndClose(false);
                        }
                    });

                    document.addEventListener('submit', (event) => {
                        const form = event.target instanceof HTMLFormElement ? event.target : null;
                        if (!form || !form.matches('form[data-danger-confirm]')) {
                            return;
                        }

                        if (form.dataset.dangerConfirmed === '1') {
                            delete form.dataset.dangerConfirmed;
                            return;
                        }

                        event.preventDefault();
                        window.adminDangerConfirm({
                            title: form.dataset.dangerTitle || 'Delete Coupon',
                            description: form.dataset.dangerDescription || 'This action is permanent and cannot be undone.',
                        }).then((confirmed) => {
                            if (!confirmed) return;
                            form.dataset.dangerConfirmed = '1';
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        });
                    }, true);
                })();
            </script>
        @endif
        @stack('scripts')
    </body>
</html>
