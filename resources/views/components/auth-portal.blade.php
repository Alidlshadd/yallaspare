@props([
    'heading',
    'formSubtitle',
    'kicker',
    'panelEyebrow',
    'panelTitle',
    'panelTitleAccent' => null,
    'panelSubtitle',
    'switchText',
    'switchLabel',
    'switchHref',
    'loadingMessage' => null,
    'mode' => 'login',
])

@php
    $siteName = (string) ($systemSettings['site_name'] ?? config('app.name', 'YallaSpare'));
    $mode = in_array($mode, ['login', 'register', 'recover'], true) ? $mode : 'login';
    $serial = [
        'login' => 'YS / ACCESS 01',
        'register' => 'YS / ACCESS 02',
        'recover' => 'YS / ACCESS 03',
    ][$mode];

    // One line icon per screen, drawn on the same 20x20 grid at the same
    // stroke weight, so the three kickers read as one set.
    $kickerPath = [
        'login' => 'M6 8.5V6.8a4 4 0 0 1 8 0v1.7M4.5 8.5h11v8.5h-11ZM10 11.8v2',
        'register' => 'M12.5 16v-1.2a3.8 3.8 0 0 0-3.8-3.8H5.8A3.8 3.8 0 0 0 2 14.8V16M7.2 8.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM15 6.5v5M12.5 9h5',
        'recover' => 'M11.4 8.6a3.4 3.4 0 1 0-3.2 5.6l-1 1H5.4v1.9H3.5v-1.9l4.7-4.7M13.2 6.8h.01',
    ][$mode];

    // Both screens carry the same three capabilities. They are what the
    // platform is, not a per-page selling point, so they do not change
    // between sign-in and sign-up — only the headline above them does.
    $features = [
        [
            'title' => __('Smart Inventory'),
            'body' => __('Live stock across every warehouse and shelf.'),
            'path' => 'M3 6.5 10 3l7 3.5-7 3.5-7-3.5ZM3 10l7 3.5 7-3.5M3 13.5 10 17l7-3.5',
        ],
        [
            'title' => __('Order Management'),
            'body' => __('From basket to delivery in one timeline.'),
            'path' => 'M5.5 3.5h9v13h-9zM8 7h4M8 10h4M8 13h2.5',
        ],
        [
            'title' => __('Dealer Network'),
            'body' => __('Pricing, permissions and partners in one place.'),
            'path' => 'M10 3.2 16.5 6v4.6c0 3.2-2.6 5.7-6.5 7.2-3.9-1.5-6.5-4-6.5-7.2V6L10 3.2ZM7.4 10.2l1.8 1.8 3.6-3.8',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#070740">

    <title>{{ $heading }} | {{ $siteName }}</title>
    @include('partials.brand-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ys-auth-page antialiased selection:bg-accent selection:text-navy">
    <x-loading-overlay :message="$loadingMessage ?: __('Preparing your workspace')" variant="full" />

    <main class="ys-auth-stage">
        <aside class="ys-auth-aside">
            {{-- Blueprint backdrop: a caliper ring over a shop-floor datum
                 line. Drawn rather than decorated, so it reads as a technical
                 document instead of an abstract blob. --}}
            <div class="ys-auth-blueprint" aria-hidden="true">
                <svg viewBox="0 0 640 600" xmlns="http://www.w3.org/2000/svg">
                    <g data-line="soft">
                        <circle cx="320" cy="300" r="228" />
                        <circle cx="320" cy="300" r="176" />
                        <circle cx="320" cy="300" r="108" />
                        <path d="M320 44v512M64 300h512" />
                        <path d="M158 138 482 462M482 138 158 462" />
                    </g>
                    <g data-line="strong">
                        <circle cx="320" cy="300" r="140" stroke-dasharray="6 14" />
                        <path d="M320 300 320 160" />
                        <path d="M48 468h176l54-52h188" />
                        <path d="M44 496h150" />
                        <path d="M86 440h96l34-32" />
                        <path d="M556 300h44M320 32v40M320 528v40" />
                    </g>
                    <g data-line="accent">
                        <path d="M92 300a228 228 0 0 1 228-228" />
                        <circle cx="320" cy="300" r="9" />
                        <path d="M44 468h96" />
                        <g data-line="sweep">
                            <path d="M320 300 320 92" stroke-opacity="0.85" />
                        </g>
                    </g>
                </svg>
            </div>

            <div class="ys-auth-aside-top">
                <a href="{{ url('/') }}" class="ys-auth-brand" aria-label="{{ $siteName }}">
                    <x-brand-mark
                        :brand="$siteName"
                        wrapper-class="ys-auth-brand-mark"
                        img-class="ys-auth-brand-image"
                        fallback-class="ys-auth-brand-fallback"
                        fallback-text-class="ys-auth-brand-initials"
                    />
                    <span class="ys-auth-wordmark">Yalla<span>Spare</span></span>
                </a>

                <span class="ys-auth-serial" aria-hidden="true">{{ $serial }}</span>
            </div>

            <div class="ys-auth-story">
                <span class="ys-auth-eyebrow">
                    <span class="ys-auth-dot" aria-hidden="true"></span>
                    {{ $panelEyebrow }}
                </span>

                <h1>
                    {{ $panelTitle }}@if ($panelTitleAccent) <em>{{ $panelTitleAccent }}</em>@endif
                </h1>

                <p>{{ $panelSubtitle }}</p>

                <div class="ys-auth-rule" aria-hidden="true"></div>

                <ul class="ys-auth-features">
                    @foreach ($features as $feature)
                        <li class="ys-auth-feature">
                            <span class="ys-auth-feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 20 20"><path d="{{ $feature['path'] }}" /></svg>
                            </span>
                            <span class="ys-auth-feature-body">
                                <b>{{ $feature['title'] }}</b>
                                <small>{{ $feature['body'] }}</small>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="ys-auth-aside-foot">
                <span class="ys-auth-shield" aria-hidden="true">
                    <svg viewBox="0 0 20 20">
                        <path d="M10 2.5 16 5v4.4c0 3.6-2.4 6.6-6 8.1-3.6-1.5-6-4.5-6-8.1V5l6-2.5Z" />
                        <path d="m7.5 10 1.6 1.6 3.6-3.8" />
                    </svg>
                </span>
                <span class="ys-auth-secure-copy">
                    <b>{{ __('Encrypted session') }}</b>
                    <small>{{ __('Authorized users only') }}</small>
                </span>
            </div>
        </aside>

        <div class="ys-auth-main">
            <div class="ys-auth-toolbar">
                <span class="ys-auth-toolbar-label">{{ __('Management System') }}</span>
                <x-language-switcher class="ys-auth-language" />
            </div>

            <div class="ys-auth-panel-wrap">
                <div id="auth-panel" class="ys-auth-panel">
                    <span class="ys-auth-kicker">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="{{ $kickerPath }}" /></svg>
                        {{ $kicker }}
                    </span>

                    <h2>{{ $heading }}</h2>
                    <p class="ys-auth-sub">{{ $formSubtitle }}</p>

                    <div class="ys-auth-form">
                        {{ $slot }}
                    </div>

                    <p class="ys-auth-switch">
                        {{ $switchText }}
                        <a href="{{ $switchHref }}">{{ $switchLabel }}</a>
                    </p>
                </div>
            </div>

            <p class="ys-auth-foot">
                <svg viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M10 2.5 16 5v4.4c0 3.6-2.4 6.6-6 8.1-3.6-1.5-6-4.5-6-8.1V5l6-2.5Z" />
                </svg>
                © {{ now()->year }} {{ $siteName }} · {{ __('Protected connection') }}
            </p>
        </div>
    </main>

    @include('partials.language-switcher-script')
</body>
</html>
