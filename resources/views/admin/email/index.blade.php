<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-white shadow-sm">
                        <i class="fas fa-envelope-open-text text-sm"></i>
                    </span>
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('Email Center') }}</h2>
                </div>
                <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Monitor mail health, test delivery, and preview every customer-facing email template from one place.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.email.outbox') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i class="fas fa-clock-rotate-left text-slate-400"></i>
                    {{ __('Open Outbox') }}
                </a>
                <a href="{{ route('admin.email.preview', ['template' => 'order-status', 'locale' => app()->getLocale()]) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover">
                    <i class="fas fa-eye"></i>
                    {{ __('Preview Example') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $healthClasses = [
            'green' => [
                'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200',
                'bar' => 'bg-emerald-500',
            ],
            'amber' => [
                'badge' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200',
                'bar' => 'bg-amber-500',
            ],
            'rose' => [
                'badge' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200',
                'bar' => 'bg-rose-500',
            ],
        ][$health['tone']] ?? [
            'badge' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200',
            'bar' => 'bg-slate-500',
        ];

        $toneClasses = [
            'blue' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-200',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200',
            'violet' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/50 dark:bg-violet-950/30 dark:text-violet-200',
            'cyan' => 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-900/50 dark:bg-cyan-950/30 dark:text-cyan-200',
            'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-200',
            'orange' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-200',
            'slate' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200',
        ];

        $statusClasses = [
            'sent' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-200',
            'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-200',
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/60 dark:bg-red-900/30 dark:text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
                <div class="grid gap-0 xl:grid-cols-[1.2fr_0.8fr]">
                    <div class="bg-slate-950 p-6 text-white sm:p-8">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-200">{{ __('Mail Operations') }}</p>
                                <h3 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">{{ __('Delivery health and template control') }}</h3>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                                    {{ __('Use this panel before changing SMTP, after deployment, and when checking customer email quality.') }}
                                </p>
                            </div>

                            <div class="min-w-[180px] rounded-2xl border border-white/10 bg-white/10 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-300">{{ __('Health') }}</span>
                                    <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $healthClasses['badge'] }}">
                                        {{ $health['label'] }}
                                    </span>
                                </div>
                                <div class="mt-4 flex items-end gap-2">
                                    <span class="text-4xl font-semibold">{{ $health['score'] }}</span>
                                    <span class="pb-1 text-sm text-slate-300">/ 100</span>
                                </div>
                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/15">
                                    <div class="h-full rounded-full {{ $healthClasses['bar'] }}" style="width: {{ $health['score'] }}%"></div>
                                </div>
                                <p class="mt-3 text-xs text-slate-300">{{ $health['ok'] }} / {{ $health['total'] }} {{ __('checks passing') }}</p>
                            </div>
                        </div>

                        <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                                <p class="text-xs font-medium text-slate-300">{{ __('Sent 24h') }}</p>
                                <p class="mt-2 text-2xl font-semibold">{{ number_format($emailStats['sent_24h']) }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                                <p class="text-xs font-medium text-slate-300">{{ __('Failed 24h') }}</p>
                                <p class="mt-2 text-2xl font-semibold">{{ number_format($emailStats['failed_24h']) }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                                <p class="text-xs font-medium text-slate-300">{{ __('Success rate') }}</p>
                                <p class="mt-2 text-2xl font-semibold">
                                    {{ $emailStats['success_rate_24h'] === null ? '-' : $emailStats['success_rate_24h'] . '%' }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                                <p class="text-xs font-medium text-slate-300">{{ __('Last sent') }}</p>
                                <p class="mt-2 text-sm font-semibold text-white">{{ $emailStats['last_sent_label'] }}</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.email.test') }}" class="space-y-4 p-6 sm:p-8">
                        @csrf
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Quick Delivery Test') }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Send one controlled email using the selected mailer.') }}</p>
                        </div>

                        <div>
                            <label for="recipient" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Recipient') }}</label>
                            <input id="recipient" type="email" name="recipient" value="{{ old('recipient', auth()->user()?->email) }}" class="w-full rounded-xl border-slate-300 bg-white text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
                            @error('recipient')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="subject" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Subject') }}</label>
                            <input id="subject" type="text" name="subject" value="{{ old('subject', 'YallaSpare test email') }}" class="w-full rounded-xl border-slate-300 bg-white text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
                            @error('subject')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="mailer" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Mailer') }}</label>
                            <select id="mailer" name="mailer" class="w-full rounded-xl border-slate-300 bg-white text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                @foreach($mailers as $mailer)
                                    <option value="{{ $mailer }}" @selected(old('mailer', $summary['default_mailer'] ?? '') === $mailer)>{{ $mailer }}</option>
                                @endforeach
                            </select>
                            @error('mailer')
                                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover">
                            <i class="fas fa-paper-plane"></i>
                            {{ __('Send Test Email') }}
                        </button>
                    </form>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Readiness Checks') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Production delivery requirements at a glance.') }}</p>
                        </div>
                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $healthClasses['badge'] }}">{{ $health['label'] }}</span>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($checks as $check)
                            <div class="flex items-start gap-4 px-6 py-4">
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200' }}">
                                    <i class="fas {{ $check['ok'] ? 'fa-check' : 'fa-screwdriver-wrench' }} text-xs"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $check['label'] }}</p>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' }}">
                                            {{ $check['ok'] ? __('OK') : __('Action') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 font-mono text-xs text-slate-500 dark:text-slate-400">{{ __('Current:') }} {{ $check['value'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $check['detail'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Recent Activity') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Latest recorded mail attempts. Recipient emails remain private.') }}</p>
                        </div>
                        <a href="{{ route('admin.email.outbox') }}" class="text-xs font-semibold text-primary hover:underline dark:text-blue-300">{{ __('View all') }}</a>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($recentLogs as $log)
                            <div class="grid gap-3 px-6 py-4 sm:grid-cols-[130px_1fr_auto] sm:items-center">
                                <div>
                                    <p class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ optional($log->created_at)->format('M d, H:i') }}</p>
                                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ optional($log->created_at)->diffForHumans() }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100" title="{{ $log->subject }}">{{ $log->subject ?: __('No subject') }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $log->recipient_domain ?: '-' }} <span class="mx-1 text-slate-300">/</span> {{ $log->mailer ?: '-' }}
                                    </p>
                                </div>
                                <span class="inline-flex w-fit items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$log->status] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                    <i class="fas {{ $log->status === 'sent' ? 'fa-check' : 'fa-xmark' }}"></i>
                                    {{ __(ucfirst($log->status)) }}
                                </span>
                            </div>
                        @empty
                            <div class="px-6 py-12 text-center">
                                <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                    <i class="fas fa-inbox"></i>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('No mail activity yet') }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Send a test email to create the first outbox record.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Template Examples') }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Live previews from the same Blade templates customers receive.') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">EN</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">AR</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">KU</span>
                    </div>
                </div>

                <div class="grid gap-5 p-6 lg:grid-cols-3">
                    @foreach($previewShowcase as $template)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-start justify-between gap-3 border-b border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border {{ $toneClasses[$template['tone']] ?? $toneClasses['slate'] }}">
                                            <i class="fas {{ $template['icon'] }} text-xs"></i>
                                        </span>
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $template['title'] }}</p>
                                    </div>
                                    <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $template['description'] }}</p>
                                </div>
                                <a href="{{ route('admin.email.preview', ['template' => $template['key'], 'locale' => app()->getLocale()]) }}" target="_blank" rel="noopener"
                                   class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800">
                                    {{ __('Open') }}
                                </a>
                            </div>
                            <div class="bg-slate-200 p-4 dark:bg-slate-950">
                                <div class="mx-auto max-w-sm overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm dark:border-slate-700">
                                    <div class="flex items-center justify-between bg-primary px-4 py-3 text-white">
                                        <span class="text-xs font-semibold tracking-wide">YALLASPARE</span>
                                        <span class="font-mono text-[10px] uppercase tracking-[0.16em] text-blue-200">{{ $template['sample']['spec'] }}</span>
                                    </div>
                                    <div class="h-0.5 bg-orange-500"></div>
                                    <div class="space-y-4 p-5">
                                        <div>
                                            <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] {{ $toneClasses[$template['tone']] ?? $toneClasses['slate'] }}">
                                                {{ $template['badges'][0] ?? __('Email') }}
                                            </span>
                                            <p class="mt-4 text-lg font-semibold leading-6 text-slate-950">{{ $template['sample']['subject'] }}</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $template['sample']['body'] }}</p>
                                        </div>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                            <p class="font-mono text-[11px] leading-5 text-slate-600">{{ $template['sample']['meta'] }}</p>
                                        </div>
                                        <div class="h-9 rounded-lg bg-primary"></div>
                                        <div class="space-y-2">
                                            <div class="h-2 w-2/3 rounded-full bg-slate-200"></div>
                                            <div class="h-2 w-1/2 rounded-full bg-slate-200"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
                    <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Template Library') }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Open any email in English, Arabic, or Kurdish sample data.') }}</p>
                    </div>

                    <div class="grid gap-4 p-6 md:grid-cols-2">
                        @foreach($templateCards as $template)
                            <article class="rounded-2xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border {{ $toneClasses[$template['tone']] ?? $toneClasses['slate'] }}">
                                        <i class="fas {{ $template['icon'] }}"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $template['title'] }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $template['description'] }}</p>
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            @foreach($template['badges'] as $badge)
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $badge }}</span>
                                            @endforeach
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @foreach(['en' => 'EN', 'ar' => 'AR', 'ku' => 'KU'] as $locale => $label)
                                                <a href="{{ route('admin.email.preview', ['template' => $template['key'], 'locale' => $locale]) }}" target="_blank" rel="noopener"
                                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                                    <i class="fas fa-up-right-from-square text-[10px] text-slate-400"></i>
                                                    {{ $label }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
                    <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Mail Configuration') }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Sensitive values are masked and must be changed from environment configuration.') }}</p>
                    </div>
                    <div class="grid gap-3 p-6 sm:grid-cols-2">
                        @foreach($summary as $label => $value)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __(str_replace('_', ' ', $label)) }}</p>
                                <p class="mt-2 break-words text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $value !== '' ? $value : '-' }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
