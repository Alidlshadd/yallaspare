@extends('layouts.user')

@push('head')
    <style>
        .order-review-rating-stars {
            display: inline-flex;
            flex-direction: row-reverse;
            gap: 0.25rem;
        }

        .order-review-rating-stars input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .order-review-rating-stars label {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.875rem;
            background: rgb(255 255 255);
            cursor: pointer;
            color: rgb(203 213 225);
            line-height: 1;
            transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, transform 150ms ease;
        }

        .dark .order-review-rating-stars label {
            border-color: rgb(51 65 85);
            background: rgb(15 23 42);
        }

        .order-review-rating-stars label.is-active,
        .order-review-rating-stars label.is-hovered {
            border-color: rgb(251 191 36);
            background: rgb(255 251 235);
            color: rgb(245 158 11);
        }

        .dark .order-review-rating-stars label.is-active,
        .dark .order-review-rating-stars label.is-hovered {
            border-color: rgb(217 119 6);
            background: rgba(120, 53, 15, 0.35);
            color: rgb(251 191 36);
        }

        .order-review-rating-stars label:hover {
            transform: translateY(-1px) scale(1.03);
        }

        .order-review-rating-stars input:focus-visible + label {
            outline: 2px solid rgb(245 158 11);
            outline-offset: 3px;
        }
    </style>
@endpush

