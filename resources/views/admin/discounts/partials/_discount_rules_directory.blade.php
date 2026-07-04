        <section class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.99),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
            <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.12),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.95),rgba(248,250,252,0.92))] px-6 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Rule Directory') }}</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Saved Discount Rules') }}</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ number_format($discountRows->count()) }} rules
                        </span>
                        @if ($isEditingDiscount)
                            <a href="{{ route('admin.discounts.rules') }}#discount-rule-form" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('New Rule') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if ($discountRows->isEmpty())
                    <div class="rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900/70">
                        <p class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('No saved discount rules yet') }}</p>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Create the first rule below, then it will appear here with edit and delete actions.') }}</p>
                    </div>
                @else
                    <div class="overflow-hidden rounded-[1.6rem] border border-slate-200/90 bg-white dark:border-slate-700 dark:bg-slate-950/80">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50/90 dark:bg-slate-900/90">
                                    <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                        <th class="px-5 py-4">{{ __('Rule') }}</th>
                                        <th class="px-5 py-4">{{ __('Scope') }}</th>
                                        <th class="px-5 py-4">{{ __('Value') }}</th>
                                        <th class="px-5 py-4">{{ __('Window') }}</th>
                                        <th class="px-5 py-4">{{ __('Usage') }}</th>
                                        <th class="px-5 py-4 text-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                @foreach ($discountRows as $row)
                                    <tbody x-data="toggle" class="divide-y divide-slate-200 dark:divide-slate-800">
                                            <tr class="align-top hover:bg-slate-50/70 dark:hover:bg-slate-900/60">
                                                <td class="px-5 py-4">
                                                    <div class="space-y-2">
                                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</div>
                                                        <div>
                                                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $row['statusClass'] }}">
                                                                {{ $row['statusLabel'] }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['scopeLabel'] }}</div>
                                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $row['targetLabel'] }}</div>
                                                </td>
                                                <td class="px-5 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['valueLabel'] }}</td>
                                                <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $row['windowLabel'] }}</td>
                                                <td class="px-5 py-4">
                                                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($row['usedCount']) }}</div>
                                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Total redemptions') }}</div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="flex flex-wrap justify-end gap-2">
                                                        <button type="button" @click="toggle()" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                                            <span x-text="open ? @js(__('Hide Details')) : @js(__('Details'))"></span>
                                                        </button>
                                                        <form action="{{ route('admin.discounts.update-rule-status', $row['id']) }}" method="POST">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="is_active" value="{{ $row['isActive'] ? 0 : 1 }}">
                                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $row['isActive'] ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300 dark:hover:bg-amber-950/30' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300 dark:hover:bg-emerald-950/30' }}">
                                                                {{ $row['isActive'] ? __('Deactivate') : __('Activate') }}
                                                            </button>
                                                        </form>
                                                        <a href="{{ $row['editUrl'] }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                                            {{ __('Edit') }}
                                                        </a>
                                                        <form action="{{ route('admin.discounts.destroy-rule', $row['id']) }}" method="POST" data-danger-confirm data-danger-title="{{ __('Delete Discount Rule') }}" data-danger-description="{{ __('This will permanently delete the selected discount rule.') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                                                {{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr x-show="open" x-cloak class="bg-slate-50/70 dark:bg-slate-900/50">
                                                <td colspan="6" class="px-5 pb-5 pt-0">
                                                    <div class="rounded-[1.5rem] border border-slate-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-950/90">
                                                        <div class="grid gap-4 xl:grid-cols-[1.45fr_1fr]">
                                                            <div class="space-y-4">
                                                                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                                                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Status') }}</p>
                                                                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['statusLabel'] }}</p>
                                                                    </div>
                                                                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Minimum Subtotal') }}</p>
                                                                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['minimumSubtotalLabel'] }}</p>
                                                                    </div>
                                                                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Usage Limit') }}</p>
                                                                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['usageLimitLabel'] }}</p>
                                                                    </div>
                                                                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Updated') }}</p>
                                                                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['updatedAtLabel'] }}</p>
                                                                    </div>
                                                                </div>
                                                                <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Target Details') }}</p>
                                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                                        @foreach ($row['targetPreview'] as $target)
                                                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $target }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="space-y-4">
                                                                <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Schedule Window') }}</p>
                                                                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['windowLabel'] }}</p>
                                                                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('Created') }} {{ $row['createdAtLabel'] }}</p>
                                                                </div>
                                                                <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Quick Actions') }}</p>
                                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                                        <form action="{{ route('admin.discounts.update-rule-status', $row['id']) }}" method="POST">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <input type="hidden" name="is_active" value="{{ $row['isActive'] ? 0 : 1 }}">
                                                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $row['isActive'] ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300 dark:hover:bg-amber-950/30' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300 dark:hover:bg-emerald-950/30' }}">
                                                                                {{ $row['isActive'] ? __('Deactivate Rule') : __('Activate Rule') }}
                                                                            </button>
                                                                        </form>
                                                                        <a href="{{ $row['editUrl'] }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                                                            {{ __('Edit In Builder') }}
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                    </tbody>
                                @endforeach
                            </table>
                        </div>
                    </div>
                @endif
