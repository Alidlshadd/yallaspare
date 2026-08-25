@extends('layouts.user')

@section('content')
    @php
        $isPaid = $payment->status === \App\Models\Payment::STATUS_PAID;
        $isFailed = in_array($payment->status, [\App\Models\Payment::STATUS_FAILED, \App\Models\Payment::STATUS_CANCELLED], true);
    @endphp

    <div class="mx-auto w-full max-w-3xl space-y-6 py-4">
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
            <p class="text-sm font-medium text-slate-500">{{ __('WAYL Payment Result') }}</p>

            @if($verificationFailed)
                <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-amber-700">{{ __('Payment verification is temporarily unavailable') }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Your order is saved and remains pending. Please check again later or contact support with your order number.') }}</p>
            @elseif($isPaid)
                <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-emerald-700">{{ __('Payment confirmed') }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('WAYL confirmed your payment on the server. Your order is now processing.') }}</p>
            @elseif($isFailed)
                <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-rose-700">{{ __('Payment was not completed') }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('WAYL reported that this payment was cancelled or rejected.') }}</p>
            @else
                <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-sky-700">{{ __('Payment is pending') }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('WAYL has not confirmed the payment yet. Your order will remain pending until verification succeeds.') }}</p>
            @endif

            <div class="mt-6 rounded-2xl border border-slate-200/80 bg-slate-50 p-4 dark:bg-slate-950">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="text-slate-500">{{ __('Order') }}</span>
                    <span class="font-semibold text-slate-900">#{{ $order->order_number ?: $order->id }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between gap-4 text-sm">
                    <span class="text-slate-500">{{ __('Payment status') }}</span>
                    <span class="font-semibold text-slate-900">{{ __(ucfirst(str_replace('_', ' ', (string) $payment->status))) }}</span>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="{{ route('account.orders.show', $order) }}" class="inline-flex items-center justify-center rounded-2xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-hover">
                    {{ __('View Order') }}
                </a>
                <a href="{{ route('shop.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                    {{ __('Continue Shopping') }}
                </a>
            </div>
        </section>
    </div>
@endsection
