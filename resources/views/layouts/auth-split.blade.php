@php
    $isFormLeft = $formPosition === 'left';
    $asideOrder = $isFormLeft ? 'lg:order-2' : 'lg:order-1';
    $formOrder = $isFormLeft ? 'lg:order-1' : 'lg:order-2';
    $panelEnterClass = $enterDirection === 'left' ? 'auth-enter-left' : 'auth-enter-right';
    $asideEnterClass = $enterDirection === 'left' ? 'auth-enter-right' : 'auth-enter-left';
    // Login and register used to be told apart by hue — sky versus amber —
    // neither of which is a brand colour. They are the same brand now; the
    // panel that is *not* the current page is the one wearing the accent,
    // since its button is the way across.
    $isRegisterTheme = $panelTheme === 'register';
    $tagClasses = $isRegisterTheme
        ? 'border-accent/35 bg-accent/10 text-accent-ink dark:border-accent/30 dark:bg-accent/10 dark:text-accent'
        : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300';
    $buttonClasses = $isRegisterTheme
        ? 'border-accent/35 bg-accent/10 text-accent-ink hover:border-accent/60 hover:bg-accent/15 dark:border-accent/40 dark:bg-accent/10 dark:text-accent dark:hover:border-accent/70 dark:hover:bg-accent/20'
        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:border-white/20 dark:hover:bg-white/10';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $heading }} | {{ $systemSettings['site_name'] ?? 'YallaSpare' }}</title>
    @include('partials.brand-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes authEnterFromLeft {
            from { opacity: 0; transform: translateX(-40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes authEnterFromRight {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes authSlideOutLeft {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(-40px); }
        }

        @keyframes authSlideOutRight {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(40px); }
        }

        .auth-enter-left {
            animation: authEnterFromLeft 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .auth-enter-right {
            animation: authEnterFromRight 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .auth-exit-left {
            animation: authSlideOutLeft 280ms ease-in forwards;
        }

        .auth-exit-right {
            animation: authSlideOutRight 280ms ease-in forwards;
        }

        #auth-aside a,
        #auth-aside button,
        #auth-panel button,
        #auth-panel a {
            touch-action: manipulation;
        }

        html:not(.dark) #auth-panel :is(label, .text-slate-300) {
            color: #334155 !important;
        }

        html:not(.dark) #auth-panel :is(input:not([type='checkbox']):not([type='radio']), select, textarea) {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }

        html:not(.dark) #auth-panel :is(input:not([type='checkbox']):not([type='radio']), select, textarea)::placeholder {
            color: var(--text-muted) !important;
        }

        html:not(.dark) #auth-panel a {
            color: #475569 !important;
            text-decoration-color: var(--text-secondary) !important;
        }

        html:not(.dark) #auth-panel a:hover {
            color: var(--brand-orange-ink) !important;
            text-decoration-color: var(--brand-orange) !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased selection:bg-accent selection:text-navy dark:bg-slate-950 dark:text-white">
    <x-loading-overlay message="{{ __('Processing, please wait...') }}" variant="full" />

    <div class="fixed right-4 top-4 z-50">
        <x-language-switcher />
    </div>

    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 -z-20 bg-[radial-gradient(circle_at_top,_rgba(7,7,64,0.09),_transparent_42%),radial-gradient(circle_at_75%_80%,_rgba(255,106,0,0.07),_transparent_30%)] dark:bg-[radial-gradient(circle_at_top,_rgba(43,43,143,0.30),_transparent_42%),radial-gradient(circle_at_75%_80%,_rgba(255,138,61,0.10),_transparent_30%)]"></div>

        <section class="grid w-full max-w-6xl grid-cols-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 backdrop-blur dark:border-white/10 dark:bg-slate-900/50 dark:shadow-black/50 lg:grid-cols-2">
            <aside id="auth-aside" class="relative overflow-hidden bg-gradient-to-br from-white via-slate-50 to-slate-100 p-8 text-slate-950 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-white sm:p-10 lg:p-12 {{ $asideOrder }} {{ $asideEnterClass }}">
                <div class="pointer-events-none absolute -left-10 top-10 h-40 w-40 rounded-full border border-slate-200 dark:border-white/10"></div>
                <div class="pointer-events-none absolute right-12 top-20 h-24 w-24 rotate-12 rounded-lg border border-accent/25 dark:border-accent/25"></div>
                <div class="pointer-events-none absolute -bottom-10 right-0 h-52 w-52 rounded-full bg-slate-200/70 blur-2xl dark:bg-white/5"></div>

                <div class="relative z-10">
                    @if ($panelTag)
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold tracking-wide {{ $tagClasses }}">
                            {{ $panelTag }}
                        </span>
                    @endif
                    <h1 class="text-4xl font-bold tracking-tight text-primary dark:text-white sm:text-5xl">{{ $panelTitle }}</h1>
                    <p class="mt-3 max-w-md text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $panelSubtitle }}</p>

                    @if ($panelButtonText)
                        @if ($panelButtonAction === 'submit')
                            <button
                                type="button"
                                data-auth-submit
                                class="pointer-events-auto mt-6 inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-semibold transition {{ $buttonClasses }}"
                            >
                                {{ $panelButtonText }}
                            </button>
                        @elseif ($panelButtonAction === 'none')
                            <span
                                class="pointer-events-none mt-6 inline-flex cursor-default items-center justify-center rounded-lg border px-4 py-2 text-sm font-semibold transition {{ $buttonClasses }}"
                            >
                                {{ $panelButtonText }}
                            </span>
                        @elseif ($panelButtonHref)
                            <a
                                href="{{ $panelButtonHref }}"
                                @if ($panelExitDirection) data-auth-nav data-exit-direction="{{ $panelExitDirection }}" @endif
                                class="pointer-events-auto mt-6 inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-semibold transition {{ $buttonClasses }}"
                            >
                                {{ $panelButtonText }}
                            </a>
                        @endif
                    @endif
                </div>
            </aside>

            <div class="flex items-center bg-slate-50/75 p-6 dark:bg-slate-950/75 sm:p-8 lg:p-10 {{ $formOrder }}">
                <div id="auth-panel" class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/10 transition-all duration-300 dark:border-white/10 dark:bg-slate-900/70 dark:shadow-black/40 {{ $panelEnterClass }} sm:p-7">
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ $heading }}</h2>
                    {{ $slot }}
                </div>
            </div>
        </section>
    </main>

    <script nonce="{{ $cspNonce }}">
        document.addEventListener('click', (event) => {
            const link = event.target.closest('[data-auth-nav]');
            if (!link) {
                return;
            }

            if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }

            const href = link.getAttribute('href');
            if (!href) {
                return;
            }

            if (link.dataset.navLocked === '1') {
                event.preventDefault();
                return;
            }
            link.dataset.navLocked = '1';

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            const panel = document.getElementById('auth-panel');
            const aside = document.getElementById('auth-aside');
            if (!panel || !aside) {
                return;
            }

            event.preventDefault();
            panel.classList.remove('auth-exit-left', 'auth-exit-right');
            aside.classList.remove('auth-exit-left', 'auth-exit-right');
            panel.classList.add(link.dataset.exitDirection === 'left' ? 'auth-exit-left' : 'auth-exit-right');
            aside.classList.add(link.dataset.exitDirection === 'left' ? 'auth-exit-right' : 'auth-exit-left');

            window.setTimeout(() => {
                window.location.assign(href);
            }, 140);
        });

        document.querySelectorAll('[data-auth-submit]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = document.querySelector('[data-auth-form]') || document.querySelector('form');
                if (!form) {
                    return;
                }

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                form.submit();
            });
        });

    </script>
    @include('partials.language-switcher-script')
</body>
</html>
