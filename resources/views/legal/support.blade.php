@extends('layouts.user')

@section('title', __('Support | Yalla Spare'))

@php
    // Support desk runs Mon-Sat; Iraq local time, app timezone stays UTC.
    $supportOpenToday = ! now('Asia/Baghdad')->isSunday();
@endphp

@section('content')
    <section class="sup-in mx-auto w-full max-w-5xl">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-600 dark:text-amber-400">{{ __('Support Center') }}</p>
        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('What do you need help with today?') }}</h1>
        <p class="mt-6 max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
            {{ __('Pick your topic below and your request goes straight to the right team. Most requests get a same-day reply.') }}
        </p>
    </section>

    <section class="mx-auto mt-10 grid w-full max-w-5xl grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <a
            href="{{ route('legal.contact', ['topic' => 'order']) }}"
            class="sup-in group relative rounded-2xl border-2 border-amber-400/80 bg-white p-6 shadow-sm ring-4 ring-amber-400/15 transition duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:ring-amber-400/30 dark:border-amber-400/60 dark:bg-slate-900"
            style="animation-delay: .08s"
        >
            <span class="absolute end-4 top-4 rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">{{ __('Most asked') }}</span>
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-700 transition duration-300 group-hover:scale-110 dark:bg-amber-400/15 dark:text-amber-300">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 8.25 12 3 3 8.25v7.5L12 21l9-5.25v-7.5Z" /><path d="m3.3 8.4 8.7 5.1 8.7-5.1" /><path d="M12 13.5V21" />
                </svg>
            </span>
            <span class="mt-5 block text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Where is my order?') }}</span>
            <span class="mt-1.5 block text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Shipping tracking and delivery status') }}</span>
        </a>

        <a
            href="{{ route('legal.return-exchange') }}"
            class="sup-in group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:border-slate-300 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            style="animation-delay: .16s"
        >
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-primary transition duration-300 group-hover:scale-110 group-hover:bg-amber-100 group-hover:text-amber-700 dark:bg-slate-800 dark:text-slate-200 dark:group-hover:bg-amber-400/15 dark:group-hover:text-amber-300">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 15 4.5 10.5 9 6" /><path d="M4.5 10.5H15a4.5 4.5 0 0 1 0 9h-3" />
                </svg>
            </span>
            <span class="mt-5 block text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Return / Exchange') }}</span>
            <span class="mt-1.5 block text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Open a return request within 7 days') }}</span>
        </a>

        <a
            href="{{ route('legal.contact', ['topic' => 'account']) }}"
            class="sup-in group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:border-slate-300 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            style="animation-delay: .24s"
        >
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-primary transition duration-300 group-hover:scale-110 group-hover:bg-amber-100 group-hover:text-amber-700 dark:bg-slate-800 dark:text-slate-200 dark:group-hover:bg-amber-400/15 dark:group-hover:text-amber-300">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="8" cy="15" r="4" /><path d="m10.85 12.15 7.4-7.4" /><path d="m15.5 7.5 3 3" /><path d="m18 5 1.5 1.5" />
                </svg>
            </span>
            <span class="mt-5 block text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Account') }}</span>
            <span class="mt-1.5 block text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Password, verification, and sign-in issues') }}</span>
        </a>

        <a
            href="{{ route('legal.contact', ['topic' => 'billing']) }}"
            class="sup-in group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:border-slate-300 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            style="animation-delay: .32s"
        >
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-primary transition duration-300 group-hover:scale-110 group-hover:bg-amber-100 group-hover:text-amber-700 dark:bg-slate-800 dark:text-slate-200 dark:group-hover:bg-amber-400/15 dark:group-hover:text-amber-300">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 3h12v18l-2.25-1.5L13.5 21l-1.5-1.5L10.5 21l-2.25-1.5L6 21V3Z" /><path d="M9.5 8h5" /><path d="M9.5 12h5" />
                </svg>
            </span>
            <span class="mt-5 block text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Payment & Billing') }}</span>
            <span class="mt-1.5 block text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Payments, invoices, and billing requests') }}</span>
        </a>
    </section>

    <section class="sup-in mx-auto mt-7 flex w-full max-w-5xl flex-wrap gap-3" style="animation-delay: .42s">
        <span class="inline-flex items-center gap-2.5 rounded-full border border-slate-200/80 bg-white px-4 py-2 text-xs font-medium text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
            @if ($supportOpenToday)
                <span class="sup-dot relative inline-flex h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                {{ __('Support is open now') }} · {{ __('Mon-Sat') }}
            @else
                <span class="relative inline-flex h-2 w-2 rounded-full bg-slate-400" aria-hidden="true"></span>
                {{ __('Support is closed today') }} · {{ __('Mon-Sat') }}
            @endif
        </span>
        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-white px-4 py-2 text-xs font-medium text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
            {{ __('First response: ~24h') }} · {{ __('Urgent: 2-6h') }}
        </span>
    </section>

    <section class="sup-in mx-auto mt-7 w-full max-w-5xl rounded-xl border border-amber-200/70 bg-amber-50/70 px-5 py-3.5 text-sm text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/5 dark:text-amber-200/90" style="animation-delay: .5s">
        {{ __('Helpful details: account email, order or invoice reference, error message, and clear reproduction steps.') }}
    </section>

    <section class="sup-in mx-auto mt-16 w-full max-w-5xl" style="animation-delay: .58s">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('FAQ') }}</h2>
        <div class="mt-5 rounded-2xl border border-slate-200/80 bg-white px-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="divide-y divide-slate-200/70 dark:divide-slate-800/70">
                <details class="group py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-primary focus:outline-none dark:text-slate-300 dark:hover:text-white">
                        <span>{{ __('How do I reset my account password?') }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180 group-open:text-amber-500" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </summary>
                    <div class="grid grid-rows-[0fr] transition-all duration-300 group-open:grid-rows-[1fr]">
                        <div class="overflow-hidden">
                            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ __('Use the password reset flow from the sign-in page. If you do not receive the reset email, contact support and we will verify and assist.') }}
                            </p>
                        </div>
                    </div>
                </details>

                <details class="group py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-primary focus:outline-none dark:text-slate-300 dark:hover:text-white">
                        <span>{{ __('Can you help with billing and invoice questions?') }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180 group-open:text-amber-500" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </summary>
                    <div class="grid grid-rows-[0fr] transition-all duration-300 group-open:grid-rows-[1fr]">
                        <div class="overflow-hidden">
                            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ __('Yes. Include your account details and invoice reference in your email so we can route your request and resolve it faster.') }}
                            </p>
                        </div>
                    </div>
                </details>

                <details class="group py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-primary focus:outline-none dark:text-slate-300 dark:hover:text-white">
                        <span>{{ __('Where can I report a technical issue?') }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180 group-open:text-amber-500" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </summary>
                    <div class="grid grid-rows-[0fr] transition-all duration-300 group-open:grid-rows-[1fr]">
                        <div class="overflow-hidden">
                            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ __('Send a message to support with steps to reproduce, screenshots if available, and timestamps so our team can investigate accurately.') }}
                            </p>
                        </div>
                    </div>
                </details>

                <details class="group py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-primary focus:outline-none dark:text-slate-300 dark:hover:text-white">
                        <span>{{ __('What should I include in a support request?') }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180 group-open:text-amber-500" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </summary>
                    <div class="grid grid-rows-[0fr] transition-all duration-300 group-open:grid-rows-[1fr]">
                        <div class="overflow-hidden">
                            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ __('Add your account email, affected page URL, exact error text, and reproduction steps. This usually cuts resolution time significantly.') }}
                            </p>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </section>

    <section class="sup-in mx-auto mt-10 w-full max-w-5xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900" style="animation-delay: .66s">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Still need help?') }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Send your message and we will route it to the right team.') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('legal.contact', ['topic' => 'urgent']) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                >
                    {{ __('Urgent Issue') }}
                </a>
                <a
                    href="{{ route('legal.contact', ['topic' => 'general']) }}"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#0d1156] hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-950"
                >
                    {{ __('General Support') }}
                </a>
            </div>
        </div>
    </section>
@endsection
