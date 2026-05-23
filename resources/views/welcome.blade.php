<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('YallaSpare') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased selection:bg-red-600 selection:text-white">
    <div class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.22),_transparent_42%),radial-gradient(circle_at_75%_80%,_rgba(220,38,38,0.14),_transparent_30%)]"></div>

        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-12 sm:px-8 lg:px-12">
            <section class="flex flex-1 flex-col justify-center">
                <div class="max-w-3xl">
                    <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('YallaSpare Auto Parts System') }}
                    </h1>

                    <p class="mt-4 text-base text-slate-300 sm:text-lg">
                        {{ __('Inventory. Orders. Dealers. Full Control.') }}
                    </p>

                    <div class="relative z-20 mt-8 inline-flex flex-col items-center gap-4">
                        <div class="flex flex-wrap items-center justify-center gap-4">
                            <a
                                href="/login"
                                class="pointer-events-auto inline-flex items-center justify-center rounded-lg border border-red-500/60 bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                            >
                                {{ __('Login') }}
                            </a>

                            @if (Route::has('register'))
                                <a
                                    href="/register"
                                    class="pointer-events-auto inline-flex items-center justify-center rounded-lg border border-slate-700 bg-slate-900 px-5 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                                >
                                    {{ __('Create Account') }}
                                </a>
                            @endif
                        </div>

                        <a
                            href="{{ route('shop.index') }}"
                            class="pointer-events-auto inline-flex items-center justify-center rounded-lg border border-slate-600 bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-slate-400 hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                        >
                            {{ __('Guest') }}
                        </a>
                    </div>
                </div>
            </section>

            <section class="mt-12">
                <h2 class="sr-only">{{ __('Core Features') }}</h2>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-6 shadow-xl shadow-black/20 transition duration-200 hover:-translate-y-1 hover:border-red-500/50 hover:bg-slate-900">
                        <h3 class="text-lg font-semibold text-white">{{ __('Smart Inventory') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            {{ __('Real-time stock tracking and inventory movements.') }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-6 shadow-xl shadow-black/20 transition duration-200 hover:-translate-y-1 hover:border-red-500/50 hover:bg-slate-900">
                        <h3 class="text-lg font-semibold text-white">{{ __('Order Management') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            {{ __('Full order lifecycle tracking with status history.') }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-6 shadow-xl shadow-black/20 transition duration-200 hover:-translate-y-1 hover:border-red-500/50 hover:bg-slate-900">
                        <h3 class="text-lg font-semibold text-white">{{ __('Dealer System') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            {{ __('Manage dealers and permissions securely.') }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-6 shadow-xl shadow-black/20 transition duration-200 hover:-translate-y-1 hover:border-red-500/50 hover:bg-slate-900">
                        <h3 class="text-lg font-semibold text-white">{{ __('Audit Logs') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
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


