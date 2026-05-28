@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-900/30 dark:text-red-200">
        {{ $errors->first() }}
    </div>
@endif

<div class="mt-4 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Mail Configuration') }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Sensitive values are masked and must be changed from environment configuration.') }}</p>
        </div>
        <div class="grid gap-3 p-6 sm:grid-cols-2">
            @foreach ($summary as $label => $value)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __(str_replace('_', ' ', $label)) }}</p>
                    <p class="mt-2 break-words text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $value !== '' ? $value : '-' }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Send Test Email') }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Use this after changing SMTP, queue, or sender settings.') }}</p>
        </div>
        <form method="POST" action="{{ route('admin.email.test') }}" class="space-y-4 p-6">
            @csrf
            <div>
                <label for="recipient" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Recipient') }}</label>
                <input id="recipient" type="email" name="recipient" value="{{ old('recipient', auth()->user()?->email) }}" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required>
                @error('recipient')
                    <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="subject" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Subject') }}</label>
                <input id="subject" type="text" name="subject" value="{{ old('subject', 'YallaSpare test email') }}" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required>
            </div>
            <div>
                <label for="mailer" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Mailer') }}</label>
                <select id="mailer" name="mailer" class="w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    @foreach ($mailers as $mailer)
                        <option value="{{ $mailer }}" @selected(old('mailer', $summary['default_mailer'] ?? '') === $mailer)>{{ $mailer }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow shadow-red-900/20 transition hover:bg-red-500">
                <i class="fas fa-paper-plane"></i>
                {{ __('Send Test Email') }}
            </button>
        </form>
    </section>
</div>

<section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30">
    <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Readiness Checks') }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('These checks help catch the most common mail delivery problems.') }}</p>
    </div>
    <div class="grid gap-4 p-6 md:grid-cols-2">
        @foreach ($checks as $check)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $check['label'] }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Current:') }} {{ $check['value'] }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' }}">
                        {{ $check['ok'] ? __('OK') : __('Action') }}
                    </span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $check['detail'] }}</p>
            </div>
        @endforeach
    </div>
</section>
