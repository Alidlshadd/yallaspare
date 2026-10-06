<!DOCTYPE html>
<html lang="{{ $locale ?? str_replace('_', '-', app()->getLocale()) }}" dir="{{ !empty($isRtl) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoiceNumber }}</title>
    @include('partials.brand-head')
    @include('admin.orders.partials.invoice-styles')
</head>
<body class="{{ !empty($isRtl) ? 'rtl' : 'ltr' }}">
    @php
        // A phone number, a date or an amount is read left to right whatever
        // the page is written in. Without the embedding marks an Arabic or
        // Kurdish invoice prints "+964…" with the plus trailing, the date
        // back to front, "IQD 88,000" for "88,000 IQD", and a discount's
        // minus sign after the figure.
        $ltr = fn (?string $value): string => "\u{202A}".$value."\u{202C}";
        $money = fn (float $amount, string $sign = ''): string => $ltr($sign.number_format($amount).' '.$currency);
    @endphp
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if (!empty($logoPath))
                    <img src="{{ $logoPath }}" alt="{{ __('YallaSpare logo') }}" class="logo-img">
                @else
                    <div class="logo-box">YS</div>
                @endif
                <p class="company-name">{{ __('invoice.company_name') }}</p>
                <p class="company-address">{{ __('invoice.company_address') }}</p>
                <p class="company-address">support@yallaspare.com</p>
                <p class="company-address">+964 770 448 8315</p>
            </td>
            <td class="text-right" style="width: 45%;">
                <h1 class="invoice-title">{{ __('invoice.title') }}</h1>
                <p class="invoice-meta"><span class="meta-label">{{ __('invoice.invoice_number') }}</span> <span class="value">{{ $invoiceNumber }}</span></p>
                <p class="invoice-meta"><span class="meta-label">{{ __('invoice.order_date') }}</span> <span class="value">{{ $ltr(optional($order->created_at)->format('Y-m-d H:i')) }}</span></p>
            </td>
        </tr>
    </table>

    <table class="cards-table">
        <tr>
            <td style="width: 49%;">
                {{-- A nested table, not a bordered div: mPDF drops border, background
                     and padding on a block element inside a table cell. --}}
                <table class="info-card">
                    <tr><td class="card-title">{{ __('invoice.customer_information') }}</td></tr>
                    <tr><td class="info-card-body">
                        <div class="label">{{ __('invoice.customer_name') }}</div>
                        <div class="value">{{ $order->user?->name ?? __('invoice.guest_customer') }}</div>
                        @if ($order->user?->email)
                            <div class="muted">{{ $order->user->email }}</div>
                        @endif
                        @if ($order->user?->phone)
                            <div class="muted">{{ __('invoice.phone') }}: {{ $ltr($order->user->phone) }}</div>
                        @endif
                    </td></tr>
                </table>
            </td>
            <td class="card-spacer" style="width: 2%;"></td>
            <td style="width: 49%;">
                <table class="info-card">
                    <tr><td class="card-title">{{ __('invoice.shipping_information') }}</td></tr>
                    <tr><td class="info-card-body">
                        <div class="label">{{ __('invoice.ship_to') }}</div>
                        <div class="value">{{ $order->user?->name ?? __('invoice.guest_customer') }}</div>
                        <div>{{ $order->delivery_address }}</div>
                        <div>{{ $order->delivery_city }}@if ($order->delivery_governorate), {{ $order->delivery_governorate }}@endif</div>
                        <div class="muted">{{ __('invoice.phone') }}: {{ $ltr($order->delivery_phone) }}</div>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('invoice.product_name') }}</th>
                <th style="width: 105px;">{{ __('invoice.sku') }}</th>
                <th class="text-center" style="width: 70px;">{{ __('invoice.quantity') }}</th>
                <th class="text-right" style="width: 105px;">{{ __('invoice.unit_price') }}</th>
                <th class="text-right" style="width: 110px;">{{ __('invoice.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <div class="product-name">
                            {{ $item->product?->localizedName($locale ?? app()->getLocale()) ?: $item->soldName() }}
                        </div>
                        @if ($item->product?->brand)
                            <div class="sku"><span>{{ __('invoice.brand') }}</span>: {{ $item->product->brand }}</div>
                        @endif
                    </td>
                    <td class="sku">{{ $item->soldSku() ?: __('invoice.not_available') }}</td>
                    <td class="text-center">{{ number_format((int) $item->quantity) }}</td>
                    <td class="text-right">{{ $money((float) $item->unit_price) }}</td>
                    <td class="text-right">{{ $money((float) $item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="summary-label">{{ __('invoice.subtotal') }}</td>
            <td class="text-right">{{ $money((float) $subtotal) }}</td>
        </tr>
        <tr>
            <td class="summary-label">{{ __('invoice.shipping') }}</td>
            <td class="text-right">{{ $money((float) $shipping) }}</td>
        </tr>
        @if (!empty($discount) && (float) $discount > 0)
            <tr>
                <td class="summary-label">{{ __('invoice.discount') }}</td>
                <td class="text-right">{{ $money((float) $discount, '- ') }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>{{ __('invoice.grand_total') }}</td>
            <td class="text-right">{{ $money((float) $grandTotal) }}</td>
        </tr>
    </table>

    <div class="print-note">
        <p>
            <strong class="navy">{{ __('invoice.shipping_copy') }}:</strong>
            {{ __('invoice.shipping_copy_note_line_1') }}
        </p>
        <p>{{ __('invoice.shipping_copy_note_line_2') }}</p>
    </div>

    <div class="invoice-policies">
        <p>
            <span class="invoice-policies-title">{{ __('invoice.return_exchange_title') }}:</span>
            {{ __('invoice.return_exchange_note_line_1') }}
        </p>
        <p>{{ __('invoice.return_exchange_note_line_2') }}</p>
        <p>
            <span class="invoice-policies-title">{{ __('invoice.warranty_title') }}:</span>
            {{ __('invoice.warranty_note_line_1') }}
        </p>
        <p>{{ __('invoice.warranty_note_line_2') }}</p>
    </div>

    <div class="footer">
        {{ __('invoice.thank_you') }}<br>
        {{ __('invoice.generated_by') }}
    </div>
</body>
</html>
