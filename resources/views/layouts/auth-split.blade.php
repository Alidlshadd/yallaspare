@php
    $isFormLeft = $formPosition === 'left';
    $asideOrder = $isFormLeft ? 'lg:order-2' : 'lg:order-1';
    $formOrder = $isFormLeft ? 'lg:order-1' : 'lg:order-2';
    $panelEnterClass = $enterDirection === 'left' ? 'auth-enter-left' : 'auth-enter-right';
    $asideEnterClass = $enterDirection === 'left' ? 'auth-enter-right' : 'auth-enter-left';
    $isRegisterTheme = $panelTheme === 'register';
    $tagClasses = $isRegisterTheme
        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-300/30 dark:bg-amber-300/10 dark:text-amber-100'
        : 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-300/30 dark:bg-sky-300/10 dark:text-sky-100';
    $buttonClasses = $isRegisterTheme
        ? 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100 dark:border-amber-400/40 dark:bg-amber-300/10 dark:text-amber-100 dark:hover:border-amber-300/70 dark:hover:bg-amber-300/20'
        : 'border-sky-200 bg-sky-50 text-sky-700 hover:border-sky-300 hover:bg-sky-100 dark:border-sky-400/40 dark:bg-sky-300/10 dark:text-sky-100 dark:hover:border-sky-300/70 dark:hover:bg-sky-300/20';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
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

        html:not(.dark) #auth-panel :is(label, .text-slate-300) {
            color: #334155 !important;
        }

        html:not(.dark) #auth-panel :is(input, select, textarea) {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }

        html:not(.dark) #auth-panel :is(input, select, textarea)::placeholder {
            color: #94a3b8 !important;
        }

        html:not(.dark) #auth-panel a {
            color: #475569 !important;
            text-decoration-color: #cbd5e1 !important;
        }

        html:not(.dark) #auth-panel a:hover {
            color: #dc2626 !important;
            text-decoration-color: #f87171 !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased selection:bg-red-600 selection:text-white dark:bg-slate-950 dark:text-white">
    <div class="fixed right-4 top-4 z-50">
        <x-language-switcher />
    </div>

    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 -z-20 bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.10),_transparent_42%),radial-gradient(circle_at_75%_80%,_rgba(220,38,38,0.08),_transparent_30%)] dark:bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.22),_transparent_42%),radial-gradient(circle_at_75%_80%,_rgba(220,38,38,0.14),_transparent_30%)]"></div>

        <section class="grid w-full max-w-6xl grid-cols-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 backdrop-blur dark:border-white/10 dark:bg-slate-900/50 dark:shadow-black/50 lg:grid-cols-2">
            <aside id="auth-aside" class="relative overflow-hidden bg-gradient-to-br from-white via-slate-50 to-slate-100 p-8 text-slate-950 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-white sm:p-10 lg:p-12 {{ $asideOrder }} {{ $asideEnterClass }}">
                <div class="pointer-events-none absolute -left-10 top-10 h-40 w-40 rounded-full border border-slate-200 dark:border-white/10"></div>
                <div class="pointer-events-none absolute right-12 top-20 h-24 w-24 rotate-12 rounded-lg border border-red-200 dark:border-red-400/20"></div>
                <div class="pointer-events-none absolute -bottom-10 right-0 h-52 w-52 rounded-full bg-slate-200/70 blur-2xl dark:bg-white/5"></div>

                <div class="relative z-10">
                    @if ($panelTag)
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold tracking-wide {{ $tagClasses }}">
                            {{ $panelTag }}
                        </span>
                    @endif
                    <h1 class="text-4xl font-bold tracking-tight text-[#070740] dark:text-white sm:text-5xl">{{ $panelTitle }}</h1>
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

        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const wrapper = button.closest('.relative');
                const input = wrapper?.querySelector('[data-password-input]');
                const icon = button.querySelector('[data-password-toggle-icon]');

                if (!input) {
                    return;
                }

                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';

                const label = isVisible
                    ? button.dataset.showLabel || 'Show password'
                    : button.dataset.hideLabel || 'Hide password';

                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
                icon?.classList.toggle('fa-eye', isVisible);
                icon?.classList.toggle('fa-eye-slash', !isVisible);
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
    @include('partials.language-switcher-script')
</body>
</html>
