@extends('layouts.user')

@section('title', __('Return & Exchange Policy'))
@section('meta_description', __('Information about returns, exchanges, and refund conditions for orders.'))

@section('content')
    <section class="mx-auto w-full max-w-[900px] space-y-8">
        <header class="rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-slate-100/80 p-8 shadow-sm dark:border-slate-800/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800/60">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Customer Service') }}</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('Return & Exchange Policy') }}</h1>
            <p class="mt-5 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Customer satisfaction is very important to us. We try to handle all return and exchange requests fairly and transparently. Please read the following conditions before requesting a return or exchange.') }}
            </p>
            <div class="mt-6 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full border border-slate-300/70 bg-white/85 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">{{ __('Return window: 7 days') }}</span>
                <span class="inline-flex items-center rounded-full border border-slate-300/70 bg-white/85 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">{{ __('Damaged item report: 24 hours') }}</span>
            </div>
        </header>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                        <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('Return Period') }}
            </h2>
            <div class="mt-4 space-y-3">
                <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('Customers can request a return or exchange within 7 days from the date the order is delivered.') }}
                </p>
                <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('To start a return or exchange request, please contact our customer service and provide your order number and product details.') }}
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('Return Conditions') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">{{ __('To be eligible for a return, the product must meet the following conditions:') }}</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('The item must be unused and not installed') }}</li>
                <li>{{ __('The product must be in its original packaging') }}</li>
                <li>{{ __('All accessories, labels, and included parts must be returned') }}</li>
                <li>{{ __('The product must not be damaged due to improper installation or misuse') }}</li>
            </ul>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Products that do not meet these conditions may not be accepted for return.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3l9 16H3L12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                        <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                {{ __('Damaged or Incorrect Items') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('If you receive a damaged product or the wrong item, please contact us within 24 hours of delivery.') }}
            </p>
            <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">{{ __('Please provide:') }}</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('Your order number') }}</li>
                <li>{{ __('Photos of the product') }}</li>
                <li>{{ __('Photos of the packaging') }}</li>
            </ul>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('After reviewing the issue, we will arrange a replacement or refund if applicable.') }}
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
                {{ __('Shipping Costs') }}
            </h2>
            <div class="mt-4 space-y-3">
                <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('If the return is caused by our mistake (wrong item or defective product), the return shipping cost will be covered by us.') }}
                </p>
                <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('If the return is due to customer preference (wrong order, change of mind, etc.), the return shipping cost will be the customer\'s responsibility.') }}
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 12a8 8 0 1 0 2.3-5.7M4 6v4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('Refund Process') }}
            </h2>
            <div class="mt-4 space-y-3">
                <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('Once the returned item is received and inspected, we will notify you about the approval or rejection of your refund.') }}
                </p>
                <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('If approved, the refund will be processed through the original payment method.') }}
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M7 7h10M14 4l3 3-3 3M17 17H7M10 14l-3 3 3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('Exchange Requests') }}
            </h2>
            <div class="mt-4 space-y-3">
                <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('If you request an exchange, the replacement product will be shipped after the returned item is received and inspected.') }}
                </p>
                <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('If the product is confirmed defective or incorrect, the replacement may be shipped immediately.') }}
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6l12 12M18 6l-12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                </span>
                {{ __('Non-Returnable Cases') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Returns may not be accepted in the following situations:') }}
            </p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('Products that have been used or installed') }}</li>
                <li>{{ __('Items with missing parts or damaged packaging') }}</li>
                <li>{{ __('Products damaged due to incorrect installation or misuse') }}</li>
            </ul>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-slate-50 p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8" />
                        <path d="m5 7 7 5 7-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('Contact') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('For return or exchange requests, please contact us through our Contact Page or customer support channels.') }}
            </p>
            <a
                href="{{ route('legal.contact', ['topic' => 'general']) }}"
                class="mt-5 inline-flex items-center rounded-xl bg-[#070740] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0d1156] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-slate-500 dark:focus-visible:ring-offset-slate-950"
            >
                {{ __('Contact Support') }}
            </a>
        </section>
    </section>
@endsection
