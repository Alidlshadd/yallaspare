@extends('layouts.user')

@section('title', __('About Us'))
@section('meta_description', __('Learn about Yalla Spare mission, products, delivery, support, and commitment to customer satisfaction.'))

@section('content')
    <section class="mx-auto w-full max-w-[900px] space-y-8">
        <header class="rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-slate-100/70 p-8 shadow-sm dark:border-slate-800/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800/60">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Information') }}</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('About Us') }}</h1>
            <p class="mt-5 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('We are an online auto spare parts store dedicated to providing reliable and high-quality automotive parts for different vehicle brands and models.') }}
            </p>
            <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Our goal is to make it easier for customers to find the right parts quickly and safely through a simple and modern online shopping experience.') }}
            </p>
        </header>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                        <path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                {{ __('1. Introduction') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('We are an online auto spare parts store dedicated to providing reliable and high-quality automotive parts for different vehicle brands and models.') }}</p>
                <p>{{ __('Our goal is to make it easier for customers to find the right parts quickly and safely through a simple and modern online shopping experience.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 4l6 3v5c0 4-2.7 7-6 8-3.3-1-6-4-6-8V7l6-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                        <path d="m9.5 12 1.8 1.8L14.8 10.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('2. Our Mission') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Our mission is to provide high-quality auto spare parts, competitive prices, and fast delivery while maintaining excellent customer service.') }}</p>
                <p>{{ __('We focus on building long-term relationships with our customers by offering reliable products and professional support.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 7h18M6 7v11h12V7M9 7V5h6v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('3. What We Offer') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Our store provides a wide range of auto spare parts including:') }}
            </p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('Engine parts') }}</li>
                <li>{{ __('Brake components') }}</li>
                <li>{{ __('Suspension parts') }}</li>
                <li>{{ __('Electrical components') }}</li>
                <li>{{ __('Filters and maintenance products') }}</li>
                <li>{{ __('Body parts and accessories') }}</li>
            </ul>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('All products are carefully selected to ensure quality and compatibility with different vehicle models.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M18 10a6 6 0 1 0-12 0v5a2 2 0 0 0 2 2h2v-4H7.5M18 13h-2.5v4h2A2 2 0 0 0 20 15v-5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 19h1.5a1.5 1.5 0 0 0 0-3H12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                {{ __('4. Customer Support') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('If you are not sure which part you need, our support team can help you find the correct product.') }}
            </p>
            <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">{{ __('Simply send us:') }}</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('Car brand and model') }}</li>
                <li>{{ __('Year of manufacture') }}</li>
                <li>{{ __('Engine type (if available)') }}</li>
                <li>{{ __('Part name or photo') }}</li>
            </ul>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Our team will assist you in finding the correct spare part.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 7h12v9H3zM15 10h3l3 3v3h-6z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="7" cy="18" r="1.5" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="17" cy="18" r="1.5" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                </span>
                {{ __('5. Fast & Reliable Delivery') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('We work with trusted shipping partners to deliver orders quickly and safely.') }}</p>
                <p>{{ __('Orders are processed as soon as possible and shipped through our delivery partners.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-slate-50 p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3l7 4v5c0 4.4-2.8 8-7 9-4.2-1-7-4.6-7-9V7l7-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('6. Our Commitment') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">{{ __('We are committed to providing:') }}</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('Quality products') }}</li>
                <li>{{ __('Reliable service') }}</li>
                <li>{{ __('Competitive prices') }}</li>
                <li>{{ __('Fast delivery') }}</li>
            </ul>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Customer satisfaction is always our top priority.') }}
            </p>
        </section>
    </section>
@endsection
