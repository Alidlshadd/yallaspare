@extends('layouts.user')

@section('title', __('Shipping & Delivery'))
@section('meta_description', __('Shipping process, delivery times, tracking details, and important delivery information for orders.'))

@section('content')
    <div data-vision-page class="mx-auto w-full max-w-5xl">
        <section class="sup-in">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-600 dark:text-amber-400">{{ __('Shipping & Delivery') }}</p>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('The journey of your part') }}</h1>
            <p class="mt-6 max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('From checkout to your door — every step of your order on one road.') }}
            </p>
        </section>

        {{-- Animated road: flowing center dashes + delivery truck driving across --}}
        <div class="del-road sup-in mt-10" style="animation-delay: .15s" aria-hidden="true">
            <div class="del-road-dash"></div>
            <div class="del-truck">
                <svg viewBox="0 0 96 44" class="h-[52px] w-[113px]">
                    {{-- cargo box --}}
                    <rect x="2" y="4" width="56" height="28" rx="3" fill="#ffffff" />
                    <rect x="2" y="25" width="56" height="4" fill="#fbbf24" />
                    <rect x="2.75" y="4.75" width="54.5" height="26.5" rx="2.5" fill="none" stroke="#94a3b8" stroke-width="1.5" />
                    {{-- cab --}}
                    <path d="M58 32V12h14.5a5 5 0 0 1 4.2 2.3L82.5 23a6 6 0 0 1 1 3.3V32H58Z" fill="#2a35a0" stroke="#94a3b8" stroke-width="1" />
                    <path d="M62 15.5h9.2l4.6 7H62v-7Z" fill="#bfdbfe" />
                    {{-- bumper + headlight --}}
                    <rect x="83" y="27.5" width="4" height="5.5" rx="1.5" fill="#94a3b8" />
                    <circle cx="84" cy="25" r="1.8" fill="#fbbf24" />
                    {{-- chassis line --}}
                    <rect x="4" y="31" width="80" height="2.5" rx="1.25" fill="#0b0d2e" />
                    {{-- wheels --}}
                    <circle cx="17" cy="36" r="6.5" fill="#0b0d2e" stroke="#64748b" stroke-width="2" />
                    <circle cx="17" cy="36" r="2.2" fill="#cbd5e1" />
                    <circle cx="69" cy="36" r="6.5" fill="#0b0d2e" stroke="#64748b" stroke-width="2" />
                    <circle cx="69" cy="36" r="2.2" fill="#cbd5e1" />
                </svg>
            </div>
        </div>

        {{-- The four stops of the journey --}}
        <section class="del-steps mt-12">
            <article data-vision-reveal class="del-step">
                <span class="del-tag">{{ __('Step 1') }}</span>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Order received') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('Orders can be placed 24/7 through our website.') }}
                </p>
            </article>

            <article data-vision-reveal class="del-step">
                <span class="del-tag">{{ __('Step 2') }}</span>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Prepared in the warehouse') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('After your order is confirmed, our team will prepare the product and send it to the shipping company as soon as possible.') }}
                </p>
            </article>

            <article data-vision-reveal class="del-step del-step-now">
                <span class="del-tag del-tag-now">{{ __('On the road') }}</span>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Handed to the courier') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('Once your order is shipped, you may receive a tracking number from the shipping company to follow the delivery status.') }}
                    {{ __('Orders are shipped through reliable delivery partners to ensure safe and fast delivery.') }}
                </p>
            </article>

            <article data-vision-reveal class="del-step">
                <span class="del-tag">{{ __('Step 4') }}</span>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('At your door') }}</h2>
                <ul class="mt-2 list-disc space-y-1.5 ps-5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>{{ __('Local deliveries: 1 business days') }}</li>
                    <li>{{ __('Other cities: 1 - 3 business days') }}</li>
                </ul>
                <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ __('Please note that delivery times may vary depending on shipping conditions and holidays.') }}
                </p>
            </article>
        </section>

        {{-- Stats band --}}
        <section data-vision-reveal class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">1–3</p>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ __('Business days to deliver') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">7/24</p>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ __('Online ordering') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100"><span data-vision-count="24">0</span></p>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ __('Hours to report damage') }}</p>
            </div>
        </section>

        {{-- Delivery checklist --}}
        <section data-vision-reveal class="mt-12 rounded-2xl border border-slate-200/80 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Delivery checklist') }}</h2>
            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Please make sure the following information is correct when placing your order:') }}
            </p>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <li class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 dark:bg-slate-800/60 dark:text-slate-200">
                    <svg class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4.5 10.5 3.5 3.5 7.5-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    {{ __('Full name') }}
                </li>
                <li class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 dark:bg-slate-800/60 dark:text-slate-200">
                    <svg class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4.5 10.5 3.5 3.5 7.5-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    {{ __('Correct phone number') }}
                </li>
                <li class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 dark:bg-slate-800/60 dark:text-slate-200">
                    <svg class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4.5 10.5 3.5 3.5 7.5-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    {{ __('Accurate delivery address') }}
                </li>
            </ul>
            <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">{{ __('Incorrect information may cause delays in delivery.') }}</p>
        </section>

        {{-- Damaged package warning --}}
        <section data-vision-reveal class="mt-6 rounded-2xl border border-amber-300/70 bg-amber-50/70 p-7 dark:border-amber-400/25 dark:bg-amber-400/5">
            <h2 class="flex items-center gap-3 text-xl font-semibold tracking-tight text-amber-900 dark:text-amber-200">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 3l9 16H3L12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                {{ __('Damaged package?') }}
            </h2>
            <p class="mt-3 text-sm leading-relaxed text-amber-900/90 dark:text-amber-200/90">
                {{ __('If the package appears damaged during delivery, please inform the delivery driver and contact our support team immediately.') }}
            </p>
        </section>
    </div>
@endsection
