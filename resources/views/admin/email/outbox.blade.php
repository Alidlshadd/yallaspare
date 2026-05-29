<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('Email Outbox') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Recent mail activity. Recipients are hashed; only the domain is shown.') }}</p>
            </div>
            <a href="{{ route('admin.email.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                <i class="fas fa-arrow-left"></i>
                {{ __('Back to Email Center') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Total (24h)') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format($stats['total_24h']) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-900/30">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-200">{{ __('Sent (24h)') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900 dark:text-emerald-100">{{ number_format($stats['sent_24h']) }}</p>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm dark:border-red-900/60 dark:bg-red-900/30">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-red-700 dark:text-red-200">{{ __('Failed (24h)') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-red-900 dark:text-red-100">{{ number_format($stats['failed_24h']) }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.email.outbox') }}"
                  class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <label class="flex flex-col text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                    {{ __('Status') }}
                    <select name="status" class="mt-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="">{{ __('Any') }}</option>
                        <option value="sent" @selected($status === 'sent')>{{ __('Sent') }}</option>
                        <option value="failed" @selected($status === 'failed')>{{ __('Failed') }}</option>
                    </select>
                </label>
                <label class="flex flex-col text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                    {{ __('Recipient domain') }}
                    <input type="text" name="domain" value="{{ $domain }}" placeholder="example.com"
                           class="mt-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                </label>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-500">
                    {{ __('Filter') }}
                </button>
            </form>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('When') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3">{{ __('Domain') }}</th>
                            <th class="px-4 py-3">{{ __('Subject') }}</th>
                            <th class="px-4 py-3">{{ __('Mailer') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($logs as $log)
                            <tr class="text-slate-700 dark:text-slate-200">
                                <td class="px-4 py-3 font-mono text-xs">
                                    {{ optional($log->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->status === 'sent')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-200">
                                            <i class="fas fa-check"></i> {{ __('Sent') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/60 dark:text-red-200" title="{{ $log->error_message }}">
                                            <i class="fas fa-times"></i> {{ __('Failed') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $log->recipient_domain ?: '-' }}</td>
                                <td class="px-4 py-3 max-w-md truncate" title="{{ $log->subject }}">{{ $log->subject ?: '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $log->mailer ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No mail activity recorded yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                    {{ $logs->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
