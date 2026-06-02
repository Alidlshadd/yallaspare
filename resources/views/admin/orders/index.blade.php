<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-slate-100">{{ __('Orders Management') }}</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Track orders, update status, and review customer or dealer activity.') }}</p>
            </div>
            <a href="{{ route('admin.orders.export-excel', array_filter([
                    'from' => request('from'),
                    'to' => request('to'),
                    'status' => request('status'),
                ], fn ($v) => $v !== null && $v !== '')) }}"
               class="inline-flex items-center gap-2 self-start rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 md:self-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                </svg>
                {{ __('Export Excel (.xlsx)') }}
            </a>
        </div>
    </x-slot>

    <style>
        .orders-page {
            /* ─── tokens ─── */
            --surface-base: #131a2e;
            --surface-raised: #1c2438;
            --surface-elevated: #1f283f;
            --surface-input: #131a2e;
            --border-default: #2a3553;
            --border-row: #232b42;
            --border-checkbox: #475569;
            --text-primary: #f8fafc;
            --text-body: #e6ecf5;
            --text-secondary: #cbd5e1;
            --text-muted: #8a95b0;
            --text-faint: #6b7794;
            --text-disabled: #525f7d;
            --accent-from: #0891b2;
            --accent-to: #0e7490;
            --accent-glow: rgba(8,145,178,0.35);

            background: var(--surface-base);
            color: var(--text-body);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            min-height: 100vh;
        }
        .orders-page * { box-sizing: border-box; }
    </style>

    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
    @endphp

    <div class="orders-page py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('Total') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($stats['total'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-900/50 dark:bg-amber-900/20">
                    <p class="text-xs uppercase text-amber-700 dark:text-amber-300">{{ __('Pending') }}</p>
                    <p class="mt-2 text-2xl font-bold text-amber-800 dark:text-amber-200">{{ number_format($stats['pending'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm dark:border-blue-900/50 dark:bg-blue-900/20">
                    <p class="text-xs uppercase text-blue-700 dark:text-blue-300">{{ __('Processing') }}</p>
                    <p class="mt-2 text-2xl font-bold text-blue-800 dark:text-blue-200">{{ number_format($stats['processing'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm dark:border-indigo-900/50 dark:bg-indigo-900/20">
                    <p class="text-xs uppercase text-indigo-700 dark:text-indigo-300">{{ __('Shipped') }}</p>
                    <p class="mt-2 text-2xl font-bold text-indigo-800 dark:text-indigo-200">{{ number_format($stats['shipped'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-900/20">
                    <p class="text-xs uppercase text-emerald-700 dark:text-emerald-300">{{ __('Delivered') }}</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ number_format($stats['delivered'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm dark:border-rose-900/50 dark:bg-rose-900/20">
                    <p class="text-xs uppercase text-rose-700 dark:text-rose-300">{{ __('Cancelled') }}</p>
                    <p class="mt-2 text-2xl font-bold text-rose-800 dark:text-rose-200">{{ number_format($stats['cancelled'] ?? 0) }}</p>
                </div>
            </div>

            @php
                $currentAttention = $attention ?? '';
                $attentionChips = [
                    ''                       => __('All'),
                    'today_pending'          => __('Today pending orders'),
                    'needs_shipping'         => __('Needs shipping'),
                    'cancellation_requests'  => __('Cancellation requests'),
                    'open_returns'           => __('Return requests'),
                ];
            @endphp
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach($attentionChips as $value => $label)
                    @php $isActive = $currentAttention === $value; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['attention' => $value === '' ? null : $value, 'page' => null]) }}"
                       class="rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $isActive
                            ? 'border-blue-600 bg-blue-600 text-white shadow-sm'
                            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                 x-data="{
                    selected: [],
                    allIds: @js($orders->pluck('id')->all()),
                    toggleAll(e) { this.selected = e.target.checked ? [...this.allIds] : []; },
                    allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
                 }">
                <div x-show="selected.length > 0" x-cloak
                     class="flex flex-wrap items-center gap-3 border-b border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-900/50 dark:bg-blue-900/20">
                    <span class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                        <span x-text="selected.length"></span> {{ __('selected') }}
                    </span>
                    <form method="POST" action="{{ route('admin.orders.bulk-status') }}"
                          @submit="if (!confirm('{{ __('Apply this status change to the selected orders?') }}')) $event.preventDefault()"
                          class="ml-auto flex flex-wrap items-center gap-2">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="order_ids[]" :value="id">
                        </template>
                        <select name="status" required class="rounded-md border-slate-300 bg-white py-1.5 text-xs text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <option value="">{{ __('Set status…') }}</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}">{{ \App\Models\Order::statusMeta((string) $status)['label'] }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                            {{ __('Apply') }}
                        </button>
                        <button type="button" @click="selected = []" class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            {{ __('Clear') }}
                        </button>
                    </form>
                </div>
                <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                    <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @if($currentAttention !== '')
                            <input type="hidden" name="attention" value="{{ $currentAttention }}">
                        @endif
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="{{ __('Search order #, city, phone, user...') }}"
                            class="rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 sm:col-span-2 xl:col-span-3"
                        >
                        <select name="status" class="rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <option value="">{{ __('All Statuses') }}</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>
                                    {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <select name="association" class="rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <option value="">{{ __('All Users') }}</option>
                            <option value="user" @selected(($association ?? '') === 'user')>{{ __('Retail Users') }}</option>
                            <option value="dealer" @selected(($association ?? '') === 'dealer')>{{ __('Dealers') }}</option>
                        </select>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex flex-col text-xs font-medium text-slate-600 dark:text-slate-400">
                                {{ __('From') }}
                                <input type="date" name="from" value="{{ request('from') }}"
                                       class="mt-1 rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </label>
                            <label class="flex flex-col text-xs font-medium text-slate-600 dark:text-slate-400">
                                {{ __('To') }}
                                <input type="date" name="to" value="{{ request('to') }}"
                                       class="mt-1 rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </label>
                        </div>
                        <div class="flex gap-2 sm:col-span-2 xl:col-span-3">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                {{ __('Apply Filters') }}
                            </button>
                            @if(request()->hasAny(['search', 'status', 'association', 'from', 'to']))
                                <a href="{{ route('admin.orders.index', $currentAttention !== '' ? ['attention' => $currentAttention] : []) }}"
                                   class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th class="w-10 px-4 py-3">
                                    <input type="checkbox"
                                           class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-900"
                                           @change="toggleAll($event)"
                                           :checked="allSelected()"
                                           aria-label="{{ __('Select all') }}">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Order') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('User / Dealer') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Items') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Total') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Payment') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            @forelse($orders as $order)
                                @php
                                    $isDealer = $order->user && $order->user->role === \App\Models\User::ROLE_DEALER;
                                    $statusMeta = \App\Models\Order::statusMeta((string) $order->status);
                                    $paymentMeta = \App\Models\Order::paymentStatusMeta((string) $order->payment_status);
                                    $allowedTransitions = $transitionOptions[$order->id] ?? [$order->status];
                                @endphp
                                <tr :class="selected.includes({{ $order->id }}) ? 'bg-blue-50/40 dark:bg-blue-900/10' : ''">
                                    <td class="w-10 px-4 py-4">
                                        <input type="checkbox"
                                               class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-900"
                                               value="{{ $order->id }}"
                                               x-model.number="selected"
                                               aria-label="{{ __('Select order #:order', ['order' => $order->order_number]) }}">
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="max-w-[13rem] break-words text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $order->order_number }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">ID #{{ $order->id }}</p>
                                        @if($order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELLED)
                                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                {{ __('Cancellation Requested') }}
                                            </span>
                                        @endif
                                        @if(($order->open_returns_count ?? 0) > 0)
                                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-orange-800 dark:bg-orange-900/30 dark:text-orange-300">
                                                {{ __('Return requests') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $order->user?->name ?? __('Guest customer') }}</p>
                                        <p class="max-w-[12rem] break-words text-xs text-slate-500 dark:text-slate-400">{{ $order->user?->email ?? '-' }}</p>
                                        @if($order->user)
                                            <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $isDealer ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $isDealer ? __('Dealer') : __('User') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">{{ $order->items_count }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float) $order->total_amount, $currencyDecimals) }} {{ $currencyLabel }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $paymentMeta['class'] }}">
                                            {{ $paymentMeta['label'] }}
                                        </span>
                                        @if($order->payment_method)
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $order->payment_method }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">
                                            {{ $statusMeta['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="text-sm text-slate-700 dark:text-slate-300">{{ $order->created_at?->format('M d, Y') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $order->created_at?->format('h:i A') }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="whitespace-nowrap rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                                {{ __('View') }}
                                            </a>
                                            <x-invoice-language-links :order="$order" route-name="admin.orders.invoice" mode="inline" size="xs" />
                                            <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="flex items-center gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="rounded-md border-slate-300 bg-white py-1 pl-2 pr-7 text-xs text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                    {{-- Show all real statuses from one source of truth.
                                                         Workflow restrictions stay enforced by:
                                                         1) disabled options in UI
                                                         2) Order::canTransition() in backend --}}
                                                    @foreach($statusOptions as $status)
                                                        <option
                                                            value="{{ $status }}"
                                                            @selected($order->status === $status)
                                                            @disabled(!in_array($status, $allowedTransitions, true))
                                                        >
                                                            {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" title="{{ __('Save') }}" class="whitespace-nowrap rounded-md bg-blue-600 px-2 py-1 text-xs font-semibold text-white hover:bg-blue-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" data-danger-confirm data-danger-title="{{ __('Archive Order') }}" data-danger-description="{{ __('The order will be hidden from the active order list but kept for financial history and audit review.') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="{{ __('Archive') }}" class="whitespace-nowrap rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M10 12v6m4-6v6M6 8l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 8V6a2 2 0 012-2h2a2 2 0 012 2v2"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('No orders found for the current filter.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                    <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
