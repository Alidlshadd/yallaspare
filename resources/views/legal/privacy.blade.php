@extends('layouts.user')

@section('title', __('Privacy Policy & SSL Security'))
@section('meta_description', __('Privacy policy, SSL security, cookies, and data protection information for Yalla Spare.'))

@section('content')
    <div data-vision-page data-privacy-page class="pv-page mx-auto w-full max-w-6xl">

        <section class="pv-hero relative overflow-hidden rounded-[2rem] px-6 py-12 sm:px-10 sm:py-14 lg:px-14 lg:py-16">
            <span class="pv-grid" aria-hidden="true"></span>
            <span class="pv-glow" aria-hidden="true"></span>
            <svg class="pv-deco" style="top:-36px; left:-36px; width:200px; height:200px;" viewBox="0 0 100 100" aria-hidden="true">
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
                    <span class="sup-in inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.18em] text-white backdrop-blur-sm">
                        {{ __('Legal') }}
                    </span>
                    <h1 class="sup-in mt-6 text-4xl font-black leading-[1.02] tracking-[-0.03em] text-white sm:text-5xl" style="animation-delay: .1s">
                        {{ __('Privacy Policy & SSL Security') }}
                    </h1>
                    <p class="sup-in mt-5 max-w-xl text-base leading-relaxed text-slate-300" style="animation-delay: .18s">
                        {{ __('We value the privacy of our customers and are committed to protecting your personal information while providing a safe and reliable shopping experience.') }}
                    </p>

                    <div class="pv-badge-row sup-in mt-7" style="animation-delay: .26s">
                        <span class="pv-badge is-secure">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                            {{ __('Secure') }}
                        </span>
                        <span class="pv-badge is-encrypted">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6 9 17l-5-5" /></svg>
                            {{ __('Encrypted') }}
                        </span>
                        <span class="pv-badge is-protected">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-4 7-10V5l-7-3-7 3v6c0 6 7 10 7 10Z" /></svg>
                            {{ __('Protected') }}
                        </span>
                        <span class="pv-badge is-gdpr">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9" /><path d="M9 12.5 11 15l4-5" /></svg>
                            {{ __('GDPR Ready') }}
                        </span>
                    </div>
                </div>

                <div class="pv-shield-wrap sup-in" style="animation-delay: .2s" aria-hidden="true">
                    <svg class="pv-shield" viewBox="0 0 100 110">
                        <path class="pv-shield-outline" d="M50 6 88 20 88 52C88 80 70 96 50 104 30 96 12 80 12 52L12 20Z" />
                        <path class="pv-shield-check" d="M35 54 46 65 68 40" />
                    </svg>
                </div>
            </div>

            <div class="pv-steps sup-in" style="animation-delay: .32s">
                <div class="pv-step">
                    <span class="pv-step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                    </span>
                    <span>{{ __('Encrypted Connection') }}</span>
                </div>
                <span class="pv-step-dots" aria-hidden="true"></span>
                <div class="pv-step">
                    <span class="pv-step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21V8l9-5 9 5v13" /><path d="M9 21v-6h6v6" /></svg>
                    </span>
                    <span>{{ __('Secure Storage') }}</span>
                </div>
                <span class="pv-step-dots" aria-hidden="true"></span>
                <div class="pv-step">
                    <span class="pv-step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="13" rx="2" /><path d="M3 10h18" /><path d="M8 20 6 20" /></svg>
                    </span>
                    <span>{{ __('No Card Data Stored') }}</span>
                </div>
            </div>
        </section>

        <div class="mt-10">
            <div class="space-y-5">
                <article id="pv-1" data-privacy-panel="pv-1" data-vision-reveal class="pv-card pv-c1">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 21s7-4 7-10V5l-7-3-7 3v6c0 6 7 10 7 10Z" /></svg>
                        </span>
                        <h2>{{ __('1. Protection of Your Personal Information') }}</h2>
                        <span class="pv-status is-protected">{{ __('Protected') }}</span>
                    </div>
                    <p>{{ __('We value the privacy of our customers and are committed to protecting your personal information. Any information you provide to us is handled with care and is not sold, rented, or shared with third parties for commercial purposes.') }}</p>
                    <p>{{ __('Your personal information is collected only for the purpose of providing better service, processing orders, and improving your shopping experience.') }}</p>
                </article>

                <article id="pv-2" data-privacy-panel="pv-2" data-vision-reveal class="pv-card pv-c2">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" /></svg>
                        </span>
                        <h2>{{ __('2. Browsing Our Website') }}</h2>
                    </div>
                    <p>{{ __('You may visit our website and browse products without providing personal information.') }}</p>
                    <p>{{ __('During normal browsing, we do not require personal identity details unless you choose to contact us, create an account, or place an order.') }}</p>
                </article>

                <article id="pv-3" data-privacy-panel="pv-3" data-vision-reveal class="pv-card pv-c3">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 7h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5L3 7Z" /><path d="M8 7V5a4 4 0 0 1 8 0v2" /></svg>
                        </span>
                        <h2>{{ __('3. Shopping and Data Protection') }}</h2>
                    </div>
                    <p>{{ __('When you place an order on our website, we may ask for certain personal details such as your name, phone number, address, and email address in order to process and deliver your order.') }}</p>
                    <p>{{ __('This information is stored securely and is used only for order processing, customer support, and service improvement.') }}</p>
                </article>

                <article id="pv-4" data-privacy-panel="pv-4" data-vision-reveal class="pv-card pv-c4">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="6" width="18" height="13" rx="2" /><path d="M3 10h18" /></svg>
                        </span>
                        <h2>{{ __('4. Payment Information') }}</h2>
                        <span class="pv-status is-encrypted">{{ __('Encrypted') }}</span>
                    </div>
                    <p>{{ __('Orders may be paid by cash on delivery, direct agreement, or an enabled online payment provider.') }}</p>
                    <p>{{ __('Online payments are completed on the selected provider payment page or app. We do not collect or store card numbers, PINs, CVV codes, or banking credentials.') }}</p>
                    <p>{{ __('We store only payment status, provider reference numbers, and safe gateway responses needed to verify and support your order.') }}</p>
                </article>

                <article id="pv-5" data-privacy-panel="pv-5" data-vision-reveal class="pv-card pv-c5">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                        </span>
                        <h2>{{ __('5. SSL Security') }}</h2>
                        <span class="pv-status is-secure">{{ __('Secure') }}</span>
                    </div>
                    <p>{{ __('Our website uses SSL (Secure Sockets Layer) technology to help protect your personal information during data transmission.') }}</p>
                    <p>{{ __('SSL helps encrypt the connection between your browser and our website, reducing the risk of unauthorized access to your data.') }}</p>
                </article>

                <article id="pv-6" data-privacy-panel="pv-6" data-vision-reveal class="pv-card pv-c6">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9" /><circle cx="9" cy="10" r="1" /><circle cx="14" cy="9" r="1" /><circle cx="15" cy="14" r="1" /><circle cx="9.5" cy="15" r="1" /></svg>
                        </span>
                        <h2>{{ __('6. Cookies') }}</h2>
                    </div>
                    <p>{{ __('Our website may use cookies to improve user experience, remember preferences, and help us understand how visitors use the site.') }}</p>
                    <p>{{ __('Cookies are small data files stored on your device by your browser. You may disable cookies in your browser settings, but some parts of the website may not function properly if cookies are disabled.') }}</p>
                </article>

                <article id="pv-7" data-privacy-panel="pv-7" data-vision-reveal class="pv-card pv-c7">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9" /><path d="M14.5 9.5a3 3 0 1 0 0 5" /></svg>
                        </span>
                        <h2>{{ __('7. Copyright') }}</h2>
                    </div>
                    <p>{{ __('All content on this website, including text, images, graphics, logos, and other materials, belongs to the website owner or the original content creator where applicable.') }}</p>
                    <p>{{ __('These materials may not be copied, reproduced, distributed, or used for commercial purposes without permission.') }}</p>
                </article>

                <article id="pv-8" data-privacy-panel="pv-8" data-vision-reveal class="pv-card pv-c8">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 21V8l9-5 9 5v13" /><path d="M9 21v-6h6v6" /></svg>
                        </span>
                        <h2>{{ __('8. Data Security') }}</h2>
                        <span class="pv-status is-gdpr">{{ __('GDPR Ready') }}</span>
                    </div>
                    <p>{{ __('We use commercially reasonable security measures to protect your personal information.') }}</p>
                    <p>{{ __('However, no method of transmission over the internet or electronic storage is completely secure, and we cannot guarantee absolute security.') }}</p>
                </article>

                <article id="pv-9" data-privacy-panel="pv-9" data-vision-reveal class="pv-card pv-c9">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 14a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1" /><path d="M14 10a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1" /></svg>
                        </span>
                        <h2>{{ __('9. Third-Party Links') }}</h2>
                    </div>
                    <p>{{ __('Our website may contain links to external websites. Please note that we are not responsible for the privacy practices or content of third-party websites.') }}</p>
                    <p>{{ __('We recommend reviewing the privacy policies of any external websites you visit.') }}</p>
                </article>

                <article id="pv-10" data-privacy-panel="pv-10" data-vision-reveal class="pv-card pv-c10">
                    <div class="pv-card-head">
                        <span class="pv-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 12a9 9 0 1 1-2.6-6.4" /><path d="M21 4v5h-5" /></svg>
                        </span>
                        <h2>{{ __('10. Changes to This Privacy Policy') }}</h2>
                    </div>
                    <p>{{ __('We may update this Privacy Policy from time to time.') }}</p>
                    <p>{{ __('Any changes will be posted on this page, and the updated version will become effective once published.') }}</p>
                </article>

            </div>
        </div>

        <section id="pv-11" data-privacy-panel="pv-11" data-vision-reveal class="pv-cta relative mt-10 overflow-hidden">
            <svg class="pv-deco" style="bottom:-30px; right:-30px; width:180px; height:180px;" viewBox="0 0 100 100" aria-hidden="true">
                <circle cx="50" cy="50" r="34" fill="none" stroke="currentColor" stroke-width="2" />
                <circle cx="50" cy="50" r="10" fill="none" stroke="currentColor" stroke-width="2" />
                <circle cx="50" cy="34" r="2" fill="currentColor" /><circle cx="50" cy="66" r="2" fill="currentColor" />
                <circle cx="34" cy="50" r="2" fill="currentColor" /><circle cx="66" cy="50" r="2" fill="currentColor" />
                <circle cx="61.3" cy="38.7" r="2" fill="currentColor" /><circle cx="38.7" cy="61.3" r="2" fill="currentColor" />
                <circle cx="61.3" cy="61.3" r="2" fill="currentColor" /><circle cx="38.7" cy="38.7" r="2" fill="currentColor" />
            </svg>
            <div class="relative flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <span class="pv-cta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v9a2.5 2.5 0 0 1-2.5 2.5H9l-5 4v-4.8a2.5 2.5 0 0 1-1-2V5.5Z" /></svg>
                    </span>
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-amber-400">{{ __('Questions?') }}</p>
                        <h2 class="mt-1 text-2xl font-black text-white sm:text-3xl">{{ __('11. Contact Us') }}</h2>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">{{ __('If you have any questions or suggestions regarding this Privacy Policy, please contact us through our Contact Page or customer support channels.') }}</p>
                    </div>
                </div>
                <a href="{{ route('legal.contact') }}" class="pv-cta-btn">
                    {{ __('Contact Support') }}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </a>
            </div>
        </section>
    </div>
@endsection
