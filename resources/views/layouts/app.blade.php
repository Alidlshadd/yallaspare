<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $systemSettings['site_name'] ?? config('app.name', 'Laravel') }}</title>
        @include('partials.brand-head')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
              integrity="sha384-iw3OoTErCYJJB9mCa8LNS2hbsQ7M3C0EpIsO/H5+EGAkPGc6rk+V8i04oW/K5xq0"
              crossorigin="anonymous"
              referrerpolicy="no-referrer">

        <!-- Scripts -->
        @vite(request()->routeIs('admin.*')
            ? ['resources/css/app.css', 'resources/js/app.js', 'resources/js/motion/admin.js']
            : ['resources/css/app.css', 'resources/js/app.js'])

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
            <style>
                /* HYBRID A+C ADMIN TOPBAR */
                .admin-topbar {
                    background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%) !important;
                    border-bottom: 0 !important;
                    backdrop-filter: none !important;
                    position: sticky; top: 0; z-index: 40;
                    overflow: visible;
                }
                .admin-topbar::before {
                    content: ""; position: absolute; inset: 0; pointer-events: none; opacity: 0.45;
                    background-image:
                        repeating-linear-gradient(135deg, rgba(255,255,255,0.04) 0 1px, transparent 1px 18px),
                        repeating-linear-gradient(45deg, rgba(255,255,255,0.02) 0 1px, transparent 1px 18px);
                }
                .admin-topbar::after {
                    content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 3px;
                    background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
                }
                /* Clip-only wrapper for decorations that extend past the header bounds.
                   Header itself must stay overflow:visible so dropdowns can escape. */
                .admin-topbar-decor {
                    position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0;
                }
                .admin-topbar-hairline {
                    position: absolute; top: 0; left: 0; right: 0; height: 1px;
                    background: linear-gradient(90deg, transparent, rgba(251,191,36,0.45), transparent);
                    z-index: 1;
                }
                .admin-topbar-glow {
                    position: absolute; top: -72px; right: -72px; height: 168px; width: 168px;
                    border-radius: 9999px; background: rgba(251,191,36,0.07); filter: blur(60px); pointer-events: none;
                }
                .emboss-badge {
                    background:
                        radial-gradient(circle at 30% 25%, rgba(255,255,255,0.22), transparent 60%),
                        linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
                    box-shadow:
                        inset 0 1px 0 rgba(255,255,255,0.45),
                        inset 0 -2px 4px rgba(120,53,15,0.4),
                        0 2px 6px rgba(245,158,11,0.30);
                }
                /* Icon buttons — neutral white-glass (default action) */
                .topbar-action {
                    align-items: center; justify-content: center;
                    height: 36px; width: 36px; border-radius: 10px;
                    border: 1px solid rgba(255,255,255,0.10);
                    background: rgba(255,255,255,0.04);
                    color: rgba(255,255,255,0.85);
                    transition: all .15s ease;
                }
                .topbar-action:hover {
                    background: rgba(255,255,255,0.10);
                    color: #fff;
                    border-color: rgba(255,255,255,0.18);
                }
                /* Logout — semantic red (subtle idle hint, full red on hover) */
                .topbar-logout { color: rgba(253,164,175,0.85); }
                .topbar-logout:hover {
                    background: rgba(244,63,94,0.18);
                    border-color: rgba(244,63,94,0.55);
                    color: #fecdd3;
                }
                /* Language switcher pill — neutral white-glass to match icon buttons */
                .admin-topbar [data-header-dropdown] > button {
                    height: 36px !important; border-radius: 10px !important;
                    background: rgba(255,255,255,0.04) !important;
                    border-color: rgba(255,255,255,0.10) !important;
                    color: rgba(255,255,255,0.90) !important;
                }
                .admin-topbar [data-header-dropdown] > button:hover {
                    background: rgba(255,255,255,0.10) !important;
                    border-color: rgba(255,255,255,0.18) !important;
                    color: #fff !important;
                }
                /* Profile — amber (focal premium action) */
                .topbar-profile {
                    border: 1px solid rgba(251,191,36,0.40);
                    background: rgba(251,191,36,0.07);
                    color: #fff;
                    transition: all .15s ease;
                }
                .topbar-profile:hover {
                    background: rgba(251,191,36,0.14);
                    border-color: rgba(251,191,36,0.65);
                }
                .topbar-pulse-ring { position: relative; }
                .topbar-pulse-ring::after {
                    content: ""; position: absolute; inset: -4px; border-radius: 999px;
                    border: 2px solid currentColor; opacity: 0.35; animation: tb-ring 1.6s ease-out infinite;
                }
                @keyframes tb-ring { 0% { transform: scale(0.6); opacity: 0.6; } 100% { transform: scale(1.6); opacity: 0; } }

                /* ─────── ADMIN SIDEBAR — INSTRUMENT STACK ─────── */
                .admin-shell .admin-sidebar {
                    background:
                        linear-gradient(180deg, #04042a 0%, #06063a 50%, #08084a 100%) !important;
                    display: flex;
                    flex-direction: column;
                }
                /* Thin amber hairline on the trailing edge — matches topbar's left amber bar */
                .admin-shell .admin-sidebar::after {
                    content: ""; position: absolute; top: 0; bottom: 0; right: 0; width: 1px;
                    background: linear-gradient(180deg, transparent, rgba(251,191,36,0.35), transparent);
                    pointer-events: none;
                }
                [dir='rtl'] .admin-shell .admin-sidebar::after {
                    right: auto; left: 0;
                }
                .admin-shell .admin-sidebar-header {
                    border-bottom-color: rgba(255,255,255,0.06) !important;
                }
                .admin-shell .admin-sidebar-meta {
                    color: #fcd34d !important;
                    font-family: ui-monospace, 'JetBrains Mono', monospace;
                    letter-spacing: 0.22em !important;
                }
                .admin-shell .admin-sidebar-logo:hover {
                    background-color: rgba(251,191,36,0.06) !important;
                }

                /* Nav layout — let footer be pushed to the bottom */
                .admin-shell .admin-nav { flex: 1 0 auto; }

                /* Section headers between nav groups */
                .admin-nav-section {
                    display: flex; align-items: center; gap: 8px;
                    padding: 16px 18px 8px;
                    font-size: 9px; font-weight: 900; letter-spacing: 0.28em;
                    text-transform: uppercase; color: #fcd34d;
                    font-family: ui-monospace, 'JetBrains Mono', monospace;
                    user-select: none;
                }
                .admin-nav-section::after {
                    content: ""; flex: 1; height: 1px;
                    background: linear-gradient(90deg, rgba(251,191,36,0.35), transparent);
                }
                [dir='rtl'] .admin-nav-section::after {
                    background: linear-gradient(270deg, rgba(251,191,36,0.35), transparent);
                }
                .admin-nav > .admin-nav-section:first-child { padding-top: 6px; }

                /* Override existing cyan active state → amber instrument bar + soft halo */
                .admin-shell .admin-nav-link.is-active {
                    background: linear-gradient(90deg, rgba(251,191,36,0.18), rgba(251,191,36,0.04)) !important;
                    color: #fff !important;
                    box-shadow:
                        inset 0 1px 0 rgba(255,255,255,0.06),
                        0 6px 20px -8px rgba(251,191,36,0.35);
                }
                .admin-shell .admin-nav-link.is-active::before {
                    background: linear-gradient(180deg, #fbbf24, #f59e0b) !important;
                    width: 3px !important;
                    inset-block: 0.5rem !important;
                    box-shadow: 0 0 10px rgba(251,191,36,0.65);
                    border-radius: 0 2px 2px 0 !important;
                }
                /* Simpler icon treatment — no cyan tile, just amber-cream icon */
                .admin-shell .admin-nav-icon {
                    background-color: transparent !important;
                    color: rgba(255,255,255,0.55) !important;
                    width: 1.75rem !important; height: 1.75rem !important; flex-basis: 1.75rem !important;
                }
                .admin-shell .admin-nav-link:hover .admin-nav-icon,
                .admin-shell .admin-nav-link.is-active .admin-nav-icon {
                    background-color: transparent !important;
                    color: #fcd34d !important;
                }
                .admin-shell .admin-nav-link:hover {
                    background-color: rgba(255,255,255,0.05) !important;
                }

                /* ─────── CLICK SWEEP ANIMATION (subtle amber light, left → right) ─────── */
                .admin-nav-sweep-clip {
                    position: absolute; inset: 0;
                    overflow: hidden; pointer-events: none;
                    border-radius: inherit;
                }
                .admin-nav-sweep {
                    position: absolute; top: 0; left: -60%; width: 60%; height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(251,191,36,0.32), transparent);
                    opacity: 0;
                    will-change: left, opacity;
                }
                .admin-nav-link.admin-nav-sweep-active .admin-nav-sweep {
                    animation: admin-nav-sweep 650ms ease-out forwards;
                }
                @keyframes admin-nav-sweep {
                    0%   { left: -60%; opacity: 0; }
                    12%  { opacity: 1; }
                    88%  { opacity: 1; }
                    100% { left: 100%; opacity: 0; }
                }
                /* RTL — sweep travels right → left */
                [dir='rtl'] .admin-nav-sweep { left: auto; right: -60%; }
                [dir='rtl'] .admin-nav-link.admin-nav-sweep-active .admin-nav-sweep {
                    animation-name: admin-nav-sweep-rtl;
                }
                @keyframes admin-nav-sweep-rtl {
                    0%   { right: -60%; opacity: 0; }
                    12%  { opacity: 1; }
                    88%  { opacity: 1; }
                    100% { right: 100%; opacity: 0; }
                }
                @media (prefers-reduced-motion: reduce) {
                    .admin-nav-link.admin-nav-sweep-active .admin-nav-sweep { animation: none; }
                }

                /* ─────── SIDEBAR FOOTER ─────── */
                .admin-sidebar-footer {
                    flex: 0 0 auto;
                    margin-top: auto;
                    padding: 12px 18px;
                    border-top: 1px solid rgba(255,255,255,0.06);
                    display: flex; align-items: center; justify-content: space-between; gap: 8px;
                    font-family: ui-monospace, 'JetBrains Mono', monospace;
                    font-size: 10px; color: rgba(255,255,255,0.45);
                }
                .admin-sidebar-status {
                    display: inline-flex; align-items: center; gap: 6px;
                    padding: 4px 8px; border-radius: 999px;
                    background: rgba(16,185,129,0.10); color: #6ee7b7;
                    border: 1px solid rgba(16,185,129,0.25);
                    font-size: 9px; font-weight: 900; letter-spacing: 0.18em; text-transform: uppercase;
                    white-space: nowrap;
                }
                .admin-sidebar-status-dot {
                    width: 6px; height: 6px; border-radius: 999px; background: #34d399;
                    box-shadow: 0 0 6px rgba(52,211,153,0.7);
                }
                /* Hide section headers and footer in collapsed sidebar */
                .admin-shell.admin-sidebar-collapsed .admin-nav-section,
                .admin-sidebar-precollapsed .admin-shell .admin-nav-section,
                .admin-shell.admin-sidebar-collapsed .admin-sidebar-footer,
                .admin-sidebar-precollapsed .admin-shell .admin-sidebar-footer {
                    display: none;
                }
            </style>
            <script nonce="{{ $cspNonce }}">
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

                        if (window.matchMedia('(min-width: 1024px)').matches && localStorage.getItem('admin-sidebar-collapsed') === '1') {
                            document.documentElement.classList.add('admin-sidebar-precollapsed');
                        }
                    } catch (error) {
                        document.documentElement.classList.remove('dark');
                    }
                })();
            </script>
        @endif
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        <x-loading-overlay message="{{ __('Processing, please wait...') }}" variant="full" />

        @if(request()->routeIs('admin.*'))
            @php
                $isRtl = in_array(app()->getLocale(), ['ar', 'ku'], true);
                $adminUser = auth()->user();
                $adminAvatarInitial = strtoupper(substr((string) ($adminUser?->name ?: 'A'), 0, 1));
                $adminProfilePhotoUrl = !empty($adminUser?->profile_photo_path)
                    ? asset('storage/' . ltrim((string) $adminUser->profile_photo_path, '/'))
                    : null;

                // Shared admin topbar page-title mapping — keeps every admin page header consistent
                $adminPageTitlePatterns = [
                    'admin.dashboard'              => __('Dashboard'),
                    'admin.revenue.*'              => __('Revenue Analytics'),
                    'admin.analytics.*'            => __('Site Analytics'),
                    'admin.search-insights.*'      => __('Search Insights'),
                    'admin.products.*'             => __('Products'),
                    'admin.categories.*'           => __('Categories'),
                    'admin.vehicle-fitments.*'     => __('Vehicle Finder'),
                    'admin.reviews.*'              => __('Customer Reviews'),
                    'admin.inventory.*'            => __('Inventory Movements'),
                    'admin.purchase-planning.*'    => __('Purchase Planning'),
                    'admin.stock-requests.*'       => __('Stock Requests'),
                    'admin.dead-stock.*'           => __('Dead Stock'),
                    'admin.orders.*'               => __('Orders Management'),
                    'admin.returns.*'              => __('Returns & Refunds'),
                    'admin.dealers.*'              => __('Dealers'),
                    'admin.users.*'                => __('Users'),
                    'admin.discounts.coupons.*'    => __('Coupon Management'),
                    'admin.discounts.edit'         => __('Coupon Management'),
                    'admin.discounts.rules'        => __('Discount Rules'),
                    'admin.email.*'                => __('Email Center'),
                    'admin.messaging.*'            => __('SMS & WhatsApp Center'),
                    'admin.settings.*'             => __('Settings'),
                    'admin.activity-logs.*'        => __('Activity Logs'),
                    'admin.profile.*'              => __('Profile'),
                    'admin.notifications.*'        => __('Notifications'),
                ];
                $adminPageTitle = __('Admin');
                foreach ($adminPageTitlePatterns as $pattern => $title) {
                    if (request()->routeIs($pattern)) {
                        $adminPageTitle = $title;
                        break;
                    }
                }
            @endphp
            <div
                class="min-h-screen admin-shell"
                data-admin-shell
                data-admin-sidebar-collapsed-class="admin-sidebar-collapsed"
                data-sidebar-storage-key="admin-sidebar-collapsed"
            >
                <!-- Mobile Overlay -->
                <div
                    hidden
                    class="admin-sidebar-backdrop fixed inset-0 bg-slate-950/55 lg:hidden"
                    aria-hidden="true"
                    data-admin-sidebar-backdrop
                ></div>

                <!-- Sidebar -->
                <aside
                    id="admin-sidebar"
                    data-admin-sidebar
                    class="admin-sidebar fixed inset-y-0 text-slate-100 h-screen overflow-y-auto overflow-x-hidden overscroll-contain scrollbar-hide"
                    aria-hidden="false"
                    aria-label="{{ __('Admin navigation') }}"
                >
                    <div class="admin-sidebar-header" data-admin-sidebar-header>
                        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-logo focus:outline-none" data-admin-sidebar-logo aria-label="{{ __('Go to admin dashboard') }}">
                            <x-brand-mark
                                :logo-url="$systemSettings['site_logo_url'] ?? null"
                                :brand="$systemSettings['site_name'] ?? 'YallaSpare'"
                                wrapper-class="app-logo-mark logo-remove-white-bg"
                                img-class="h-full w-auto object-contain"
                                fallback-class="inline-flex h-full w-full items-center justify-center rounded-lg bg-slate-800"
                                fallback-text-class="text-sm font-semibold text-white"
                            />
                            <span class="admin-sidebar-brand-block">
                                <span class="admin-sidebar-brand-copy app-logo-text truncate" data-admin-sidebar-logo-text>{{ $systemSettings['site_name'] ?? 'YallaSpare' }}</span>
                                <span class="admin-sidebar-meta text-[10px] uppercase tracking-widest" data-admin-sidebar-meta>{{ __('Admin · Live') }}</span>
                            </span>
                        </a>
                        <button
                            type="button"
                            class="admin-sidebar-toggle hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/10 text-slate-100 transition hover:border-white/20 hover:bg-white/15 hover:text-white lg:inline-flex"
                            aria-expanded="true"
                            aria-label="{{ __('Collapse sidebar') }}"
                            title="{{ __('Collapse sidebar') }}"
                            aria-controls="admin-sidebar"
                            data-expand-label="{{ __('Expand sidebar') }}"
                            data-collapse-label="{{ __('Collapse sidebar') }}"
                            data-admin-sidebar-toggle
                        >
                            <i class="fas {{ $isRtl ? 'fa-angles-right' : 'fa-angles-left' }} text-sm admin-toggle-collapse-icon"></i>
                            <i class="fas {{ $isRtl ? 'fa-angles-left' : 'fa-angles-right' }} text-sm admin-toggle-expand-icon"></i>
                        </button>
                        <button
                            type="button"
                            class="admin-sidebar-toggle inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/10 text-slate-100 transition hover:bg-white/15 lg:hidden"
                            aria-controls="admin-sidebar"
                            aria-label="{{ __('Collapse sidebar') }}"
                            title="{{ __('Collapse sidebar') }}"
                            data-admin-mobile-sidebar-close
                        >
                            <i class="fas fa-xmark text-sm"></i>
                        </button>
                    </div>
                    @php
                        $navItem = function (bool $active) {
                            return $active
                                ? 'is-active text-white'
                                : 'text-slate-300';
                        };
                        $adminUserForNav = auth()->user();
                        $canDashboard  = $adminUserForNav?->can(\App\Models\User::PERMISSION_DASHBOARD_VIEW);
                        $canCatalog    = $adminUserForNav?->can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE);
                        $canOrders     = $adminUserForNav?->can(\App\Models\User::PERMISSION_ORDERS_MANAGE);
                        $canFinance    = $adminUserForNav?->can(\App\Models\User::PERMISSION_FINANCE_VIEW);
                        $canStock      = $adminUserForNav?->can(\App\Models\User::PERMISSION_STOCK_MANAGE);
                        $canDealers    = $adminUserForNav?->can('manage-dealers');
                        $canFinanceMgr = $adminUserForNav?->can(\App\Models\User::PERMISSION_FINANCE_MANAGE);
                        $canSettings   = $adminUserForNav?->can(\App\Models\User::PERMISSION_SETTINGS_MANAGE);
                        $canUsersView  = $adminUserForNav?->can('viewAny', \App\Models\User::class);
                        $canActLogs    = $adminUserForNav?->can(\App\Models\User::PERMISSION_ACTIVITY_LOGS_VIEW);
                        $hasAnalytics  = $canDashboard || $canFinance || $canStock;
                        $hasMarketing  = $canFinanceMgr || $canSettings;
                        $hasAdminGrp   = $canUsersView || $canSettings || $canActLogs;
                    @endphp
                    <nav class="admin-nav space-y-1.5" aria-label="{{ __('Admin sections') }}">
                        {{-- ── OPERATIONS ── --}}
                        <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Operations') }}</span></div>
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="admin-nav-link {{ $navItem(request()->routeIs('admin.dashboard')) }}"
                            data-admin-sidebar-tooltip="{{ __('Dashboard') }}"
                            @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif
                        >
                            <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></span>
                            <span class="admin-nav-label">{{ __('Dashboard') }}</span>
                        </a>
                        @can(\App\Models\User::PERMISSION_ORDERS_MANAGE)
                            <a
                                href="{{ route('admin.orders.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.orders.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Orders Management') }}"
                                @if(request()->routeIs('admin.orders.*')) aria-current="page" @endif
                            >
                                <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                                <span class="admin-nav-label">{{ __('Orders Management') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.returns.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.returns.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Returns & Refunds') }}"
                                @if(request()->routeIs('admin.returns.*')) aria-current="page" @endif
                            >
                                <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-rotate-left"></i></span>
                                <span class="admin-nav-label">{{ __('Returns & Refunds') }}</span>
                            </a>
                        @endcan

                        {{-- ── CATALOG ── --}}
                        @if($canCatalog)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Catalog') }}</span></div>
                            <a
                                href="{{ route('admin.products.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.products.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Products') }}"
                                @if(request()->routeIs('admin.products.*')) aria-current="page" @endif
                            >
                                <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-box"></i></span>
                                <span class="admin-nav-label">{{ __('Products') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.categories.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.categories.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Categories') }}"
                                @if(request()->routeIs('admin.categories.*')) aria-current="page" @endif
                            >
                                <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                                <span class="admin-nav-label">{{ __('Categories') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.vehicle-fitments.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.vehicle-fitments.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Vehicle Finder') }}"
                                @if(request()->routeIs('admin.vehicle-fitments.*')) aria-current="page" @endif
                            >
                                <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-car-side"></i></span>
                                <span class="admin-nav-label">{{ __('Vehicle Finder') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.reviews.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.reviews.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Customer Reviews') }}"
                                @if(request()->routeIs('admin.reviews.*')) aria-current="page" @endif
                            >
                                <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-star"></i></span>
                                <span class="admin-nav-label">{{ __('Customer Reviews') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.dead-stock.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.dead-stock.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Dead Stock') }}"
                                @if(request()->routeIs('admin.dead-stock.*')) aria-current="page" @endif
                            >
                                <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-boxes-stacked"></i></span>
                                <span class="admin-nav-label">{{ __('Dead Stock') }}</span>
                            </a>
                        @endif

                        {{-- ── ANALYTICS ── --}}
                        @if($hasAnalytics)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Analytics') }}</span></div>
                            @can(\App\Models\User::PERMISSION_FINANCE_VIEW)
                                <a
                                    href="{{ route('admin.revenue.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.revenue.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Revenue') }}"
                                    @if(request()->routeIs('admin.revenue.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-sack-dollar"></i></span>
                                    <span class="admin-nav-label">{{ __('Revenue') }}</span>
                                </a>
                            @endcan
                            @can(\App\Models\User::PERMISSION_DASHBOARD_VIEW)
                                <a
                                    href="{{ route('admin.analytics.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.analytics.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Site Analytics') }}"
                                    @if(request()->routeIs('admin.analytics.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></span>
                                    <span class="admin-nav-label">{{ __('Site Analytics') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.search-insights.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.search-insights.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Search Insights') }}"
                                    @if(request()->routeIs('admin.search-insights.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-magnifying-glass-chart"></i></span>
                                    <span class="admin-nav-label">{{ __('Search Insights') }}</span>
                                </a>
                            @endcan
                            @can(\App\Models\User::PERMISSION_STOCK_MANAGE)
                                <a
                                    href="{{ route('admin.inventory.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.inventory.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Inventory') }}"
                                    @if(request()->routeIs('admin.inventory.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-warehouse"></i></span>
                                    <span class="admin-nav-label">{{ __('Inventory') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.purchase-planning.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.purchase-planning.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Purchase Planning') }}"
                                    @if(request()->routeIs('admin.purchase-planning.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-cart-flatbed"></i></span>
                                    <span class="admin-nav-label">{{ __('Purchase Planning') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.stock-requests.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.stock-requests.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Stock Requests') }}"
                                    @if(request()->routeIs('admin.stock-requests.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-bell"></i></span>
                                    <span class="admin-nav-label">{{ __('Stock Requests') }}</span>
                                </a>
                            @endcan
                        @endif

                        {{-- ── PARTNERS ── --}}
                        @if($canDealers)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Partners') }}</span></div>
                            <a
                                href="{{ route('admin.dealers.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.dealers.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Dealers') }}"
                                @if(request()->routeIs('admin.dealers.*')) aria-current="page" @endif
                            >
                                <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-handshake"></i></span>
                                <span class="admin-nav-label">{{ __('Dealers') }}</span>
                            </a>
                        @endif

                        {{-- ── MARKETING ── --}}
                        @if($hasMarketing)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Marketing') }}</span></div>
                            @can(\App\Models\User::PERMISSION_FINANCE_MANAGE)
                                <a
                                    href="{{ route('admin.discounts.edit') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.discounts.edit') || request()->routeIs('admin.discounts.coupons.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Coupon Management') }}"
                                    @if(request()->routeIs('admin.discounts.edit') || request()->routeIs('admin.discounts.coupons.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-tags"></i></span>
                                    <span class="admin-nav-label">{{ __('Coupon Management') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.discounts.rules') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.discounts.rules')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Discount Rules') }}"
                                    @if(request()->routeIs('admin.discounts.rules')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-percent"></i></span>
                                    <span class="admin-nav-label">{{ __('Discount Rules') }}</span>
                                </a>
                            @endcan
                            @can(\App\Models\User::PERMISSION_SETTINGS_MANAGE)
                                <a
                                    href="{{ route('admin.email.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.email.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Email Center') }}"
                                    @if(request()->routeIs('admin.email.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-envelope-open-text"></i></span>
                                    <span class="admin-nav-label">{{ __('Email Center') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.messaging.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.messaging.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('SMS & WhatsApp Center') }}"
                                    @if(request()->routeIs('admin.messaging.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-comments"></i></span>
                                    <span class="admin-nav-label">{{ __('SMS & WhatsApp Center') }}</span>
                                </a>
                            @endcan
                        @endif

                        {{-- ── ADMIN ── --}}
                        @if($hasAdminGrp)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Admin') }}</span></div>
                            @can('viewAny', \App\Models\User::class)
                                <a
                                    href="{{ route('admin.users.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.users.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Users') }}"
                                    @if(request()->routeIs('admin.users.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-users"></i></span>
                                    <span class="admin-nav-label">{{ __('Users') }}</span>
                                </a>
                            @endcan
                            @can(\App\Models\User::PERMISSION_SETTINGS_MANAGE)
                                <a
                                    href="{{ route('admin.settings.edit') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.settings.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Settings') }}"
                                    @if(request()->routeIs('admin.settings.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-gear"></i></span>
                                    <span class="admin-nav-label">{{ __('Settings') }}</span>
                                </a>
                            @endcan
                            @can(\App\Models\User::PERMISSION_ACTIVITY_LOGS_VIEW)
                                <a
                                    href="{{ route('admin.activity-logs.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.activity-logs.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Activity Logs') }}"
                                    @if(request()->routeIs('admin.activity-logs.*')) aria-current="page" @endif
                                >
                                    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                                    <span class="admin-nav-label">{{ __('Activity Logs') }}</span>
                                </a>
                            @endcan
                        @endif
                    </nav>

                    {{-- Sidebar footer — version + system status pill --}}
                    <div class="admin-sidebar-footer" aria-hidden="true">
                        <span class="admin-sidebar-version">v {{ config('app.version', '2.4.1') }}</span>
                        <span class="admin-sidebar-status">
                            <span class="admin-sidebar-status-dot"></span>
                            <span>{{ __('All systems') }}</span>
                        </span>
                    </div>
                </aside>

                <!-- Main Content -->
                <div
                    class="admin-main min-h-screen flex flex-col"
                    data-admin-main
                >
                    <header class="admin-topbar">
                        <div class="admin-topbar-decor" aria-hidden="true">
                            <div class="admin-topbar-hairline"></div>
                            <div class="admin-topbar-glow"></div>
                        </div>

                        <div class="relative z-10 flex min-w-0 items-center justify-between gap-3 px-3 sm:px-5 lg:px-7" style="min-height: 72px; padding-top: 10px; padding-bottom: 10px;">
                            {{-- LEFT: menu + YS badge + brand --}}
                            <div class="flex min-w-0 items-center gap-2 shrink-0">
                                {{-- Desktop expand (visibility controlled by app.css — hidden until sidebar collapsed) --}}
                                <button
                                    type="button"
                                    class="admin-sidebar-top-expand topbar-action"
                                    aria-expanded="false"
                                    aria-controls="admin-sidebar"
                                    aria-label="{{ __('Expand sidebar') }}"
                                    title="{{ __('Expand sidebar') }}"
                                    data-admin-sidebar-expand
                                >
                                    <i class="fas {{ $isRtl ? 'fa-angles-left' : 'fa-angles-right' }} text-sm"></i>
                                </button>
                                {{-- Mobile menu toggle --}}
                                <button
                                    type="button"
                                    class="admin-mobile-sidebar-toggle topbar-action inline-flex lg:hidden"
                                    aria-expanded="false"
                                    aria-controls="admin-sidebar"
                                    aria-label="{{ __('Expand sidebar') }}"
                                    title="{{ __('Expand sidebar') }}"
                                    data-admin-mobile-sidebar-toggle
                                >
                                    <i class="fas fa-bars text-sm"></i>
                                </button>
                            </div>

                            {{-- CENTER: shared page title (auto-generated from route name for consistency) --}}
                            <div class="hidden md:flex flex-1 items-center justify-center min-w-0 px-3">
                                <div class="flex flex-col items-center leading-tight min-w-0 max-w-full">
                                    <h2 class="text-lg md:text-xl lg:text-2xl font-black text-white tracking-tight whitespace-nowrap truncate max-w-full">{{ $adminPageTitle }}</h2>
                                    <div class="mt-1 inline-flex items-center gap-2 text-[10px] lg:text-[11px] uppercase tracking-[0.2em] text-white/55 font-bold whitespace-nowrap truncate max-w-full" style="font-family: 'JetBrains Mono', ui-monospace, monospace;">
                                        <span class="topbar-pulse-ring text-emerald-400 inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                        <span class="text-amber-300">{{ __('ADMIN · LIVE') }}</span>
                                        <span class="text-white/25" aria-hidden="true">·</span>
                                        <span>{{ now()->format('l, F d · Y') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- MOBILE: compact title --}}
                            <div class="flex md:hidden flex-1 items-center justify-center min-w-0 px-2">
                                <h2 class="text-base font-black text-white tracking-tight whitespace-nowrap truncate max-w-full">{{ $adminPageTitle }}</h2>
                            </div>

                            {{-- RIGHT: actions — all 36px tall, consistent spacing --}}
                            <div class="admin-topbar-actions flex min-w-0 items-center gap-1.5 shrink-0">
                                {{-- Language switcher (dark variant, hidden on small) --}}
                                <div class="hidden sm:flex">
                                    <x-language-switcher variant="dark" />
                                </div>

                                <button
                                    id="adminThemeToggle"
                                    type="button"
                                    class="topbar-action inline-flex"
                                    aria-label="{{ __('Toggle dark mode') }}"
                                >
                                    <i id="adminThemeIcon" class="fas fa-moon text-[13px]"></i>
                                </button>
                                <div class="relative">
                                    <button
                                        id="adminNotificationsButton"
                                        type="button"
                                        class="topbar-action inline-flex relative"
                                        aria-label="{{ __('Notifications') }}"
                                    >
                                        <i class="fas fa-bell text-[13px]"></i>
                                        <span
                                            id="adminNotificationsBadge"
                                            class="hidden absolute -top-0.5 {{ $isRtl ? '-left-0.5' : '-right-0.5' }} min-w-[15px] h-[15px] px-1 rounded-full bg-rose-500 text-white text-[9px] font-bold items-center justify-center"
                                            style="font-family: 'JetBrains Mono', ui-monospace, monospace;"
                                        >
                                            0
                                        </span>
                                    </button>

                                    <div
                                        id="adminNotificationsDropdown"
                                        class="admin-popover-enter hidden absolute {{ $isRtl ? 'left-0' : 'right-0' }} mt-2 w-[360px] max-w-[92vw] bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden z-30 dark:bg-slate-900 dark:border-slate-800 dark:shadow-black/30"
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
                                    class="topbar-profile inline-flex items-center gap-2 pl-1 pr-2 sm:pr-3 rounded-lg shrink-0"
                                    style="height: 36px;"
                                    title="{{ __('Profile') }}"
                                >
                                    @if ($adminProfilePhotoUrl)
                                        <img src="{{ $adminProfilePhotoUrl }}" alt="{{ __(':name profile photo', ['name' => $adminUser->name]) }}" class="h-7 w-7 rounded-md object-cover">
                                    @else
                                        <span class="h-7 w-7 rounded-md grid place-items-center emboss-badge text-[#04042a] text-[11px] font-black" style="font-family: 'JetBrains Mono', ui-monospace, monospace;">
                                            {{ $adminAvatarInitial }}
                                        </span>
                                    @endif
                                    <span class="hidden lg:inline text-xs font-bold text-white leading-none">{{ __('Profile') }}</span>
                                </a>

                                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="topbar-action topbar-logout inline-flex"
                                        aria-label="{{ __('Log Out') }}"
                                        title="{{ __('Log Out') }}"
                                    >
                                        <i class="fas fa-right-from-bracket text-[13px]"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </header>

                    <main class="admin-content admin-page-enter flex-1 px-3 py-5 sm:px-6 sm:py-6 lg:px-8">
                        {{ $slot }}
                    </main>

                    <div id="adminDangerModal" class="admin-danger-backdrop fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
                        <div class="admin-danger-surface w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-slate-800 dark:bg-slate-900">
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
            <script nonce="{{ $cspNonce }}">
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

                    const setClass = (element, className) => {
                        element.className = className;
                        return element;
                    };

                    const appendText = (parent, tagName, className, text) => {
                        const element = setClass(document.createElement(tagName), className);
                        element.textContent = text || '';
                        parent.appendChild(element);
                        return element;
                    };

                    const safeNotificationUrl = (url) => {
                        try {
                            const parsed = new URL(String(url || '#'), window.location.origin);
                            return parsed.origin === window.location.origin ? parsed.toString() : '#';
                        } catch (error) {
                            return '#';
                        }
                    };

                    const renderEmpty = (container, message) => {
                        container.textContent = '';
                        appendText(container, 'p', 'text-xs text-slate-400 dark:text-slate-500', message);
                    };

                    const renderItems = (container, items) => {
                        if (!items || items.length === 0) {
                            return false;
                        }

                        container.textContent = '';
                        items.forEach((item, index) => {
                            const wrapper = setClass(
                                document.createElement('div'),
                                `admin-notification-item block rounded-lg px-2 py-2 ${item.read ? 'bg-white dark:bg-slate-900/60' : 'bg-indigo-50/50 dark:bg-indigo-500/10'} hover:bg-slate-50 dark:hover:bg-slate-800/80 transition`
                            );
                            wrapper.style.setProperty('--admin-item-index', String(index));

                            const link = setClass(document.createElement('a'), 'block');
                            link.setAttribute('href', safeNotificationUrl(item.url));
                            appendText(link, 'p', 'text-sm font-medium text-slate-800 dark:text-slate-100', item.title);
                            appendText(link, 'p', 'text-xs text-slate-500 dark:text-slate-400', item.subtitle);

                            const metaRow = setClass(document.createElement('div'), 'flex items-center justify-between mt-1');
                            appendText(metaRow, 'p', 'text-[11px] text-slate-400 dark:text-slate-500', item.meta || '');
                            appendText(metaRow, 'p', 'text-[11px] text-slate-400 dark:text-slate-500', item.time || '');
                            link.appendChild(metaRow);
                            wrapper.appendChild(link);

                            if (!item.read) {
                                const markReadButton = setClass(
                                    document.createElement('button'),
                                    'admin-mark-read mt-2 text-[11px] font-semibold text-indigo-600 hover:text-indigo-700'
                                );
                                markReadButton.setAttribute('type', 'button');
                                markReadButton.setAttribute('data-key', String(item.key || ''));
                                markReadButton.textContent = markReadLabel;
                                wrapper.appendChild(markReadButton);
                            }

                            container.appendChild(wrapper);
                        });
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
                        const submitter = event.submitter instanceof HTMLButtonElement ? event.submitter : null;
                        window.adminDangerConfirm({
                            title: submitter?.dataset.dangerTitle || form.dataset.dangerTitle || 'Delete Coupon',
                            description: submitter?.dataset.dangerDescription || form.dataset.dangerDescription || 'This action is permanent and cannot be undone.',
                        }).then((confirmed) => {
                            if (!confirmed) return;
                            form.dataset.dangerConfirmed = '1';
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit(submitter || undefined);
                            } else {
                                form.submit();
                            }
                        });
                    }, true);
                })();

                /* Sidebar click sweep — adds a brief amber light pass on click,
                   restarts on each click, removes itself when the animation ends. */
                (function () {
                    const SWEEP_CLASS = 'admin-nav-sweep-active';
                    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                    const ensureSweepNode = (link) => {
                        if (link.querySelector(':scope > .admin-nav-sweep-clip')) return;
                        const clip = document.createElement('span');
                        clip.className = 'admin-nav-sweep-clip';
                        clip.setAttribute('aria-hidden', 'true');
                        const sweep = document.createElement('span');
                        sweep.className = 'admin-nav-sweep';
                        clip.appendChild(sweep);
                        link.insertBefore(clip, link.firstChild);
                    };

                    document.querySelectorAll('.admin-nav-link').forEach(ensureSweepNode);

                    if (reduceMotion) {
                        return;
                    }

                    document.addEventListener('click', (event) => {
                        const target = event.target instanceof Element ? event.target : null;
                        if (!target) return;
                        const link = target.closest('.admin-nav-link');
                        if (!link) return;
                        ensureSweepNode(link);
                        link.classList.remove(SWEEP_CLASS);
                        // Force reflow so the animation restarts on rapid re-clicks.
                        void link.offsetWidth;
                        link.classList.add(SWEEP_CLASS);
                        const clear = () => {
                            link.classList.remove(SWEEP_CLASS);
                            link.removeEventListener('animationend', clear);
                        };
                        link.addEventListener('animationend', clear);
                        // Fallback in case the page navigates before animationend fires.
                        setTimeout(clear, 800);
                    });
                })();
            </script>
        @endif
        @stack('scripts')
    </body>
</html>
