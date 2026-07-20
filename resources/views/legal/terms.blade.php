@extends('layouts.user')

@section('title', __('Terms of Service | Yalla Spare'))
@section('meta_description', __('The terms governing access to and use of Yalla Spare, including account, payment, liability, and legal provisions.'))

@section('content')
    <div data-vision-page data-terms-page class="tos-page mx-auto w-full max-w-6xl">
        <span class="tos-bp" aria-hidden="true"></span>

        <section class="tos-hero relative overflow-hidden rounded-[2rem] px-6 py-12 sm:px-10 sm:py-14 lg:px-14 lg:py-16">
            <span class="tos-hero-grid" aria-hidden="true"></span>
            <svg class="tos-deco" style="top:-30px; left:-30px; width:180px; height:180px;" viewBox="0 0 100 100" aria-hidden="true">
                <circle cx="50" cy="50" r="30" fill="none" stroke="currentColor" stroke-width="2" />
                <circle cx="50" cy="50" r="10" fill="none" stroke="currentColor" stroke-width="2" />
                <g stroke="currentColor" stroke-width="2">
                    <line x1="50" y1="20" x2="50" y2="12" /><line x1="50" y1="80" x2="50" y2="88" />
                    <line x1="20" y1="50" x2="12" y2="50" /><line x1="80" y1="50" x2="88" y2="50" />
                    <line x1="29" y1="29" x2="23" y2="23" /><line x1="71" y1="71" x2="77" y2="77" />
                    <line x1="71" y1="29" x2="77" y2="23" /><line x1="29" y1="71" x2="23" y2="77" />
                </g>
            </svg>

            <div class="relative grid items-center gap-10 lg:grid-cols-[1.15fr_.85fr]">
                <div>
                    <span class="tos-eyebrow sup-in">{{ __('Legal') }}</span>
                    <h1 class="sup-in mt-6 text-4xl sm:text-5xl" style="animation-delay: .1s">{{ __('Terms of Service') }}</h1>
                    <p class="tos-sub sup-in mt-3 text-base sm:text-lg" style="animation-delay: .16s">{{ __('The agreement behind every order, account, and interaction on Yalla Spare.') }}</p>
                    <p class="tos-lede sup-in mt-5 text-sm leading-relaxed sm:text-base" style="animation-delay: .22s">
                        {{ __('These terms govern your access to and use of Yalla Spare, including platform features, account operations, and commercial interactions across the service.') }}
                    </p>
                    <span class="tos-meta sup-in mt-6" style="animation-delay: .3s">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8" /><path d="M12 7v5l3 2" /></svg>
                        {{ __('Last updated: February 28, 2026') }}
                    </span>
                </div>

                <div class="tos-halo sup-in" style="animation-delay: .24s" aria-hidden="true">
                    <div class="tos-halo-glow"></div>
                    <div class="tos-halo-ring r1"></div>
                    <div class="tos-halo-ring r2"></div>
                    <div class="tos-halo-ring r3"></div>
                    <div class="tos-halo-orbit"><i></i></div>
                    <div class="tos-halo-orbit rev"><i></i></div>
                    <div class="tos-halo-core">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M12 21s7-4 7-10V5l-7-3-7 3v6c0 6 7 10 7 10Z" /><path d="m9 12 2 2 4-4" /></svg>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-10">
            <div class="space-y-6">
                <article id="service-usage" data-terms-section data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M9 9h6M9 13h6M9 17h3" /><rect x="4" y="4" width="16" height="16" rx="2" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9 9h6M9 13h6M9 17h3" /><rect x="4" y="4" width="16" height="16" rx="2" /></svg></span>
                        <div><span class="n">{{ __('Article 01') }}</span><h2>{{ __('Service Usage') }}</h2></div>
                    </div>
                    <p>{{ __('By accessing or using Yalla Spare, you agree to use the platform only for lawful business activities and in a manner consistent with these terms and applicable regulations.') }}</p>
                    <p>{{ __('You must not misuse the service, attempt unauthorized access, interfere with platform operations, or use the service to distribute harmful, fraudulent, or infringing content.') }}</p>
                </article>

                <article id="account-responsibility" data-terms-section data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="12" cy="8" r="4" /><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="4" /><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7" /></svg></span>
                        <div><span class="n">{{ __('Article 02') }}</span><h2>{{ __('Account Responsibility') }}</h2></div>
                    </div>
                    <p>{{ __('You are responsible for maintaining account credential confidentiality and for all actions that occur under your account, whether authorized by you or not.') }}</p>
                    <p>{{ __('You agree to provide accurate account information and to promptly notify us of any suspected unauthorized use, credential compromise, or security incident.') }}</p>
                </article>

                <article id="payment-terms" data-terms-section data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="6" width="18" height="13" rx="2" /><path d="M3 10h18" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="6" width="18" height="13" rx="2" /><path d="M3 10h18" /></svg></span>
                        <div><span class="n">{{ __('Article 03') }}</span><h2>{{ __('Payment Terms') }}</h2></div>
                    </div>
                    <p>{{ __('Paid features, subscription plans, or service fees are billed according to your active commercial agreement, including applicable invoicing cycles, taxes, and payment methods.') }}</p>
                    <p>{{ __('You agree to maintain valid billing details and to make timely payments for all charges associated with your account and selected services.') }}</p>
                </article>

                <article id="limitation-of-liability" data-terms-section data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 21s7-4 7-10V5l-7-3-7 3v6c0 6 7 10 7 10Z" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 21s7-4 7-10V5l-7-3-7 3v6c0 6 7 10 7 10Z" /></svg></span>
                        <div><span class="n">{{ __('Article 04') }}</span><h2>{{ __('Limitation of Liability') }}</h2></div>
                    </div>
                    <p>{{ __('To the maximum extent permitted by law, Yalla Spare will not be liable for indirect, incidental, special, consequential, or punitive damages arising from or related to platform use.') }}</p>
                    <p>{{ __('Total liability for claims related to the service is limited to the amount paid by you for the applicable service period preceding the event giving rise to the claim.') }}</p>
                </article>

                <article id="termination" data-terms-section data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="12" cy="12" r="9" /><path d="M9 9l6 6M15 9l-6 6" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9" /><path d="M9 9l6 6M15 9l-6 6" /></svg></span>
                        <div><span class="n">{{ __('Article 05') }}</span><h2>{{ __('Termination') }}</h2></div>
                    </div>
                    <p>{{ __('We may suspend or terminate account access where required to protect service integrity, address security risks, or enforce these terms in response to violations.') }}</p>
                    <p>{{ __('Upon termination, access rights end immediately, while provisions relating to payment obligations, legal rights, and liability limitations remain in effect.') }}</p>
                </article>

                <article id="governing-law" data-terms-section data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 3v18M5 8l7-5 7 5M4 8h6M14 8h6" /><path d="M5 8v6a2 2 0 0 0 2 2h1M19 8v6a2 2 0 0 1-2 2h-1" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3v18M5 8l7-5 7 5M4 8h6M14 8h6" /><path d="M5 8v6a2 2 0 0 0 2 2h1M19 8v6a2 2 0 0 1-2 2h-1" /></svg></span>
                        <div><span class="n">{{ __('Article 06') }}</span><h2>{{ __('Governing Law') }}</h2></div>
                    </div>
                    <p>{{ __('These terms are governed by and construed in accordance with applicable local laws, without regard to conflict of law principles.') }}</p>
                    <p>{{ __('Any dispute arising from or related to these terms will be subject to the exclusive jurisdiction of the competent courts in the governing legal venue.') }}</p>
                </article>

                <section id="contact-information" data-terms-section data-vision-reveal class="tos-cta relative">
                    <svg class="tos-deco" style="bottom:-30px; right:-30px; width:170px; height:170px;" viewBox="0 0 100 100" aria-hidden="true">
                        <circle cx="50" cy="50" r="34" fill="none" stroke="currentColor" stroke-width="2" />
                        <circle cx="50" cy="50" r="10" fill="none" stroke="currentColor" stroke-width="2" />
                        <circle cx="50" cy="34" r="2" fill="currentColor" /><circle cx="50" cy="66" r="2" fill="currentColor" />
                        <circle cx="34" cy="50" r="2" fill="currentColor" /><circle cx="66" cy="50" r="2" fill="currentColor" />
                        <circle cx="61.3" cy="38.7" r="2" fill="currentColor" /><circle cx="38.7" cy="61.3" r="2" fill="currentColor" />
                        <circle cx="61.3" cy="61.3" r="2" fill="currentColor" /><circle cx="38.7" cy="38.7" r="2" fill="currentColor" />
                    </svg>
                    <div class="relative flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="tos-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="5" width="17" height="14" rx="3" /><path d="m5 7 7 5 7-5" /></svg>
                            </span>
                            <div>
                                <span class="n" style="color:#9294bb;">{{ __('Article 07') }}</span>
                                <h2 class="mt-1 text-2xl font-black text-white sm:text-3xl">{{ __('Contact Information') }}</h2>
                                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">{{ __('We are committed to fairness, transparency, and your success. For legal, contractual, or policy-related inquiries, contact:') }}</p>
                            </div>
                        </div>
                        <a href="mailto:support@yallaspare.com" class="tos-cta-btn">
                            support@yallaspare.com
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
