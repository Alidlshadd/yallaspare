<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
                    const storedTheme = localStorage.getItem('admin-theme');
                    if (storedTheme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else if (storedTheme === 'light') {
                        document.documentElement.classList.remove('dark');
                    }
                })();
            </script>
        @endif
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        @if(request()->routeIs('admin.*'))
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
                    class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-100 transform transition-transform duration-200 lg:translate-x-0 dark:bg-slate-900 h-screen overflow-y-auto overflow-x-hidden overscroll-contain scrollbar-hide"
                    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                >
                    <div class="flex items-center gap-3 px-6 py-6 border-b border-white/10">
                        @if(!empty($systemSettings['site_logo_url']))
                            <div class="h-10 w-10 rounded-lg bg-slate-800 overflow-hidden flex items-center justify-center">
                                <img src="{{ $systemSettings['site_logo_url'] }}" alt="Logo" class="h-full w-full object-cover">
                            </div>
                        @else
                            <div class="h-10 w-10 rounded-lg bg-slate-800 flex items-center justify-center">
                                <span class="text-lg font-semibold">YS</span>
                            </div>
                        @endif
                        <div>
                            <p class="text-sm uppercase tracking-widest text-slate-400">Admin</p>
                            <p class="font-semibold">{{ $systemSettings['site_name'] ?? 'YallaSpare' }}</p>
                        </div>
                    </div>
                    <div class="px-4 py-4 border-b border-slate-200 flex items-center gap-3 dark:border-slate-800">
                        <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-100">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400">{{ auth()->user()->email }}</p>
                        </div>
                    </div>

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
                            <span>Dashboard</span>
                        </a>
                        <a
                            href="{{ route('admin.products.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.products.*')) }}"
                        >
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                <i class="fas fa-box"></i>
                            </span>
                            <span>Products</span>
                        </a>
                        <a
                            href="{{ route('admin.categories.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.categories.*')) }}"
                        >
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                <i class="fas fa-layer-group"></i>
                            </span>
                            <span>Categories</span>
                        </a>
                        <a
                            href="{{ route('admin.inventory.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.inventory.*')) }}"
                        >
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                <i class="fas fa-warehouse"></i>
                            </span>
                            <span>Inventory</span>
                        </a>
                        <a
                            href="{{ route('admin.orders.index') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.orders.*')) }}"
                        >
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                <i class="fas fa-receipt"></i>
                            </span>
                            <span>Orders Management</span>
                        </a>
                        @can('manage-dealers')
                            <a
                                href="{{ route('admin.dealers.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.dealers.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-handshake"></i>
                                </span>
                                <span>Dealers</span>
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
                                <span>Users</span>
                            </a>
                        @endcan
                        <a
                            href="{{ route('admin.settings.edit') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.settings.*')) }}"
                        >
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                <i class="fas fa-gear"></i>
                            </span>
                            <span>Settings</span>
                        </a>
                        @if(auth()->user()->role === 'super_admin')
                            <a
                                href="{{ route('admin.activity-logs.index') }}"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ $navItem(request()->routeIs('admin.activity-logs.*')) }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-800/80">
                                    <i class="fas fa-clipboard-list"></i>
                                </span>
                                <span>Activity Logs</span>
                            </a>
                        @endif
                    </nav>
                </aside>

                <!-- Main Content -->
                <div class="lg:pl-64 min-h-screen flex flex-col">
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
                                        <div class="text-lg font-semibold text-slate-900 dark:text-slate-100">Admin Panel</div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <button
                                    id="adminThemeToggle"
                                    type="button"
                                    class="inline-flex items-center justify-center h-10 w-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                    aria-label="Toggle dark mode"
                                >
                                    <i id="adminThemeIcon" class="fas fa-moon"></i>
                                </button>
                                <div class="relative">
                                    <button
                                        id="adminNotificationsButton"
                                        type="button"
                                        class="relative inline-flex items-center justify-center h-10 w-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                        aria-label="Notifications"
                                    >
                                        <i class="fas fa-bell"></i>
                                        <span
                                            id="adminNotificationsBadge"
                                            class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-semibold items-center justify-center"
                                        >
                                            0
                                        </span>
                                    </button>

                                    <div
                                        id="adminNotificationsDropdown"
                                        class="hidden absolute right-0 mt-2 w-[360px] max-w-[92vw] bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden z-30 dark:bg-slate-900 dark:border-slate-800 dark:shadow-black/30"
                                    >
                                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between dark:border-slate-800">
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Notifications</p>
                                            <div class="flex items-center gap-3">
                                                <button
                                                    id="adminNotificationsMarkAll"
                                                    type="button"
                                                    class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700"
                                                >
                                                    Mark all read
                                                </button>
                                                <span id="adminNotificationsUpdatedAt" class="text-[11px] text-slate-400 dark:text-slate-500">--</span>
                                            </div>
                                        </div>

                                        <div class="max-h-[420px] overflow-y-auto">
                                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Out Of Stock</p>
                                                    <span id="adminOutOfStockCount" class="text-xs font-semibold text-rose-700">0</span>
                                                </div>
                                                <div id="adminOutOfStockList" class="space-y-2"></div>
                                            </div>

                                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Low Stock</p>
                                                    <span id="adminLowStockCount" class="text-xs font-semibold text-amber-700">0</span>
                                                </div>
                                                <div id="adminLowStockList" class="space-y-2"></div>
                                            </div>

                                            <div class="px-4 py-3">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Dealer Requests</p>
                                                    <span id="adminDealerRequestCount" class="text-xs font-semibold text-indigo-700">0</span>
                                                </div>
                                                <div id="adminDealerRequestList" class="space-y-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-md border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                    >
                                        <i class="fas fa-right-from-bracket"></i>
                                        <span class="hidden sm:inline">Log Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </header>

                    <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6">
                        {{ $slot }}
                    </main>

                    <footer class="mt-auto border-t border-slate-200/60 bg-white/80 px-6 py-4 backdrop-blur-sm dark:border-slate-700/60 dark:bg-[#070740]/80">
                        <div class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-3 sm:flex-row">
                            <p class="text-xs text-slate-600/90 dark:text-slate-300/80">
                                &copy; {{ date('Y') }} Yalla Spare. All rights reserved.
                            </p>

                            <nav aria-label="Footer links" class="flex items-center gap-5">
                                <a
                                    href="{{ url('/privacy-policy') }}"
                                    class="group relative inline-block text-xs text-slate-600/90 transition-colors duration-200 hover:text-slate-900 dark:text-slate-300/85 dark:hover:text-white"
                                >
                                    Privacy Policy
                                    <span class="pointer-events-none absolute left-0 -bottom-0.5 h-px w-full origin-left scale-x-0 bg-current transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
                                </a>
                                <a
                                    href="{{ url('/terms') }}"
                                    class="group relative inline-block text-xs text-slate-600/90 transition-colors duration-200 hover:text-slate-900 dark:text-slate-300/85 dark:hover:text-white"
                                >
                                    Terms
                                    <span class="pointer-events-none absolute left-0 -bottom-0.5 h-px w-full origin-left scale-x-0 bg-current transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
                                </a>
                                <a
                                    href="{{ url('/support') }}"
                                    class="group relative inline-block text-xs text-slate-600/90 transition-colors duration-200 hover:text-slate-900 dark:text-slate-300/85 dark:hover:text-white"
                                >
                                    Support
                                    <span class="pointer-events-none absolute left-0 -bottom-0.5 h-px w-full origin-left scale-x-0 bg-current transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
                                </a>
                            </nav>
                        </div>
                    </footer>
                </div>
            </div>
        @else
            <div class="min-h-screen bg-gray-100">
                @include('layouts.navigation')

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
            </div>
        @endif

        @if(request()->routeIs('admin.*'))
            <script>
                (function () {
                    const themeToggle = document.getElementById('adminThemeToggle');
                    const themeIcon = document.getElementById('adminThemeIcon');
                    const themeStorageKey = 'admin-theme';
                    const applyTheme = (isDark) => {
                        document.documentElement.classList.toggle('dark', isDark);
                        if (themeIcon) {
                            themeIcon.classList.toggle('fa-moon', !isDark);
                            themeIcon.classList.toggle('fa-sun', isDark);
                        }
                    };
                    const storedTheme = localStorage.getItem(themeStorageKey);
                    applyTheme(storedTheme === 'dark');

                    if (themeToggle) {
                        themeToggle.addEventListener('click', () => {
                            const isDark = !document.documentElement.classList.contains('dark');
                            applyTheme(isDark);
                            localStorage.setItem(themeStorageKey, isDark ? 'dark' : 'light');
                        });
                    }

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
                                ${item.read ? '' : `<button type="button" data-key="${item.key}" class="admin-mark-read mt-2 text-[11px] font-semibold text-indigo-600 hover:text-indigo-700">Mark read</button>`}
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
            </script>
        @endif
    </body>
</html>
