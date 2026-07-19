@extends('layouts.user')

@section('title', __('About Us'))
@section('meta_description', __('Learn about Yalla Spare mission, products, delivery, support, and commitment to customer satisfaction.'))

@section('content')
    <div data-vision-page data-about-page class="mx-auto w-full max-w-6xl">
        {{-- Hero: bold headline with flowing amber gradient, breathing glow blobs --}}
        <section class="abt-hero relative overflow-hidden rounded-3xl px-6 py-16 text-center sm:py-20">
            <span class="abt-blob abt-b1" aria-hidden="true"></span>
            <span class="abt-blob abt-b2" aria-hidden="true"></span>
            <span class="sup-in relative inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-white px-4 py-1.5 text-xs font-bold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                <span class="inline-block h-2 w-2 rounded-full bg-amber-400" aria-hidden="true"></span>
                {{ __('Modern auto spare parts store') }}
            </span>
            <h1 class="sup-in relative mx-auto mt-6 max-w-3xl text-5xl font-extrabold leading-[1.05] tracking-tighter text-primary sm:text-6xl dark:text-white" style="animation-delay: .12s">
                {{ __('Finding the right part') }}<br>
                <span class="abt-grad">{{ __('is no longer a struggle.') }}</span>
            </h1>
            <p class="sup-in relative mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-600 dark:text-slate-300" style="animation-delay: .24s">
                {{ __('We are an online auto spare parts store dedicated to providing reliable and high-quality automotive parts for different vehicle brands and models.') }}
            </p>
            <div class="sup-in relative mt-8 flex flex-wrap items-center justify-center gap-3" style="animation-delay: .36s">
                <a href="{{ route('shop.index') }}" class="inline-flex items-center rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-lg shadow-primary/25 transition hover:-translate-y-0.5 hover:bg-[#0d1156] hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:bg-amber-400 dark:text-[#070740] dark:shadow-amber-400/20 dark:hover:bg-amber-300">
                    {{ __('Find your part') }}
                </a>
                <a href="{{ route('legal.vision') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-primary transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:hover:border-slate-600">
                    {{ __('Our Vision') }} <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </section>

        {{-- Infinite category marquee --}}
        <div class="abt-marquee mt-4 rounded-2xl" aria-hidden="true">
            <div class="abt-track">
                @for ($i = 0; $i < 2; $i++)
                    <span>{{ __('Engine parts') }}</span><span class="abt-dot">&#9670;</span>
                    <span>{{ __('Brake components') }}</span><span class="abt-dot">&#9670;</span>
                    <span>{{ __('Suspension parts') }}</span><span class="abt-dot">&#9670;</span>
                    <span>{{ __('Electrical components') }}</span><span class="abt-dot">&#9670;</span>
                    <span>{{ __('Filters and maintenance products') }}</span><span class="abt-dot">&#9670;</span>
                    <span>{{ __('Body parts and accessories') }}</span><span class="abt-dot">&#9670;</span>
                @endfor
            </div>
        </div>

        {{-- Scrollytelling: sticky rail + story panels --}}
        <section class="mt-14 lg:grid lg:grid-cols-[240px,1fr] lg:gap-12">
            <aside class="hidden lg:block">
                <div class="sticky top-28 space-y-1">
                    <div data-about-dot="why" class="abt-dotr abt-on"><i aria-hidden="true"></i>{{ __('Why we exist') }}</div>
                    <div data-about-dot="how" class="abt-dotr"><i aria-hidden="true"></i>{{ __('How we work') }}</div>
                    <div data-about-dot="next" class="abt-dotr"><i aria-hidden="true"></i>{{ __("Where we're going") }}</div>
                </div>
            </aside>
            <div class="space-y-8">
                <article data-vision-reveal data-about-panel="why" class="rounded-3xl border border-slate-200/80 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-amber-600 lg:hidden dark:text-amber-400">{{ __('Why we exist') }}</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-primary lg:mt-0 dark:text-white">{{ __('Because the wrong part is lost time.') }}</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ __('Shop after shop, wrong parts, days of waiting — YallaSpare was founded to break that loop.') }}
                        {{ __('All products are carefully selected to ensure quality and compatibility with different vehicle models.') }}
                    </p>
                </article>
                <article data-vision-reveal data-about-panel="how" class="rounded-3xl border border-slate-200/80 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-amber-600 lg:hidden dark:text-amber-400">{{ __('How we work') }}</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-primary lg:mt-0 dark:text-white">{{ __('A simple promise, kept every day.') }}</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ __('Our mission is to provide high-quality auto spare parts, competitive prices, and fast delivery while maintaining excellent customer service.') }}
                        {{ __('If you are not sure which part you need, our support team can help you find the correct product.') }}
                    </p>
                </article>
                <article data-vision-reveal data-about-panel="next" class="rounded-3xl border border-slate-200/80 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-amber-600 lg:hidden dark:text-amber-400">{{ __("Where we're going") }}</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-primary lg:mt-0 dark:text-white">{{ __('This is only the beginning.') }}</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ __('A mobile app, VIN matching, same-day delivery, and more are on the road ahead — see the full journey on Our Vision.') }}
                    </p>
                </article>
            </div>
        </section>

        {{-- Bento grid --}}
        <section class="mt-14 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div data-vision-reveal class="abt-card rounded-3xl border border-slate-200/80 bg-white p-7 shadow-sm sm:col-span-2 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-sm font-extrabold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('YallaSpare in numbers') }}</h2>
                <div class="mt-5 grid grid-cols-2 gap-6 sm:grid-cols-4">
                    @foreach ($stats as $stat)
                        <div>
                            <p class="text-3xl font-extrabold tracking-tighter text-primary tabular-nums dark:text-white">
                                <span data-vision-count="{{ $stat['value'] }}">0</span>{{ $stat['suffix'] }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div data-vision-reveal class="abt-card rounded-3xl border border-amber-300/70 bg-amber-50/60 p-7 dark:border-amber-400/25 dark:bg-amber-400/5">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 4l6 3v5c0 4-2.7 7-6 8-3.3-1-6-4-6-8V7l6-3Z" /><path d="m9.5 12 1.8 1.8 3.5-3.5" />
                    </svg>
                </span>
                <h2 class="mt-4 text-base font-extrabold tracking-tight text-primary dark:text-white">{{ __('Fit guarantee') }}</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ __('Every part is fit-checked for your vehicle.') }}</p>
            </div>
            <div data-vision-reveal class="abt-card rounded-3xl border border-slate-200/80 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-primary dark:bg-slate-800 dark:text-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M13 3 5 14h6l-1 7 8-11h-6l1-7Z" />
                    </svg>
                </span>
                <h2 class="mt-4 text-base font-extrabold tracking-tight text-primary dark:text-white">{{ __('Fast delivery') }}</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ __('1-3 business days with trusted shipping partners.') }}</p>
            </div>
            <div data-vision-reveal class="abt-card rounded-3xl border border-slate-200/80 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-primary dark:bg-slate-800 dark:text-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 10h8M8 14h5" /><path d="M21 12a9 9 0 1 1-4-7.5" />
                    </svg>
                </span>
                <h2 class="mt-4 text-base font-extrabold tracking-tight text-primary dark:text-white">{{ __('Not sure which part?') }}</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ __('Send brand, model, year, and a photo — we will find it.') }}</p>
            </div>
            <div data-vision-reveal class="abt-card abt-dark relative overflow-hidden rounded-3xl p-7 sm:col-span-2 lg:col-span-2">
                <span class="abt-glow" aria-hidden="true"></span>
                <h2 class="relative text-base font-extrabold tracking-tight text-white">{{ __('Tomorrow: even more') }}</h2>
                <p class="relative mt-1.5 max-w-xl text-sm leading-relaxed text-slate-300">
                    {{ __('A mobile app, VIN matching, same-day delivery, and more are on the road ahead — see the full journey on Our Vision.') }}
                </p>
                <a href="{{ route('legal.vision') }}" class="relative mt-5 inline-flex items-center gap-1.5 rounded-xl bg-amber-400 px-5 py-2.5 text-sm font-bold text-primary transition hover:-translate-y-0.5 hover:bg-amber-300 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-white dark:text-[#070740]">
                    {{ __('Our Vision') }} <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </section>
    </div>
@endsection
