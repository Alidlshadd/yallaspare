<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'Yalla Spare'))</title>
        <meta name="description" content="@yield('meta_description', 'Yalla Spare legal and support information.')">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

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
    </head>
    <body class="min-h-screen bg-white text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <header class="h-16 border-b border-slate-200/60 bg-white dark:border-slate-800/60 dark:bg-slate-950">
            <div class="mx-auto flex h-full w-full max-w-6xl items-center justify-between px-6">
                <a
                    href="{{ route('admin.dashboard') }}"
                    aria-label="{{ __('Go to dashboard') }}"
                    class="text-sm font-semibold tracking-tight text-slate-900 transition-opacity duration-200 hover:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-slate-100 dark:focus-visible:ring-slate-600 dark:focus-visible:ring-offset-slate-950"
                >
                    {{ __('Yalla Spare') }}
                </a>

                <button
                    type="button"
                    onclick="history.back()"
                    class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-slate-100 dark:focus-visible:ring-slate-600 dark:focus-visible:ring-offset-slate-950"
                    aria-label="{{ __('Go back') }}"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ __('Back') }}</span>
                </button>

                <x-language-switcher />
            </div>
        </header>

        <main class="px-6 py-24">
            <div class="mx-auto w-full max-w-5xl">
                @yield('content')
            </div>
        </main>

        @include('partials.site-footer', ['maxWidth' => 'max-w-6xl'])
        @include('partials.language-switcher-script')
    </body>
</html>
