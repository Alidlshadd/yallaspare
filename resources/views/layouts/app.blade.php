<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $systemSettings['site_name'] ?? config('app.name', 'Laravel') }}</title>
        @include('partials.brand-head')

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
                    background: linear-gradient(135deg, #04041f 0%, #070740 50%, #070740 100%) !important;
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
                    background: linear-gradient(180deg, #ff8a3d 0%, #e65c00 100%);
                }
                /* Clip-only wrapper for decorations that extend past the header bounds.
                   Header itself must stay overflow:visible so dropdowns can escape. */
                .admin-topbar-decor {
                    position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0;
                }
                .admin-topbar-hairline {
                    position: absolute; top: 0; left: 0; right: 0; height: 1px;
                    background: linear-gradient(90deg, transparent, rgb(255 138 61 / 0.45), transparent);
                    z-index: 1;
                }
                .admin-topbar-glow {
                    position: absolute; top: -72px; right: -72px; height: 168px; width: 168px;
                    border-radius: 9999px; background: rgb(255 138 61 / 0.07); filter: blur(60px); pointer-events: none;
                }
                .emboss-badge {
                    background:
                        radial-gradient(circle at 30% 25%, rgba(255,255,255,0.22), transparent 60%),
                        linear-gradient(135deg, #ff8a3d 0%, #e65c00 100%);
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
                    border: 1px solid rgb(255 138 61 / 0.40);
                    background: rgb(255 138 61 / 0.07);
                    color: #fff;
                    transition: all .15s ease;
                }
                .topbar-profile:hover {
                    background: rgb(255 138 61 / 0.14);
                    border-color: rgb(255 138 61 / 0.65);
                }
                .topbar-pulse-ring { position: relative; }
                .topbar-pulse-ring::after {
                    content: ""; position: absolute; inset: -4px; border-radius: 999px;
                    border: 2px solid currentColor; opacity: 0.35; animation: tb-ring 1.6s ease-out infinite;
                }
                @keyframes tb-ring { 0% { transform: scale(0.6); opacity: 0.6; } 100% { transform: scale(1.6); opacity: 0; } }

                /* ═══════════ ADMIN SIDEBAR — COMMAND CENTER ═══════════
                   One dark navy field, not a stack of cards. Depth comes from
                   light: a slow gradient down the panel, a cool wash at the
                   top corner, and a cursor-following highlight that is felt
                   more than seen. Orange is spent on one thing only — the rail
                   that marks where you are. */
                .admin-shell .admin-sidebar {
                    --sb-pad: 14px;
                    --sb-row-h: 44px;
                    --sb-radius: 10px;
                    --sb-line: rgba(255, 255, 255, 0.07);
                    --sb-text: rgba(236, 238, 251, 0.78);
                    --sb-text-dim: rgba(236, 238, 251, 0.42);
                    --sb-flame: #ff8a3d;
                    --sb-ease: cubic-bezier(0.16, 1, 0.3, 1);
                    --sb-ease-micro: cubic-bezier(0.2, 0, 0, 1);
                    /* Spotlight origin, written by the motion module. */
                    --sb-x: 50%;
                    --sb-y: -200px;

                    position: fixed;
                    display: flex;
                    flex-direction: column;
                    isolation: isolate;
                    background:
                        radial-gradient(120% 55% at 0% 0%, #12124f 0%, rgba(18, 18, 79, 0) 62%),
                        linear-gradient(180deg, #070740 0%, #06062f 58%, #04041f 100%) !important;
                }

                /* The cursor highlight. A separate layer so it can be moved
                   without repainting anything that carries text. */
                .admin-shell .admin-sidebar::before {
                    content: "";
                    position: absolute;
                    inset: 0;
                    z-index: 0;
                    pointer-events: none;
                    opacity: 0;
                    transition: opacity 260ms var(--sb-ease-micro);
                    background: radial-gradient(
                        circle 190px at var(--sb-x) var(--sb-y),
                        rgba(255, 255, 255, 0.038),
                        transparent 70%
                    );
                }
                .admin-shell .admin-sidebar.is-lit::before { opacity: 1; }

                /* Separation from the content: a hairline, not a border. */
                .admin-shell .admin-sidebar::after {
                    content: "";
                    position: absolute;
                    top: 0;
                    bottom: 0;
                    inset-inline-end: 0;
                    width: 1px;
                    z-index: 2;
                    pointer-events: none;
                    background: linear-gradient(
                        180deg,
                        rgba(255, 255, 255, 0.12),
                        rgba(255, 255, 255, 0.05) 40%,
                        rgba(255, 255, 255, 0.02)
                    );
                }

                .admin-shell .admin-sidebar > * { position: relative; z-index: 1; }

                /* ── brand ─────────────────────────────────────────────── */
                .admin-shell .admin-sidebar-header {
                    padding: 18px var(--sb-pad) 14px;
                    border-bottom: 1px solid var(--sb-line) !important;
                }
                .admin-shell .admin-sidebar-logo {
                    border-radius: var(--sb-radius);
                    transition: background-color 200ms var(--sb-ease-micro);
                }
                .admin-shell .admin-sidebar-logo:hover {
                    background-color: rgba(255, 255, 255, 0.045) !important;
                }
                .admin-shell .admin-sidebar-brand-copy {
                    font-size: 15px;
                    letter-spacing: -0.012em;
                }
                .admin-shell .admin-sidebar-meta {
                    color: var(--sb-text-dim) !important;
                    font-family: ui-monospace, 'JetBrains Mono', monospace;
                    font-size: 9.5px !important;
                    letter-spacing: 0.15em !important;
                }

                /* ── nav ───────────────────────────────────────────────── */
                .admin-shell .admin-nav {
                    flex: 1 0 auto;
                    position: relative;
                    padding: 6px 8px 14px;
                }

                /* The rail. One element for the whole list; the motion module
                   moves it between rows so the eye can follow it there. */
                .admin-shell .admin-nav-rail {
                    position: absolute;
                    inset-inline-start: 0;
                    top: 0;
                    width: 3px;
                    height: var(--sb-row-h);
                    border-radius: 0 3px 3px 0;
                    background: linear-gradient(180deg, var(--sb-flame), #e65c00);
                    transform-origin: center;
                    opacity: 0;
                    pointer-events: none;
                    z-index: 2;
                }
                [dir='rtl'] .admin-shell .admin-nav-rail { border-radius: 3px 0 0 3px; }
                .admin-shell .admin-nav-rail.is-ready { opacity: 1; }

                /* ── section headings ──────────────────────────────────── */
                .admin-nav-section {
                    display: block;
                    padding: 18px 10px 6px;
                    font-size: 10.5px;
                    font-weight: 600;
                    letter-spacing: 0.09em;
                    text-transform: uppercase;
                    color: var(--sb-text-dim);
                    user-select: none;
                }
                .admin-nav > .admin-nav-section:first-child { padding-top: 6px; }
                .admin-nav-section::after { content: none; }

                /* ── rows ──────────────────────────────────────────────── */
                .admin-shell .admin-nav-link {
                    display: flex;
                    align-items: center;
                    gap: 11px;
                    min-height: var(--sb-row-h);
                    padding: 0 10px;
                    border-radius: var(--sb-radius);
                    color: var(--sb-text) !important;
                    font-size: 13.5px;
                    font-weight: 500;
                    letter-spacing: -0.004em;
                    position: relative;
                    background-color: transparent !important;
                    transition: background-color 180ms var(--sb-ease-micro),
                                color 180ms var(--sb-ease-micro);
                }
                .admin-shell .admin-nav-link:hover {
                    background-color: rgba(255, 255, 255, 0.05) !important;
                    color: #fff !important;
                }
                .admin-shell .admin-nav-link:focus-visible {
                    outline: 2px solid var(--sb-flame);
                    outline-offset: -2px;
                }

                /* The current page. The rail carries the accent; the row only
                   lifts off the ground. */
                .admin-shell .admin-nav-link.is-active {
                    background-color: rgba(255, 255, 255, 0.055) !important;
                    background-image: linear-gradient(
                        90deg,
                        rgba(255, 138, 61, 0.07),
                        rgba(255, 138, 61, 0)
                    ) !important;
                    color: #fff !important;
                    font-weight: 600;
                    box-shadow: none;
                }
                [dir='rtl'] .admin-shell .admin-nav-link.is-active {
                    background-image: linear-gradient(
                        270deg,
                        rgba(255, 138, 61, 0.07),
                        rgba(255, 138, 61, 0)
                    ) !important;
                }
                /* Without the moving rail — reduced motion, or before the
                   script is up — the current row keeps a plain bar of its own.
                   The panel must never be left with no answer to "where am I". */
                .admin-shell .admin-nav-link.is-active::before {
                    content: "";
                    position: absolute;
                    inset-inline-start: -8px;
                    top: 8px;
                    bottom: 8px;
                    width: 3px;
                    border-radius: 0 3px 3px 0;
                    background: var(--sb-flame);
                }
                [dir='rtl'] .admin-shell .admin-nav-link.is-active::before {
                    border-radius: 3px 0 0 3px;
                }
                .admin-shell .admin-nav.rail-on .admin-nav-link.is-active::before {
                    content: none;
                }

                .admin-nav-label {
                    min-width: 0;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                /* ── icons ─────────────────────────────────────────────── */
                /* Phosphor, one family, two weights layered and cross-faded.
                   No tile, no circle: the glyph sits directly on the panel. */
                .admin-shell .admin-nav-icon.ph {
                    position: relative;
                    display: inline-grid;
                    place-items: center;
                    width: var(--ph-size, 20px);
                    height: var(--ph-size, 20px);
                    flex: none;
                    background: none !important;
                    color: rgba(255, 255, 255, 0.62) !important;
                    transition: color 180ms var(--sb-ease-micro);
                }
                .admin-shell .ph-glyph {
                    grid-area: 1 / 1;
                    width: 100%;
                    height: 100%;
                    transition: opacity 200ms var(--sb-ease-micro);
                }
                .admin-shell .ph-fill { opacity: 0; }
                .admin-shell .admin-nav-link:hover .admin-nav-icon.ph {
                    color: rgba(255, 255, 255, 0.95) !important;
                }
                .admin-shell .admin-nav-link.is-active .admin-nav-icon.ph {
                    color: var(--sb-flame) !important;
                }
                .admin-shell .admin-nav-link.is-active .ph-regular { opacity: 0; }
                .admin-shell .admin-nav-link.is-active .ph-fill { opacity: 1; }

                /* ── badges ────────────────────────────────────────────── */
                .admin-nav-badge {
                    margin-inline-start: auto;
                    flex: none;
                    min-width: 20px;
                    padding: 1px 6px;
                    border-radius: 6px;
                    font-family: ui-monospace, 'JetBrains Mono', monospace;
                    font-size: 10.5px;
                    font-weight: 600;
                    line-height: 1.5;
                    text-align: center;
                    color: var(--sb-text-dim);
                    background: rgba(255, 255, 255, 0.07);
                }
                .admin-nav-badge[data-tone='accent'] {
                    color: #ffc08a;
                    background: rgba(255, 138, 61, 0.16);
                }

                /* ── footer ────────────────────────────────────────────── */
                .admin-sidebar-footer {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px;
                    padding: 10px var(--sb-pad);
                    border-top: 1px solid var(--sb-line);
                }
                .admin-sidebar-version {
                    font-family: ui-monospace, 'JetBrains Mono', monospace;
                    font-size: 10px;
                    color: var(--sb-text-dim);
                }
                .admin-sidebar-status {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 3px 8px;
                    border-radius: 999px;
                    font-size: 10.5px;
                    font-weight: 600;
                    color: #6ee7b7;
                    background: rgba(16, 185, 129, 0.1);
                }
                .admin-sidebar-status-dot {
                    width: 5px;
                    height: 5px;
                    border-radius: 999px;
                    background: #34d399;
                }

                /* ── collapsed ─────────────────────────────────────────── */
                .admin-shell.admin-sidebar-collapsed .admin-nav-section,
                .admin-sidebar-precollapsed .admin-shell .admin-nav-section,
                .admin-shell.admin-sidebar-collapsed .admin-nav-badge,
                .admin-sidebar-precollapsed .admin-shell .admin-nav-badge {
                    display: none;
                }
                .admin-shell.admin-sidebar-collapsed .admin-nav-label,
                .admin-sidebar-precollapsed .admin-shell .admin-nav-label,
                .admin-shell.admin-sidebar-collapsed .admin-sidebar-brand-block,
                .admin-sidebar-precollapsed .admin-shell .admin-sidebar-brand-block {
                    opacity: 0;
                    width: 0;
                    overflow: hidden;
                    pointer-events: none;
                }
                .admin-shell.admin-sidebar-collapsed .admin-nav-link,
                .admin-sidebar-precollapsed .admin-shell .admin-nav-link {
                    justify-content: center;
                    padding-inline: 0;
                    gap: 0;
                }
                .admin-shell.admin-sidebar-collapsed .admin-sidebar-logo,
                .admin-sidebar-precollapsed .admin-shell .admin-sidebar-logo {
                    justify-content: center;
                }
                .admin-shell.admin-sidebar-collapsed .admin-sidebar-version,
                .admin-sidebar-precollapsed .admin-shell .admin-sidebar-version {
                    display: none;
                }
                .admin-shell.admin-sidebar-collapsed .admin-sidebar-footer,
                .admin-sidebar-precollapsed .admin-shell .admin-sidebar-footer {
                    justify-content: center;
                }
                .admin-shell.admin-sidebar-collapsed .admin-sidebar-status span:last-child,
                .admin-sidebar-precollapsed .admin-shell .admin-sidebar-status span:last-child {
                    display: none;
                }

                /* ── collapsed tooltip ─────────────────────────────────── */
                /* Fixed, so the sidebar's own overflow cannot clip it. */
                .admin-sidebar-tip {
                    position: fixed;
                    z-index: 200;
                    pointer-events: none;
                    padding: 6px 10px;
                    border-radius: 9px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    background: #0b0b2e;
                    box-shadow: 0 12px 30px -12px rgba(0, 0, 0, 0.7);
                    color: #eceefb;
                    font-size: 12.5px;
                    font-weight: 500;
                    white-space: nowrap;
                    opacity: 0;
                }

                /* ── edge control ──────────────────────────────────────── */
                .admin-shell .admin-sidebar-toggle {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    height: 28px;
                    width: 28px;
                    border-radius: 999px;
                    border: 1px solid rgba(255, 255, 255, 0.12);
                    background: rgba(255, 255, 255, 0.06);
                    color: rgba(236, 238, 251, 0.75);
                    transition: width 220ms var(--sb-ease),
                                background-color 200ms var(--sb-ease-micro),
                                border-color 200ms var(--sb-ease-micro),
                                color 200ms var(--sb-ease-micro);
                }
                .admin-shell .admin-sidebar-toggle:hover {
                    width: 34px;
                    background: rgba(255, 255, 255, 0.11);
                    border-color: rgba(255, 255, 255, 0.2);
                    color: #fff;
                }
                .admin-shell .admin-sidebar-toggle svg {
                    width: 14px;
                    height: 14px;
                    transition: translate 200ms var(--sb-ease-micro), rotate 300ms var(--sb-ease);
                }
                .admin-shell .admin-sidebar-toggle:hover svg { translate: 2px 0; }
                [dir='rtl'] .admin-shell .admin-sidebar-toggle:hover svg { translate: -2px 0; }
                .admin-shell.admin-sidebar-collapsed .admin-sidebar-toggle svg,
                .admin-sidebar-precollapsed .admin-shell .admin-sidebar-toggle svg {
                    rotate: 180deg;
                }

                /* The control sits on the seam between panel and content, a
                   sibling of both, so the panel's own scrolling cannot clip it
                   and it rides the width variable without being told to. */
                .admin-sidebar-edge {
                    display: none;
                }
                @media (min-width: 768px) {
                    .admin-sidebar-edge {
                        position: fixed;
                        top: 92px;
                        inset-inline-start: calc(var(--sidebar-width) - 14px);
                        z-index: 95;
                        display: inline-flex;
                        background: #0b0b33;
                        transition: inset-inline-start 460ms cubic-bezier(0.16, 1, 0.3, 1),
                                    width 220ms cubic-bezier(0.16, 1, 0.3, 1),
                                    background-color 200ms cubic-bezier(0.2, 0, 0, 1),
                                    border-color 200ms cubic-bezier(0.2, 0, 0, 1),
                                    color 200ms cubic-bezier(0.2, 0, 0, 1);
                    }
                    .admin-sidebar-edge:hover { background: #14144a; }
                }
                /* The glyph points at the panel in either writing direction. */
                [dir='rtl'] .admin-sidebar-edge svg,
                [dir='rtl'] .admin-shell .admin-sidebar-header-toggle svg { scale: -1 1; }

                /* The header control sits beneath the mark once the column is
                   narrow, which is where the eye already is when looking for a
                   way back out. */
                .admin-shell .admin-sidebar-header-toggle { margin-inline-start: auto; }
                .admin-shell.admin-sidebar-collapsed .admin-sidebar-header-toggle,
                .admin-sidebar-precollapsed .admin-shell .admin-sidebar-header-toggle {
                    margin-inline: auto;
                }

                /* ── motion off ────────────────────────────────────────── */
                @media (prefers-reduced-motion: reduce) {
                    .admin-shell .admin-sidebar::before { display: none; }
                    .admin-shell .admin-sidebar *,
                    .admin-shell .admin-sidebar-toggle,
                    .admin-shell .admin-sidebar-toggle svg {
                        transition-duration: 1ms !important;
                    }
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
                    'admin.goals.*'                => __('Progress Center'),
                    'admin.revenue.*'              => __('Revenue Analytics'),
                    'admin.analytics.*'            => __('Site Analytics'),
                    'admin.search-insights.*'      => __('Search Insights'),
                    'admin.products.*'             => __('Products'),
                    'admin.product-brands.*'       => __('Product Brands'),
                    'admin.categories.*'           => __('Categories'),
                    'admin.vehicle-fitments.*'     => __('Vehicle Finder'),
                    'admin.reviews.*'              => __('Customer Reviews'),
                    'admin.inventory.bulk-stock*'  => __('Bulk Stock Adjustment'),
                    'admin.inventory.*'            => __('Inventory Movements'),
                    'admin.purchase-planning.*'    => __('Purchase Planning'),
                    'admin.stock-requests.*'       => __('Product Requests'),
                    'admin.dead-stock.*'           => __('Dead Stock'),
                    'admin.orders.*'               => __('Orders Management'),
                    'admin.returns.*'              => __('Returns & Refunds'),
                    'admin.shipping.*'             => __('Shipping'),
                    'admin.dealers.*'              => __('Dealers'),
                    'admin.users.*'                => __('Users'),
                    'admin.discounts.coupons.*'    => __('Coupon Management'),
                    'admin.discounts.edit'         => __('Coupon Management'),
                    'admin.discounts.rules'        => __('Discount Rules'),
                    'admin.email.*'                => __('Email Center'),
                    'admin.popups.*'               => __('Popups'),
                    'admin.messaging.*'            => config('services.otpiq.whatsapp.admin_visible', true) ? __('SMS & WhatsApp Center') : __('SMS Center'),
                    'admin.whatsapp.*'             => __('Inbound WhatsApp'),
                    'admin.wayl.*'                 => __('WAYL Payments'),
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
                        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-logo focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" data-admin-sidebar-logo aria-label="{{ __('Go to admin dashboard') }}">
                            <x-brand-mark
                                :logo-url="$systemSettings['site_logo_url'] ?? null"
                                :brand="$systemSettings['site_name'] ?? 'YallaSpare'"
                                wrapper-class="app-logo-mark"
                                img-class="h-full w-auto object-contain"
                                fallback-class="inline-flex h-full w-full items-center justify-center rounded-lg bg-slate-800"
                                fallback-text-class="text-sm font-semibold text-white"
                            />
                            <span class="admin-sidebar-brand-block">
                                <x-brand-wordmark :brand="$systemSettings['site_name'] ?? 'YallaSpare'" class="admin-sidebar-brand-copy app-logo-text truncate" data-admin-sidebar-logo-text />
                                <span class="admin-sidebar-meta uppercase" data-admin-sidebar-meta>{{ __('Command Center') }}</span>
                            </span>
                        </a>
                        {{--
                            One control, two jobs, because the panel means
                            different things on either side of the breakpoint:
                            on a phone it is a drawer and this closes it, on a
                            desktop it is a column and this narrows it. The
                            chevron points the way the panel is about to go and
                            turns around once it has gone.
                        --}}
                        <button
                            type="button"
                            class="admin-sidebar-toggle admin-sidebar-header-toggle shrink-0"
                            aria-controls="admin-sidebar"
                            aria-expanded="true"
                            aria-label="{{ __('Collapse sidebar') }}"
                            title="{{ __('Collapse sidebar') }}"
                            data-expand-label="{{ __('Expand sidebar') }}"
                            data-collapse-label="{{ __('Collapse sidebar') }}"
                            data-close-label="{{ __('Close menu') }}"
                            data-admin-sidebar-panel-toggle
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M15 5l-7 7 7 7" />
                            </svg>
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
                        $canGoals      = $adminUserForNav?->can(\App\Models\User::PERMISSION_GOALS_VIEW);
                        $canCatalog    = $adminUserForNav?->can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE);
                        $canOrders     = $adminUserForNav?->can(\App\Models\User::PERMISSION_ORDERS_MANAGE);
                        $canFinance    = $adminUserForNav?->can(\App\Models\User::PERMISSION_FINANCE_VIEW);
                        $canStock      = $adminUserForNav?->can(\App\Models\User::PERMISSION_STOCK_MANAGE);
                        $canStockRequests = $adminUserForNav?->can('stock-requests.manage');
                        $canDealers    = $adminUserForNav?->can('manage-dealers');
                        $canFinanceMgr = $adminUserForNav?->can(\App\Models\User::PERMISSION_FINANCE_MANAGE);
                        $canSettings   = $adminUserForNav?->can(\App\Models\User::PERMISSION_SETTINGS_MANAGE);
                        $canWhatsapp   = $adminUserForNav?->can('manage-whatsapp-webhooks');
                        $canUsersView  = $adminUserForNav?->can('viewAny', \App\Models\User::class);
                        $canActLogs    = $adminUserForNav?->can(\App\Models\User::PERMISSION_ACTIVITY_LOGS_VIEW);
                        // A section header with nothing under it is noise, so each
                        // one asks whether any of its links survived the permissions.
                        $hasStock      = $canStock || $canCatalog;
                        $hasMarketing  = $canFinanceMgr || $canSettings || $canWhatsapp;
                        $hasReports    = $canGoals || $canFinance || $canDashboard;
                        $hasAdminGrp   = $canUsersView || $canSettings || $canActLogs || $canDealers;
                    @endphp
                    <nav class="admin-nav space-y-1.5" aria-label="{{ __('Admin sections') }}">
                        {{-- Marks the current page. One element for the list, moved
                             between rows by the motion module so the eye can follow
                             it rather than find it again. --}}
                        <span class="admin-nav-rail" data-admin-nav-rail aria-hidden="true"></span>
                        {{-- ── OPERATIONS ── what the day is actually spent on --}}
                        <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Operations') }}</span></div>
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="admin-nav-link {{ $navItem(request()->routeIs('admin.dashboard')) }}"
                            data-admin-sidebar-tooltip="{{ __('Dashboard') }}"
                            @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif
                        >
                            <x-ph-icon name="gauge" class="admin-nav-icon" />
                            <span class="admin-nav-label">{{ __('Dashboard') }}</span>
                        </a>
                        @can(\App\Models\User::PERMISSION_ORDERS_MANAGE)
                            <a
                                href="{{ route('admin.orders.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.orders.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Orders Management') }}"
                                @if(request()->routeIs('admin.orders.*')) aria-current="page" @endif
                            >
                                <x-ph-icon name="receipt" class="admin-nav-icon" />
                                <span class="admin-nav-label">{{ __('Orders Management') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.returns.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.returns.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Returns & Refunds') }}"
                                @if(request()->routeIs('admin.returns.*')) aria-current="page" @endif
                            >
                                <x-ph-icon name="arrow-u-down-left" class="admin-nav-icon" />
                                <span class="admin-nav-label">{{ __('Returns & Refunds') }}</span>
                            </a>
                        @endcan
                        {{-- Was a section of its own, "Customer Demand", holding this one link. --}}
                        @if($canStockRequests)
                            <a
                                href="{{ route('admin.stock-requests.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.stock-requests.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Product Requests') }}"
                                @if(request()->routeIs('admin.stock-requests.*')) aria-current="page" @endif
                            >
                                <x-ph-icon name="bell-ringing" class="admin-nav-icon" />
                                <span class="admin-nav-label">{{ __('Product Requests') }}</span>
                            </a>
                        @endif

                        {{-- ── CATALOG ── what the shop sells --}}
                        @if($canCatalog)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Catalog') }}</span></div>
                            <a
                                href="{{ route('admin.products.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.products.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Products') }}"
                                @if(request()->routeIs('admin.products.*')) aria-current="page" @endif
                            >
                                <x-ph-icon name="cube" class="admin-nav-icon" />
                                <span class="admin-nav-label">{{ __('Products') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.categories.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.categories.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Categories') }}"
                                @if(request()->routeIs('admin.categories.*')) aria-current="page" @endif
                            >
                                <x-ph-icon name="grid-four" class="admin-nav-icon" />
                                <span class="admin-nav-label">{{ __('Categories') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.product-brands.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.product-brands.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Product Brands') }}"
                                @if(request()->routeIs('admin.product-brands.*')) aria-current="page" @endif
                            >
                                <x-ph-icon name="tag" class="admin-nav-icon" />
                                <span class="admin-nav-label">{{ __('Product Brands') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.vehicle-fitments.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.vehicle-fitments.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Vehicle Finder') }}"
                                @if(request()->routeIs('admin.vehicle-fitments.*')) aria-current="page" @endif
                            >
                                <x-ph-icon name="car-profile" class="admin-nav-icon" />
                                <span class="admin-nav-label">{{ __('Vehicle Finder') }}</span>
                            </a>
                            <a
                                href="{{ route('admin.reviews.index') }}"
                                class="admin-nav-link {{ $navItem(request()->routeIs('admin.reviews.*')) }}"
                                data-admin-sidebar-tooltip="{{ __('Customer Reviews') }}"
                                @if(request()->routeIs('admin.reviews.*')) aria-current="page" @endif
                            >
                                <x-ph-icon name="star" class="admin-nav-icon" />
                                <span class="admin-nav-label">{{ __('Customer Reviews') }}</span>
                            </a>
                        @endif

                        {{-- ── STOCK ── how much of it there is. These four were split between
                             "Analytics" and "Catalog": none of them is analysis, and only one
                             of them is about the catalogue. --}}
                        @if($hasStock)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Stock') }}</span></div>
                            @can(\App\Models\User::PERMISSION_STOCK_MANAGE)
                                <a
                                    href="{{ route('admin.inventory.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.inventory.index')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Inventory') }}"
                                    @if(request()->routeIs('admin.inventory.index')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="stack" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Inventory') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.inventory.bulk-stock') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.inventory.bulk-stock*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Bulk Stock') }}"
                                    @if(request()->routeIs('admin.inventory.bulk-stock*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="list-checks" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Bulk Stock') }}</span>
                                </a>
                            @endcan
                            {{-- Dead Stock keeps the catalogue permission it has always had; only
                                 where it is listed has changed. --}}
                            @if($canCatalog)
                                <a
                                    href="{{ route('admin.dead-stock.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.dead-stock.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Dead Stock') }}"
                                    @if(request()->routeIs('admin.dead-stock.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="archive" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Dead Stock') }}</span>
                                </a>
                            @endif
                            @can(\App\Models\User::PERMISSION_STOCK_MANAGE)
                                <a
                                    href="{{ route('admin.purchase-planning.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.purchase-planning.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Purchase Planning') }}"
                                    @if(request()->routeIs('admin.purchase-planning.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="clipboard-text" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Purchase Planning') }}</span>
                                </a>
                            @endcan
                        @endif

                        {{-- ── MARKETING ── reaching customers. WAYL Payments used to sit here;
                             taking money is not marketing. --}}
                        @if($hasMarketing)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Marketing') }}</span></div>
                            @can(\App\Models\User::PERMISSION_FINANCE_MANAGE)
                                <a
                                    href="{{ route('admin.discounts.edit') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.discounts.edit') || request()->routeIs('admin.discounts.coupons.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Coupon Management') }}"
                                    @if(request()->routeIs('admin.discounts.edit') || request()->routeIs('admin.discounts.coupons.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="ticket" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Coupon Management') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.discounts.rules') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.discounts.rules')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Discount Rules') }}"
                                    @if(request()->routeIs('admin.discounts.rules')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="percent" class="admin-nav-icon" />
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
                                    <x-ph-icon name="envelope-simple" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Email Center') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.messaging.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.messaging.*')) }}"
                                    data-admin-sidebar-tooltip="{{ config('services.otpiq.whatsapp.admin_visible', true) ? __('SMS & WhatsApp Center') : __('SMS Center') }}"
                                    @if(request()->routeIs('admin.messaging.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="chat-circle-dots" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ config('services.otpiq.whatsapp.admin_visible', true) ? __('SMS & WhatsApp Center') : __('SMS Center') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.popups.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.popups.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Popups') }}"
                                    @if(request()->routeIs('admin.popups.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="megaphone-simple" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Popups') }}</span>
                                </a>
                            @endcan
                            @if($canWhatsapp)
                                <a
                                    href="{{ route('admin.whatsapp.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.whatsapp.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Inbound WhatsApp') }}"
                                    @if(request()->routeIs('admin.whatsapp.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="whatsapp-logo" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Inbound WhatsApp') }}</span>
                                </a>
                            @endif
                        @endif

                        {{-- ── REPORTS ── looking back at what happened --}}
                        @if($hasReports)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Reports') }}</span></div>
                            @if($canGoals)
                                <a
                                    href="{{ route('admin.goals.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.goals.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Progress Center') }}"
                                    @if(request()->routeIs('admin.goals.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="target" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Progress Center') }}</span>
                                </a>
                            @endif
                            @can(\App\Models\User::PERMISSION_FINANCE_VIEW)
                                <a
                                    href="{{ route('admin.revenue.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.revenue.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Revenue') }}"
                                    @if(request()->routeIs('admin.revenue.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="coins" class="admin-nav-icon" />
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
                                    <x-ph-icon name="chart-line-up" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Site Analytics') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.search-insights.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.search-insights.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Search Insights') }}"
                                    @if(request()->routeIs('admin.search-insights.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="magnifying-glass" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Search Insights') }}</span>
                                </a>
                            @endcan
                            @can(\App\Models\User::PERMISSION_FINANCE_VIEW)
                                <a
                                    href="{{ route('admin.wayl.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.wayl.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('WAYL Payments') }}"
                                    @if(request()->routeIs('admin.wayl.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="credit-card" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('WAYL Payments') }}</span>
                                </a>
                            @endcan
                        @endif

                        {{-- ── ADMINISTRATION ── running the panel itself. Dealers had a section
                             of its own, "Partners", for one link. --}}
                        @if($hasAdminGrp)
                            <div class="admin-nav-section" aria-hidden="true"><span>{{ __('Administration') }}</span></div>
                            @if($canDealers)
                                <a
                                    href="{{ route('admin.dealers.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.dealers.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Dealers') }}"
                                    @if(request()->routeIs('admin.dealers.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="handshake" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Dealers') }}</span>
                                </a>
                            @endif
                            @can('viewAny', \App\Models\User::class)
                                <a
                                    href="{{ route('admin.users.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.users.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Users') }}"
                                    @if(request()->routeIs('admin.users.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="users-three" class="admin-nav-icon" />
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
                                    <x-ph-icon name="gear-six" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Settings') }}</span>
                                </a>
                                <a
                                    href="{{ route('admin.shipping.governorates') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.shipping.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Shipping') }}"
                                    @if(request()->routeIs('admin.shipping.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="truck" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Shipping') }}</span>
                                </a>
                            @endcan
                            @can(\App\Models\User::PERMISSION_ACTIVITY_LOGS_VIEW)
                                <a
                                    href="{{ route('admin.activity-logs.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.activity-logs.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Activity Logs') }}"
                                    @if(request()->routeIs('admin.activity-logs.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="clock-counter-clockwise" class="admin-nav-icon" />
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

                <button
                    type="button"
                    class="admin-sidebar-toggle admin-sidebar-edge"
                    aria-expanded="true"
                    aria-label="{{ __('Collapse sidebar') }}"
                    title="{{ __('Collapse sidebar') }}"
                    aria-controls="admin-sidebar"
                    data-expand-label="{{ __('Expand sidebar') }}"
                    data-collapse-label="{{ __('Collapse sidebar') }}"
                    data-admin-sidebar-toggle
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M15 5l-7 7 7 7" />
                    </svg>
                </button>

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
                                    <i class="fas {{ $isRtl ? 'fa-angles-left' : 'fa-angles-right' }} text-sm" aria-hidden="true"></i>
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
                                    <i class="fas fa-bars text-sm" aria-hidden="true"></i>
                                </button>
                            </div>

                            {{-- CENTER: shared page title (auto-generated from route name for consistency) --}}
                            <div class="hidden md:flex flex-1 items-center justify-center min-w-0 px-3">
                                <div class="flex flex-col items-center leading-tight min-w-0 max-w-full">
                                    <h2 class="text-lg md:text-xl lg:text-2xl font-bold text-white tracking-tight whitespace-nowrap truncate max-w-full">{{ $adminPageTitle }}</h2>
                                    <div class="mt-1 inline-flex items-center gap-2 text-[10px] lg:text-[11px] uppercase tracking-[0.2em] text-white/55 font-bold whitespace-nowrap truncate max-w-full" style="font-family: 'JetBrains Mono', ui-monospace, monospace;">
                                        <span class="topbar-pulse-ring text-emerald-400 inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                        <span class="text-accent">{{ __('ADMIN · LIVE') }}</span>
                                        <span class="text-white/25" aria-hidden="true">·</span>
                                        <span>{{ now()->format('l, F d · Y') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- MOBILE: compact title --}}
                            <div class="flex md:hidden flex-1 items-center justify-center min-w-0 px-2">
                                <h2 class="text-base font-bold text-white tracking-tight whitespace-nowrap truncate max-w-full">{{ $adminPageTitle }}</h2>
                            </div>

                            {{-- RIGHT: actions — all 36px tall, consistent spacing --}}
                            <div class="admin-topbar-actions flex min-w-0 items-center gap-1.5 shrink-0">
                                {{-- Language switcher (dark variant, hidden on small) --}}
                                <div class="hidden sm:flex">
                                    <x-language-switcher variant="dark" />
                                </div>

                                <x-theme-toggle storage="admin-theme" />
                                <div class="relative">
                                    <button
                                        id="adminNotificationsButton"
                                        type="button"
                                        class="topbar-action inline-flex relative"
                                        aria-label="{{ __('Notifications') }}"
                                    >
                                        <i class="fas fa-bell text-[13px]" aria-hidden="true"></i>
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
                                                    class="text-sm font-semibold text-info hover:text-info"
                                                >
                                                    {{ __('Mark all read') }}
                                                </button>
                                                <span id="adminNotificationsUpdatedAt" class="text-[11px] text-muted dark:text-slate-500">--</span>
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
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-info">{{ __('Dealer Requests') }}</p>
                                                    <span id="adminDealerRequestCount" class="text-xs font-semibold text-info">0</span>
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
                                        <span class="h-7 w-7 rounded-md grid place-items-center emboss-badge text-navy-deep text-[11px] font-bold" style="font-family: 'JetBrains Mono', ui-monospace, monospace;">
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
                                        <i class="fas fa-right-from-bracket text-[13px]" aria-hidden="true"></i>
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
                                    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 id="adminDangerTitle" class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Delete Coupon') }}</h3>
                                    <p id="adminDangerDescription" class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('This action is permanent and cannot be undone.') }}</p>
                                </div>
                            </div>

                            {{--
                                Naming the record, for actions where getting the
                                wrong row would be expensive. Hidden unless the
                                form supplies a subject.
                            --}}
                            <div id="adminDangerSubject" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                                <div id="adminDangerSubjectName" class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100"></div>
                                <div id="adminDangerSubjectMeta" class="mt-0.5 font-mono text-[11px] text-slate-500 dark:text-slate-400"></div>
                            </div>

                            {{--
                                The second stage. A single click is too cheap for
                                something that cannot be undone, so the button
                                stays inert until the record's own code is typed
                                back — deliberate, and impossible to hit by
                                muscle memory on the wrong row.
                            --}}
                            <div id="adminDangerPhraseWrap" class="mt-4 hidden">
                                <label for="adminDangerPhrase" id="adminDangerPhraseLabel"
                                       data-template="{{ __('Type :phrase to confirm.') }}"
                                       class="block text-[12px] font-semibold text-slate-600 dark:text-slate-300"></label>
                                <input type="text" id="adminDangerPhrase" autocomplete="off" spellcheck="false"
                                       class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm text-slate-900 outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:ring-rose-900/50">
                            </div>

                            <div class="mt-5 flex justify-end gap-2">
                                <button type="button" id="adminDangerCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Cancel') }}</button>
                                <button type="button" id="adminDangerConfirm" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40">{{ __('Confirm Delete') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="min-h-screen bg-app">
                @unless ($hideNavigation)
                    @include('layouts.navigation')
                @endunless

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-surface-2 shadow-app">
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
                    const themeStorageKey = 'admin-theme';
                    const lightDefaultResetKey = 'admin-theme-light-default-20260523';
                    const applyTheme = (isDark) => {
                        document.documentElement.classList.toggle('dark', isDark);
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
                        appendText(container, 'p', 'text-xs text-muted dark:text-slate-500', message);
                    };

                    const renderItems = (container, items) => {
                        if (!items || items.length === 0) {
                            return false;
                        }

                        container.textContent = '';
                        items.forEach((item, index) => {
                            const wrapper = setClass(
                                document.createElement('div'),
                                `admin-notification-item block rounded-lg px-2 py-2 ${item.read ? 'bg-white dark:bg-slate-900/60' : 'bg-info/50 dark:bg-info/10'} hover:bg-slate-50 dark:hover:bg-slate-800/80 transition`
                            );
                            wrapper.style.setProperty('--admin-item-index', String(index));

                            const link = setClass(document.createElement('a'), 'block');
                            link.setAttribute('href', safeNotificationUrl(item.url));
                            appendText(link, 'p', 'text-sm font-medium text-slate-800 dark:text-slate-100', item.title);
                            appendText(link, 'p', 'text-xs text-slate-500 dark:text-slate-400', item.subtitle);

                            const metaRow = setClass(document.createElement('div'), 'flex items-center justify-between mt-1');
                            appendText(metaRow, 'p', 'text-[11px] text-muted dark:text-slate-500', item.meta || '');
                            appendText(metaRow, 'p', 'text-[11px] text-muted dark:text-slate-500', item.time || '');
                            link.appendChild(metaRow);
                            wrapper.appendChild(link);

                            if (!item.read) {
                                const markReadButton = setClass(
                                    document.createElement('button'),
                                    'admin-mark-read mt-2 text-[11px] font-semibold text-info hover:text-info'
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
                    const subjectEl = document.getElementById('adminDangerSubject');
                    const subjectNameEl = document.getElementById('adminDangerSubjectName');
                    const subjectMetaEl = document.getElementById('adminDangerSubjectMeta');
                    const phraseWrap = document.getElementById('adminDangerPhraseWrap');
                    const phraseLabel = document.getElementById('adminDangerPhraseLabel');
                    const phraseInput = document.getElementById('adminDangerPhrase');

                    if (!modal || !titleEl || !descriptionEl || !cancelBtn || !confirmBtn) {
                        return;
                    }

                    let resolver = null;
                    let requiredPhrase = '';
                    const defaultConfirmLabel = confirmBtn.textContent;

                    const setVisible = (visible) => {
                        modal.classList.toggle('hidden', !visible);
                        modal.classList.toggle('flex', visible);
                    };

                    const syncConfirmState = () => {
                        if (requiredPhrase === '') {
                            confirmBtn.disabled = false;

                            return;
                        }

                        const typed = (phraseInput?.value || '').trim();
                        confirmBtn.disabled = typed.toLowerCase() !== requiredPhrase.toLowerCase();
                    };

                    const resolveAndClose = (value) => {
                        if (value === true && confirmBtn.disabled) {
                            return;
                        }

                        setVisible(false);
                        if (resolver) {
                            resolver(value);
                            resolver = null;
                        }
                    };

                    window.adminDangerConfirm = ({ title, description, subject, meta, phrase, actionLabel } = {}) => {
                        titleEl.textContent = title || 'Delete Coupon';
                        descriptionEl.textContent = description || 'This action is permanent and cannot be undone.';
                        confirmBtn.textContent = actionLabel || defaultConfirmLabel;

                        if (subjectEl && subjectNameEl && subjectMetaEl) {
                            subjectNameEl.textContent = subject || '';
                            subjectMetaEl.textContent = meta || '';
                            subjectEl.classList.toggle('hidden', !subject && !meta);
                        }

                        requiredPhrase = (phrase || '').trim();

                        if (phraseWrap && phraseLabel && phraseInput) {
                            phraseInput.value = '';
                            phraseLabel.textContent = phraseLabel.dataset.template
                                ? phraseLabel.dataset.template.replace(':phrase', requiredPhrase)
                                : requiredPhrase;
                            phraseWrap.classList.toggle('hidden', requiredPhrase === '');
                        }

                        syncConfirmState();
                        setVisible(true);

                        if (requiredPhrase !== '' && phraseInput) {
                            window.setTimeout(() => phraseInput.focus(), 30);
                        }

                        return new Promise((resolve) => {
                            resolver = resolve;
                        });
                    };

                    phraseInput?.addEventListener('input', syncConfirmState);
                    phraseInput?.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            resolveAndClose(true);
                        }
                    });

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
                        const read = (key) => submitter?.dataset[key] || form.dataset[key] || '';
                        window.adminDangerConfirm({
                            title: read('dangerTitle') || 'Delete Coupon',
                            description: read('dangerDescription') || 'This action is permanent and cannot be undone.',
                            subject: read('dangerSubject'),
                            meta: read('dangerMeta'),
                            phrase: read('dangerPhrase'),
                            actionLabel: read('dangerAction'),
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

            </script>
        @endif
        @stack('scripts')
    </body>
</html>
