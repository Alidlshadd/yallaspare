@extends('layouts.user')

@section('title', __('Distance Sales Agreement'))
@section('meta_description', __('Distance Sales Agreement terms for online orders, delivery, returns, payment security, and dispute resolution.'))

@section('content')
    <section class="mx-auto w-full max-w-[900px] space-y-8">
        <header class="rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-slate-100/70 p-8 shadow-sm dark:border-slate-800/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800/60">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Legal') }}</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('Distance Sales Agreement') }}</h1>
            <p class="mt-5 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('This Distance Sales Agreement governs the sale of products purchased through our website. By placing an order on this website, the customer agrees to the terms and conditions stated below.') }}
            </p>
        </header>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('1. Subject of the Agreement') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('The subject of this agreement is to define the rights and obligations of the seller and the buyer regarding the sale and delivery of products ordered electronically through the website.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 19h16M6 19V8l6-4 6 4v11M9 19v-5h6v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('2. Seller Information') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Company Name') }}</span><br>{{ __('Yalla Spare - Auto Parts Store') }}</p>
                <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Business Type') }}</span><br>{{ __('Online Auto Spare Parts Supplier') }}</p>
                <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Email') }}</span><br>support@yallaspare.com</p>
                <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Location') }}</span><br>{{ __('Erbil, Iraq') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.8" />
                        <path d="M5 19a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                {{ __('3. Buyer Information') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('The buyer is the person who places an order through the website and provides their personal information during the checkout process.') }}</p>
                <p>{{ __('The information entered during the order process will be considered valid for communication and delivery.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 7h18M6 7v11h12V7M9 7V5h6v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('4. Product Information') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Product type, quantity, brand, model, specifications, price, payment method, and delivery information are defined at the time the order is completed on the website.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M8 3h8l5 5v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                        <path d="M16 3v5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('5. General Terms') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('The buyer confirms that they have read and accepted all product details, price information, payment conditions, and delivery terms before completing the order.') }}</p>
                <p>{{ __('The seller agrees to deliver the purchased product in a complete and undamaged condition according to the specifications provided during the order process.') }}</p>
                <p>{{ __('Delivery time may vary depending on the customer\'s location and shipping conditions.') }}</p>
                <p>{{ __('If delivery becomes impossible due to unexpected events such as transportation issues, weather conditions, or other force majeure situations, the buyer will be informed and an alternative solution will be provided.') }}</p>
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
                {{ __('6. Delivery') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Orders will be shipped through trusted delivery companies.') }}</p>
                <p>{{ __('Estimated delivery times may vary depending on the destination and shipping conditions.') }}</p>
                <p>{{ __('The seller is not responsible if the delivery cannot be completed due to incorrect address or contact information provided by the buyer.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 12a8 8 0 1 0 2.3-5.7M4 6v4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('7. Right of Withdrawal') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">{{ __('The buyer has the right to request a return within 7 days from the date of delivery.') }}</p>
            <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">{{ __('To exercise this right:') }}</p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('The product must be unused') }}</li>
                <li>{{ __('The product must be in its original packaging') }}</li>
                <li>{{ __('All accessories and documents must be returned') }}</li>
            </ul>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Return shipping costs may be the responsibility of the buyer unless the product is defective or incorrectly delivered.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="6.5" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8" />
                        <path d="M3 10.5h18" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                </span>
                {{ __('8. Payment Method') }}
            </h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Orders placed through the website do not require online payment.') }}</p>
                <p>{{ __('Payments are completed through cash on delivery or direct agreement with the customer.') }}</p>
                <p>{{ __('Since the website does not process online payments, no credit card or banking information is collected or stored on the system.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-slate-50 p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 10h12M6 14h12M4 6h16v12H4z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                {{ __('9. Dispute Resolution') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('In case of any dispute related to this agreement, both parties agree to resolve the issue through communication and mutual agreement whenever possible.') }}
            </p>
        </section>
    </section>
@endsection
