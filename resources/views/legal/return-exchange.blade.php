@extends('layouts.user')

@section('title', __('Return & Exchange Policy'))
@section('meta_description', __('Information about returns, exchanges, and refund conditions for orders.'))

@section('content')
    <div data-vision-page class="mx-auto w-full max-w-6xl">
        <section class="sup-in">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-600 dark:text-amber-400">{{ __('Customer Service') }}</p>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('Return & Exchange Policy') }}</h1>
            <p class="mt-6 max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Customer satisfaction is very important to us. We try to handle all return and exchange requests fairly and transparently. Please read the following conditions before requesting a return or exchange.') }}
            </p>
            <a
                href="{{ route('legal.contact', ['topic' => 'order']) }}"
                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-primary dark:text-[#070740] shadow-sm transition hover:-translate-y-0.5 hover:bg-amber-300 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 md:hidden"
            >
                {{ __('Open a return request') }} <span aria-hidden="true">&rarr;</span>
            </a>
        </section>

        <div class="mt-10 md:grid md:grid-cols-[200px,1fr] md:gap-8 lg:grid-cols-[230px,1fr] lg:gap-10">
            <aside class="hidden md:block">
                <nav class="sup-in sticky top-24 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="animation-delay: .12s">
                    <p class="px-3 pb-2 pt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ __('On this page') }}</p>
                    <a href="#period" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">{{ __('Return Period') }}</a>
                    <a href="#conditions" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">{{ __('Return Conditions') }}</a>
                    <a href="#damaged" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">{{ __('Damaged or Incorrect Items') }}</a>
                    <a href="#shipping" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">{{ __('Shipping Costs') }}</a>
                    <a href="#refund" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">{{ __('Refund Process') }}</a>
                    <a href="#exchange" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">{{ __('Exchange Requests') }}</a>
                    <a href="#nonreturnable" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">{{ __('Non-Returnable Cases') }}</a>
                    <div class="mt-3 border-t border-slate-200/80 pt-3 dark:border-slate-800">
                        <a
                            href="{{ route('legal.contact', ['topic' => 'order']) }}"
                            class="block rounded-xl bg-amber-400 px-3 py-2.5 text-center text-sm font-bold text-primary dark:text-[#070740] transition hover:-translate-y-0.5 hover:bg-amber-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            {{ __('Open a return request') }} <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </nav>
            </aside>

            <div class="space-y-5">
                <section id="period" data-vision-reveal class="grid scroll-mt-28 grid-cols-[104px,1fr] overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <aside class="flex flex-col items-center justify-center gap-0.5 bg-primary px-3 py-6 text-center">
                        <span class="text-3xl font-bold tracking-tight text-amber-400"><span data-vision-count="7">0</span></span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('days') }}</span>
                    </aside>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Return Period') }}</h2>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('Customers can request a return or exchange within 7 days from the date the order is delivered.') }}
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('To start a return or exchange request, please contact our customer service and provide your order number and product details.') }}
                        </p>
                    </div>
                </section>

                <section id="conditions" data-vision-reveal class="grid scroll-mt-28 grid-cols-[104px,1fr] overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <aside class="flex flex-col items-center justify-center gap-0.5 bg-primary px-3 py-6 text-center">
                        <span class="text-3xl font-bold tracking-tight text-amber-400"><span data-vision-count="4">0</span></span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('conditions') }}</span>
                    </aside>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Return Conditions') }}</h2>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ __('To be eligible for a return, the product must meet the following conditions:') }}</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ([
                                __('The item must be unused and not installed'),
                                __('The product must be in its original packaging'),
                                __('All accessories, labels, and included parts must be returned'),
                                __('The product must not be damaged due to improper installation or misuse'),
                            ] as $condition)
                                <li class="flex items-start gap-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                    <svg class="mt-1 h-3.5 w-3.5 shrink-0 text-amber-500" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4.5 10.5 3.5 3.5 7.5-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    {{ $condition }}
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                            {{ __('Products that do not meet these conditions may not be accepted for return.') }}
                        </p>
                    </div>
                </section>

                <section id="damaged" data-vision-reveal class="grid scroll-mt-28 grid-cols-[104px,1fr] overflow-hidden rounded-2xl border border-amber-300/70 bg-white shadow-sm ring-2 ring-amber-400/15 dark:border-amber-400/30 dark:bg-slate-900">
                    <aside class="flex flex-col items-center justify-center gap-0.5 bg-primary px-3 py-6 text-center">
                        <span class="text-3xl font-bold tracking-tight text-amber-400"><span data-vision-count="24">0</span></span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('hours') }}</span>
                    </aside>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Damaged or Incorrect Items') }}</h2>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('If you receive a damaged product or the wrong item, please contact us within 24 hours of delivery.') }}
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ __('Please provide:') }}</p>
                        <ul class="mt-2 flex flex-wrap gap-2">
                            <li class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __('Your order number') }}</li>
                            <li class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __('Photos of the product') }}</li>
                            <li class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __('Photos of the packaging') }}</li>
                        </ul>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('After reviewing the issue, we will arrange a replacement or refund if applicable.') }}
                        </p>
                    </div>
                </section>

                <section id="shipping" data-vision-reveal class="grid scroll-mt-28 grid-cols-[104px,1fr] overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <aside class="flex flex-col items-center justify-center bg-primary px-3 py-6 text-amber-400">
                        <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7h12v9H3zM15 10h3l3 3v3h-6z" /><circle cx="7" cy="18" r="1.5" /><circle cx="17" cy="18" r="1.5" />
                        </svg>
                    </aside>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Shipping Costs') }}</h2>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('If the return is caused by our mistake (wrong item or defective product), the return shipping cost will be covered by us.') }}
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('If the return is due to customer preference (wrong order, change of mind, etc.), the return shipping cost will be the customer\'s responsibility.') }}
                        </p>
                    </div>
                </section>

                <section id="refund" data-vision-reveal class="grid scroll-mt-28 grid-cols-[104px,1fr] overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <aside class="flex flex-col items-center justify-center gap-0.5 bg-primary px-3 py-6 text-center">
                        <span class="text-3xl font-bold tracking-tight text-amber-400">%<span data-vision-count="100">0</span></span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('refund') }}</span>
                    </aside>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Refund Process') }}</h2>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('Once the returned item is received and inspected, we will notify you about the approval or rejection of your refund.') }}
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('If approved, the refund will be processed through the original payment method.') }}
                        </p>
                    </div>
                </section>

                <section id="exchange" data-vision-reveal class="grid scroll-mt-28 grid-cols-[104px,1fr] overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <aside class="flex flex-col items-center justify-center gap-0.5 bg-primary px-3 py-6 text-center">
                        <span class="text-3xl font-bold tracking-tight text-amber-400">1:1</span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('exchange') }}</span>
                    </aside>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Exchange Requests') }}</h2>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('If you request an exchange, the replacement product will be shipped after the returned item is received and inspected.') }}
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('If the product is confirmed defective or incorrect, the replacement may be shipped immediately.') }}
                        </p>
                    </div>
                </section>

                <section id="nonreturnable" data-vision-reveal class="grid scroll-mt-28 grid-cols-[104px,1fr] overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <aside class="flex flex-col items-center justify-center bg-primary px-3 py-6 text-amber-400">
                        <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" /><path d="M7.5 7.5l9 9M16.5 7.5l-9 9" />
                        </svg>
                    </aside>
                    <div class="p-6">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Non-Returnable Cases') }}</h2>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('Returns may not be accepted in the following situations:') }}
                        </p>
                        <ul class="mt-3 space-y-2">
                            @foreach ([
                                __('Products that have been used or installed'),
                                __('Items with missing parts or damaged packaging'),
                                __('Products damaged due to incorrect installation or misuse'),
                            ] as $case)
                                <li class="flex items-start gap-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                    <svg class="mt-1 h-3.5 w-3.5 shrink-0 text-rose-500" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5.5 5.5 9 9M14.5 5.5l-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                                    {{ $case }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>

                <section data-vision-reveal class="rounded-2xl border border-slate-200/80 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Contact') }}</h2>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ __('For return or exchange requests, please contact us through our Contact Page or customer support channels.') }}
                            </p>
                        </div>
                        <a
                            href="{{ route('legal.contact', ['topic' => 'order']) }}"
                            class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-primary dark:text-[#070740] shadow-sm transition hover:-translate-y-0.5 hover:bg-amber-300 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-950"
                        >
                            {{ __('Open a return request') }} <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
