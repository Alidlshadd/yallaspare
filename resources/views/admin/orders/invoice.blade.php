<!DOCTYPE html>
<html lang="{{ $locale ?? str_replace('_', '-', app()->getLocale()) }}" dir="{{ !empty($isRtl) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoiceNumber }}</title>
    @include('partials.brand-head')
    <style>
        @page {
            margin: 26px 30px 58px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #ffffff;
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
        }

        body.ltr {
            direction: ltr;
            text-align: left;
        }

        body.rtl {
            direction: rtl;
            text-align: right;
            font-size: 13.5px;
            line-height: 1.65;
        }

        body.rtl .invoice-title {
            font-size: 28px;
        }

        body.rtl .items-table td,
        body.rtl .items-table th {
            padding: 11px 10px;
        }

        body.rtl .summary-table {
            width: 340px;
        }

        body.rtl .summary-table td {
            padding: 11px 13px;
        }

        body.rtl .print-note,
        body.rtl .invoice-policies {
            padding: 12px 14px;
        }

        body.rtl .info-card-body {
            padding: 14px 16px;
        }

        body.rtl table,
        body.rtl tr,
        body.rtl td,
        body.rtl th,
        body.rtl p,
        body.rtl div,
        body.rtl span {
            direction: rtl !important;
            text-align: right !important;
            unicode-bidi: embed;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        body.rtl .text-center {
            text-align: center !important;
        }

        body.rtl .logo-box {
            text-align: center !important;
        }

        .muted {
            color: #6b7280;
        }

        .navy {
            color: #070740;
        }

        .header-table {
            border-bottom: 4px solid #070740;
            margin-bottom: 22px;
            padding-bottom: 14px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-box {
            background: #070740;
            border-radius: 8px;
            color: #ffffff;
            display: inline-block;
            font-size: 16px;
            font-weight: 700;
            height: 56px;
            letter-spacing: 1px;
            line-height: 56px;
            text-align: center;
            width: 56px;
        }

        .logo-img {
            display: block;
            max-height: 58px;
            max-width: 150px;
        }

        .company-name {
            color: #070740;
            font-size: 18px;
            font-weight: 700;
            margin: 8px 0 2px;
        }

        .company-address {
            color: #6b7280;
            font-size: 11px;
            margin: 0;
        }

        .invoice-title {
            color: #070740;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 1px;
            margin: 0 0 8px;
        }

        .invoice-meta {
            color: #374151;
            font-size: 11px;
            margin: 0;
        }

        .label {
            color: #6b7280;
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .5px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .value {
            color: #111827;
            font-weight: 700;
        }

        .cards-table {
            margin-bottom: 20px;
        }

        .cards-table td {
            vertical-align: top;
            width: 49%;
        }

        .card-spacer {
            width: 2% !important;
        }

        .info-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            border-collapse: separate;
            width: 100%;
        }

        .info-card-body {
            padding: 12px 14px;
        }

        .meta-label {
            color: #6b7280;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .card-title {
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            color: #070740;
            font-size: 11px;
            font-weight: 700;
            padding: 8px 14px;
            text-transform: uppercase;
        }

        .status-badge {
            background: #070740;
            border-radius: 12px;
            color: #ffffff;
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .3px;
            padding: 4px 10px;
            text-transform: uppercase;
        }

        .items-table {
            margin-top: 6px;
        }

        .items-table th {
            background: #070740;
            border: 1px solid #070740;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 9px 8px;
            text-align: left;
            text-transform: uppercase;
        }

        body.rtl .items-table th {
            text-align: right !important;
        }

        body.rtl .text-right {
            text-align: right !important;
        }

        body.rtl .summary-table {
            margin-left: 0;
            margin-right: auto;
        }

        body.rtl .print-note,
        body.rtl .invoice-policies {
            text-align: right !important;
        }

        .items-table td {
            border: 1px solid #d1d5db;
            padding: 9px 8px;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .product-name {
            color: #111827;
            font-weight: 700;
        }

        .sku {
            color: #6b7280;
            font-size: 10px;
        }

        .summary-table {
            margin-left: auto;
            margin-top: 18px;
            width: 310px;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 9px 11px;
        }

        .summary-table .summary-label {
            background: #f3f4f6;
            color: #374151;
            font-weight: 700;
        }

        .summary-table .grand td {
            background: #070740;
            border-color: #070740;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
        }

        .print-note {
            background: #f9fafb;
            border: 1px solid #d1d5db;
            color: #374151;
            font-size: 11px;
            margin-top: 18px;
            padding: 10px 12px;
        }

        .invoice-policies {
            background: #ffffff;
            border: 1px solid #d1d5db;
            color: #374151;
            font-size: 10.8px;
            margin-top: 12px;
            padding: 10px 12px;
        }

        .invoice-policies-title {
            color: #070740;
            font-weight: 700;
        }

        .invoice-policies p,
        .print-note p {
            margin: 0;
        }

        .invoice-policies p + p,
        .print-note p + p {
            margin-top: 2px;
        }

        /* Vertical breath between different policy blocks
           (return → warranty): each policy has 2 paragraphs,
           so the 3rd paragraph starts a new policy block. */
        .invoice-policies p:nth-of-type(3) {
            margin-top: 8px;
        }

        .footer {
            border-top: 1px solid #d1d5db;
            bottom: -34px;
            color: #6b7280;
            font-size: 10.5px;
            left: 0;
            line-height: 1.5;
            padding-top: 9px;
            position: fixed;
            right: 0;
            text-align: center;
        }
    </style>
</head>
<body class="{{ !empty($isRtl) ? 'rtl' : 'ltr' }}">
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
                <p class="invoice-meta"><span class="meta-label">{{ __('invoice.order_date') }}</span> <span class="value">{{ optional($order->created_at)->format('Y-m-d H:i') }}</span></p>
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
                            <div class="muted">{{ __('invoice.phone') }}: {{ $order->user->phone }}</div>
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
                        <div class="muted">{{ __('invoice.phone') }}: {{ $order->delivery_phone }}</div>
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
                    <td class="text-right">{{ number_format((float) $item->unit_price) }} {{ $currency }}</td>
                    <td class="text-right">{{ number_format((float) $item->subtotal) }} {{ $currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="summary-label">{{ __('invoice.subtotal') }}</td>
            <td class="text-right">{{ number_format((float) $subtotal) }} {{ $currency }}</td>
        </tr>
        <tr>
            <td class="summary-label">{{ __('invoice.shipping') }}</td>
            <td class="text-right">{{ number_format((float) $shipping) }} {{ $currency }}</td>
        </tr>
        @if (!empty($discount) && (float) $discount > 0)
            <tr>
                <td class="summary-label">{{ __('invoice.discount') }}</td>
                <td class="text-right">- {{ number_format((float) $discount) }} {{ $currency }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>{{ __('invoice.grand_total') }}</td>
            <td class="text-right">{{ number_format((float) $grandTotal) }} {{ $currency }}</td>
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
