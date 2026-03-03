@extends('layouts.legal')

@section('title', 'Terms of Service | Yalla Spare')

@section('content')
    <section class="mx-auto w-full max-w-4xl">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Legal</p>
        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">
            Terms of Service
        </h1>
        <p class="mt-6 max-w-2xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
            These terms govern your access to and use of Yalla Spare, including platform features, account operations,
            and commercial interactions across the service.
        </p>
        <p class="mt-7 text-sm text-slate-500 dark:text-slate-400">Last updated: February 28, 2026</p>
        <div class="mt-8 h-px w-full bg-slate-200/70 dark:bg-slate-800/70"></div>
    </section>

    <section class="mx-auto mt-14 grid w-full max-w-5xl grid-cols-1 gap-14 lg:grid-cols-[220px_minmax(0,1fr)]">
        <aside class="hidden lg:block">
            <nav aria-label="Terms sections" class="sticky top-24">
                <ul class="space-y-1.5 text-sm">
                    <li>
                        <a href="#service-usage" data-terms-nav class="terms-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Service Usage</span>
                        </a>
                    </li>
                    <li>
                        <a href="#account-responsibility" data-terms-nav class="terms-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Account Responsibility</span>
                        </a>
                    </li>
                    <li>
                        <a href="#payment-terms" data-terms-nav class="terms-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Payment Terms</span>
                        </a>
                    </li>
                    <li>
                        <a href="#limitation-of-liability" data-terms-nav class="terms-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Limitation of Liability</span>
                        </a>
                    </li>
                    <li>
                        <a href="#termination" data-terms-nav class="terms-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Termination</span>
                        </a>
                    </li>
                    <li>
                        <a href="#governing-law" data-terms-nav class="terms-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Governing Law</span>
                        </a>
                    </li>
                    <li>
                        <a href="#contact-information" data-terms-nav class="terms-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Contact</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <article class="max-w-3xl space-y-16 scroll-smooth text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            <section id="service-usage" data-terms-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Service Usage</h2>
                <p class="mt-4">
                    By accessing or using Yalla Spare, you agree to use the platform only for lawful business
                    activities and in a manner consistent with these terms and applicable regulations.
                </p>
                <p class="mt-4">
                    You must not misuse the service, attempt unauthorized access, interfere with platform operations,
                    or use the service to distribute harmful, fraudulent, or infringing content.
                </p>
            </section>

            <section id="account-responsibility" data-terms-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Account Responsibility</h2>
                <p class="mt-4">
                    You are responsible for maintaining account credential confidentiality and for all actions that
                    occur under your account, whether authorized by you or not.
                </p>
                <p class="mt-4">
                    You agree to provide accurate account information and to promptly notify us of any suspected
                    unauthorized use, credential compromise, or security incident.
                </p>
            </section>

            <section id="payment-terms" data-terms-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Payment Terms</h2>
                <p class="mt-4">
                    Paid features, subscription plans, or service fees are billed according to your active commercial
                    agreement, including applicable invoicing cycles, taxes, and payment methods.
                </p>
                <p class="mt-4">
                    You agree to maintain valid billing details and to make timely payments for all charges associated
                    with your account and selected services.
                </p>
            </section>

            <section id="limitation-of-liability" data-terms-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Limitation of Liability</h2>
                <p class="mt-4">
                    To the maximum extent permitted by law, Yalla Spare will not be liable for indirect, incidental,
                    special, consequential, or punitive damages arising from or related to platform use.
                </p>
                <p class="mt-4">
                    Total liability for claims related to the service is limited to the amount paid by you for the
                    applicable service period preceding the event giving rise to the claim.
                </p>
            </section>

            <section id="termination" data-terms-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Termination</h2>
                <p class="mt-4">
                    We may suspend or terminate account access where required to protect service integrity, address
                    security risks, or enforce these terms in response to violations.
                </p>
                <p class="mt-4">
                    Upon termination, access rights end immediately, while provisions relating to payment obligations,
                    legal rights, and liability limitations remain in effect.
                </p>
            </section>

            <section id="governing-law" data-terms-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Governing Law</h2>
                <p class="mt-4">
                    These terms are governed by and construed in accordance with applicable local laws, without regard
                    to conflict of law principles.
                </p>
                <p class="mt-4">
                    Any dispute arising from or related to these terms will be subject to the exclusive jurisdiction of
                    the competent courts in the governing legal venue.
                </p>
            </section>

            <section id="contact-information" data-terms-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Contact Information</h2>
                <p class="mt-4">
                    For legal, contractual, or policy-related inquiries, contact:
                </p>
                <p class="mt-4 text-slate-500 dark:text-slate-400">
                    <a href="mailto:support@yallaspare.com" class="font-medium text-[#070740] underline decoration-[#070740]/60 underline-offset-4 transition-colors hover:text-slate-900 dark:text-slate-200 dark:hover:text-white">
                        support@yallaspare.com
                    </a>
                </p>
            </section>
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.documentElement.classList.add('scroll-smooth');

            const sections = Array.from(document.querySelectorAll('[data-terms-section]'));
            const links = Array.from(document.querySelectorAll('[data-terms-nav]'));

            if (sections.length === 0 || links.length === 0) return;

            const activeClasses = ['text-[#070740]', 'dark:text-slate-100', 'font-semibold'];

            const setActive = (id) => {
                links.forEach((link) => {
                    const isActive = link.getAttribute('href') === `#${id}`;
                    link.classList.toggle(activeClasses[0], isActive);
                    link.classList.toggle(activeClasses[1], isActive);
                    link.classList.toggle(activeClasses[2], isActive);
                    link.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
            };

            const observer = new IntersectionObserver(
                (entries) => {
                    const visible = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

                    if (visible[0]) setActive(visible[0].target.id);
                },
                {
                    root: null,
                    rootMargin: '-30% 0px -55% 0px',
                    threshold: [0.2, 0.4, 0.7],
                }
            );

            sections.forEach((section) => observer.observe(section));
            setActive(sections[0].id);
        });
    </script>
@endsection
