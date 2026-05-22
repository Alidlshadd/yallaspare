@extends('layouts.user')

@section('title', __('Contact Us'))
@section('meta_description', __('Get in touch with Yalla Spare customer support for products, orders, delivery, and business inquiries.'))

@section('content')
    <section class="mx-auto w-full max-w-[900px] space-y-8">
        <header class="rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-slate-100/70 p-8 shadow-sm dark:border-slate-800/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800/60">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Customer Service') }}</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('Contact Us') }}</h1>
            <p class="mt-5 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('We are here to help you with any questions about our products, orders, or delivery services. If you need assistance, feel free to contact our customer support team.') }}
            </p>
        </header>

        @if (session('success'))
            <x-ui.alert variant="success" :title="__('Message sent')">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="danger" :title="__('Message not sent')">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('1. Customer Support') }}</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Our support team is available to assist you during the following hours:') }}
            </p>
            <div class="mt-4 rounded-xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Working Hours') }}</p>
                <p class="mt-2 text-base font-medium text-slate-900 dark:text-slate-100">{{ __('Monday - Saturday') }}</p>
                <p class="text-base text-slate-700 dark:text-slate-200">09:00 - 18:00</p>
                <p class="mt-2 text-base font-medium text-slate-900 dark:text-slate-100">{{ __('Sunday: Closed') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('2. Contact Information') }}</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article class="rounded-xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#070740] shadow-sm dark:bg-slate-900">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 4h3l2 5-2 1.5a16 16 0 0 0 5 5L14.5 13l5 2v3a2 2 0 0 1-2 2C10.6 20 4 13.4 4 6a2 2 0 0 1 1-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Phone / WhatsApp') }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">+964 770 448 8315</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#070740] shadow-sm dark:bg-slate-900">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8" />
                                <path d="m5 7 7 5 7-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Email') }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">support@yallaspare.com</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-[#070740] shadow-sm dark:bg-slate-900">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h14M12 5a7 7 0 1 1 0 14a7 7 0 0 1 0-14Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Website') }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('www.yallaspare.com') }}</p>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Send a Support Request') }}</h2>
            <form method="POST" action="{{ route('legal.contact.send') }}" class="mt-5 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Full name') }}</label>
                        <input id="name" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[#070740]/30 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[#070740]/30 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Phone') }}</label>
                        <input id="phone" name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[#070740]/30 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="topic" class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Topic') }}</label>
                        <select id="topic" name="topic" required class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[#070740]/30 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            @foreach (['general' => __('General'), 'urgent' => __('Urgent'), 'billing' => __('Billing'), 'account' => __('Account'), 'order' => __('Order'), 'parts' => __('Parts')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('topic', request('topic', 'general')) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('topic') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="subject" class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Subject') }}</label>
                    <input id="subject" name="subject" value="{{ old('subject') }}" required class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[#070740]/30 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    @error('subject') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="message" class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Message') }}</label>
                    <textarea id="message" name="message" rows="6" required class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-[#070740]/30 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('message') }}</textarea>
                    @error('message') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#070740] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0a0d55]">
                    {{ __('Send Message') }}
                </button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('3. Business Information') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Company Name') }}</span><br>{{ __('Yalla Spare - Auto Parts Store') }}</p>
                <p><span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Business Type') }}</span><br>{{ __('Online Auto Spare Parts Supplier') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('4. Location') }}</h2>
            <div class="mt-4 flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#070740] dark:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                </span>
                <div class="space-y-3">
                    <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ __('We are based in Erbil, Iraq, and we deliver auto spare parts across different cities.') }}
                    </p>
                    <p class="text-base leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ __('For delivery inquiries or product availability, please contact us before placing your order.') }}
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-slate-50 p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('5. Help Section') }}</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('If you need help finding the correct part for your vehicle, please send us:') }}
            </p>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <li>{{ __('Car brand and model') }}</li>
                <li>{{ __('Year of manufacture') }}</li>
                <li>{{ __('Engine type (if available)') }}</li>
                <li>{{ __('Part name or photo') }}</li>
            </ul>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('Our team will help you find the correct spare part.') }}
            </p>
        </section>
    </section>
@endsection
