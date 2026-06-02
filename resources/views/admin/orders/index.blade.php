<x-app-layout>
    <x-slot name="header">
        <div class="orders-page" style="background: transparent; min-height: 0;">
            <div class="op-hdr">
                <div>
                    <h1>{{ __('Orders Management') }}</h1>
                    <p class="sub">
                        {{ __(':total total', ['total' => number_format($stats['total'] ?? 0)]) }}
                        @if(($stats['pending'] ?? 0) > 0)
                            · <b>{{ __(':n need attention', ['n' => $stats['pending']]) }}</b>
                        @endif
                    </p>
                </div>
                <a class="op-export" href="{{ route('admin.orders.export-excel', array_filter([
                        'from' => request('from'),
                        'to' => request('to'),
                        'status' => request('status'),
                    ], fn ($v) => $v !== null && $v !== '')) }}">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    {{ __('Export Excel (.xlsx)') }}
                </a>
            </div>
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
        .orders-page .op-hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 22px;
            padding: 0 0 18px;
            border-bottom: 1px solid var(--border-default);
        }
        .orders-page .op-hdr h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.025em;
        }
        .orders-page .op-hdr .sub {
            margin: 5px 0 0;
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .orders-page .op-hdr .sub b {
            color: #fcd34d;
            font-weight: 600;
        }
        .orders-page .op-export {
            background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
            color: white;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 1px 0 rgba(255,255,255,0.08) inset, 0 4px 14px -4px var(--accent-glow);
            border: 1px solid rgba(255,255,255,0.08);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .orders-page .op-export svg { width: 14px; height: 14px; }
        .orders-page .op-stats {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .orders-page .op-stats { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 540px) {
            .orders-page .op-stats { grid-template-columns: repeat(2, 1fr); }
        }
        .orders-page .op-stat {
            background: var(--surface-raised);
            border: 1px solid var(--border-default);
            border-radius: 12px;
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
        }
        .orders-page .op-stat::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--stat-a, #475569);
            opacity: 0.7;
        }
        .orders-page .op-stat .l {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-muted);
            font-weight: 600;
        }
        .orders-page .op-stat .v {
            font-size: 22px;
            font-weight: 700;
            margin-top: 6px;
            line-height: 1;
            color: var(--stat-vc, var(--text-primary));
            font-variant-numeric: tabular-nums;
            text-shadow: 0 0 12px var(--stat-glow, transparent);
        }
        .orders-page .op-stat.tot   { --stat-vc: var(--text-primary); }
        .orders-page .op-stat.warn  { --stat-a: #d97706; --stat-vc: #fcd34d; --stat-glow: rgba(252,211,77,0.18); }
        .orders-page .op-stat.idx   { --stat-a: #6366f1; --stat-vc: #c4b5fd; --stat-glow: rgba(196,181,253,0.18); }
        .orders-page .op-stat.info  { --stat-a: #0284c7; --stat-vc: #93c5fd; --stat-glow: rgba(147,197,253,0.18); }
        .orders-page .op-stat.ok    { --stat-a: #10b981; --stat-vc: #6ee7b7; --stat-glow: rgba(110,231,183,0.18); }
        .orders-page .op-stat.err   { --stat-a: #dc2626; --stat-vc: #fda4af; --stat-glow: rgba(253,164,175,0.15); }
        .orders-page .op-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .orders-page .op-chip {
            background: var(--surface-raised);
            border: 1px solid var(--border-default);
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            transition: all .15s ease;
            text-decoration: none;
        }
        .orders-page .op-chip:hover {
            border-color: #475569;
            color: var(--text-secondary);
        }
        .orders-page .op-chip.on {
            background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
            color: white;
            border-color: rgba(255,255,255,0.15);
            box-shadow: 0 4px 12px -3px var(--accent-glow);
        }
        .orders-page .op-card {
            background: var(--surface-raised);
            border: 1px solid var(--border-default);
            border-radius: 14px;
            overflow: hidden;
        }
        .orders-page .op-filter {
            padding: 14px;
            border-bottom: 1px solid var(--border-default);
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 10px;
        }
        @media (max-width: 900px) {
            .orders-page .op-filter { grid-template-columns: 1fr; }
        }
        .orders-page .op-input,
        .orders-page .op-select {
            background: var(--surface-input);
            border: 1px solid var(--border-default);
            color: var(--text-body);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            width: 100%;
        }
        .orders-page .op-input::placeholder { color: var(--text-disabled); }
        .orders-page .op-input:focus,
        .orders-page .op-select:focus { outline: 1px solid var(--accent-from); border-color: var(--accent-from); }
        .orders-page .op-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .orders-page .op-dates label {
            display: flex; flex-direction: column;
            font-size: 10px; font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .05em;
            gap: 4px;
        }
        .orders-page .op-actions {
            display: flex; gap: 8px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-default);
        }
        .orders-page .op-btn-primary {
            background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
            color: white;
            padding: 7px 14px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
        }
        .orders-page .op-btn-ghost {
            background: transparent;
            border: 1px solid var(--border-default);
            color: var(--text-muted);
            padding: 7px 14px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
        }
        .orders-page .op-btn-ghost:hover { color: var(--text-body); border-color: #475569; }
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

            <div class="op-stats">
                <div class="op-stat tot"><div class="l">{{ __('Total') }}</div><div class="v">{{ number_format($stats['total'] ?? 0) }}</div></div>
                <div class="op-stat warn"><div class="l">{{ __('Pending') }}</div><div class="v">{{ number_format($stats['pending'] ?? 0) }}</div></div>
                <div class="op-stat idx"><div class="l">{{ __('Processing') }}</div><div class="v">{{ number_format($stats['processing'] ?? 0) }}</div></div>
                <div class="op-stat info"><div class="l">{{ __('Shipped') }}</div><div class="v">{{ number_format($stats['shipped'] ?? 0) }}</div></div>
                <div class="op-stat ok"><div class="l">{{ __('Delivered') }}</div><div class="v">{{ number_format($stats['delivered'] ?? 0) }}</div></div>
                <div class="op-stat err"><div class="l">{{ __('Cancelled') }}</div><div class="v">{{ number_format($stats['cancelled'] ?? 0) }}</div></div>
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
            <div class="op-chips">
                @foreach($attentionChips as $value => $label)
                    @php $isActive = $currentAttention === $value; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['attention' => $value === '' ? null : $value, 'page' => null]) }}"
                       class="op-chip @if($isActive) on @endif">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="op-card"
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
                <div class="op-filter">
                    @if($currentAttention !== '')
                        <input type="hidden" name="attention" value="{{ $currentAttention }}" form="orders-filter-form">
                    @endif
                    <input form="orders-filter-form" class="op-input" type="text" name="search"
                           value="{{ request('search') }}"
                           placeholder="{{ __('Search order #, city, phone, user...') }}">
                    <select form="orders-filter-form" name="status" class="op-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <div class="op-dates">
                        <label>{{ __('From') }}<input form="orders-filter-form" class="op-input" type="date" name="from" value="{{ request('from') }}"></label>
                        <label>{{ __('To') }}<input form="orders-filter-form" class="op-input" type="date" name="to" value="{{ request('to') }}"></label>
                    </div>
                    <select form="orders-filter-form" name="association" class="op-select" style="grid-column: span 1;">
                        <option value="">{{ __('All Users') }}</option>
                        <option value="user" @selected(($association ?? '') === 'user')>{{ __('Retail Users') }}</option>
                        <option value="dealer" @selected(($association ?? '') === 'dealer')>{{ __('Dealers') }}</option>
                    </select>
                </div>
                <div class="op-actions">
                    <form id="orders-filter-form" method="GET" action="{{ route('admin.orders.index') }}" style="display:contents"></form>
                    <button form="orders-filter-form" type="submit" class="op-btn-primary">{{ __('Apply Filters') }}</button>
                    @if(request()->hasAny(['search', 'status', 'association', 'from', 'to']))
                        <a class="op-btn-ghost"
                           href="{{ route('admin.orders.index', $currentAttention !== '' ? ['attention' => $currentAttention] : []) }}">
                            {{ __('Clear') }}
                        </a>
                    @endif
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
