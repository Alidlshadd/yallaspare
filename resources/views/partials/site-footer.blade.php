@php
    $footerMaxWidth = $maxWidth ?? 'max-w-7xl';
@endphp

<section class="bg-[#0A0F2C] py-10 text-white">
    <div class="mx-auto {{ $footerMaxWidth }} px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <article class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="5" y="10" width="14" height="10" rx="2" />
                        <path d="M8 10V7a4 4 0 1 1 8 0v3" stroke-linecap="round" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-semibold tracking-wide">{{ __('Secure Shopping') }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-white/75">{{ __('Your data and orders are protected.') }}</p>
                </div>
            </article>

            <article class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 7h11v8H3zM14 10h3l4 4v1h-7z" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="7" cy="18" r="1.5" />
                        <circle cx="17" cy="18" r="1.5" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-semibold tracking-wide">{{ __('Fast Delivery') }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-white/75">{{ __('Orders are processed and shipped quickly.') }}</p>
                </div>
            </article>

            <article class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round" />
                        <path d="M12 12 4.5 8M12 12l7.5-4M12 12v8" stroke-linecap="round" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-semibold tracking-wide">{{ __('Trusted Shipping') }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-white/75">{{ __('Delivered through trusted shipping partners.') }}</p>
                </div>
            </article>

        </div>
    </div>
</section>

<footer class="border-t border-slate-200/70 bg-gradient-to-b from-white to-slate-50/80 dark:border-slate-800/80 dark:from-slate-950 dark:to-slate-900/60">
    <div class="mx-auto {{ $footerMaxWidth }} px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-5">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <x-brand-mark
                        :logo-url="$systemSettings['site_logo_url'] ?? null"
                        :brand="$systemSettings['site_name'] ?? 'Yalla Spare'"
                        wrapper-class="inline-flex h-7 w-7 items-center justify-center overflow-hidden rounded-md border border-slate-200 dark:border-slate-700"
                        img-class="h-full w-full object-contain"
                        fallback-class="inline-flex h-full w-full items-center justify-center bg-slate-100 dark:bg-slate-800"
                        fallback-text-class="text-[9px] font-semibold text-slate-700 dark:text-slate-200"
                    />
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $systemSettings['site_name'] ?? 'Yalla Spare' }}</p>
                </div>
                <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ __('Trusted auto spare parts platform with fast support, reliable delivery, and transparent policies.') }}
                </p>
            </div>

            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Customer Service') }}</p>
                <div class="flex flex-col gap-2 text-sm">
                    <a href="{{ url('/support') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Support') }}</a>
                    <a href="{{ url('/return-exchange') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Return & Exchange') }}</a>
                    <a href="{{ url('/shipping-delivery') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Shipping & Delivery') }}</a>
                </div>
            </div>

            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Information') }}</p>
                <div class="flex flex-col gap-2 text-sm">
                    <a href="{{ url('/about-us') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('About Us') }}</a>
                    <a href="{{ url('/vision') }}" class="group inline-flex w-fit items-center gap-1.5 font-semibold text-amber-600 transition hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                        <span>{{ __('Our Vision') }}</span>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    <a href="{{ url('/contact') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Contact Us') }}</a>
                </div>
            </div>

            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Legal') }}</p>
                <div class="flex flex-col gap-2 text-sm">
                    <a href="{{ url('/privacy-policy') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Privacy Policy') }}</a>
                    <a href="{{ url('/terms') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Terms') }}</a>
                    <a href="{{ url('/distance-sales-agreement') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Distance Sales Agreement') }}</a>
                </div>
            </div>

            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Contact') }}</p>
                <div class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <p>support@yallaspare.com</p>
                    <p>+964 770 448 8315</p>
                    <p>{{ __('Erbil, Iraq') }}</p>
                    <div class="pt-2 flex items-center gap-3">
                        <a href="https://facebook.com/yallaspare" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Facebook') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M13.5 8.5V6.8c0-.7.5-.8.8-.8h1.6V3h-2.7C10.5 3 10 5 10 6.3v2.2H8v3h2V21h3.5v-9.5H16l.4-3h-2.9z"/>
                            </svg>
                        </a>
                        <a href="https://instagram.com/yallaspare" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Instagram') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>
                            </svg>
                        </a>
                        <a href="https://wa.me/9647704488315" target="_blank" rel="noopener noreferrer" aria-label="{{ __('WhatsApp') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 3.2A8.8 8.8 0 0 0 4.5 16l-1.2 4.8 5-1.2A8.8 8.8 0 1 0 12 3.2Zm0 15.8a7 7 0 0 1-3.5-.9l-.3-.2-2.9.7.7-2.8-.2-.3A7 7 0 1 1 12 19Zm3.9-5.2c-.2-.1-1.2-.6-1.4-.7-.2-.1-.3-.1-.5.1l-.4.5c-.1.2-.3.2-.4.1a5.8 5.8 0 0 1-1.7-1.1 6.6 6.6 0 0 1-1.2-1.5c-.1-.2 0-.3.1-.4l.3-.4.2-.3c.1-.1 0-.3 0-.4l-.6-1.4c-.2-.4-.3-.4-.5-.4h-.4a.8.8 0 0 0-.6.3c-.2.2-.8.8-.8 1.9s.8 2.3.9 2.4c.1.2 1.5 2.3 3.6 3.1.5.2 1 .4 1.3.5.5.2 1 .1 1.4.1.4-.1 1.2-.5 1.4-.9.2-.4.2-.8.1-.9 0-.1-.2-.2-.4-.3Z"/>
                            </svg>
                        </a>
                        <a href="https://youtube.com/@yallaspare" target="_blank" rel="noopener noreferrer" aria-label="{{ __('YouTube') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M21.6 7.2a2.9 2.9 0 0 0-2-2C17.9 4.7 12 4.7 12 4.7s-5.9 0-7.6.5a2.9 2.9 0 0 0-2 2A30.7 30.7 0 0 0 2 12a30.7 30.7 0 0 0 .4 4.8 2.9 2.9 0 0 0 2 2c1.7.5 7.6.5 7.6.5s5.9 0 7.6-.5a2.9 2.9 0 0 0 2-2A30.7 30.7 0 0 0 22 12a30.7 30.7 0 0 0-.4-4.8zM10 15.4V8.6L15.2 12 10 15.4z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 border-t border-slate-200/70 pt-4 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            &copy; {{ date('Y') }} {{ $systemSettings['site_name'] ?? 'Yalla Spare' }}. All rights reserved.
        </div>
    </div>
</footer>