@section('content')
    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
        $normalizedStatus = \App\Models\Order::normalizedStatus((string) $order->status);
        $statusMeta = \App\Models\Order::statusMeta($normalizedStatus);
        $paymentMeta = \App\Models\Order::paymentStatusMeta((string) ($order->payment_status ?? 'pending'));
        $itemCount = (int) $order->items->sum('quantity');
        $subtotal = (float) ($order->subtotal_amount ?: $order->items->sum('subtotal'));
        $shippingFee = (float) $order->shipping_fee;
        $discountAmount = (float) (data_get($order, 'discount_amount') ?? 0);
        $totalAmount = (float) ($order->grand_total ?: $order->total_amount ?: ($subtotal + $shippingFee - $discountAmount));
        $hasShipping = abs($shippingFee) > 0.0001;
        $hasDiscount = abs($discountAmount) > 0.0001;
        $statusFlow = ['pending', 'processing', 'shipped', 'delivered'];
        $currentStatusIndex = array_search((string) $order->status, $statusFlow, true);
        $history = $order->statusHistory->sortBy('created_at')->values();
        $customerName = trim((string) ($order->user?->name ?? auth()->user()?->name ?? ''));
        $deliveryPhone = trim((string) ($order->delivery_phone ?? ''));
        $deliveryCity = trim((string) ($order->delivery_city ?? ''));
        $deliveryAddress = trim((string) ($order->delivery_address ?? ''));
        $orderNotes = trim((string) ($order->notes ?? ''));
        $isDeliveredOrder = $normalizedStatus === \App\Models\Order::STATUS_DELIVERED;
        $canCancelDirectly = $normalizedStatus === \App\Models\Order::STATUS_PENDING;
        $canRequestCancellation = $normalizedStatus === \App\Models\Order::STATUS_PROCESSING;
        $userReviews = $userReviews ?? collect();
        $reviewableItems = $order->items
            ->filter(fn ($item) => $item->product && $item->product->is_active)
            ->unique('product_id')
            ->values();
    @endphp

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        @if (session('review_status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('review_status') }}
            </div>
        @endif

        @if (session('review_error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('review_error') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Order Detail') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-[-0.02em] text-slate-900 dark:text-slate-100">#{{ $order->order_number ?: $order->id }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">
                        {{ $statusMeta['label'] }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ optional($order->created_at)->format('M d, Y H:i') ?: __('Not provided') }}</span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('account.orders.reorder', $order) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#10105c]">
                        {{ __('Reorder') }}
                    </button>
                </form>
                <x-invoice-language-links :order="$order" route-name="account.orders.invoice" />
                <a href="{{ route('account.orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('Back to Orders') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Order Summary') }}</h2>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Order Reference') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">#{{ $order->order_number ?: $order->id }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Status') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $statusMeta['label'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Total Amount') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($totalAmount, $currencyDecimals) }} {{ $currencyLabel }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Payment Method') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __(ucfirst(str_replace('_', ' ', (string) ($order->payment_method ?: 'Not provided')))) }}</p>
                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $paymentMeta['class'] }}">
                            {{ $paymentMeta['label'] }}
                        </span>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Created') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ optional($order->created_at)->format('M d, Y H:i') ?: __('Not provided') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Last Updated') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ optional($order->updated_at)->format('M d, Y H:i') ?: __('Not provided') }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Delivery Information') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Customer') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900 dark:text-slate-100">{{ $customerName !== '' ? $customerName : __('Not provided') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Phone') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900 dark:text-slate-100">{{ $deliveryPhone !== '' ? $deliveryPhone : __('Not provided') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Delivery City') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900 dark:text-slate-100">{{ $deliveryCity !== '' ? $deliveryCity : __('Not provided') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Full Address') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900 dark:text-slate-100">{{ $deliveryAddress !== '' ? $deliveryAddress : __('Not provided') }}</dd>
                    </div>
                </dl>
                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Delivery Notes') }}</p>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $orderNotes !== '' ? $orderNotes : __('Not provided') }}</p>
                </div>
            </section>
        </div>

        @if ($canCancelDirectly || $canRequestCancellation || $order->cancellation_requested_at)
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $canCancelDirectly ? __('Cancel Order') : __('Cancellation Request') }}</h2>
                @if ($order->cancellation_requested_at)
                    <p class="mt-3 rounded-2xl border {{ $normalizedStatus === \App\Models\Order::STATUS_CANCELLED ? 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300' : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300' }} px-4 py-3 text-sm">
                        @if ($normalizedStatus === \App\Models\Order::STATUS_CANCELLED)
                            {{ __('Order cancelled on') }} {{ $order->cancellation_requested_at->format('M d, Y H:i') }}.
                        @else
                            {{ __('Cancellation requested on') }} {{ $order->cancellation_requested_at->format('M d, Y H:i') }}.
                        @endif
                    </p>
                    @if ($order->cancellation_reason)
                        <p class="mt-3 text-sm text-slate-700 dark:text-slate-300">{{ $order->cancellation_reason }}</p>
                    @endif
                @elseif ($canCancelDirectly)
                    <p class="mt-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                        {{ __('This order is still pending, so you can cancel it immediately without admin approval.') }}
                    </p>
                    <form method="POST" action="{{ route('account.orders.cancellation-request', $order) }}" class="mt-4 space-y-3" data-direct-cancel-form>
                        @csrf
                        <textarea name="reason" rows="3" maxlength="1000" placeholder="{{ __('Reason is optional') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white">{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="text-sm font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                            {{ __('Cancel Order Now') }}
                        </button>
                    </form>

                    <div data-direct-cancel-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm">
                        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm.75 4.75a.75.75 0 0 0-1.5 0v4.1a.75.75 0 0 0 1.5 0v-4.1ZM10 14.25a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Cancel this order?') }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                        {{ __('Order #:order is still pending. If you continue, it will be cancelled immediately and the items will return to stock.', ['order' => $order->order_number ?: $order->id]) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <button type="button" data-direct-cancel-close class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                    {{ __('Keep Order') }}
                                </button>
                                <button type="button" data-direct-cancel-confirm class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                                    {{ __('Yes, Cancel Order') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('account.orders.cancellation-request', $order) }}" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="reason" rows="3" required maxlength="1000" placeholder="{{ __('Tell us why you want to cancel this order') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white">{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="text-sm font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                            {{ __('Request Cancellation') }}
                        </button>
                    </form>
                @endif
            </section>
        @endif

        @if ($isDeliveredOrder)
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Return / Exchange / Refund') }}</h2>
                @if($order->returnRequests->isNotEmpty())
                    <div class="mt-4 space-y-3">
                        @foreach($order->returnRequests as $returnRequest)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-semibold capitalize text-slate-900 dark:text-slate-100">{{ __(ucfirst(str_replace('_', ' ', (string) $returnRequest->type))) }}</span>
                                    <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold capitalize text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ __(ucfirst(str_replace('_', ' ', (string) $returnRequest->status))) }}</span>
                                </div>
                                <p class="mt-2 text-slate-700 dark:text-slate-300">{{ $returnRequest->reason }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('account.orders.return-request', $order) }}" class="mt-4 space-y-3">
                    @csrf
                    <select name="type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        <option value="return">{{ __('Return') }}</option>
                        <option value="exchange">{{ __('Exchange') }}</option>
                        <option value="refund">{{ __('Refund') }}</option>
                    </select>
                    <textarea name="reason" rows="3" required maxlength="1500" placeholder="{{ __('Describe the issue and preferred resolution') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-950 dark:text-white">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-sm font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#10105c]">
                        {{ __('Submit Request') }}
                    </button>
                </form>
            </section>
        @else
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Return / Exchange / Refund') }}</h2>
                <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Available after delivery') }}</p>
                    <p class="mt-1">{{ __('Return, exchange, and refund requests can be submitted once this order is marked as delivered.') }}</p>
                </div>
            </section>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Ordered Items') }}</h2>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __(':count items', ['count' => $itemCount]) }}</span>
            </div>

            @if ($order->items->isEmpty())
                <p class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">
                    {{ __('No items found for this order.') }}
                </p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-300">{{ __('Product') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-300">{{ __('SKU') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-300">{{ __('Qty') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-300">{{ __('Unit Price') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-300">{{ __('Line Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($order->items as $item)
                                @php
                                    $itemProduct = $item->product;
                                    $itemName = (string) ($itemProduct?->name ?: __('Product unavailable'));
                                    $itemSku = trim((string) ($itemProduct?->sku ?? ''));
                                    $itemImage = $itemProduct?->image
                                        ? asset('storage/' . ltrim((string) $itemProduct->image, '/'))
                                        : null;
                                @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950">
                                                @if ($itemImage)
                                                    <img src="{{ $itemImage }}" alt="{{ $itemName }}" class="h-full w-full object-contain">
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
                                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16 9 11l4 4 3-3 4 4" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $itemName }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">{{ $itemSku !== '' ? $itemSku : __('Not provided') }}</td>
                                    <td class="px-4 py-4 text-sm font-medium text-slate-900 dark:text-slate-100">{{ (int) $item->quantity }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">{{ number_format((float) $item->unit_price, $currencyDecimals) }} {{ $currencyLabel }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float) $item->subtotal, $currencyDecimals) }} {{ $currencyLabel }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($isDeliveredOrder && $reviewableItems->isNotEmpty())
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Rate Ordered Items') }}</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Leave one review for each delivered product in this order.') }}</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                        {{ __('Delivered') }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach ($reviewableItems as $item)
                        @php
                            $itemProduct = $item->product;
                            $itemName = (string) ($itemProduct?->name ?: __('Product unavailable'));
                            $itemSku = trim((string) ($itemProduct?->sku ?? ''));
                            $itemImage = $itemProduct?->image
                                ? asset('storage/' . ltrim((string) $itemProduct->image, '/'))
                                : null;
                            $existingReview = $userReviews->get($itemProduct->id);
                            $isReviewAttempt = (int) old('review_product_id') === (int) $itemProduct->id;
                            $selectedRating = $isReviewAttempt ? (int) old('rating', 5) : 5;
                            $titleValue = $isReviewAttempt ? old('title') : '';
                            $commentValue = $isReviewAttempt ? old('comment') : '';
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-start gap-3">
                                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                                    @if ($itemImage)
                                        <img src="{{ $itemImage }}" alt="{{ $itemName }}" class="h-full w-full object-contain">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16 9 11l4 4 3-3 4 4" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $itemName }}</h3>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $itemSku !== '' ? __('SKU:') . ' ' . $itemSku : __('SKU not provided') }}</p>
                                </div>
                            </div>

                            @if ($existingReview)
                                <div class="mt-4 rounded-2xl border border-emerald-200 bg-white p-4 dark:border-emerald-900/40 dark:bg-slate-900">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ __('Review submitted') }}</p>
                                            <div class="mt-2 flex items-center gap-1">
                                                @for ($rating = 1; $rating <= 5; $rating++)
                                                    <svg class="h-4 w-4 {{ $rating <= (int) $existingReview->rating ? 'text-amber-500' : 'text-slate-300 dark:text-slate-700' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M9.1 2.3c.3-.9 1.5-.9 1.8 0l1.4 4.2h4.4c.9 0 1.3 1.2.6 1.8l-3.6 2.6 1.4 4.2c.3.9-.7 1.6-1.5 1.1L10 13.6l-3.6 2.6c-.8.5-1.8-.2-1.5-1.1l1.4-4.2-3.6-2.6c-.7-.6-.3-1.8.6-1.8h4.4l1.4-4.2Z" />
                                                    </svg>
                                                @endfor
                                            </div>
                                        </div>
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-900/40">{{ __('Done') }}</span>
                                    </div>
                                    @if ($existingReview->title)
                                        <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $existingReview->title }}</p>
                                    @endif
                                    @if ($existingReview->comment)
                                        <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $existingReview->comment }}</p>
                                    @endif
                                </div>
                            @else
                                <form method="POST" action="{{ route('shop.reviews.store', $itemProduct) }}" class="mt-4 space-y-4">
                                    @csrf
                                    <input type="hidden" name="review_product_id" value="{{ $itemProduct->id }}">

                                    <div>
                                        <fieldset data-order-review-rating>
                                            <legend class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Rating') }}</legend>
                                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                                <div class="order-review-rating-stars" aria-label="{{ __('Select rating') }}">
                                                    @for ($rating = 5; $rating >= 1; $rating--)
                                                        <input
                                                            id="order_review_{{ $itemProduct->id }}_rating_{{ $rating }}"
                                                            type="radio"
                                                            name="rating"
                                                            value="{{ $rating }}"
                                                            required
                                                            @checked($selectedRating === $rating)
                                                        >
                                                        <label for="order_review_{{ $itemProduct->id }}_rating_{{ $rating }}" data-rating-value="{{ $rating }}" aria-label="{{ __(':rating out of 5', ['rating' => $rating]) }}">
                                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path d="M9.1 2.3c.3-.9 1.5-.9 1.8 0l1.4 4.2h4.4c.9 0 1.3 1.2.6 1.8l-3.6 2.6 1.4 4.2c.3.9-.7 1.6-1.5 1.1L10 13.6l-3.6 2.6c-.8.5-1.8-.2-1.5-1.1l1.4-4.2-3.6-2.6c-.7-.6-.3-1.8.6-1.8h4.4l1.4-4.2Z" />
                                                            </svg>
                                                        </label>
                                                    @endfor
                                                </div>
                                                <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300" data-order-review-rating-output>
                                                    {{ $selectedRating }} / 5
                                                </span>
                                            </div>
                                        </fieldset>
                                        @if ($isReviewAttempt)
                                            @error('rating')
                                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>

                                    <div>
                                        <label for="order_review_{{ $itemProduct->id }}_title" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Title') }}</label>
                                        <input id="order_review_{{ $itemProduct->id }}_title" name="title" value="{{ $titleValue }}" maxlength="120" placeholder="{{ __('Short review title') }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-900 dark:text-white">
                                        @if ($isReviewAttempt)
                                            @error('title')
                                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>

                                    <div>
                                        <label for="order_review_{{ $itemProduct->id }}_comment" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Comment') }}</label>
                                        <textarea id="order_review_{{ $itemProduct->id }}_comment" name="comment" rows="3" maxlength="1500" placeholder="{{ __('Tell other customers about fit, quality, or delivery.') }}" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-900 dark:text-white">{{ $commentValue }}</textarea>
                                        @if ($isReviewAttempt)
                                            @error('comment')
                                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>

                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#10105c]">
                                        <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M17.8 2.2a.8.8 0 0 1 .2.8l-4.3 13.8a.8.8 0 0 1-1.4.2l-3-4.5-4.5-3a.8.8 0 0 1 .2-1.4L18 2a.8.8 0 0 1-.2.2ZM7 9l3.1 2.1L13.8 6 7 9Z" />
                                        </svg>
                                        {{ __('Submit Review') }}
                                    </button>
                                </form>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Pricing Breakdown') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">{{ number_format($subtotal, $currencyDecimals) }} {{ $currencyLabel }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600 dark:text-slate-300">{{ __('Shipping Fee') }}</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">{{ $hasShipping ? number_format($shippingFee, $currencyDecimals) . ' ' . $currencyLabel : __('Not provided') }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600 dark:text-slate-300">{{ __('Discount') }}</dt>
                        <dd class="font-medium text-slate-900 dark:text-slate-100">{{ $hasDiscount ? '- ' . number_format(abs($discountAmount), $currencyDecimals) . ' ' . $currencyLabel : __('Not provided') }}</dd>
                    </div>
                    <div class="border-t border-slate-200 pt-3 dark:border-slate-800 flex items-center justify-between">
                        <dt class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Total') }}</dt>
                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ number_format($totalAmount, $currencyDecimals) }} {{ $currencyLabel }}</dd>
                    </div>
                </dl>
            </section>

            <section class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Order Status Timeline') }}</h2>
                @if ($history->isNotEmpty())
                    <ol class="mt-4 space-y-4">
                        @foreach ($history as $entry)
                            @php
                                $entryMeta = \App\Models\Order::statusMeta((string) $entry->to_status);
                                $entryTime = $entry->created_at ?: ($entry->from_status ? $order->updated_at : $order->created_at);
                            @endphp
                            <li class="flex gap-3">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-primary"></span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $entryMeta['label'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ optional($entryTime)->format('M d, Y') }}
                                    </p>
                                    @if (!empty($entry->note))
                                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $entry->note }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <ol class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($statusFlow as $step)
                            <li class="rounded-2xl border px-3 py-2 text-sm {{ ($currentStatusIndex !== false && $loop->index <= $currentStatusIndex) ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300' }}">
                                {{ \App\Models\Order::statusMeta($step)['label'] }}
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('Current status: :status', ['status' => $statusMeta['label']]) }}</p>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce }}">
        document.addEventListener('DOMContentLoaded', () => {
            const directCancelForm = document.querySelector('[data-direct-cancel-form]');
            const directCancelModal = document.querySelector('[data-direct-cancel-modal]');
            const directCancelClose = document.querySelector('[data-direct-cancel-close]');
            const directCancelConfirm = document.querySelector('[data-direct-cancel-confirm]');
            let directCancelConfirmed = false;

            const openDirectCancelModal = () => {
                if (!directCancelModal) return;
                directCancelModal.classList.remove('hidden');
                directCancelModal.classList.add('flex');
                directCancelClose?.focus();
            };

            const closeDirectCancelModal = () => {
                if (!directCancelModal) return;
                directCancelModal.classList.add('hidden');
                directCancelModal.classList.remove('flex');
                directCancelConfirmed = false;
            };

            if (directCancelForm && directCancelModal) {
                directCancelForm.addEventListener('submit', (event) => {
                    if (directCancelConfirmed) return;

                    event.preventDefault();
                    openDirectCancelModal();
                });

                directCancelClose?.addEventListener('click', closeDirectCancelModal);
                directCancelConfirm?.addEventListener('click', () => {
                    directCancelConfirmed = true;
                    directCancelForm.submit();
                });

                directCancelModal.addEventListener('click', (event) => {
                    if (event.target === directCancelModal) {
                        closeDirectCancelModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !directCancelModal.classList.contains('hidden')) {
                        closeDirectCancelModal();
                    }
                });
            }

            document.querySelectorAll('[data-order-review-rating]').forEach((group) => {
                const output = group.querySelector('[data-order-review-rating-output]');
                const inputs = Array.from(group.querySelectorAll('input[type="radio"][name="rating"]'));
                const labels = Array.from(group.querySelectorAll('label[data-rating-value]'));

                const selectedValue = () => {
                    const selected = inputs.find((input) => input.checked);

                    return selected ? Number.parseInt(selected.value, 10) : 0;
                };

                const paintStars = (value, className = 'is-active') => {
                    labels.forEach((label) => {
                        const rating = Number.parseInt(label.dataset.ratingValue || '0', 10);
                        label.classList.toggle(className, rating > 0 && rating <= value);
                    });
                };

                const clearHover = () => {
                    labels.forEach((label) => label.classList.remove('is-hovered'));
                    const value = selectedValue();
                    if (output && value > 0) {
                        output.textContent = `${value} / 5`;
                    }
                    paintStars(value);
                };

                const syncOutput = () => {
                    const value = selectedValue();
                    if (output && value > 0) {
                        output.textContent = `${value} / 5`;
                    }
                    paintStars(value);
                };

                inputs.forEach((input) => {
                    input.addEventListener('change', syncOutput);
                });

                labels.forEach((label) => {
                    label.addEventListener('mouseenter', () => {
                        const hoverValue = Number.parseInt(label.dataset.ratingValue || '0', 10);
                        labels.forEach((star) => star.classList.remove('is-active'));
                        clearHover();
                        labels.forEach((star) => star.classList.remove('is-active'));
                        if (output && hoverValue > 0) {
                            output.textContent = `${hoverValue} / 5`;
                        }
                        paintStars(hoverValue, 'is-hovered');
                    });

                    label.addEventListener('mouseleave', clearHover);
                });

                group.addEventListener('mouseleave', clearHover);

                syncOutput();
            });
        });
    </script>
@endpush
