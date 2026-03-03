@php
    $isFormLeft = $formPosition === 'left';
    $asideOrder = $isFormLeft ? 'lg:order-2' : 'lg:order-1';
    $formOrder = $isFormLeft ? 'lg:order-1' : 'lg:order-2';
    $panelEnterClass = $enterDirection === 'left' ? 'auth-enter-left' : 'auth-enter-right';
    $asideEnterClass = $enterDirection === 'left' ? 'auth-enter-right' : 'auth-enter-left';
    $isRegisterTheme = $panelTheme === 'register';
    $tagClasses = $isRegisterTheme
        ? 'border-amber-300/30 bg-amber-300/10 text-amber-100'
        : 'border-sky-300/30 bg-sky-300/10 text-sky-100';
    $buttonClasses = $isRegisterTheme
        ? 'border-amber-400/40 bg-amber-300/10 text-amber-100 hover:border-amber-300/70 hover:bg-amber-300/20'
        : 'border-sky-400/40 bg-sky-300/10 text-sky-100 hover:border-sky-300/70 hover:bg-sky-300/20';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $heading }} | {{ $systemSettings['site_name'] ?? 'YallaSpare' }}</title>

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
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased selection:bg-red-600 selection:text-white">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 -z-20 bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.22),_transparent_42%),radial-gradient(circle_at_75%_80%,_rgba(220,38,38,0.14),_transparent_30%)]"></div>

        <section class="grid w-full max-w-6xl grid-cols-1 overflow-hidden rounded-3xl border border-white/10 bg-slate-900/50 shadow-2xl shadow-black/50 backdrop-blur lg:grid-cols-2">
            <aside id="auth-aside" class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-8 sm:p-10 lg:p-12 {{ $asideOrder }} {{ $asideEnterClass }}">
                <div class="pointer-events-none absolute -left-10 top-10 h-40 w-40 rounded-full border border-white/10"></div>
                <div class="pointer-events-none absolute right-12 top-20 h-24 w-24 rotate-12 rounded-lg border border-red-400/20"></div>
                <div class="pointer-events-none absolute -bottom-10 right-0 h-52 w-52 rounded-full bg-white/5 blur-2xl"></div>

                <div class="relative z-10">
                    @if ($panelTag)
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold tracking-wide {{ $tagClasses }}">
                            {{ $panelTag }}
                        </span>
                    @endif
                    <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">{{ $panelTitle }}</h1>
                    <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">{{ $panelSubtitle }}</p>

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

            <div class="flex items-center bg-slate-950/75 p-6 sm:p-8 lg:p-10 {{ $formOrder }}">
                <div id="auth-panel" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl shadow-black/40 transition-all duration-300 {{ $panelEnterClass }} sm:p-7">
                    <h2 class="text-2xl font-semibold tracking-tight text-white">{{ $heading }}</h2>
                    {{ $slot }}
                </div>
            </div>
        </section>
    </main>

    <script>
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

        document.querySelectorAll('[data-auth-form]').forEach((form) => {
            form.addEventListener('submit', () => {
                const submitButtons = form.querySelectorAll('button[type="submit"]');
                submitButtons.forEach((button) => {
                    button.disabled = true;
                    button.classList.add('opacity-70', 'cursor-not-allowed');
                });
            });
        });
    </script>
</body>
</html>
