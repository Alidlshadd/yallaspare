@extends('layouts.user')

@section('title', __('Distance Sales Agreement'))
@section('meta_description', __('Distance Sales Agreement terms for online orders, delivery, returns, payment security, and dispute resolution.'))

@section('content')
    <div data-vision-page data-terms-page class="tos-page mx-auto w-full max-w-6xl">
        <span class="tos-bp" aria-hidden="true"></span>

        <section class="tos-hero relative overflow-hidden rounded-[2rem] px-6 py-12 sm:px-10 sm:py-14 lg:px-14 lg:py-16">
            <span class="tos-hero-grid" aria-hidden="true"></span>
            <svg class="tos-deco" style="top:-30px; left:-30px; width:180px; height:180px;" viewBox="0 0 100 100" aria-hidden="true">
                <circle cx="50" cy="50" r="34" fill="none" stroke="currentColor" stroke-width="2" />
                <circle cx="50" cy="50" r="10" fill="none" stroke="currentColor" stroke-width="2" />
                <circle cx="50" cy="34" r="2" fill="currentColor" /><circle cx="50" cy="66" r="2" fill="currentColor" />
                <circle cx="34" cy="50" r="2" fill="currentColor" /><circle cx="66" cy="50" r="2" fill="currentColor" />
                <circle cx="61.3" cy="38.7" r="2" fill="currentColor" /><circle cx="38.7" cy="61.3" r="2" fill="currentColor" />
                <circle cx="61.3" cy="61.3" r="2" fill="currentColor" /><circle cx="38.7" cy="38.7" r="2" fill="currentColor" />
            </svg>

            <div class="relative grid items-center gap-10 lg:grid-cols-[1.15fr_.85fr]">
                <div>
                    <span class="tos-eyebrow sup-in">{{ __('Legal') }}</span>
                    <h1 class="sup-in mt-6 text-4xl sm:text-5xl" style="animation-delay: .1s">{{ __('Distance Sales Agreement') }}</h1>
                    <p class="tos-sub sup-in mt-3 text-base sm:text-lg" style="animation-delay: .16s">{{ __('Every order, clearly defined — from checkout to delivery.') }}</p>
                    <p class="tos-lede sup-in mt-5 text-sm leading-relaxed sm:text-base" style="animation-delay: .22s">
                        {{ __('This Distance Sales Agreement governs the sale of products purchased through our website. By placing an order on this website, the customer agrees to the terms and conditions stated below.') }}
                    </p>
                </div>

                <div class="tos-halo sup-in" style="animation-delay: .24s" aria-hidden="true">
                    <div class="tos-halo-glow"></div>
                    <div class="tos-halo-ring r1"></div>
                    <div class="tos-halo-ring r2"></div>
                    <div class="tos-halo-ring r3"></div>
                    <div class="tos-halo-orbit"><i></i></div>
                    <div class="tos-halo-orbit rev"><i></i></div>
                    <div class="tos-halo-core">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M3 7h12v9H3zM15 10h3l3 3v3h-6z" /><circle cx="7" cy="18" r="1.5" /><circle cx="17" cy="18" r="1.5" /></svg>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-10">
            <div class="space-y-6">
                <article data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4" /></svg></span>
                        <div><span class="n">{{ __('Article 01') }}</span><h2>{{ __('1. Subject of the Agreement') }}</h2></div>
                    </div>
                    <p>{{ __('The subject of this agreement is to define the rights and obligations of the seller and the buyer regarding the sale and delivery of products ordered electronically through the website.') }}</p>
                </article>

                <article data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M4 19h16M6 19V8l6-4 6 4v11M9 19v-5h6v5" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 19h16M6 19V8l6-4 6 4v11M9 19v-5h6v5" /></svg></span>
                        <div><span class="n">{{ __('Article 02') }}</span><h2>{{ __('2. Seller Information') }}</h2></div>
                    </div>
                    <div class="mt-4 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Company Name') }}</span><br>{{ __('Yalla Spare - Auto Parts Store') }}</p>
                        <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Business Type') }}</span><br>{{ __('Online Auto Spare Parts Supplier') }}</p>
                        <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Email') }}</span><br>support@yallaspare.com</p>
                        <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Location') }}</span><br>{{ __('Erbil, Iraq') }}</p>
                    </div>
                </article>

                <article data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="12" cy="8" r="3.2" /><path d="M5 19a7 7 0 0 1 14 0" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="3.2" /><path d="M5 19a7 7 0 0 1 14 0" /></svg></span>
                        <div><span class="n">{{ __('Article 03') }}</span><h2>{{ __('3. Buyer Information') }}</h2></div>
                    </div>
                    <p>{{ __('The buyer is the person who places an order through the website and provides their personal information during the checkout process.') }}</p>
                    <p>{{ __('The information entered during the order process will be considered valid for communication and delivery.') }}</p>
                </article>

                <article data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 7h18M6 7v11h12V7M9 7V5h6v2" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 7h18M6 7v11h12V7M9 7V5h6v2" /></svg></span>
                        <div><span class="n">{{ __('Article 04') }}</span><h2>{{ __('4. Product Information') }}</h2></div>
                    </div>
                    <p>{{ __('Product type, quantity, brand, model, specifications, price, payment method, and delivery information are defined at the time the order is completed on the website.') }}</p>
                </article>

                <article data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M8 3h8l5 5v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" /><path d="M16 3v5h5" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 3h8l5 5v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" /><path d="M16 3v5h5" /></svg></span>
                        <div><span class="n">{{ __('Article 05') }}</span><h2>{{ __('5. General Terms') }}</h2></div>
                    </div>
                    <p>{{ __('The buyer confirms that they have read and accepted all product details, price information, payment conditions, and delivery terms before completing the order.') }}</p>
                    <p>{{ __('The seller agrees to deliver the purchased product in a complete and undamaged condition according to the specifications provided during the order process.') }}</p>
                    <p>{{ __('Delivery time may vary depending on the customer\'s location and shipping conditions.') }}</p>
                    <p>{{ __('If delivery becomes impossible due to unexpected events such as transportation issues, weather conditions, or other force majeure situations, the buyer will be informed and an alternative solution will be provided.') }}</p>
                </article>

                <article data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 7h12v9H3zM15 10h3l3 3v3h-6z" /><circle cx="7" cy="18" r="1.5" /><circle cx="17" cy="18" r="1.5" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 7h12v9H3zM15 10h3l3 3v3h-6z" /><circle cx="7" cy="18" r="1.5" /><circle cx="17" cy="18" r="1.5" /></svg></span>
                        <div><span class="n">{{ __('Article 06') }}</span><h2>{{ __('6. Delivery') }}</h2></div>
                    </div>
                    <p>{{ __('Orders will be shipped through trusted delivery companies.') }}</p>
                    <p>{{ __('Estimated delivery times may vary depending on the destination and shipping conditions.') }}</p>
                    <p>{{ __('The seller is not responsible if the delivery cannot be completed due to incorrect address or contact information provided by the buyer.') }}</p>
                </article>

                <article data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M4 12a8 8 0 1 0 2.3-5.7M4 6v4h4" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 12a8 8 0 1 0 2.3-5.7M4 6v4h4" /></svg></span>
                        <div><span class="n">{{ __('Article 07') }}</span><h2>{{ __('7. Right of Withdrawal') }}</h2></div>
                    </div>
                    <p>{{ __('The buyer has the right to request a return within 7 days from the date of delivery.') }}</p>
                    <p>{{ __('To exercise this right:') }}</p>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                        <li>{{ __('The product must be unused') }}</li>
                        <li>{{ __('The product must be in its original packaging') }}</li>
                        <li>{{ __('All accessories and documents must be returned') }}</li>
                    </ul>
                    <p>{{ __('Return shipping costs may be the responsibility of the buyer unless the product is defective or incorrectly delivered.') }}</p>
                </article>

                <article data-vision-reveal class="tos-card">
                    <svg class="tos-card-deco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="6.5" width="18" height="11" rx="2" /><path d="M3 10.5h18" /></svg>
                    <div class="tos-card-head">
                        <span class="tos-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="6.5" width="18" height="11" rx="2" /><path d="M3 10.5h18" /></svg></span>
                        <div><span class="n">{{ __('Article 08') }}</span><h2>{{ __('8. Payment Method') }}</h2></div>
                    </div>
                    <p>{{ __('Orders placed through the website do not require online payment.') }}</p>
                    <p>{{ __('Payments are completed through cash on delivery or direct agreement with the customer.') }}</p>
                    <p>{{ __('Since the website does not process online payments, no credit card or banking information is collected or stored on the system.') }}</p>
                </article>

                <section data-vision-reveal class="tos-cta relative">
                    <svg class="tos-deco" style="bottom:-30px; right:-30px; width:170px; height:170px;" viewBox="0 0 100 100" aria-hidden="true">
                        <circle cx="50" cy="50" r="30" fill="none" stroke="currentColor" stroke-width="2" />
                        <circle cx="50" cy="50" r="10" fill="none" stroke="currentColor" stroke-width="2" />
                        <g stroke="currentColor" stroke-width="2">
                            <line x1="50" y1="20" x2="50" y2="12" /><line x1="50" y1="80" x2="50" y2="88" />
                            <line x1="20" y1="50" x2="12" y2="50" /><line x1="80" y1="50" x2="88" y2="50" />
                            <line x1="29" y1="29" x2="23" y2="23" /><line x1="71" y1="71" x2="77" y2="77" />
                            <line x1="71" y1="29" x2="77" y2="23" /><line x1="29" y1="71" x2="23" y2="77" />
                        </g>
                    </svg>
                    <div class="relative flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="tos-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 10h12M6 14h12M4 6h16v12H4z" /></svg>
                            </span>
                            <div>
                                <span class="n" style="color:#9294bb;">{{ __('Article 09') }}</span>
                                <h2 class="mt-1 text-2xl font-black text-white sm:text-3xl">{{ __('9. Dispute Resolution') }}</h2>
                                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300">{{ __('In case of any dispute related to this agreement, both parties agree to resolve the issue through communication and mutual agreement whenever possible.') }}</p>
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
