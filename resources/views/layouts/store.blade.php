<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($systemSettings['site_name'] ?? config('app.name', 'YallaSpare')))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --store-bg: #f4f8f7;
            --store-ink: #102723;
            --store-muted: #4f6762;
            --store-accent: #e85d2a;
            --store-accent-dark: #b83a14;
            --store-surface: #ffffff;
            --store-border: #d7e4e0;
        }
        body {
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
            color: var(--store-ink);
            background: radial-gradient(circle at 12% 8%, rgba(17, 94, 89, 0.18), transparent 38%),
                        radial-gradient(circle at 90% 22%, rgba(232, 93, 42, 0.16), transparent 36%),
                        linear-gradient(180deg, #edf4f2 0%, var(--store-bg) 55%, #eef3f1 100%);
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
    @php
        $headerCartCount = (int) ($cartCount ?? 0);
    @endphp

    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-20 top-16 h-72 w-72 rounded-full bg-emerald-300/25 blur-3xl"></div>
        <div class="absolute -right-16 top-40 h-80 w-80 rounded-full bg-orange-300/25 blur-3xl"></div>
    </div>

    <header class="store-fade sticky top-0 z-40 border-b border-white/60 bg-white/80 backdrop-blur-xl">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="store-title text-xl font-bold text-slate-900">
                {{ $systemSettings['site_name'] ?? 'YallaSpare' }}
            </a>

            <nav class="flex items-center gap-2 sm:gap-4">
                <a href="{{ route('shop.index') }}" class="rounded-full px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">
                    Shop
                </a>
                <a href="{{ route('cart.index') }}" class="rounded-full px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-orange-50 hover:text-orange-700">
                    Cart ({{ $headerCartCount }})
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        Account
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="rounded-full bg-[var(--store-accent)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[var(--store-accent-dark)]">
                        Register
                    </a>
                @endauth
            </nav>
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

    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @yield('content')
    </main>
</body>
</html>
