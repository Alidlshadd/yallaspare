@extends('layouts.legal')

@section('title', 'Privacy Policy | Yalla Spare')

@section('content')
    <section class="mx-auto w-full max-w-4xl">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Legal</p>
        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">
            Privacy Policy
        </h1>
        <p class="mt-6 max-w-2xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
            This policy outlines how Yalla Spare collects, uses, and protects information across our platform
            operations, account management, and support interactions.
        </p>
        <p class="mt-7 text-sm text-slate-500 dark:text-slate-400">Last updated: February 28, 2026</p>
        <div class="mt-8 h-px w-full bg-slate-200/70 dark:bg-slate-800/70"></div>
    </section>

    <section class="mx-auto mt-14 grid w-full max-w-5xl grid-cols-1 gap-14 lg:grid-cols-[220px_minmax(0,1fr)]">
        <aside class="hidden lg:block">
            <nav aria-label="Privacy sections" class="sticky top-24">
                <ul class="space-y-1.5 text-sm">
                    <li>
                        <a href="#introduction" data-privacy-nav class="privacy-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Introduction</span>
                        </a>
                    </li>
                    <li>
                        <a href="#data-collection" data-privacy-nav class="privacy-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Data Collection</span>
                        </a>
                    </li>
                    <li>
                        <a href="#data-usage" data-privacy-nav class="privacy-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Data Usage</span>
                        </a>
                    </li>
                    <li>
                        <a href="#data-security" data-privacy-nav class="privacy-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Data Security</span>
                        </a>
                    </li>
                    <li>
                        <a href="#contact-information" data-privacy-nav class="privacy-nav-link group inline-flex items-center gap-3 rounded-md px-2 py-1.5 text-slate-500 transition-colors hover:text-[#070740] dark:text-slate-400 dark:hover:text-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-45 transition-opacity group-hover:opacity-100"></span>
                            <span>Contact</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <article class="max-w-3xl space-y-16 text-sm leading-8 text-slate-600 dark:text-slate-300">
            <section id="introduction" data-privacy-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Introduction</h2>
                <p class="mt-5">
                    Yalla Spare is committed to protecting privacy while delivering secure and reliable platform
                    services. We process data with a focus on transparency, lawful handling, and operational
                    necessity across every touchpoint in the system.
                </p>
                <p class="mt-4">
                    This policy explains what information we process, the purpose behind processing, and the
                    safeguards we apply to keep data protected.
                </p>
            </section>

            <section id="data-collection" data-privacy-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Data Collection</h2>
                <p class="mt-5">
                    We collect account profile details, authentication records, transactional metadata, and usage
                    activity required to operate the dashboard and related business workflows.
                </p>
                <p class="mt-4">
                    Information may include user identifiers, contact data, device or session signals, and service
                    interaction logs used for stability, diagnostics, and service improvement.
                </p>
            </section>

            <section id="data-usage" data-privacy-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Data Usage</h2>
                <p class="mt-5">
                    We use collected information to deliver core features, maintain account security, process
                    operational events, and support platform administration.
                </p>
                <p class="mt-4">
                    Data is also used to improve performance, identify service risks, and communicate relevant
                    updates that affect account reliability and continuity.
                </p>
            </section>

            <section id="data-security" data-privacy-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Data Security</h2>
                <p class="mt-5">
                    Yalla Spare applies layered administrative and technical safeguards, including access controls,
                    system monitoring, and protective operational procedures.
                </p>
                <p class="mt-4">
                    These controls are designed to reduce unauthorized access, misuse, loss, or disclosure of
                    protected information.
                </p>
            </section>

            <section id="contact-information" data-privacy-section class="scroll-mt-28">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Contact Information</h2>
                <p class="mt-5">
                    For privacy questions, policy requests, or data-related support, contact:
                </p>
                <p class="mt-4">
                    <a href="mailto:support@yallaspare.com" class="font-medium text-[#070740] underline decoration-[#070740]/60 underline-offset-4 transition-colors hover:text-slate-900 dark:text-slate-200 dark:hover:text-white">
                        support@yallaspare.com
                    </a>
                </p>
            </section>
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sections = Array.from(document.querySelectorAll('[data-privacy-section]'));
            const links = Array.from(document.querySelectorAll('[data-privacy-nav]'));

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
