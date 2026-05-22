<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-slate-100">{{ __('Orders Management') }}</h2>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Track orders, update status, and review customer or dealer activity.') }}</p>
        </div>
    </x-slot>
    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
    @endphp

    <div class="py-8">
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

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                    <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="{{ __('Search order #, city, phone, user...') }}"
                            class="md:col-span-2 rounded-lg border-slate-300 bg-white text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
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
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            {{ __('Apply Filters') }}
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Order') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('User / Dealer') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Items') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Total') }}</th>
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
                                    $allowedTransitions = $transitionOptions[$order->id] ?? [$order->status];
                                @endphp
                                <tr>
                                    <td class="px-4 py-4">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $order->order_number }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">ID #{{ $order->id }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $order->user?->name ?? __('Guest customer') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $order->user?->email ?? '-' }}</p>
                                        @if($order->user)
                                            <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $isDealer ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $isDealer ? __('Dealer') : __('User') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">{{ $order->items_count }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float) $order->total_amount, $currencyDecimals) }} {{ $currencyLabel }}</td>
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
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                                {{ __('View') }}
                                            </a>
                                            <x-invoice-language-links :order="$order" route-name="admin.orders.invoice" mode="inline" size="xs" />
                                            <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="flex items-center gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="rounded-md border-slate-300 bg-white py-1 text-xs text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
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
                                                <button type="submit" class="rounded-md bg-blue-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                                    {{ __('Save') }}
                                                </button>
                                            </form>
                                            @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" data-danger-confirm data-danger-title="{{ __('Archive Order') }}" data-danger-description="{{ __('The order will be hidden from the active order list but kept for financial history and audit review.') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-md bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                                                        {{ __('Archive') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
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
