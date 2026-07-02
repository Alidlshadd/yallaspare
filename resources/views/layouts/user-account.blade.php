@php
    $locale = app()->getLocale();
    $isRtl = str_starts_with($locale, 'ar') || str_starts_with($locale, 'ku');
    $dir = $isRtl ? 'rtl' : 'ltr';
    $brand = (string) ($systemSettings['site_name'] ?? 'YallaSpare');
    $titleContent = trim($__env->yieldContent('title'));
    $subtitleContent = trim($__env->yieldContent('subtitle'));
    $actionsContent = trim($__env->yieldContent('actions'));
    $themePreference = auth()->check() ? (auth()->user()->theme_preference ?? 'light') : 'light';
    $themePreference = in_array($themePreference, ['light', 'dark'], true) ? $themePreference : 'light';
    $fontSizePreference = auth()->check() ? (auth()->user()->font_size_preference ?? 'default') : 'default';
    $reducedMotion = auth()->check() ? (bool) (auth()->user()->reduced_motion ?? false) : false;
    $highContrastMode = auth()->check() ? (bool) (auth()->user()->high_contrast_mode ?? false) : false;
    $htmlClasses = trim('h-full'
        . ($fontSizePreference === 'large' ? ' user-font-large' : '')
        . ($fontSizePreference === 'xl' ? ' user-font-xl' : '')
        . ($reducedMotion ? ' user-reduced-motion' : '')
    );
    $bodyClasses = trim('user-shell min-h-full bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100'
        . ($highContrastMode ? ' user-high-contrast' : '')
    );
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}" class="{{ $htmlClasses }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $titleContent !== '' ? $titleContent . ' | ' . $brand : $brand }}</title>
        @include('partials.brand-head')
        <script nonce="{{ $cspNonce }}">
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
    <body class="{{ $bodyClasses }}" x-data="{ accountOpen: false }">
        <div class="min-h-screen">
            <x-user.account-header :title="$titleContent !== '' ? $titleContent : 'Account'" :subtitle="$subtitleContent !== '' ? $subtitleContent : null">
                @if ($actionsContent !== '')
                    <x-slot name="actions">
                        @yield('actions')
                    </x-slot>
                @endif
            </x-user.account-header>

            <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
                @yield('content')
            </main>

            @include('partials.site-footer', ['maxWidth' => 'max-w-6xl'])
            @include('partials.language-switcher-script')
        </div>
    </body>
</html>
