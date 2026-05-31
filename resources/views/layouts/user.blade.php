@php
    $locale = app()->getLocale();
    $isRtl = str_starts_with($locale, 'ar') || str_starts_with($locale, 'ku');
    $dir = $isRtl ? 'rtl' : 'ltr';
    $brand = (string) ($systemSettings['site_name'] ?? 'YallaSpare');
    $brandLogoUrl = $systemSettings['site_logo_url'] ?? null;
    $headerCartCount = (int) ($headerCartCount ?? 0);
    $headerWishlistCount = (int) ($headerWishlistCount ?? 0);
    $headerCartSubtotal = (float) ($headerCartSubtotal ?? 0);
    $cartCount = isset($cartCount) ? (int) $cartCount : $headerCartCount;
    $wishlistCount = $headerWishlistCount;
    $authUser = auth()->user();
    $userInitial = strtoupper(substr((string) ($authUser->name ?? 'U'), 0, 1));
    $userProfilePhotoUrl = !empty($authUser?->profile_photo_path)
        ? asset('storage/' . ltrim((string) $authUser->profile_photo_path, '/'))
        : null;
    $fontSizePreference = auth()->check() ? (auth()->user()->font_size_preference ?? 'default') : 'default';
    $reducedMotion = auth()->check() ? (bool) (auth()->user()->reduced_motion ?? false) : false;
    $highContrastMode = auth()->check() ? (bool) (auth()->user()->high_contrast_mode ?? false) : false;
    $currencyLabel = (string) ($systemSettings['currency_code'] ?? 'IQD');

    $cartCount = max(0, (int) $cartCount);
    $cartRef = $cartRef ?? $headerCartRef ?? '#17-3118';
    $cartTotalFormatted = $cartTotalFormatted ?? $headerCartTotalFormatted ?? trim($currencyLabel . ' ' . number_format($headerCartSubtotal, 2));
    $isAuthenticated = auth()->check();
    $storeHomeUrl = $isAuthenticated ? route('user.shop.home') : route('home');
    $cartUrl = $isAuthenticated
        ? (Route::has('cart.index') ? route('cart.index') : (Route::has('user.cart.index') ? route('user.cart.index') : url('/user/cart')))
        : route('login');
    $wishlistUrl = $isAuthenticated
        ? (Route::has('user.wishlist.index') ? route('user.wishlist.index') : url('/user/wishlist'))
        : route('login');
    $isUserHomeRoute = request()->routeIs('user.shop.home');
    $htmlClasses = trim('h-full'
        . ($fontSizePreference === 'large' ? ' user-font-large' : '')
        . ($fontSizePreference === 'xl' ? ' user-font-xl' : '')
        . ($reducedMotion ? ' user-reduced-motion' : '')
    );
    $bodyClasses = trim('user-shell min-h-full text-slate-900 antialiased dark:text-slate-100'
        . ($highContrastMode ? ' user-high-contrast' : '')
    );
    $shellClasses = trim('min-h-screen bg-slate-50 dark:bg-slate-950');
    $mainClasses = $isUserHomeRoute
        ? 'mx-auto w-full max-w-7xl px-4 pb-8 pt-0 sm:px-6 sm:pb-10 sm:pt-0 lg:px-8 lg:pb-12 lg:pt-0'
        : 'mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8 lg:py-12';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}" class="{{ $htmlClasses }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', $brand)</title>
        <meta name="description" content="@yield('meta_description', __('Yalla Spare auto parts catalog, support, and legal information.'))">
        @include('partials.seo-locale')
        @stack('head')
        @php
            $themePreference = auth()->check() ? (auth()->user()->theme_preference ?? 'light') : 'light';
            $themePreference = in_array($themePreference, ['light', 'dark'], true) ? $themePreference : 'light';
        @endphp
        <script>
            (function () {
                try {
                    const normalizeTheme = (value) => ['light', 'dark'].includes(value) ? value : null;
                    const lightDefaultResetKey = 'user-theme-light-default-20260523';
                    let storedThemeValue = localStorage.getItem('user-theme');
                    let storedTheme = normalizeTheme(storedThemeValue);
                    const serverTheme = @js($themePreference);
                    const isAuthenticated = @js(auth()->check());

                    if (storedTheme === 'dark' && localStorage.getItem(lightDefaultResetKey) !== '1') {
                        storedThemeValue = 'light';
                        storedTheme = 'light';
                        localStorage.setItem('user-theme', 'light');
                    }

                    localStorage.setItem(lightDefaultResetKey, '1');

                    const selectedTheme = isAuthenticated ? (normalizeTheme(serverTheme) || 'light') : (storedTheme || 'light');

                    if (storedThemeValue !== null && storedTheme === null) {
                        localStorage.setItem('user-theme', 'light');
                    } else if (isAuthenticated && storedThemeValue !== selectedTheme) {
                        localStorage.setItem('user-theme', selectedTheme);
                    }

                    document.documentElement.classList.toggle('dark', selectedTheme === 'dark');
                } catch (error) {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            html, body { margin: 0; padding: 0; background: #070740; }
            html.user-font-large { font-size: 17px; }
            html.user-font-xl { font-size: 18px; }
            html.user-reduced-motion *, html.user-reduced-motion *::before, html.user-reduced-motion *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
            body.user-high-contrast {
                filter: contrast(1.08);
            }
        </style>
    </head>
    <body class="{{ $bodyClasses }}" x-data="{ accountOpen: false, mobileNavOpen: false }">
        <div class="{{ $shellClasses }}">
            <header data-store-header class="relative sticky top-0 z-40 border-0 bg-[linear-gradient(180deg,#070740_0%,#0a0d3f_100%)] text-white shadow-none transition-transform duration-300 ease-out will-change-transform" style="margin-top:0;border-top:0">
                @php
                    $headerCategories = $headerCategories ?? $dropdownCategories ?? collect();
                @endphp

                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="hidden h-14 items-center lg:grid lg:grid-cols-[1fr_auto_1fr] lg:gap-4">
                        <div></div>
                        <a href="{{ $storeHomeUrl }}" class="app-logo app-logo-dark app-logo-user justify-self-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25">
                            <x-brand-mark
                                :logo-url="$brandLogoUrl"
                                :brand="$brand"
                                wrapper-class="app-logo-mark logo-remove-white-bg"
                                img-class="h-full w-auto object-contain"
                                fallback-class="inline-flex h-full w-full items-center justify-center"
                                fallback-text-class="text-[11px] font-semibold tracking-[0.18em] text-white"
                            />
                            <span class="app-logo-text">{{ $brand }}</span>
                        </a>
                        <div class="justify-self-end">
                            <div class="flex items-center gap-2">
                                <x-language-switcher variant="dark" />

                            @auth
                                <div class="relative" data-header-account>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 items-center gap-3 rounded-xl border border-white/10 bg-white/10 px-3 text-sm font-medium text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25"
                                        data-header-account-trigger
                                        aria-expanded="false"
                                        aria-haspopup="menu"
                                    >
                                        @if($userProfilePhotoUrl)
                                            <img src="{{ $userProfilePhotoUrl }}" alt="{{ __(':name profile photo', ['name' => $authUser->name ?? __('User')]) }}" class="h-7 w-7 rounded-full object-cover border border-white/30">
                                        @else
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-primary">
                                                {{ $userInitial }}
                                            </span>
                                        @endif
                                        <span>{{ auth()->user()->name ?? __('Account') }}</span>
                                        <svg class="h-4 w-4 text-white/65 transition-transform" data-header-account-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.126l3.71-3.895a.75.75 0 1 1 1.08 1.04l-4.25 4.46a.75.75 0 0 1-1.08 0l-4.25-4.46a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <div
                                        data-header-account-menu
                                        class="absolute {{ $isRtl ? 'left-0' : 'right-0' }} top-full z-50 mt-3 hidden w-56 overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-2 text-slate-900 shadow-2xl shadow-slate-900/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:shadow-black/30"
                                        role="menu"
                                    >
                                        <div class="rounded-2xl border border-slate-100 px-4 py-3 dark:border-slate-800">
                                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name ?? __('User') }}</p>
                                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email ?? '' }}</p>
                                        </div>
                                        <div class="mt-2 space-y-1">
                                            <a href="{{ route('user.account.edit') }}" class="flex rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-primary/30" role="menuitem">
                                                {{ __('Profile') }}
                                            </a>
                                            <a href="{{ route('user.settings.edit') }}" class="flex rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-primary/30" role="menuitem">
                                                {{ __('Settings') }}
                                            </a>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="flex w-full rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-primary/30" role="menuitem">
                                                    {{ __('Logout') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('login') }}" class="inline-flex h-9 items-center rounded-xl border border-white/10 bg-white/10 px-3 text-sm font-medium text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25">
                                        {{ __('Login') }}
                                    </a>
                                    <a href="{{ route('register') }}" class="inline-flex h-9 items-center rounded-xl bg-white px-3 text-sm font-semibold text-primary transition duration-200 hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40">
                                        {{ __('Register') }}
                                    </a>
                                </div>
                            @endauth
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 py-2 lg:hidden">
                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ $storeHomeUrl }}" class="app-logo app-logo-dark app-logo-user focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25">
                                <x-brand-mark
                                    :logo-url="$brandLogoUrl"
                                    :brand="$brand"
                                    wrapper-class="app-logo-mark logo-remove-white-bg"
                                    img-class="h-full w-auto object-contain"
                                    fallback-class="inline-flex h-full w-full items-center justify-center"
                                    fallback-text-class="text-[11px] font-semibold tracking-[0.18em] text-white"
                                />
                                <span class="app-logo-text">{{ $brand }}</span>
                            </a>

                            <div class="flex shrink-0 items-center gap-1.5">
                                <x-language-switcher variant="dark" />

                            @auth
                            <div class="relative" data-header-account>
                                <button
                                    type="button"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-white/10 bg-white/10 px-2 text-xs font-medium text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25 sm:h-9 sm:rounded-xl sm:px-3 sm:text-sm"
                                    data-header-account-trigger
                                    aria-expanded="false"
                                    aria-haspopup="menu"
                                >
                                    @if($userProfilePhotoUrl)
                                            <img src="{{ $userProfilePhotoUrl }}" alt="{{ __(':name profile photo', ['name' => $authUser->name ?? __('User')]) }}" class="h-6 w-6 rounded-full object-cover border border-white/30 sm:h-7 sm:w-7">
                                        @else
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white text-[10px] font-semibold text-primary sm:h-7 sm:w-7 sm:text-[11px]">
                                            {{ $userInitial }}
                                        </span>
                                    @endif
                                    <span class="hidden sm:block">{{ auth()->user()->name ?? __('Account') }}</span>
                                    <svg class="h-4 w-4 text-white/65 transition-transform" data-header-account-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.126l3.71-3.895a.75.75 0 1 1 1.08 1.04l-4.25 4.46a.75.75 0 0 1-1.08 0l-4.25-4.46a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div
                                    data-header-account-menu
                                    class="absolute {{ $isRtl ? 'left-0' : 'right-0' }} top-full z-50 mt-3 hidden w-56 overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-2 text-slate-900 shadow-2xl shadow-slate-900/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:shadow-black/30"
                                    role="menu"
                                >
                                    <div class="rounded-2xl border border-slate-100 px-4 py-3 dark:border-slate-800">
                                        <p class="truncate text-sm font-semibold">{{ auth()->user()->name ?? __('User') }}</p>
                                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email ?? '' }}</p>
                                    </div>
                                    <div class="mt-2 space-y-1">
                                        <a href="{{ route('user.account.edit') }}" class="flex rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-primary/30" role="menuitem">
                                            {{ __('Profile') }}
                                        </a>
                                        <a href="{{ route('user.settings.edit') }}" class="flex rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-primary/30" role="menuitem">
                                            {{ __('Settings') }}
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="flex w-full rounded-2xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:hover:bg-slate-900 dark:hover:text-white dark:focus-visible:ring-primary/30" role="menuitem">
                                                {{ __('Logout') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex h-8 items-center rounded-lg border border-white/10 bg-white/10 px-2.5 text-xs font-medium text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25 sm:h-9 sm:rounded-xl sm:px-3 sm:text-sm">
                                    {{ __('Login') }}
                                </a>
                            @endauth
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/10 text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25 sm:h-9 sm:w-9 sm:rounded-xl"
                                    @click="mobileNavOpen = !mobileNavOpen"
                                    :aria-expanded="mobileNavOpen.toString()"
                                    aria-label="{{ __('Menu') }}"
                                >
                                    <svg x-show="!mobileNavOpen" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                                    </svg>
                                    <svg x-cloak x-show="mobileNavOpen" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('shop.index') }}" data-search-autocomplete data-search-autocomplete-url="{{ route('shop.autocomplete') }}">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-white/55">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35" />
                                        <circle cx="11" cy="11" r="6" />
                                    </svg>
                                </span>
                                <input
                                    type="search"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="{{ __('Search part name, OEM number, SKU...') }}"
                                    aria-label="{{ __('Search catalog') }}"
                                    autocomplete="off"
                                    data-search-autocomplete-input
                                    class="block h-9 w-full rounded-full border border-white/10 bg-white/10 py-2 pl-10 pr-20 text-xs text-white outline-none transition duration-200 placeholder:text-white/45 focus:border-white/20 focus:bg-white/15 focus:ring-4 focus:ring-white/10 sm:h-10 sm:pr-24 sm:text-sm"
                                />
                                <button
                                    type="submit"
                                    class="absolute {{ $isRtl ? 'left-1' : 'right-1' }} top-1 inline-flex h-7 items-center justify-center rounded-full bg-white px-3 text-xs font-semibold text-primary transition duration-200 hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40 sm:h-8 sm:px-4 sm:text-sm"
                                >
                                    {{ __('Search') }}
                                </button>
                                <div data-search-autocomplete-panel class="absolute left-0 right-0 top-full z-50 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-xl shadow-slate-950/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"></div>
                            </div>
                        </form>

                        <div class="flex items-center justify-end gap-1.5 sm:gap-2">
                            @auth
                                <a
                                    href="{{ $cartUrl }}"
                                    class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/10 bg-white/10 text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25 sm:h-10 sm:w-10 sm:rounded-xl"
                                    aria-label="{{ __('Cart') }}"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836L5.61 7.5m0 0h12.84a1.125 1.125 0 0 1 1.089 1.41l-1.12 4.5a1.125 1.125 0 0 1-1.09.84H8.382a1.125 1.125 0 0 1-1.09-.84L5.61 7.5Zm0 0L4.5 4.125M8.25 18.75a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5Zm9 0a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5Z" />
                                    </svg>
                                    <span class="absolute -top-1 {{ $isRtl ? '-left-1' : '-right-1' }} inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-orange-500 px-1 py-0.5 text-[10px] font-semibold leading-none text-white" data-cart-count-badge data-cart-count-value="{{ $cartCount }}" aria-hidden="true">
                                        {{ $cartCount > 99 ? '99+' : $cartCount }}
                                    </span>
                                </a>

                                <a
                                    href="{{ $wishlistUrl }}"
                                    class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/10 bg-white/10 text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25 sm:h-10 sm:w-10 sm:rounded-xl"
                                    aria-label="{{ __('Wishlist') }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12.001 20.727 10.59 19.44C5.58 14.905 2.25 11.89 2.25 8.188 2.25 5.173 4.612 2.812 7.626 2.812c1.704 0 3.34.793 4.375 2.037a5.755 5.755 0 0 1 4.373-2.037c3.016 0 5.376 2.361 5.376 5.376 0 3.702-3.328 6.717-8.339 11.252L12 20.727Z" />
                                    </svg>
                                    <span class="absolute -top-1 {{ $isRtl ? '-left-1' : '-right-1' }} inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-orange-500 px-1 py-0.5 text-[10px] font-semibold leading-none text-white" data-wishlist-count-badge data-wishlist-count-value="{{ $wishlistCount }}" aria-hidden="true">
                                        {{ $wishlistCount > 99 ? '99+' : $wishlistCount }}
                                    </span>
                                </a>
                            @else
                                <a
                                    href="{{ route('register') }}"
                                    class="inline-flex h-9 items-center rounded-lg bg-white px-3 text-xs font-semibold text-primary transition duration-200 hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40 sm:h-10 sm:rounded-xl sm:text-sm"
                                >
                                    {{ __('Register') }}
                                </a>
                            @endauth

                            <a
                                href="{{ url('/support') }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/10 bg-white/10 text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25 sm:h-10 sm:w-10 sm:rounded-xl"
                                aria-label="{{ __('Support') }}"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 10a6 6 0 1 0-12 0v5a2 2 0 0 0 2 2h2v-4H7.5M18 13h-2.5v4h2A2 2 0 0 0 20 15v-5Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19h1.5a1.5 1.5 0 0 0 0-3H12" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="hidden py-3 lg:grid lg:grid-cols-[minmax(11rem,0.75fr)_minmax(30rem,1.45fr)_minmax(11rem,0.75fr)] lg:items-center lg:gap-5">
                        <div class="justify-self-start">
                            @auth
                                <a
                                    href="{{ $cartUrl }}"
                                    class="inline-flex min-w-[10.5rem] items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25"
                                    aria-label="{{ __('Cart summary') }}"
                                >
                                    <span class="relative inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836L5.61 7.5m0 0h12.84a1.125 1.125 0 0 1 1.089 1.41l-1.12 4.5a1.125 1.125 0 0 1-1.09.84H8.382a1.125 1.125 0 0 1-1.09-.84L5.61 7.5Zm0 0L4.5 4.125M8.25 18.75a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5Zm9 0a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5Z" />
                                        </svg>
                                        <span class="absolute -top-1 {{ $isRtl ? '-left-1' : '-right-1' }} inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-orange-500 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white" data-cart-count-badge data-cart-count-value="{{ $cartCount }}" aria-hidden="true">
                                            {{ $cartCount > 99 ? '99+' : $cartCount }}
                                        </span>
                                    </span>

                                    <span class="min-w-0 flex-1 text-start">
                                        <span class="block truncate text-[10px] font-semibold uppercase tracking-[0.12em] text-white/80" data-cart-items-label>{{ __('Items (:count)', ['count' => $cartCount]) }}</span>
                                        <span class="block truncate text-[11px] font-medium text-white/55" data-cart-ref>{{ $cartRef }}</span>
                                    </span>

                                    <span class="shrink-0 text-right">
                                        <span class="block text-sm font-semibold tracking-[-0.02em] text-orange-400" data-cart-total>{{ $cartTotalFormatted }}</span>
                                    </span>
                                </a>
                            @else
                                <span class="inline-flex min-w-[10.5rem] items-center justify-center rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white">
                                    {{ __('Guest browsing') }}
                                </span>
                            @endauth
                        </div>

                        <form method="GET" action="{{ route('shop.index') }}" class="w-full min-w-0 justify-self-center" data-search-autocomplete data-search-autocomplete-url="{{ route('shop.autocomplete') }}">
                            <div class="relative mx-auto w-full max-w-[38rem]">
                                <span class="pointer-events-none absolute inset-y-0 left-5 flex items-center text-white/55">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35" />
                                        <circle cx="11" cy="11" r="6" />
                                    </svg>
                                </span>
                                <input
                                    type="search"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="{{ __('Search part name, OEM number, SKU...') }}"
                                    aria-label="{{ __('Search catalog') }}"
                                    autocomplete="off"
                                    data-search-autocomplete-input
                                    class="block h-11 w-full rounded-full border border-white/10 bg-white/10 py-2.5 pl-14 pr-28 text-sm text-white outline-none transition duration-200 placeholder:text-white/45 focus:border-white/20 focus:bg-white/15 focus:ring-4 focus:ring-white/10"
                                />
                                <button
                                    type="submit"
                                    class="absolute {{ $isRtl ? 'left-1.5' : 'right-1.5' }} top-1.5 inline-flex h-8 items-center justify-center rounded-full bg-white px-4 text-sm font-semibold text-primary transition duration-200 hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                                >
                                    {{ __('Search') }}
                                </button>
                                <div data-search-autocomplete-panel class="absolute left-0 right-0 top-full z-50 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-xl shadow-slate-950/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"></div>
                            </div>
                        </form>

                        <div class="justify-self-end">
                            <div class="flex items-center gap-2">
                                @auth
                                    <a
                                        href="{{ $wishlistUrl }}"
                                        class="inline-flex h-10 items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25"
                                        aria-label="{{ __('Wishlist') }}"
                                    >
                                        <span class="relative inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12.001 20.727 10.59 19.44C5.58 14.905 2.25 11.89 2.25 8.188 2.25 5.173 4.612 2.812 7.626 2.812c1.704 0 3.34.793 4.375 2.037a5.755 5.755 0 0 1 4.373-2.037c3.016 0 5.376 2.361 5.376 5.376 0 3.702-3.328 6.717-8.339 11.252L12 20.727Z" />
                                            </svg>
                                            <span class="absolute -top-1 {{ $isRtl ? '-left-1' : '-right-1' }} inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-orange-500 px-1 py-0.5 text-[10px] font-semibold leading-none text-white" data-wishlist-count-badge data-wishlist-count-value="{{ $wishlistCount }}" aria-hidden="true">
                                                {{ $wishlistCount > 99 ? '99+' : $wishlistCount }}
                                            </span>
                                        </span>
                                        <span class="text-sm font-semibold">{{ __('Wishlist') }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex h-10 items-center rounded-full border border-white/10 bg-white/5 px-4 text-sm font-semibold text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25">
                                        {{ __('Login') }}
                                    </a>
                                    <a href="{{ route('register') }}" class="inline-flex h-10 items-center rounded-full bg-white px-4 text-sm font-semibold text-primary transition duration-200 hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40">
                                        {{ __('Register') }}
                                    </a>
                                @endauth

                                <a
                                    href="{{ url('/support') }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/10 text-white transition duration-200 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25"
                                aria-label="{{ __('Support') }}"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 10a6 6 0 1 0-12 0v5a2 2 0 0 0 2 2h2v-4H7.5M18 13h-2.5v4h2A2 2 0 0 0 20 15v-5Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19h1.5a1.5 1.5 0 0 0 0-3H12" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <nav
                    class="pb-2 pt-1 text-white lg:pb-2.5 lg:pt-1.5"
                    aria-label="{{ __('Main navigation') }}"
                    x-data="{
                        categoriesOpen: false,
                        closeTimer: null,
                        isDesktop() { return window.innerWidth >= 1024; },
                        openNow() {
                            this.cancelClose();
                            this.categoriesOpen = true;
                        },
                        toggleMenu() {
                            if (this.isDesktop()) {
                                this.openNow();
                                return;
                            }
                            this.categoriesOpen = !this.categoriesOpen;
                        },
                        queueClose() {
                            if (!this.isDesktop()) return;
                            this.cancelClose();
                            this.closeTimer = setTimeout(() => this.categoriesOpen = false, 180);
                        },
                        cancelClose() {
                            if (this.closeTimer) {
                                clearTimeout(this.closeTimer);
                                this.closeTimer = null;
                            }
                        },
                        closeNow() {
                            this.cancelClose();
                            this.categoriesOpen = false;
                        }
                    }"
                    @keydown.escape.window="closeNow()"
                >
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="relative" @mouseenter="cancelClose()" @mouseleave="queueClose()">
                            <div
                                x-cloak
                                x-show="mobileNavOpen || isDesktop()"
                                x-transition
                                class="grid grid-cols-2 gap-1.5 rounded-2xl border border-white/10 bg-white/5 p-2 lg:flex lg:items-center lg:justify-center lg:gap-1 lg:overflow-x-visible lg:rounded-none lg:border-0 lg:bg-transparent lg:p-0"
                            >
                                <a
                                    href="{{ $storeHomeUrl }}"
                                    class="inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium transition duration-200 {{ request()->routeIs('user.shop.home') ? 'bg-white text-primary' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                >
                                    {{ __('Home') }}
                                </a>
                                <a
                                    href="{{ route('shop.index') }}"
                                    class="inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium transition duration-200 {{ request()->routeIs('shop.index') || request()->routeIs('user.shop.index') ? 'bg-white text-primary' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                >
                                    {{ __('Shop') }}
                                </a>

                                <a
                                    href="{{ route('categories.index') }}"
                                    class="inline-flex items-center gap-1 rounded-xl px-3 py-1.5 text-sm font-medium transition duration-200 {{ request()->routeIs('categories.*') ? 'bg-white text-primary' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                    data-store-categories-trigger
                                    aria-expanded="false"
                                    aria-haspopup="menu"
                                >
                                    <span>{{ __('Categories') }}</span>
                                    <svg class="h-4 w-4 transition-transform" data-store-categories-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.126l3.71-3.895a.75.75 0 1 1 1.08 1.04l-4.25 4.46a.75.75 0 0 1-1.08 0l-4.25-4.46a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </a>

                                <a
                                    href="{{ route('legal.about') }}"
                                    class="inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium text-white/80 transition duration-200 hover:bg-white/10 hover:text-white"
                                >
                                    {{ __('About Us') }}
                                </a>
                                <a
                                    href="{{ route('legal.contact') }}"
                                    class="inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium text-white/80 transition duration-200 hover:bg-white/10 hover:text-white"
                                >
                                    {{ __('Contact') }}
                                </a>
                            </div>

                            <div
                                data-store-categories-menu
                                class="mt-3 hidden rounded-3xl border border-slate-200/80 bg-white p-4 text-slate-900 shadow-2xl shadow-slate-900/10 dark:border-slate-800 dark:bg-slate-900 dark:text-white lg:absolute lg:left-0 lg:right-0 lg:top-full lg:z-50 lg:mt-2 lg:p-6"
                                role="menu"
                            >
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                        {{ __('Browse Categories') }}
                                    </p>
                                    <a href="{{ route('categories.index') }}" class="text-sm font-semibold text-primary transition hover:text-[#0a0a55] dark:text-slate-200 dark:hover:text-white">
                                        {{ __('View all') }}
                                    </a>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                    @forelse ($headerCategories as $categoryItem)
                                        <a
                                            href="{{ $categoryItem['url'] }}"
                                            class="group flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary/20 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:border-slate-700 dark:bg-slate-950"
                                        >
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-primary dark:bg-slate-800 dark:text-slate-200">
                                                @if (!empty($categoryItem['image']))
                                                    <img src="{{ $categoryItem['image'] }}" alt="{{ $categoryItem['label'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                                @else
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5h16M6.75 7.5v9a1.5 1.5 0 0 0 1.5 1.5h7.5a1.5 1.5 0 0 0 1.5-1.5v-9" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 10.5h4.5M9.75 13.5h4.5" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900 transition group-hover:text-primary dark:text-white">{{ $categoryItem['label'] }}</p>
                                                @if (filled($categoryItem['desc']))
                                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $categoryItem['desc'] }}</p>
                                                @endif
                                            </div>
                                        </a>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400 sm:col-span-2 lg:col-span-3 xl:col-span-4">
                                            {{ __('No categories found.') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>

            <main class="{{ $mainClasses }}">
                @yield('content')
            </main>

            @include('partials.site-footer')
        </div>
        <script>
            (() => {
                const header = document.querySelector('[data-store-header]');

                if (!header) {
                    return;
                }

                let lastY = window.scrollY || 0;
                let directionBuffer = 0;
                let isVisible = true;
                let ticking = false;
                const minVisibleOffset = 96;
                const downThreshold = 30;
                const upThreshold = 16;

                const showHeader = () => {
                    if (isVisible) {
                        return;
                    }

                    isVisible = true;
                    header.classList.remove('-translate-y-full');
                    header.classList.add('translate-y-0');
                };

                const hideHeader = () => {
                    if (!isVisible) {
                        return;
                    }

                    isVisible = false;
                    header.classList.remove('translate-y-0');
                    header.classList.add('-translate-y-full');
                };

                const updateHeaderState = () => {
                    const currentY = window.scrollY || 0;
                    const delta = currentY - lastY;

                    if (Math.abs(delta) < 4) {
                        ticking = false;
                        return;
                    }

                    if (currentY <= minVisibleOffset) {
                        directionBuffer = 0;
                        showHeader();
                        lastY = currentY;
                        ticking = false;
                        return;
                    }

                    if (delta > 0) {
                        directionBuffer = Math.max(0, directionBuffer) + delta;
                        if (directionBuffer >= downThreshold) {
                            hideHeader();
                            directionBuffer = 0;
                        }
                    } else {
                        directionBuffer = Math.min(0, directionBuffer) + delta;
                        if (Math.abs(directionBuffer) >= upThreshold) {
                            showHeader();
                            directionBuffer = 0;
                        }
                    }

                    lastY = currentY;
                    ticking = false;
                };

                const requestTick = () => {
                    if (ticking) {
                        return;
                    }

                    ticking = true;
                    window.requestAnimationFrame(updateHeaderState);
                };

                header.classList.add('translate-y-0');
                window.addEventListener('scroll', requestTick, { passive: true });
                window.addEventListener('resize', () => {
                    if ((window.scrollY || 0) <= minVisibleOffset) {
                        directionBuffer = 0;
                        showHeader();
                    }
                }, { passive: true });
            })();
        </script>
        <script>
            (() => {
                const forms = Array.from(document.querySelectorAll('[data-search-autocomplete]'));
                if (forms.length === 0) {
                    return;
                }

                const labels = {
                    products: @json(__('Products')),
                    categories: @json(__('Categories')),
                    brands: @json(__('Brands')),
                    sku: @json(__('SKU:')),
                    inStock: @json(__('In stock')),
                    outOfStock: @json(__('Out of stock')),
                };

                const makeText = (tag, className, text) => {
                    const node = document.createElement(tag);
                    node.className = className;
                    node.textContent = text || '';
                    return node;
                };

                const addSection = (panel, title, items, renderItem) => {
                    if (!items || items.length === 0) {
                        return;
                    }

                    panel.appendChild(makeText('div', 'px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400', title));
                    const list = document.createElement('div');
                    list.className = 'py-1';
                    items.forEach((item) => list.appendChild(renderItem(item)));
                    panel.appendChild(list);
                };

                const productRow = (item) => {
                    const row = document.createElement('a');
                    row.href = item.url;
                    row.className = 'flex items-center gap-3 px-3 py-2.5 text-sm transition hover:bg-slate-50 dark:hover:bg-slate-900';

                    const media = document.createElement('span');
                    media.className = 'flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-400 dark:border-slate-800 dark:bg-slate-900';
                    if (item.image_url) {
                        const image = document.createElement('img');
                        image.src = item.image_url;
                        image.alt = item.label || '';
                        image.className = 'h-full w-full object-contain';
                        media.appendChild(image);
                    } else {
                        media.textContent = (item.label || '?').slice(0, 1).toUpperCase();
                    }

                    const body = document.createElement('span');
                    body.className = 'min-w-0 flex-1';
                    body.appendChild(makeText('span', 'block truncate font-semibold text-slate-900 dark:text-white', item.label));
                    body.appendChild(makeText('span', 'mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400', `${labels.sku} ${item.sku || '-'} | ${item.price_formatted || ''}`));

                    const stock = makeText('span', `shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold ${Number(item.stock_quantity || 0) > 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300'}`, Number(item.stock_quantity || 0) > 0 ? labels.inStock : labels.outOfStock);

                    row.append(media, body, stock);
                    return row;
                };

                const simpleRow = (item, meta = '') => {
                    const row = document.createElement('a');
                    row.href = item.url;
                    row.className = 'flex items-center justify-between gap-3 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-900';
                    row.appendChild(makeText('span', 'truncate', item.label));
                    if (meta) {
                        row.appendChild(makeText('span', 'shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400', meta));
                    }
                    return row;
                };

                forms.forEach((form) => {
                    const input = form.querySelector('[data-search-autocomplete-input]');
                    const panel = form.querySelector('[data-search-autocomplete-panel]');
                    const endpoint = form.dataset.searchAutocompleteUrl;
                    let timer = null;
                    let controller = null;

                    if (!input || !panel || !endpoint) {
                        return;
                    }

                    const hide = () => {
                        panel.classList.add('hidden');
                    };

                    const render = (payload) => {
                        panel.replaceChildren();
                        addSection(panel, labels.products, payload.products || [], productRow);
                        addSection(panel, labels.categories, payload.categories || [], (item) => simpleRow(item, item.product_count ? String(item.product_count) : ''));
                        addSection(panel, labels.brands, payload.brands || [], (item) => simpleRow(item));

                        if (panel.childElementCount === 0) {
                            hide();
                            return;
                        }

                        panel.classList.remove('hidden');
                    };

                    const search = () => {
                        const query = input.value.trim();
                        if (query.length < 2) {
                            hide();
                            return;
                        }

                        controller?.abort();
                        controller = new AbortController();

                        const url = new URL(endpoint, window.location.origin);
                        url.searchParams.set('q', query);

                        fetch(url, {
                            headers: { 'Accept': 'application/json' },
                            signal: controller.signal,
                        })
                            .then((response) => response.ok ? response.json() : null)
                            .then((json) => {
                                if (json?.data) {
                                    render(json.data);
                                }
                            })
                            .catch((error) => {
                                if (error.name !== 'AbortError') {
                                    hide();
                                }
                            });
                    };

                    input.addEventListener('input', () => {
                        window.clearTimeout(timer);
                        timer = window.setTimeout(search, 180);
                    });
                    input.addEventListener('focus', search);
                    input.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') {
                            hide();
                        }
                    });

                    document.addEventListener('click', (event) => {
                        if (!form.contains(event.target)) {
                            hide();
                        }
                    });
                });
            })();
        </script>
        <script>
            (() => {
                const desktopQuery = window.matchMedia('(min-width: 1024px)');
                const languageDropdowns = Array.from(document.querySelectorAll('[data-header-dropdown]'));
                const accountDropdowns = Array.from(document.querySelectorAll('[data-header-account]'));
                const categoryTrigger = document.querySelector('[data-store-categories-trigger]');
                const categoryMenu = document.querySelector('[data-store-categories-menu]');
                const categoryIcon = document.querySelector('[data-store-categories-icon]');
                let categoryCloseTimer = null;
                let languageCloseTimer = null;
                let accountCloseTimer = null;

                const closeLanguageDropdowns = (except = null) => {
                    if (languageCloseTimer) {
                        window.clearTimeout(languageCloseTimer);
                        languageCloseTimer = null;
                    }

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

                const closeCategoryMenu = () => {
                    if (categoryCloseTimer) {
                        window.clearTimeout(categoryCloseTimer);
                        categoryCloseTimer = null;
                    }

                    categoryMenu?.classList.add('hidden');
                    categoryTrigger?.setAttribute('aria-expanded', 'false');
                    categoryIcon?.classList.remove('rotate-180');
                };

                const closeAccountDropdowns = (except = null) => {
                    if (accountCloseTimer) {
                        window.clearTimeout(accountCloseTimer);
                        accountCloseTimer = null;
                    }

                    accountDropdowns.forEach((root) => {
                        if (root === except) {
                            return;
                        }

                        const menu = root.querySelector('[data-header-account-menu]');
                        const trigger = root.querySelector('[data-header-account-trigger]');
                        const icon = root.querySelector('[data-header-account-icon]');

                        menu?.classList.add('hidden');
                        trigger?.setAttribute('aria-expanded', 'false');
                        icon?.classList.remove('rotate-180');
                    });
                };

                const openLanguageDropdown = (root) => {
                    const menu = root.querySelector('[data-header-dropdown-menu]');
                    const trigger = root.querySelector('[data-header-dropdown-trigger]');
                    const icon = root.querySelector('[data-header-dropdown-icon]');

                    closeCategoryMenu();
                    closeAccountDropdowns();
                    closeLanguageDropdowns(root);
                    menu?.classList.remove('hidden');
                    trigger?.setAttribute('aria-expanded', 'true');
                    icon?.classList.add('rotate-180');
                };

                const toggleLanguageDropdown = (root) => {
                    const menu = root.querySelector('[data-header-dropdown-menu]');

                    if (!menu || menu.classList.contains('hidden')) {
                        openLanguageDropdown(root);
                        return;
                    }

                    closeLanguageDropdowns();
                };

                const queueLanguageClose = () => {
                    if (languageCloseTimer) {
                        window.clearTimeout(languageCloseTimer);
                    }

                    languageCloseTimer = window.setTimeout(() => closeLanguageDropdowns(), 220);
                };

                const cancelLanguageClose = () => {
                    if (languageCloseTimer) {
                        window.clearTimeout(languageCloseTimer);
                        languageCloseTimer = null;
                    }
                };

                const openAccountDropdown = (root) => {
                    const menu = root.querySelector('[data-header-account-menu]');
                    const trigger = root.querySelector('[data-header-account-trigger]');
                    const icon = root.querySelector('[data-header-account-icon]');

                    closeCategoryMenu();
                    closeLanguageDropdowns();
                    closeAccountDropdowns(root);
                    menu?.classList.remove('hidden');
                    trigger?.setAttribute('aria-expanded', 'true');
                    icon?.classList.add('rotate-180');
                };

                const toggleAccountDropdown = (root) => {
                    const menu = root.querySelector('[data-header-account-menu]');

                    if (!menu || menu.classList.contains('hidden')) {
                        openAccountDropdown(root);
                        return;
                    }

                    closeAccountDropdowns();
                };

                const queueAccountClose = () => {
                    if (accountCloseTimer) {
                        window.clearTimeout(accountCloseTimer);
                    }

                    accountCloseTimer = window.setTimeout(() => closeAccountDropdowns(), 220);
                };

                const cancelAccountClose = () => {
                    if (accountCloseTimer) {
                        window.clearTimeout(accountCloseTimer);
                        accountCloseTimer = null;
                    }
                };

                const openCategoryMenu = () => {
                    if (!categoryMenu || !categoryTrigger) {
                        return;
                    }

                    if (categoryCloseTimer) {
                        window.clearTimeout(categoryCloseTimer);
                        categoryCloseTimer = null;
                    }

                    closeLanguageDropdowns();
                    closeAccountDropdowns();
                    categoryMenu.classList.remove('hidden');
                    categoryTrigger.setAttribute('aria-expanded', 'true');
                    categoryIcon?.classList.add('rotate-180');
                };

                const queueCategoryClose = () => {
                    if (!desktopQuery.matches) {
                        return;
                    }

                    if (categoryCloseTimer) {
                        window.clearTimeout(categoryCloseTimer);
                    }

                    categoryCloseTimer = window.setTimeout(closeCategoryMenu, 180);
                };

                languageDropdowns.forEach((root) => {
                    const trigger = root.querySelector('[data-header-dropdown-trigger]');

                    root.addEventListener('mouseenter', () => {
                        cancelLanguageClose();
                        openLanguageDropdown(root);
                    });
                    root.addEventListener('mouseleave', queueLanguageClose);

                    trigger?.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        toggleLanguageDropdown(root);
                    });

                    const menu = root.querySelector('[data-header-dropdown-menu]');

                    menu?.addEventListener('mouseenter', cancelLanguageClose);
                    menu?.addEventListener('mouseleave', queueLanguageClose);
                    menu?.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                });

                accountDropdowns.forEach((root) => {
                    const trigger = root.querySelector('[data-header-account-trigger]');
                    const menu = root.querySelector('[data-header-account-menu]');

                    root.addEventListener('mouseenter', () => {
                        cancelAccountClose();
                        openAccountDropdown(root);
                    });
                    root.addEventListener('mouseleave', queueAccountClose);

                    trigger?.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        toggleAccountDropdown(root);
                    });

                    menu?.addEventListener('mouseenter', cancelAccountClose);
                    menu?.addEventListener('mouseleave', queueAccountClose);
                    menu?.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                });

                if (categoryTrigger && categoryMenu) {
                    const categoryRoot = categoryMenu.parentElement;

                    categoryRoot?.addEventListener('mouseenter', () => {
                        if (categoryCloseTimer) {
                            window.clearTimeout(categoryCloseTimer);
                            categoryCloseTimer = null;
                        }
                    });
                    categoryRoot?.addEventListener('mouseleave', queueCategoryClose);
                    categoryTrigger.addEventListener('mouseenter', () => {
                        if (desktopQuery.matches) {
                            openCategoryMenu();
                        }
                    });
                    categoryTrigger.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();

                        if (categoryMenu.classList.contains('hidden')) {
                            openCategoryMenu();
                        } else {
                            closeCategoryMenu();
                        }
                    });
                    categoryMenu.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                }

                document.addEventListener('click', () => {
                    closeLanguageDropdowns();
                    closeAccountDropdowns();
                    closeCategoryMenu();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeLanguageDropdowns();
                        closeAccountDropdowns();
                        closeCategoryMenu();
                    }
                });
            })();
        </script>
        @stack('scripts')
    </body>
</html>
