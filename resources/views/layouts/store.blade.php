<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Yalla Spare'))</title>
    @include('partials.brand-head')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html, body {
            margin: 0;
            padding: 0;
        }
        :root {
            --store-bg: #eef3fb;
            --store-ink: #14213d;
            --store-muted: #5f6f8a;
            --store-accent: #e85d2a;
            --store-accent-dark: #b83a14;
            --store-surface: #ffffff;
            --store-border: #d8e0ef;
        }
        body {
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            color: var(--store-ink);
            background: radial-gradient(circle at 14% 12%, rgba(59, 130, 246, 0.2), transparent 34%),
                        radial-gradient(circle at 88% 18%, rgba(226, 232, 240, 0.28), transparent 32%),
                        radial-gradient(circle at 50% 100%, rgba(255, 255, 255, 0.96), transparent 42%),
                        linear-gradient(180deg, #0a1533 0%, #1a2f5f 24%, #dfe7f5 68%, #fbfdff 100%);
        }
        .store-title {
            font-family: "Space Grotesk", "Plus Jakarta Sans", sans-serif;
            letter-spacing: -0.02em;
        }
        .store-fade {
            animation: storeFade 480ms ease-out both;
        }
        .store-rise {
            animation: storeRise 560ms ease-out both;
        }
        @keyframes storeFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes storeRise {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen">
    <x-loading-overlay message="{{ __('Processing, please wait...') }}" variant="full" />

    @php
        $headerCartCount = (int) ($headerCartCount ?? $cartCount ?? 0);
        $authUser = auth()->user();
        $isCustomerAuthenticated = $authUser && ! $authUser->isAdminPanelUser();
    @endphp

    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-20 top-16 h-72 w-72 rounded-full bg-blue-300/25 blur-3xl"></div>
        <div class="absolute -right-16 top-40 h-80 w-80 rounded-full bg-white/35 blur-3xl"></div>
    </div>

    <header class="store-fade sticky top-0 z-40 px-3 sm:px-6 lg:px-8" x-data="storeMenu">
        <div class="mx-auto w-full max-w-7xl">
            <div class="rounded-2xl border border-white/60 bg-white/85 px-3 py-2 shadow-[0_18px_50px_-34px_rgba(15,23,42,0.42)] backdrop-blur-2xl sm:rounded-3xl sm:px-5 sm:py-3">
                <div class="flex min-w-0 items-center justify-between gap-3">
                    <div class="header-logo-area min-h-0 min-w-0 flex-1">
                        <a href="{{ route('home') }}" class="app-logo app-logo-light app-logo-user min-w-0">
                            <x-brand-mark
                                :logo-url="$systemSettings['site_logo_url'] ?? null"
                                :brand="$systemSettings['site_name'] ?? 'YallaSpare'"
                                wrapper-class="app-logo-mark"
                                img-class="h-full w-auto object-contain"
                                fallback-class="inline-flex h-full w-full items-center justify-center rounded-lg bg-blue-600/90"
                                fallback-text-class="text-[11px] font-semibold tracking-[0.18em] text-white"
                            />
                            <span class="app-logo-text store-title text-slate-950">
                                {{ $systemSettings['site_name'] ?? 'YallaSpare' }}
                            </span>
                        </a>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 sm:hidden"
                        @click="toggle()"
                        :aria-expanded="ariaExpanded"
                        aria-label="{{ __('Menu') }}"
                    >
                        <svg x-show="!open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <svg x-cloak x-show="open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>

                <nav
                    x-cloak
                    x-show="visible"
                    x-transition
                    class="mt-3 grid grid-cols-2 gap-2 sm:mt-0 sm:flex sm:flex-wrap sm:items-center sm:justify-end"
                >
                    <x-language-switcher />

                    <a href="{{ route('shop.index') }}" class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-900/5 hover:text-slate-950 sm:rounded-2xl sm:px-4 sm:py-2.5">
                        {{ __('Shop') }}
                    </a>
                    <a href="{{ route('cart.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-900/5 hover:text-slate-950 sm:rounded-2xl sm:px-4 sm:py-2.5">
                        <span>{{ __('Cart') }}</span>
                        <span class="inline-flex min-w-[1.6rem] items-center justify-center rounded-full bg-slate-950 px-2 py-0.5 text-[11px] font-bold text-white">
                            {{ $headerCartCount }}
                        </span>
                    </a>
                    @if ($isCustomerAuthenticated)
                        <a href="{{ route('account.index') }}" class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-900/5 hover:text-slate-950 sm:rounded-2xl sm:px-4 sm:py-2.5">
                            {{ __('Account') }}
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950 sm:rounded-2xl sm:px-4 sm:py-2.5">
                                {{ __('Logout') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950 sm:rounded-2xl sm:px-4 sm:py-2.5">
                            {{ __('Login') }}
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 sm:rounded-2xl sm:px-4 sm:py-2.5">
                            {{ __('Register') }}
                        </a>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
        @yield('content')
    </main>

    @include('partials.site-footer')
    @include('partials.language-switcher-script')
</body>
</html>
