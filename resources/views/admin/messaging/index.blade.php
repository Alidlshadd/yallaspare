<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-slate-400">
                    <span>{{ __('Communications') }}</span>
                    <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                    <span>OTPIQ</span>
                </div>
                <h2 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ __('SMS & WhatsApp Center') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Monitor verification delivery, provider readiness and phone coverage') }}</p>
            </div>
            <a href="{{ route('admin.email.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-400/40 dark:hover:text-white">
                <i class="fas fa-envelope-open-text text-xs" aria-hidden="true"></i>
                {{ __('Email Center') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                    <i class="fas fa-circle-check mr-1.5" aria-hidden="true"></i>{{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                    <i class="fas fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>{{ $errors->first() }}
                </div>
            @endif

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="{{ __('Messaging audience summary') }}">
                @foreach ([
                    ['label' => __('Phone profiles'), 'value' => $stats['with_phone'], 'icon' => 'fas fa-address-book', 'tone' => 'indigo'],
                    ['label' => __('Verified phones'), 'value' => $stats['verified'], 'icon' => 'fas fa-shield-check', 'tone' => 'emerald'],
                    ['label' => __('Awaiting verification'), 'value' => $stats['unverified'], 'icon' => 'fas fa-clock', 'tone' => 'amber'],
                    ['label' => __('SMS opt-ins'), 'value' => $stats['sms_opt_in'], 'icon' => 'fas fa-comment-sms', 'tone' => 'sky'],
                    ['label' => __('WhatsApp opt-ins'), 'value' => $stats['whatsapp_opt_in'], 'icon' => 'fab fa-whatsapp', 'tone' => 'green'],
                ] as $card)
                    @php
                        $tone = match ($card['tone']) {
                            'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
                            'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
                            'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
                            'green' => 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-300',
                            default => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300',
                        };
                    @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $tone }}">
                                <i class="{{ $card['icon'] }} text-sm" aria-hidden="true"></i>
                            </span>
                            <span class="font-mono text-2xl font-black tabular-nums text-slate-900 dark:text-white">{{ number_format($card['value']) }}</span>
                        </div>
                        <p class="mt-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                    </div>
                @endforeach
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,.65fr)]">
                <div class="space-y-5">
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">{{ __('Delivery channels') }}</p>
                            <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">{{ __('Provider health') }}</h3>
                        </div>
                        <div class="grid gap-4 p-5 md:grid-cols-2">
                            @foreach ($channels as $key => $channel)
                                @php
                                    $state = $channel['state'];
                                    $cardClasses = match ($state) {
                                        'ready' => 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/60 dark:bg-emerald-950/20',
                                        'disabled' => 'border-slate-200 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900/40',
                                        default => 'border-amber-200 bg-amber-50/50 dark:border-amber-900/60 dark:bg-amber-950/20',
                                    };
                                    $badgeClasses = match ($state) {
                                        'ready' => 'border-emerald-200 bg-white text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                                        'disabled' => 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400',
                                        default => 'border-amber-200 bg-white text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300',
                                    };
                                    $dotClasses = match ($state) {
                                        'ready' => 'bg-emerald-500',
                                        'disabled' => 'bg-slate-400',
                                        default => 'bg-amber-500',
                                    };
                                @endphp
                                <article class="relative overflow-hidden rounded-2xl border p-5 {{ $cardClasses }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl {{ $key === 'whatsapp' ? 'bg-green-500 text-white' : 'bg-sky-500 text-white' }} shadow-lg">
                                            <i class="{{ $key === 'whatsapp' ? 'fab fa-whatsapp' : 'fas fa-comment-sms' }} text-xl" aria-hidden="true"></i>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $badgeClasses }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
                                            {{ $channel['status'] }}
                                        </span>
                                    </div>
                                    <h4 class="mt-5 text-xl font-black text-slate-900 dark:text-white">{{ $channel['label'] }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                        {{ $key === 'whatsapp' ? __('Approved template verification through the configured WhatsApp Business account') : __('Transactional verification codes delivered through the OTPIQ SMS API') }}
                                    </p>
                                    @if ($key === 'whatsapp' && $configuration['template_name'] !== '')
                                        <div class="mt-4 rounded-xl border border-white/70 bg-white/70 px-3 py-2 dark:border-white/5 dark:bg-slate-900/60">
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('Template') }}</p>
                                            <p class="mt-0.5 truncate font-mono text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                {{ $configuration['template_name'] }}
                                                <span class="ml-1 font-sans text-[10px] font-bold uppercase text-slate-400">{{ $configuration['template_language'] }}</span>
                                            </p>
                                        </div>
                                    @endif
                                    @if ($key === 'whatsapp' && ($channel['template_alert'] ?? false))
                                        <p class="mt-3 rounded-xl border border-amber-200 bg-white/80 px-3 py-2 text-xs font-semibold text-amber-800 dark:border-amber-900/60 dark:bg-slate-900/60 dark:text-amber-200">
                                            <i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>
                                            {{ __('An approved WhatsApp verification template is required.') }}
                                        </p>
                                    @endif
                                    @if ($state !== 'ready' && $channel['missing'] !== [])
                                        <p class="mt-3 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                            {{ __('Missing') }}: {{ implode(' · ', $channel['missing']) }}
                                        </p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">{{ __('Configuration') }}</p>
                            <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">{{ __('Readiness checks') }}</h3>
                        </div>
                        <div class="grid gap-x-6 p-5 sm:grid-cols-2">
                            @foreach ([
                                __('OTPIQ API key') => $configuration['api_key'],
                                __('OTPIQ base URL') => $configuration['base_url'],
                                __('WhatsApp channel enabled') => $configuration['whatsapp_enabled'],
                                __('WhatsApp account ID') => $configuration['whatsapp_account'],
                                __('WhatsApp phone ID') => $configuration['whatsapp_phone'],
                                __('Approved template name') => $configuration['whatsapp_template'],
                                __('Template language') => $configuration['whatsapp_language'],
                            ] as $label => $ready)
                                <div class="flex items-center gap-3 border-b border-slate-100 py-3 last:border-0 dark:border-slate-800">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full {{ $ready ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-400 dark:bg-white/5 dark:text-slate-500' }}">
                                        <i class="fas {{ $ready ? 'fa-check' : 'fa-minus' }} text-[10px]" aria-hidden="true"></i>
                                    </span>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $label }}@if ($label === __('Template language') && $ready) <span class="font-mono text-xs text-slate-400">({{ $configuration['template_language'] }})</span>@endif
                                    </span>
                                    <span class="ml-auto text-[10px] font-bold uppercase tracking-wider {{ $ready ? 'text-emerald-600 dark:text-emerald-300' : 'text-slate-400' }}">{{ $ready ? __('Ready') : __('Missing') }}</span>
                                </div>
                            @endforeach

                            @php
                                $templateStatus = $configuration['template_status'];
                                $templateCheckState = match (true) {
                                    ! $templateStatus['checked'] => 'unverified',
                                    $templateStatus['template_approved'] === true => 'ready',
                                    default => 'missing',
                                };
                            @endphp
                            <div class="flex items-center gap-3 border-b border-slate-100 py-3 last:border-0 dark:border-slate-800 sm:col-span-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full {{ $templateCheckState === 'ready' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300' : ($templateCheckState === 'missing' ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300' : 'bg-slate-100 text-slate-400 dark:bg-white/5 dark:text-slate-500') }}">
                                    <i class="fas {{ $templateCheckState === 'ready' ? 'fa-check' : ($templateCheckState === 'missing' ? 'fa-triangle-exclamation' : 'fa-question') }} text-[10px]" aria-hidden="true"></i>
                                </span>
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ __('Template approved on OTPiQ') }}
                                    @if ($templateCheckState === 'ready' && $templateStatus['language_matches'] === false)
                                        <span class="text-xs font-medium text-amber-600 dark:text-amber-300">— {{ __('template language differs from the configured value') }}</span>
                                    @endif
                                </span>
                                <span class="ml-auto text-[10px] font-bold uppercase tracking-wider {{ $templateCheckState === 'ready' ? 'text-emerald-600 dark:text-emerald-300' : ($templateCheckState === 'missing' ? 'text-amber-600 dark:text-amber-300' : 'text-slate-400') }}">
                                    {{ $templateCheckState === 'ready' ? __('Ready') : ($templateCheckState === 'missing' ? __('Missing') : __('Not verified')) }}
                                </span>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="h-fit rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-indigo-500/10 dark:text-indigo-300">
                                <i class="fas fa-flask" aria-hidden="true"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">{{ __('Diagnostics') }}</p>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ __('Send test OTP') }}</h3>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.messaging.test') }}" class="space-y-5 p-5" data-loading-form data-loading-button-text="{{ __('Sending') }}">
                        @csrf
                        <div>
                            <label for="channel" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Channel') }}</label>
                            <select id="channel" name="channel" class="w-full rounded-xl border-slate-300 bg-white text-sm font-semibold text-slate-900 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                <option value="sms" @selected(old('channel', 'sms') === 'sms') @disabled(! $channels['sms']['available'])>SMS · {{ $channels['sms']['status'] }}</option>
                                <option value="whatsapp" @selected(old('channel') === 'whatsapp' && $channels['whatsapp']['available']) @disabled(! $channels['whatsapp']['available'])>WhatsApp · {{ $channels['whatsapp']['status'] }}</option>
                            </select>
                            @if (! $channels['whatsapp']['available'] && $channels['whatsapp']['missing'] !== [])
                                <p class="mt-1.5 text-xs leading-5 text-slate-400">
                                    {{ __('WhatsApp needs') }}: {{ implode(' · ', $channels['whatsapp']['missing']) }}
                                </p>
                            @endif
                            @error('channel')<p class="mt-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Iraq phone number') }}</label>
                            <div class="flex" dir="ltr">
                                <span class="inline-flex items-center rounded-l-xl border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">+964</span>
                                <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="0770 000 0000" class="min-w-0 flex-1 rounded-r-xl border-slate-300 bg-white text-sm text-slate-900 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-950 dark:text-white" required>
                            </div>
                            <p class="mt-1.5 text-xs leading-5 text-slate-400">{{ __('A random verification code will be generated and sent once') }}</p>
                            @error('phone')<p class="mt-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-60" data-loading-button>
                            <i class="fas fa-paper-plane text-xs" aria-hidden="true"></i>
                            <span data-loading-label>{{ __('Send test code') }}</span>
                        </button>

                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs leading-5 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-200">
                            <i class="fas fa-shield-halved mr-1" aria-hidden="true"></i>
                            {{ __('Test codes are not stored, displayed or written to application logs') }}
                        </div>
                    </form>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
