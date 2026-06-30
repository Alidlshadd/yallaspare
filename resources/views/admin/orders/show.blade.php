<x-app-layout>
    <x-slot name="header">
        @php
            $currentMeta = \App\Models\Order::statusMeta((string) $order->status);
            $paymentMeta = \App\Models\Order::paymentStatusMeta((string) ($order->payment_status ?? 'pending'));
            $statusSteps = [
                \App\Models\Order::STATUS_PENDING,
                \App\Models\Order::STATUS_PROCESSING,
                \App\Models\Order::STATUS_SHIPPED,
                \App\Models\Order::STATUS_DELIVERED,
            ];
            $currentIndex = array_search(\App\Models\Order::normalizedStatus((string) $order->status), $statusSteps, true);
            $currentIndex = $currentIndex === false ? -1 : $currentIndex;
            $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
            $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
            $subtotal = (float) ($order->subtotal_amount ?: $order->items->sum('subtotal'));
            $shipping = (float) $order->shipping_fee;
            $discount = (float) $order->discount_amount;
            $total = (float) ($order->grand_total ?: $order->total_amount ?: ($subtotal + $shipping - $discount));
        @endphp
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Order') }} {{ $order->order_number }}</h2>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $currentMeta['class'] }} dark:bg-slate-800/70 dark:text-slate-100 dark:border-slate-700">
                        {{ $currentMeta['label'] }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Placed') }} {{ $order->created_at?->format('M d, Y h:i A') }}</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                <i class="fas fa-arrow-left"></i>
                {{ __('Back to Orders') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <h1 class="sr-only">{{ __('Order') }} {{ $order->order_number }}</h1>

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
                        <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Order Summary') }}</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-invoice-language-links :order="$order" route-name="admin.orders.invoice" />
                            </div>
                        </div>
                        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Total') }}</p>
                                <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($total, $currencyDecimals) }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Payment') }}</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __(ucfirst(str_replace('_', ' ', (string) ($order->payment_method ?? '-')))) }}</p>
                                <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $paymentMeta['class'] }}">
                                    {{ $paymentMeta['label'] }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Items') }}</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __(':count items', ['count' => $order->items->count()]) }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Order Items') }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-900/80">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">{{ __('Product') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">{{ __('SKU') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">{{ __('Qty') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">{{ __('Unit Price') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">{{ __('Subtotal') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @forelse($order->items as $item)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-12 w-12 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800/60">
                                                        @if($item->product?->image)
                                                            <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product?->name ?? __('Product') }}" class="h-full w-full object-cover">
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center text-slate-400">
                                                                <i class="fas fa-image"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->product?->name ?? __('Deleted product') }}</p>
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
                                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No items found for this order.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    @if($order->cancellation_requested_at)
                        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-900/50 dark:bg-amber-900/20">
                            <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-200">{{ __('Cancellation Requested') }}</h3>
                            <p class="mt-2 text-sm text-amber-800 dark:text-amber-300">
                                {{ __('Requested on') }} {{ $order->cancellation_requested_at->format('M d, Y H:i') }}
                            </p>
                            @if($order->cancellation_reason)
                                <p class="mt-3 rounded-xl bg-white/70 p-3 text-sm text-amber-900 dark:bg-slate-950/40 dark:text-amber-200">{{ $order->cancellation_reason }}</p>
                            @endif
                            <p class="mt-3 text-xs text-amber-700 dark:text-amber-300">{{ __('Use Status Management to approve by moving the order to Cancelled, or keep processing if the request is rejected.') }}</p>
                        </section>
                    @endif

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Status Management') }}</h3>
                        <div class="mt-3">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $currentMeta['class'] }} dark:bg-slate-800/70 dark:text-slate-100 dark:border-slate-700">
                                {{ __('Current: :status', ['status' => $currentMeta['label']]) }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mt-4 space-y-3" data-loading-form data-loading-button-text="Saving...">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                @php
                                    $statusChoices = array_values(array_unique(array_merge([(string) $order->status], $nextStatuses ?? [])));
                                @endphp
                                @foreach($statusChoices as $status)
                                    <option value="{{ $status }}" @selected($order->status === $status)>
                                        {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                {{ __('Update Status') }}
                            </button>
                        </form>
                        @if(empty($nextStatuses))
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('This is a terminal state. No further transitions are allowed.') }}</p>
                        @else
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('Allowed next steps: :steps.', ['steps' => implode(', ', array_map(fn ($s) => \App\Models\Order::statusMeta((string) $s)['label'], $nextStatuses))]) }}
                            </p>
                        @endif

                        <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-800">
                            <h4 class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Timeline') }}</h4>
                            @if(\App\Models\Order::normalizedStatus((string) $order->status) === \App\Models\Order::STATUS_CANCELLED)
                                <div class="mt-3 flex items-center gap-2 text-sm text-rose-600 dark:text-rose-400">
                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                    {{ __('Order cancelled') }}
                                </div>
                            @else
                                <div class="mt-3 space-y-3">
                                    @foreach($statusSteps as $index => $status)
                                        @php
                                            $isComplete = $index <= $currentIndex;
                                            $label = \App\Models\Order::statusMeta((string) $status)['label'];
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

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Payment Management') }}</h3>
                        <div class="mt-3">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $paymentMeta['class'] }}">
                                {{ __('Current: :status', ['status' => $paymentMeta['label']]) }}
                            </span>
                        </div>
                        @if($order->payments->isNotEmpty())
                            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                                @foreach($order->payments as $payment)
                                    <div class="border-b border-slate-200 px-3 py-3 text-xs last:border-b-0 dark:border-slate-800">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-semibold uppercase text-slate-700 dark:text-slate-200">{{ $payment->provider }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __(ucfirst(str_replace('_', ' ', $payment->status))) }}</span>
                                        </div>
                                        <p class="mt-1 text-slate-500 dark:text-slate-400">{{ __('Provider ID: :id', ['id' => $payment->provider_payment_id ?: '-']) }}</p>
                                        @if($payment->provider_transaction_id)
                                            <p class="mt-1 text-slate-500 dark:text-slate-400">{{ __('Transaction: :id', ['id' => $payment->provider_transaction_id]) }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.orders.update-payment', $order) }}" class="mt-4 space-y-3">
                            @csrf
                            @method('PATCH')
                            <select name="payment_status" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                @foreach(\App\Models\Order::allowedPaymentStatuses() as $paymentStatus)
                                    <option value="{{ $paymentStatus }}" @selected(\App\Models\Order::normalizedPaymentStatus((string) ($order->payment_status ?? 'pending')) === $paymentStatus)>
                                        {{ \App\Models\Order::paymentStatusMeta((string) $paymentStatus)['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <input
                                type="text"
                                name="payment_reference"
                                value="{{ old('payment_reference', $order->payment_reference) }}"
                                placeholder="{{ __('Payment reference') }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            >
                            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                                {{ __('Update Payment') }}
                            </button>
                        </form>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Internal Admin Notes') }}</h3>
                        <form method="POST" action="{{ route('admin.orders.admin-notes.store', $order) }}" class="mt-4 space-y-3">
                            @csrf
                            <textarea
                                name="note"
                                rows="3"
                                maxlength="3000"
                                required
                                placeholder="{{ __('Customer called, part checked, waiting for courier...') }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            >{{ old('note') }}</textarea>
                            @error('note')
                                <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                {{ __('Add Internal Note') }}
                            </button>
                        </form>

                        <div class="mt-5 space-y-3">
                            @forelse($order->adminNotes as $note)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                                    <p class="text-sm text-slate-800 dark:text-slate-200">{{ $note->note }}</p>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $note->user?->name ?? __('Admin') }} &middot; {{ $note->created_at?->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No internal notes yet.') }}</p>
                            @endforelse
                        </div>
                    </section>

                    @if($order->returnRequests->isNotEmpty())
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Return / Refund Requests') }}</h3>
                            <div class="mt-4 space-y-3">
                                @foreach($order->returnRequests as $returnRequest)
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-semibold capitalize text-slate-900 dark:text-slate-100">{{ __(ucfirst(str_replace('_', ' ', (string) $returnRequest->type))) }}</span>
                                            <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold capitalize text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __(ucfirst(str_replace('_', ' ', (string) $returnRequest->status))) }}</span>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $returnRequest->reason }}</p>
                                        @if($returnRequest->admin_note)
                                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Admin note:') }} {{ $returnRequest->admin_note }}</p>
                                        @endif
                                        <a href="{{ route('admin.returns.index', ['search' => $order->order_number]) }}" class="mt-3 inline-flex text-xs font-semibold text-indigo-600 hover:text-indigo-700">{{ __('Manage return') }}</a>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Totals') }}</h3>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Subtotal') }}</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($subtotal, $currencyDecimals) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Shipping') }}</span>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($shipping, $currencyDecimals) }}</span>
                        </div>
                        @if($discount > 0)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">{{ __('Discount') }}</span>
                                <span class="font-medium text-slate-900 dark:text-slate-100">- {{ $currencyLabel }} {{ number_format($discount, $currencyDecimals) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base dark:border-slate-800">
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Total') }}</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($total, $currencyDecimals) }}</span>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Customer or Dealer') }}</h3>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('Name') }}</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $order->user?->name ?? __('Guest customer') }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('Email') }}</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->user?->email ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('Phone') }}</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->user?->phone ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('Association') }}</p>
                            @if($order->user && $order->user->role === \App\Models\User::ROLE_DEALER)
                                <p class="inline-flex rounded-full bg-violet-100 px-2 py-1 text-xs font-semibold text-violet-700 dark:bg-slate-800/70 dark:text-slate-100">{{ __('Dealer') }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Status: :status', ['status' => __(ucfirst($order->user->dealer_status ?? 'inactive'))]) }} | {{ __('Discount: :percent%', ['percent' => number_format((float) ($order->user->dealer_discount ?? 0), 2)]) }}</p>
                            @else
                                <p class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800/70 dark:text-slate-100">{{ __('User') }}</p>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Delivery') }}</h3>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('City') }}</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->delivery_city ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('Address') }}</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->delivery_address ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('Phone') }}</p>
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->delivery_phone ?? '-' }}</p>
                        </div>
                        @if(!empty($order->notes))
                            <div>
                                <p class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ __('Notes') }}</p>
                                <p class="text-sm text-slate-900 dark:text-slate-100">{{ $order->notes }}</p>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
