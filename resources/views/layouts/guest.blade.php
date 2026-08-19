<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $systemSettings['site_name'] ?? config('app.name', 'Laravel') }}</title>
        @include('partials.brand-head')


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <x-loading-overlay message="{{ __('Processing, please wait...') }}" variant="full" />

        <div class="fixed right-4 top-4 z-50">
            <x-language-switcher />
        </div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-brand-mark
                        :logo-url="$systemSettings['site_logo_url'] ?? null"
                        :brand="$systemSettings['site_name'] ?? 'YallaSpare'"
                        wrapper-class="app-logo-mark"
                        img-class="h-20 w-auto object-contain"
                        fallback-class="inline-flex h-20 w-20 items-center justify-center rounded-lg bg-slate-800"
                        fallback-text-class="text-lg font-semibold text-white"
                    />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>

        @include('partials.site-footer')
        @include('partials.language-switcher-script')
    </body>
</html>
