@php
    $locale = app()->getLocale();
    $isRtl = str_starts_with($locale, 'ar') || str_starts_with($locale, 'ku');
    $dir = $isRtl ? 'rtl' : 'ltr';
    $siteName = $systemSettings['site_name'] ?? 'Yalla Spare';
    $logoUrl = $systemSettings['site_logo_url'] ?? null;
    $homeUrl = \Illuminate\Support\Facades\Route::has('user.shop.home') ? route('user.shop.home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : $homeUrl;
    $contactUrl = \Illuminate\Support\Facades\Route::has('legal.contact') ? route('legal.contact') : url('/contact');
    $pageTitle = $pageTitle ?? (($errorCode ?? 'Error') . ' | ' . $siteName);
    $errorCode = $errorCode ?? 'Error';
    $errorBadge = $errorBadge ?? 'System Notice';
    $errorTitle = $errorTitle ?? 'Something went wrong.';
    $errorDescription = $errorDescription ?? 'An unexpected error interrupted the request.';
    $primaryAction = $primaryAction ?? ['label' => __('Return Home'), 'url' => $homeUrl];
    $secondaryAction = $secondaryAction ?? ['label' => __('Browse Shop'), 'url' => $shopUrl];
    $tertiaryAction = $tertiaryAction ?? ['label' => __('Contact Support'), 'url' => $contactUrl];
    $metaCards = $metaCards ?? [];
    $recoverySteps = $recoverySteps ?? [];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $pageTitle }}</title>
        @include('partials.brand-head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[linear-gradient(180deg,#f8fafc_0%,#eef2ff_42%,#f8fafc_100%)] text-slate-900 dark:bg-[linear-gradient(180deg,#020617_0%,#0f172a_52%,#020617_100%)] dark:text-slate-100">
        <div class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-[30rem] bg-[radial-gradient(circle_at_top,rgba(14,165,233,0.16),transparent_40%),radial-gradient(circle_at_82%_18%,rgba(59,130,246,0.14),transparent_24%),radial-gradient(circle_at_20%_30%,rgba(16,185,129,0.12),transparent_28%)]"></div>
            <div class="pointer-events-none absolute left-1/2 top-20 h-64 w-64 -translate-x-1/2 rounded-full bg-cyan-200/30 blur-3xl dark:bg-cyan-500/10"></div>
            <div class="pointer-events-none absolute right-0 top-36 h-72 w-72 rounded-full bg-sky-200/20 blur-3xl dark:bg-sky-500/10"></div>

            <main class="relative mx-auto flex min-h-[calc(100vh-13rem)] max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8">
                <section class="grid w-full gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                    <div class="space-y-6">
                        <a href="{{ $homeUrl }}" class="inline-flex items-center gap-3 rounded-full border border-slate-200/80 bg-white/85 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm shadow-slate-900/5 backdrop-blur transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:border-slate-700">
                            <x-brand-mark
                                :logo-url="$logoUrl"
                                :brand="$siteName"
                                wrapper-class="inline-flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700"
                                img-class="h-full w-full object-contain"
                                fallback-class="inline-flex h-full w-full items-center justify-center bg-slate-100 dark:bg-slate-800"
                                fallback-text-class="text-[10px] font-semibold text-slate-700 dark:text-slate-200"
                            />
                            <span>{{ $siteName }}</span>
                        </a>

                        <div class="space-y-4">
                            <span class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-700 dark:border-cyan-900/60 dark:bg-cyan-950/20 dark:text-cyan-300">
                                {{ $errorBadge }}
                            </span>
                            <div class="space-y-3">
                                <p class="text-6xl font-black tracking-[-0.08em] text-slate-950 dark:text-white sm:text-7xl lg:text-8xl">{{ $errorCode }}</p>
                                <h1 class="max-w-2xl text-3xl font-bold tracking-[-0.04em] text-slate-950 dark:text-white sm:text-5xl">
                                    {{ $errorTitle }}
                                </h1>
                                <p class="max-w-2xl text-sm leading-7 text-slate-600 dark:text-slate-300 sm:text-base">
                                    {{ $errorDescription }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ $primaryAction['url'] }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100">
                                {{ $primaryAction['label'] }}
                            </a>
                            <a href="{{ $secondaryAction['url'] }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ $secondaryAction['label'] }}
                            </a>
                            <a href="{{ $tertiaryAction['url'] }}" class="inline-flex items-center justify-center rounded-2xl border border-cyan-200 bg-cyan-50 px-5 py-3 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100 dark:border-cyan-900/60 dark:bg-cyan-950/20 dark:text-cyan-300 dark:hover:bg-cyan-950/30">
                                {{ $tertiaryAction['label'] }}
                            </a>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="rounded-[2rem] border border-slate-200/80 bg-white/90 p-6 shadow-[0_28px_70px_rgba(15,23,42,0.10)] backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-black/30 sm:p-8">
                            @if (count($metaCards) > 0)
                                <div class="grid gap-4 sm:grid-cols-2">
                                    @foreach ($metaCards as $card)
                                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/70">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ $card['label'] ?? 'Note' }}</p>
                                            <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $card['title'] ?? '' }}</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $card['description'] ?? '' }}</p>
                                        </article>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-5 rounded-[1.6rem] border border-slate-200/90 bg-[linear-gradient(135deg,#ffffff_0%,#f8fafc_100%)] p-5 dark:border-slate-800 dark:bg-[linear-gradient(135deg,#0f172a_0%,#020617_100%)]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Quick Recovery') }}</p>
                                <div class="mt-4 space-y-3">
                                    @foreach ($recoverySteps as $index => $step)
                                        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950/70">
                                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-white dark:bg-cyan-500">
                                                {{ $index + 1 }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $step['title'] ?? '' }}</p>
                                                <p class="mt-1 text-xs leading-6 text-slate-500 dark:text-slate-400">{{ $step['description'] ?? '' }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            @include('partials.site-footer')
        </div>
    </body>
</html>
