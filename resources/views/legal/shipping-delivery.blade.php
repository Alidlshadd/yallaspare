@extends('layouts.user')

@section('title', __('Shipping & Delivery'))
@section('meta_description', __('Shipping process, delivery times, tracking details, and important delivery information for orders.'))

@section('content')
    <section class="mx-auto w-full max-w-[900px] space-y-8">
        <header class="rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-slate-100/70 p-8 shadow-sm dark:border-slate-800/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800/60">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Customer Service') }}</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('Shipping & Delivery') }}</h1>
            <p class="mt-5 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('We aim to deliver your orders as quickly and safely as possible. Below you can find information about our shipping and delivery process.') }}
            </p>
        </header>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3v6M12 21v-3M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                {{ __('1. Order Processing') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Orders can be placed 24/7 through our website.') }}</p>
                <p>{{ __('After your order is confirmed, our team will prepare the product and send it to the shipping company as soon as possible.') }}</p>
            </div>
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
                {{ __('2. Delivery Partners') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Orders are shipped through reliable delivery partners to ensure safe and fast delivery.') }}</p>
                <p>{{ __('We work with trusted shipping partners to deliver orders quickly and safely.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 12h18M6 6h12M8 18h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                {{ __('3. Delivery Time') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Delivery time depends on your location.') }}</p>
                <p>{{ __('Estimated delivery times:') }}</p>
                <ul class="list-disc space-y-2 pl-6">
                    <li>{{ __('Local deliveries: 1 business days') }}</li>
                    <li>{{ __('Other cities: 1 - 3 business days') }}</li>
                </ul>
                <p>{{ __('Please note that delivery times may vary depending on shipping conditions and holidays.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                        <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('4. Order Tracking') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Once your order is shipped, you may receive a tracking number from the shipping company to follow the delivery status.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('5. Delivery Notes') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Please make sure the following information is correct when placing your order:') }}
            </p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('Full name') }}</li>
                <li>{{ __('Correct phone number') }}</li>
                <li>{{ __('Accurate delivery address') }}</li>
            </ul>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Incorrect information may cause delays in delivery.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-slate-50 p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3l9 16H3L12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                        <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                {{ __('6. Important Information') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('If the package appears damaged during delivery, please inform the delivery driver and contact our support team immediately.') }}
            </p>
        </section>
    </section>
@endsection
