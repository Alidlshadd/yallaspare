@extends('layouts.user')

@section('title', __('Support | Yalla Spare'))

@section('content')
    <section class="mx-auto w-full max-w-4xl">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Support Center') }}</p>
        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('Fast, practical help when you need it.') }}</h1>
        <p class="mt-6 max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
            {{ __('Contact support for access issues, billing, orders, product data, and technical problems. Share details and we will route your request to the right team quickly.') }}
        </p>
    </section>

    <section class="mx-auto mt-10 grid w-full max-w-4xl grid-cols-1 gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('General') }}</p>
            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('24h') }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Typical first response for normal support tickets.') }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Urgent') }}</p>
            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('2-6h') }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('For checkout failures, login lockouts, and production blockers.') }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Coverage') }}</p>
            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Mon-Sat') }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Support monitors requests during core business hours.') }}</p>
        </article>
    </section>

    <section class="mx-auto mt-8 w-full max-w-4xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Contact Support') }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Use the option that matches your issue so your request reaches the right team faster.') }}
                </p>
            </div>
            <a
                href="{{ route('legal.contact', ['topic' => 'general']) }}"
                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0d1156] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-slate-600 dark:focus-visible:ring-offset-slate-950"
            >
                {{ __('General Support') }}
            </a>
        </div>
        <div class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
            {{ __('Helpful details: account email, order or invoice reference, error message, and clear reproduction steps.') }}
        </div>
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <a
                href="{{ route('legal.contact', ['topic' => 'urgent']) }}"
                class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
            >
                <span class="block font-semibold">{{ __('Urgent Issue') }}</span>
                <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ __('Critical blockers like login or checkout failures') }}</span>
            </a>
            <a
                href="{{ route('legal.contact', ['topic' => 'billing']) }}"
                class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
            >
                <span class="block font-semibold">{{ __('Billing & Invoices') }}</span>
                <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ __('Payments, invoices, and billing requests') }}</span>
            </a>
            <a
                href="{{ route('legal.contact', ['topic' => 'account']) }}"
                class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
            >
                <span class="block font-semibold">{{ __('Account Access') }}</span>
                <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ __('Password, verification, and sign-in issues') }}</span>
            </a>
        </div>
    </section>

    <section class="mx-auto mt-14 w-full max-w-4xl">
        <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('FAQ') }}</h2>
        <div class="mt-6 divide-y divide-slate-200/70 dark:divide-slate-800/70">
            <details class="group py-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-primary focus:outline-none dark:text-slate-300">
                    <span>{{ __('How do I reset my account password?') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <div class="grid grid-rows-[0fr] transition-all duration-200 group-open:grid-rows-[1fr]">
                    <div class="overflow-hidden">
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('Use the password reset flow from the sign-in page. If you do not receive the reset email, contact support and we will verify and assist.') }}
                        </p>
                    </div>
                </div>
            </details>

            <details class="group py-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-primary focus:outline-none dark:text-slate-300">
                    <span>{{ __('Can you help with billing and invoice questions?') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <div class="grid grid-rows-[0fr] transition-all duration-200 group-open:grid-rows-[1fr]">
                    <div class="overflow-hidden">
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('Yes. Include your account details and invoice reference in your email so we can route your request and resolve it faster.') }}
                        </p>
                    </div>
                </div>
            </details>

            <details class="group py-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-primary focus:outline-none dark:text-slate-300">
                    <span>{{ __('Where can I report a technical issue?') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <div class="grid grid-rows-[0fr] transition-all duration-200 group-open:grid-rows-[1fr]">
                    <div class="overflow-hidden">
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('Send a message to support with steps to reproduce, screenshots if available, and timestamps so our team can investigate accurately.') }}
                        </p>
                    </div>
                </div>
            </details>

            <details class="group py-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-primary focus:outline-none dark:text-slate-300">
                    <span>{{ __('What should I include in a support request?') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <div class="grid grid-rows-[0fr] transition-all duration-200 group-open:grid-rows-[1fr]">
                    <div class="overflow-hidden">
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ __('Add your account email, affected page URL, exact error text, and reproduction steps. This usually cuts resolution time significantly.') }}
                        </p>
                    </div>
                </div>
            </details>
        </div>
    </section>
@endsection
