<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ku'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('YallaSpare') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased selection:bg-red-600 selection:text-white dark:bg-slate-950 dark:text-white">
    <div class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.10),_transparent_42%),radial-gradient(circle_at_75%_80%,_rgba(220,38,38,0.08),_transparent_30%)] dark:bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.22),_transparent_42%),radial-gradient(circle_at_75%_80%,_rgba(220,38,38,0.14),_transparent_30%)]"></div>

        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-12">
            <section class="flex flex-1 flex-col justify-center py-8 sm:py-10">
                <div class="max-w-3xl">
                    <h1 class="text-3xl font-bold tracking-tight text-[#070740] sm:text-4xl lg:text-6xl dark:text-white">
                        {{ __('YallaSpare Auto Parts System') }}
                    </h1>

                    <p class="mt-3 text-sm text-slate-600 sm:mt-4 sm:text-base lg:text-lg dark:text-slate-300">
                        {{ __('Inventory. Orders. Dealers. Full Control.') }}
                    </p>

                    <div class="relative z-20 mt-6 inline-flex w-full flex-col items-stretch gap-3 sm:mt-8 sm:w-auto sm:items-center sm:gap-4">
                        <div class="grid grid-cols-1 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-center sm:gap-4">
                            <a
                                href="/login"
                                class="pointer-events-auto inline-flex items-center justify-center rounded-lg border border-red-500/60 bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 sm:px-5 sm:py-2.5"
                            >
                                {{ __('Login') }}
                            </a>

                            @if (Route::has('register'))
                                <a
                                    href="/register"
                                class="pointer-events-auto inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 sm:px-5 sm:py-2.5 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-500 dark:hover:bg-slate-800 dark:focus-visible:ring-offset-slate-950"
                                >
                                    {{ __('Create Account') }}
                                </a>
                            @endif
                        </div>

                        <a
                            href="{{ route('shop.index') }}"
                            class="pointer-events-auto inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 sm:px-5 sm:py-2.5 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-slate-400 dark:hover:bg-slate-700 dark:focus-visible:ring-offset-slate-950"
                        >
                            {{ __('Guest') }}
                        </a>
                    </div>
                </div>
            </section>

            <section class="mt-8 sm:mt-12">
                <h2 class="sr-only">{{ __('Core Features') }}</h2>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-5 xl:grid-cols-4">
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-900/5 transition duration-200 hover:-translate-y-1 hover:border-red-300 hover:bg-slate-50 sm:p-6 dark:border-slate-800 dark:bg-slate-900/75 dark:shadow-black/20 dark:hover:border-red-500/50 dark:hover:bg-slate-900">
                        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Smart Inventory') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ __('Real-time stock tracking and inventory movements.') }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-900/5 transition duration-200 hover:-translate-y-1 hover:border-red-300 hover:bg-slate-50 sm:p-6 dark:border-slate-800 dark:bg-slate-900/75 dark:shadow-black/20 dark:hover:border-red-500/50 dark:hover:bg-slate-900">
                        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Order Management') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ __('Full order lifecycle tracking with status history.') }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-900/5 transition duration-200 hover:-translate-y-1 hover:border-red-300 hover:bg-slate-50 sm:p-6 dark:border-slate-800 dark:bg-slate-900/75 dark:shadow-black/20 dark:hover:border-red-500/50 dark:hover:bg-slate-900">
                        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Dealer System') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ __('Manage dealers and permissions securely.') }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-900/5 transition duration-200 hover:-translate-y-1 hover:border-red-300 hover:bg-slate-50 sm:p-6 dark:border-slate-800 dark:bg-slate-900/75 dark:shadow-black/20 dark:hover:border-red-500/50 dark:hover:bg-slate-900">
                        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Audit Logs') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ __('Track every action with secure activity logging.') }}
                        </p>
                    </article>
                </div>
            </section>

        </main>
    </div>
    @include('partials.site-footer')
</body>
</html>


