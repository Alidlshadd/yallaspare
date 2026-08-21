@props([
    'heading',
    'formSubtitle',
    'panelTitle',
    'panelSubtitle',
    'panelTag',
    'panelButtonText',
    'panelButtonHref',
    'mode' => 'login',
])

@php
    $siteName = (string) ($systemSettings['site_name'] ?? config('app.name', 'YallaSpare'));
    $isRegister = $mode === 'register';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $heading }} | {{ $siteName }}</title>
    @include('partials.brand-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script nonce="{{ $cspNonce }}">
        (() => {
            try {
                const direction = window.sessionStorage.getItem('ys-auth-transition');
                if (direction === 'forward' || direction === 'backward') {
                    document.documentElement.dataset.authTransition = direction;
                }
            } catch (error) {
                // The transition is progressive enhancement; navigation still works.
            }
        })();
    </script>
</head>
<body class="ys-auth-page antialiased selection:bg-accent selection:text-navy" data-auth-mode="{{ $mode }}">
    <x-loading-overlay message="{{ __('Processing, please wait...') }}" variant="full" />

    <div class="ys-auth-route-transition" aria-hidden="true">
        <div class="ys-auth-transition-blade ys-auth-transition-accent"></div>
        <div class="ys-auth-transition-blade ys-auth-transition-shadow"></div>
        <div class="ys-auth-transition-blade ys-auth-transition-navy"></div>

        <div class="ys-auth-transition-core">
            <span class="ys-auth-transition-ring">
                <svg viewBox="0 0 100 100" fill="none">
                    <circle cx="50" cy="50" r="43" />
                    <circle cx="50" cy="50" r="30" stroke-dasharray="3 7" />
                    <path d="M50 4V19M50 81V96M4 50H19M81 50H96" />
                    <path d="M50 27 57 40l15 3-10 11 2 15-14-7-14 7 2-15-10-11 15-3 7-13Z" />
                </svg>
                <strong>YS</strong>
            </span>
            <span class="ys-auth-transition-copy">YALLASPARE · SECURE ACCESS</span>
        </div>
    </div>

    <main class="ys-auth-stage">
        <div class="ys-auth-orbit ys-auth-orbit-one" aria-hidden="true"></div>
        <div class="ys-auth-orbit ys-auth-orbit-two" aria-hidden="true"></div>

        <section class="ys-auth-shell" data-auth-portal>
            <aside class="ys-auth-aside">
                <div class="ys-auth-grid" aria-hidden="true"></div>
                <div class="ys-auth-glow" aria-hidden="true"></div>

                <div class="ys-auth-aside-top">
                    <a href="{{ url('/') }}" class="ys-auth-brand focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="{{ $siteName }}">
                        <x-brand-mark
                            :brand="$siteName"
                            wrapper-class="ys-auth-brand-mark"
                            img-class="ys-auth-brand-image"
                            fallback-class="ys-auth-brand-fallback"
                            fallback-text-class="ys-auth-brand-initials"
                        />
                        <span class="ys-auth-wordmark">Yalla<span>Spare</span></span>
                    </a>

                    <span class="ys-auth-series" aria-hidden="true">YS / ACCESS {{ $isRegister ? '02' : '01' }}</span>
                </div>

                <div class="ys-auth-technical" aria-hidden="true">
                    <svg viewBox="0 0 620 430" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g class="ys-auth-drawing-soft">
                            <circle cx="415" cy="205" r="152" />
                            <circle cx="415" cy="205" r="119" />
                            <circle cx="415" cy="205" r="72" />
                            <circle cx="415" cy="205" r="31" />
                            <path d="M415 53V357M263 205H567" />
                            <path d="M307 97L523 313M523 97L307 313" />
                            <path d="M415 86L449 153L523 165L470 217L482 291L415 256L348 291L360 217L307 165L381 153L415 86Z" />
                        </g>
                        <g class="ys-auth-drawing-strong">
                            <path d="M90 324H250L298 278H458" />
                            <path d="M86 344H224" />
                            <path d="M113 302H202L233 272" />
                            <circle cx="415" cy="205" r="92" stroke-dasharray="5 12" />
                            <path d="M574 205H608M415 18V50M415 360V397" />
                        </g>
                        <g class="ys-auth-drawing-accent">
                            <path d="M263 205A152 152 0 0 1 415 53" />
                            <path d="M85 324H174" />
                            <circle cx="415" cy="205" r="7" />
                        </g>
                    </svg>
                    <span class="ys-auth-spec ys-auth-spec-a">Ø 19.00</span>
                    <span class="ys-auth-spec ys-auth-spec-b">SYS 964</span>
                </div>

                <div class="ys-auth-story">
                    <span class="ys-auth-eyebrow">
                        <span class="ys-auth-live-dot" aria-hidden="true"></span>
                        {{ $panelTag }}
                    </span>

                    <h1>{{ $panelTitle }}</h1>
                    <p>{{ $panelSubtitle }}</p>

                    <div class="ys-auth-capabilities" aria-label="{{ __('YallaSpare Management System') }}">
                        <span>
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 6.5 10 3l7 3.5-7 3.5-7-3.5Z M3 10l7 3.5 7-3.5M3 13.5 10 17l7-3.5" /></svg>
                            {{ __('Smart Inventory') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 3.5h10v13H5zM7.5 7h5M7.5 10h5M7.5 13h3" /></svg>
                            {{ __('Order Management') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3.5 8.5 10 3l6.5 5.5v8h-13v-8ZM7 16.5V11h6v5.5" /></svg>
                            {{ __('Dealer Portal') }}
                        </span>
                    </div>
                </div>

                <div class="ys-auth-aside-footer">
                    <div class="ys-auth-secure-note">
                        <span class="ys-auth-shield" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="none"><path d="M10 2.5 16 5v4.4c0 3.6-2.4 6.6-6 8.1-3.6-1.5-6-4.5-6-8.1V5l6-2.5Z"/><path d="m7.5 10 1.6 1.6 3.6-3.8"/></svg>
                        </span>
                        <span><strong>{{ __('Secure') }}</strong><small>{{ __('Authorized Users') }}</small></span>
                    </div>

                    <a href="{{ $panelButtonHref }}" class="ys-auth-secondary-action" data-auth-nav>
                        {{ $panelButtonText }}
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5" /></svg>
                    </a>
                </div>
            </aside>

            <div class="ys-auth-main">
                <div class="ys-auth-toolbar">
                    <span class="ys-auth-toolbar-label">{{ __('YallaSpare Management System') }}</span>
                    <x-language-switcher class="ys-auth-language" />
                </div>

                <div class="ys-auth-form-wrap">
                    <div id="auth-panel" class="ys-auth-surface">
                        <div class="ys-auth-form-heading">
                            <span class="ys-auth-form-icon" aria-hidden="true">
                                @if ($isRegister)
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M15 19v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 3 17.5V19M9 9a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM17 8v6M14 11h6" /></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v11H5V10Z" /><path d="M12 14v3" /></svg>
                                @endif
                            </span>
                            <span>{{ $panelTag }}</span>
                        </div>
                        <h2>{{ $heading }}</h2>
                        <p class="ys-auth-form-subtitle">{{ $formSubtitle }}</p>

                        <div class="ys-auth-form">
                            {{ $slot }}
                        </div>
                    </div>
                </div>

                <p class="ys-auth-legal">© {{ now()->year }} {{ $siteName }} · {{ __('Secure account action') }}</p>
            </div>
        </section>
    </main>

    <script nonce="{{ $cspNonce }}">
        const authBody = document.body;
        const arrivingDirection = document.documentElement.dataset.authTransition;

        if (arrivingDirection === 'forward' || arrivingDirection === 'backward') {
            authBody.classList.add('ys-auth-arriving', `ys-auth-direction-${arrivingDirection}`);

            try {
                window.sessionStorage.removeItem('ys-auth-transition');
            } catch (error) {
                // Storage can be unavailable; the visual transition remains optional.
            }

            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(() => {
                    authBody.classList.add('ys-auth-arriving-ready');
                });
            });

            window.setTimeout(() => {
                authBody.classList.remove('ys-auth-arriving', 'ys-auth-arriving-ready', `ys-auth-direction-${arrivingDirection}`);
                delete document.documentElement.dataset.authTransition;
            }, 1050);
        }

        document.addEventListener('click', (event) => {
            const link = event.target.closest('[data-auth-nav]');
            if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }

            const href = link.getAttribute('href');
            const portal = document.querySelector('[data-auth-portal]');
            if (!href || !portal) {
                return;
            }

            event.preventDefault();

            if (link.dataset.navLocked === '1') {
                return;
            }
            link.dataset.navLocked = '1';

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                window.location.assign(href);
                return;
            }

            const direction = authBody.dataset.authMode === 'register' ? 'backward' : 'forward';

            try {
                window.sessionStorage.setItem('ys-auth-transition', direction);
            } catch (error) {
                // Navigation is unaffected when session storage is blocked.
            }

            authBody.classList.add('ys-auth-transitioning', `ys-auth-direction-${direction}`);
            window.setTimeout(() => window.location.assign(href), 720);
        });
    </script>
    @include('partials.language-switcher-script')
</body>
</html>
