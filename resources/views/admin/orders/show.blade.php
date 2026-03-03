<x-app-layout>
    <x-slot name="header">
        @php
            $currentMeta = \App\Models\Order::statusMeta((string) $order->status);
            $statusSteps = [
                \App\Models\Order::STATUS_PENDING,
                \App\Models\Order::STATUS_PROCESSING,
                \App\Models\Order::STATUS_SHIPPED,
                \App\Models\Order::STATUS_DELIVERED,
            ];
            $currentIndex = array_search(\App\Models\Order::normalizedStatus((string) $order->status), $statusSteps, true);
            $currentIndex = $currentIndex === false ? -1 : $currentIndex;
            $currencyLabel = $systemSettings['currency_label'] ?? 'IQD';
            $currencyDecimals = $systemSettings['currency_decimals'] ?? 0;
            $subtotal = (float) $order->items->sum('subtotal');
            $total = (float) $order->total_amount;
            $adjustment = $subtotal - $total;
        @endphp
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Order {{ $order->order_number }}</h2>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $currentMeta['class'] }} dark:bg-slate-800/70 dark:text-slate-100 dark:border-slate-700">
                        {{ $currentMeta['label'] }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Placed {{ $order->created_at?->format('M d, Y h:i A') }}</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                <i class="fas fa-arrow-left"></i>
                Back to Orders
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Order Summary</h3>
                        </div>
                        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Total</p>
                                <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($total, $currencyDecimals) }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Payment</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $order->payment_method ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Items</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $order->items->count() }} items</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Order Items</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-900/80">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">SKU</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">Unit Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @forelse($order->items as $item)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-12 w-12 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800/60">
                                                        @if($item->product?->image)
                                                            <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product?->name_en ?? 'Product' }}" class="h-full w-full object-cover">
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center text-slate-400">
                                                                <i class="fas fa-image"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->product?->name_en ?? 'Deleted product' }}</p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item->product?->brand ?? '-' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">{{ $item->product?->sku ?? '-' }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-900 dark:text-slate-100">{{ $item->quantity }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format((float) $item->unit_price, $currencyDecimals) }}</td>
                                            <td class="px-6 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format((float) $item->subtotal, $currencyDecimals) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No items found for this order.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Status Management</h3>
                        <div class="mt-3">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $currentMeta['class'] }} dark:bg-slate-800/70 dark:text-slate-100 dark:border-slate-700">
                                Current: {{ $currentMeta['label'] }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mt-4 space-y-3">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                @php
                                    $statusChoices = array_values(array_unique(array_merge([(string) $order->status], $nextStatuses ?? [])));
                                @endphp
                                @foreach($statusChoices as $status)
                                    <option value="{{ $status }}" @selected($order->status === $status)>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Update Status
                            </button>
                        </form>
                        @if(empty($nextStatuses))
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">This is a terminal state. No further transitions are allowed.</p>
                        @else
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Allowed next steps: {{ implode(', ', array_map(fn ($s) => ucfirst(str_replace('_', ' ', $s)), $nextStatuses)) }}.
                            </p>
                        @endif

                        <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-800">
                            <h4 class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Timeline</h4>
                            @if(\App\Models\Order::normalizedStatus((string) $order->status) === \App\Models\Order::STATUS_CANCELLED)
                                <div class="mt-3 flex items-center gap-2 text-sm text-rose-600 dark:text-rose-400">
                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                    Order cancelled
                                </div>
                            @else
                                <div class="mt-3 space-y-3">
                                    @foreach($statusSteps as $index => $status)
                                        @php
                                            $isComplete = $index <= $currentIndex;
                                            $label = ucfirst(str_replace('_', ' ', $status));
                                        @endphp
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-2.5 w-2.5 items-center justify-center rounded-full {{ $isComplete ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700' }}"></span>
                                            <span class="text-sm {{ $isComplete ? 'text-slate-900 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400' }}">{{ $label }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Totals</h3>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Subtotal</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($subtotal, $currencyDecimals) }}</span>
                        </div>
                        @if(abs($adjustment) > 0.01)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Adjustments</span>
                                <span class="font-medium text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($adjustment * -1, $currencyDecimals) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base dark:border-slate-800">
                            <span class="font-semibold text-slate-900 dark:text-slate-100">Total</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($total, $currencyDecimals) }}</span>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Customer or Dealer</h3>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Name</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $order->user?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Email</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->user?->email ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Phone</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->user?->phone ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Association</p>
                            @if($order->user && $order->user->role === \App\Models\User::ROLE_DEALER)
                                <p class="inline-flex rounded-full bg-violet-100 px-2 py-1 text-xs font-semibold text-violet-700 dark:bg-slate-800/70 dark:text-slate-100">Dealer</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Status: {{ ucfirst($order->user->dealer_status ?? 'inactive') }} | Discount: {{ number_format((float) ($order->user->dealer_discount ?? 0), 2) }}%</p>
                            @else
                                <p class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800/70 dark:text-slate-100">User</p>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Delivery</h3>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">City</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->delivery_city ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Address</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->delivery_address ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Phone</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->delivery_phone ?? '-' }}</p>
                        </div>
                        @if(!empty($order->notes))
                            <div>
                                <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Notes</p>
                                <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->notes }}</p>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
