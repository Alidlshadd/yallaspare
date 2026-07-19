@extends('layouts.user')

@section('title', __('Contact Us'))
@section('meta_description', __('Get in touch with Yalla Spare customer support for products, orders, delivery, and business inquiries.'))

@section('content')
    <div data-vision-page data-contact-page class="ct-page mx-auto w-full max-w-6xl">
        <section class="ct-hero relative overflow-hidden rounded-[2rem]">
            <span class="ct-grid" aria-hidden="true"></span>
            <span class="ct-orb ct-orb-one" aria-hidden="true"></span>
            <span class="ct-orb ct-orb-two" aria-hidden="true"></span>

            <div class="relative grid min-h-[540px] items-center gap-12 px-6 py-14 sm:px-10 lg:grid-cols-[1.08fr_.92fr] lg:px-14 lg:py-16">
                <div class="max-w-2xl">
                    <span class="sup-in inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.18em] text-white backdrop-blur-sm">
                        <span class="ct-live-dot" aria-hidden="true"></span>
                        {{ __('Customer Service') }}
                    </span>
                    <h1 class="sup-in mt-7 text-5xl font-black leading-[.98] tracking-[-0.055em] text-white sm:text-6xl lg:text-7xl" style="animation-delay: .1s">
                        {{ __('Contact Us') }}
                    </h1>
                    <p class="sup-in mt-6 max-w-xl text-base leading-8 text-slate-300 sm:text-lg" style="animation-delay: .2s">
                        {{ __('We are here to help you with any questions about our products, orders, or delivery services. If you need assistance, feel free to contact our customer support team.') }}
                    </p>
                    <div class="sup-in mt-8 flex flex-wrap items-center gap-3" style="animation-delay: .3s">
                        <a href="#contact-form" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 text-sm font-extrabold text-primary shadow-lg shadow-amber-400/20 transition hover:-translate-y-0.5 hover:bg-amber-300 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            {{ __('Send a Support Request') }}
                            <svg class="ct-arrow h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                        <span class="inline-flex h-12 items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 text-sm font-bold text-slate-200 backdrop-blur-sm">
                            <svg class="h-4 w-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8" /><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            09:00 - 18:00
                        </span>
                    </div>
                </div>

                <div class="ct-signal-wrap sup-in" style="animation-delay: .18s" aria-hidden="true">
                    <div class="ct-signal">
                        <span class="ct-ring ct-ring-one"></span>
                        <span class="ct-ring ct-ring-two"></span>
                        <span class="ct-ring ct-ring-three"></span>
                        <span class="ct-sweep"></span>
                        <span class="ct-signal-core">
                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path d="M5 4h3l2 5-2 1.5a16 16 0 0 0 5 5L14.5 13l5 2v3a2 2 0 0 1-2 2C10.6 20 4 13.4 4 6a2 2 0 0 1 1-2Z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="ct-signal-label">
                            <b>{{ __('Phone / WhatsApp') }}</b>
                            <small dir="ltr">+964 770 448 8315</small>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="mt-6">
                <x-ui.alert variant="success" :title="__('Message sent')">
                    {{ session('success') }}
                </x-ui.alert>
            </div>
        @endif

        @if (session('error'))
            <div class="mt-6">
                <x-ui.alert variant="danger" :title="__('Message not sent')">
                    {{ session('error') }}
                </x-ui.alert>
            </div>
        @endif

        <section class="ct-channels relative z-10 -mt-6 mx-4 grid gap-3 rounded-3xl border border-slate-200/80 bg-white/95 p-3 shadow-xl shadow-slate-900/10 backdrop-blur-xl sm:grid-cols-3 lg:mx-10 dark:border-slate-700 dark:bg-slate-900/95 dark:shadow-black/25">
            <a href="tel:+9647704488315" class="ct-channel group flex items-center gap-4 rounded-2xl px-4 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="ct-channel-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 4h3l2 5-2 1.5a16 16 0 0 0 5 5L14.5 13l5 2v3a2 2 0 0 1-2 2C10.6 20 4 13.4 4 6a2 2 0 0 1 1-2Z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="min-w-0">
                    <small>{{ __('Phone / WhatsApp') }}</small>
                    <b dir="ltr">+964 770 448 8315</b>
                </span>
            </a>

            <a href="mailto:support@yallaspare.com" class="ct-channel group flex items-center gap-4 rounded-2xl px-4 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="ct-channel-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3.5" y="5" width="17" height="14" rx="3" /><path d="m5 7 7 5 7-5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="min-w-0">
                    <small>{{ __('Email') }}</small>
                    <b class="truncate">support@yallaspare.com</b>
                </span>
            </a>

            <a href="{{ url('/') }}" class="ct-channel group flex items-center gap-4 rounded-2xl px-4 py-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="ct-channel-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" /><path d="M4 12h16M12 4c2 2.2 3 4.9 3 8s-1 5.8-3 8c-2-2.2-3-4.9-3-8s1-5.8 3-8Z" stroke-linecap="round" />
                    </svg>
                </span>
                <span class="min-w-0">
                    <small>{{ __('Website') }}</small>
                    <b>{{ __('www.yallaspare.com') }}</b>
                </span>
            </a>
        </section>

        <section class="mt-16 grid gap-6 lg:grid-cols-[minmax(0,1.55fr)_minmax(300px,.75fr)] lg:items-start">
            <article id="contact-form" data-vision-reveal class="ct-form-card scroll-mt-28 overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="border-b border-slate-200/80 px-6 py-7 sm:px-8 dark:border-slate-800">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-white shadow-lg shadow-primary/20 dark:bg-amber-400 dark:text-[#070740]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v9a2.5 2.5 0 0 1-2.5 2.5H9l-5 4v-4.8a2.5 2.5 0 0 1-1-2V5.5Z" stroke-linecap="round" stroke-linejoin="round" /><path d="M8 8h8M8 12h5" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-amber-600 dark:text-amber-400">{{ __('Customer Service') }}</p>
                            <h2 class="mt-1 text-2xl font-black tracking-tight text-primary sm:text-3xl dark:text-white">{{ __('Send a Support Request') }}</h2>
                        </div>
                    </div>
                </header>

                <form method="POST" action="{{ route('legal.contact.send') }}" class="space-y-7 p-6 sm:p-8" data-testid="contact-form">
                    @csrf

                    <fieldset>
                        <legend class="ct-step-title"><span>01</span>{{ __('Contact Information') }}</legend>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="name" class="ct-label">{{ __('Full name') }}</label>
                                <input id="name" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required autocomplete="name" class="ct-input">
                                @error('name') <p class="ct-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email" class="ct-label">{{ __('Email') }}</label>
                                <input id="email" type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required autocomplete="email" class="ct-input">
                                @error('email') <p class="ct-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone" class="ct-label">{{ __('Phone') }}</label>
                                <input id="phone" name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" autocomplete="tel" class="ct-input" dir="ltr">
                                @error('phone') <p class="ct-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="topic" class="ct-label">{{ __('Topic') }}</label>
                                <select id="topic" name="topic" required class="ct-input">
                                    @foreach (['general' => __('General'), 'urgent' => __('Urgent'), 'billing' => __('Billing'), 'account' => __('Account'), 'order' => __('Order'), 'parts' => __('Parts')] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('topic', request('topic', 'general')) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('topic') <p class="ct-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border-t border-slate-200/80 pt-7 dark:border-slate-800">
                        <legend class="ct-step-title"><span>02</span>{{ __('Message') }}</legend>
                        <div class="mt-5 space-y-5">
                            <div>
                                <label for="subject" class="ct-label">{{ __('Subject') }}</label>
                                <input id="subject" name="subject" value="{{ old('subject') }}" required class="ct-input">
                                @error('subject') <p class="ct-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="message" class="ct-label">{{ __('Message') }}</label>
                                <textarea id="message" name="message" rows="7" required class="ct-input resize-y">{{ old('message') }}</textarea>
                                @error('message') <p class="ct-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex flex-col gap-4 border-t border-slate-200/80 pt-7 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                        <p class="max-w-sm text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Our team will help you find the correct spare part.') }}</p>
                        <button type="submit" class="ct-submit inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-primary px-6 text-sm font-extrabold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:bg-[#10105f] hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:bg-amber-400 dark:text-[#070740] dark:shadow-amber-400/10 dark:hover:bg-amber-300">
                            {{ __('Send Message') }}
                            <svg class="ct-arrow h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </form>
            </article>

            <aside class="space-y-5 lg:sticky lg:top-28">
                <article data-vision-reveal class="ct-side-card ct-hours-card rounded-3xl p-6 text-white">
                    <div class="flex items-center justify-between gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-amber-300">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8" /><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="ct-open-badge"><i aria-hidden="true"></i>{{ __('Customer Service') }}</span>
                    </div>
                    <h3 class="mt-6 text-lg font-black">{{ __('Working Hours') }}</h3>
                    <div class="mt-4 border-t border-white/10 pt-4">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span class="text-slate-300">{{ __('Monday - Saturday') }}</span>
                            <b dir="ltr">09:00 - 18:00</b>
                        </div>
                        <p class="mt-3 text-sm text-slate-400">{{ __('Sunday: Closed') }}</p>
                    </div>
                </article>

                <article data-vision-reveal class="ct-side-card rounded-3xl border border-amber-300/70 bg-amber-50/70 p-6 dark:border-amber-400/20 dark:bg-amber-400/5">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 17h16M6 17l1-7h10l1 7M8 10l1-3h6l1 3M8 20h.01M16 20h.01" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-black tracking-tight text-primary dark:text-white">{{ __('If you need help finding the correct part for your vehicle, please send us:') }}</h3>
                    <ul class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        @foreach ([__('Car brand and model'), __('Year of manufacture'), __('Engine type (if available)'), __('Part name or photo')] as $item)
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-amber-400 text-[9px] font-black text-primary">✓</span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>

                <article data-vision-reveal class="ct-side-card rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-primary dark:bg-slate-800 dark:text-slate-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z" stroke-linecap="round" stroke-linejoin="round" /><circle cx="12" cy="10" r="2.5" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.17em] text-slate-400">{{ __('Location') }}</p>
                            <h3 class="mt-1 text-lg font-black text-primary dark:text-white">{{ __('Erbil, Iraq') }}</h3>
                        </div>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('We are based in Erbil, Iraq, and we deliver auto spare parts across different cities.') }}</p>
                </article>
            </aside>
        </section>

        <section data-vision-reveal class="ct-business mt-6 grid gap-6 overflow-hidden rounded-3xl border border-slate-200/80 bg-slate-50 px-6 py-7 sm:grid-cols-2 sm:px-8 dark:border-slate-800 dark:bg-slate-900/70">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-slate-400">{{ __('Company Name') }}</p>
                <p class="mt-2 text-base font-black text-primary dark:text-white">{{ __('Yalla Spare - Auto Parts Store') }}</p>
            </div>
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-slate-400">{{ __('Business Type') }}</p>
                <p class="mt-2 text-base font-black text-primary dark:text-white">{{ __('Online Auto Spare Parts Supplier') }}</p>
            </div>
        </section>
    </div>
@endsection
