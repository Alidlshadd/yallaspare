@extends('layouts.legal')

@section('title', 'Support | Yalla Spare')

@section('content')
    <section class="mx-auto w-full max-w-4xl">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Support</p>
        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">
            How can we help?
        </h1>
        <p class="mt-6 max-w-2xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
            Reach out for assistance with account access, platform operations, billing questions, or technical issues.
            Our team is available to help you resolve requests quickly and reliably.
        </p>
        <div class="mt-8 h-px w-full bg-slate-200/70 dark:bg-slate-800/70"></div>
    </section>

    <section class="mx-auto mt-10 w-full max-w-4xl">
        <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Contact</h2>
        <a
            href="mailto:support@yallaspare.com"
            class="mt-6 inline-flex items-center text-2xl font-semibold tracking-tight text-[#070740] transition-colors duration-200 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:hover:text-slate-100 dark:focus-visible:ring-slate-600 dark:focus-visible:ring-offset-slate-950"
        >
            support@yallaspare.com
        </a>
        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
            Typical response time: within 24 business hours.
        </p>
    </section>

    <section class="mx-auto mt-16 w-full max-w-4xl">
        <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">FAQ</h2>
        <div class="mt-6 divide-y divide-slate-200/70 dark:divide-slate-800/70">
            <details class="group py-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-[#070740] focus:outline-none dark:text-slate-300">
                    <span>How do I reset my account password?</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <div class="grid grid-rows-[0fr] transition-all duration-200 group-open:grid-rows-[1fr]">
                    <div class="overflow-hidden">
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            Use the password reset flow from the sign-in page. If you do not receive the reset email,
                            contact support and we will verify and assist.
                        </p>
                    </div>
                </div>
            </details>

            <details class="group py-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-[#070740] focus:outline-none dark:text-slate-300">
                    <span>Can you help with billing and invoice questions?</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <div class="grid grid-rows-[0fr] transition-all duration-200 group-open:grid-rows-[1fr]">
                    <div class="overflow-hidden">
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            Yes. Include your account details and invoice reference in your email so we can route your
                            request and resolve it faster.
                        </p>
                    </div>
                </div>
            </details>

            <details class="group py-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-sm font-medium text-slate-700 transition-colors hover:text-[#070740] focus:outline-none dark:text-slate-300">
                    <span>Where can I report a technical issue?</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <div class="grid grid-rows-[0fr] transition-all duration-200 group-open:grid-rows-[1fr]">
                    <div class="overflow-hidden">
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            Send a message to support with steps to reproduce, screenshots if available, and timestamps
                            so our team can investigate accurately.
                        </p>
                    </div>
                </div>
            </details>
        </div>
    </section>
@endsection
