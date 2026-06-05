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

        body.rtl .pdf-rtl-text {
            direction: ltr !important;
            text-align: right !important;
            unicode-bidi: bidi-override;
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

        body.rtl .text-center.pdf-rtl-text {
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
            width: 50%;
        }

        .card-spacer {
            width: 14px !important;
        }

        .info-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            min-height: 118px;
            padding: 13px 14px;
        }

        .card-title {
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            color: #070740;
            font-size: 11px;
            font-weight: 700;
            margin: -13px -14px 12px;
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

        .invoice-policies p {
            margin: 0 0 6px;
        }

        .invoice-policies p:last-child {
            margin-bottom: 0;
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
    @php
        $pdfText = static fn ($value) => \App\Support\PdfArabicText::forDompdf((string) $value, !empty($isRtl));
        $pdfRtlClass = !empty($isRtl) ? 'pdf-rtl-text' : '';
    @endphp
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if (!empty($logoPath))
                    <img src="{{ $logoPath }}" alt="{{ __('YallaSpare logo') }}" class="logo-img">
                @else
                    <span class="logo-box">YS</span>
                @endif
                <p class="company-name {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.company_name')) }}</p>
                <p class="company-address {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.company_address')) }}</p>
                <p class="company-address">support@yallaspare.com</p>
                <p class="company-address">+964 770 448 8315</p>
            </td>
            <td class="text-right" style="width: 45%;">
                <h1 class="invoice-title {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.title')) }}</h1>
                <p class="invoice-meta"><span class="label {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.invoice_number')) }}</span><span class="value">{{ $invoiceNumber }}</span></p>
                <p class="invoice-meta"><span class="label {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.order_date')) }}</span><span class="value">{{ optional($order->created_at)->format('Y-m-d H:i') }}</span></p>
            </td>
        </tr>
    </table>

    <table class="cards-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="card-title {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.customer_information')) }}</div>
                    <span class="label {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.customer_name')) }}</span>
                    <div class="value {{ $pdfRtlClass }}" dir="{{ !empty($isRtl) ? 'ltr' : 'auto' }}">{{ $pdfText($order->user?->name ?? __('invoice.guest_customer')) }}</div>
                    @if ($order->user?->email)
                        <div class="muted">{{ $order->user->email }}</div>
                    @endif
                    @if ($order->user?->phone)
                        <div class="muted"><span class="{{ $pdfRtlClass }}">{{ $pdfText(__('invoice.phone')) }}</span>: {{ $order->user->phone }}</div>
                    @endif
                </div>
            </td>
            <td class="card-spacer"></td>
            <td>
                <div class="info-card">
                    <div class="card-title {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.shipping_information')) }}</div>
                    <span class="label {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.ship_to')) }}</span>
                    <div class="value {{ $pdfRtlClass }}" dir="{{ !empty($isRtl) ? 'ltr' : 'auto' }}">{{ $pdfText($order->user?->name ?? __('invoice.guest_customer')) }}</div>
                    <div class="{{ $pdfRtlClass }}" dir="{{ !empty($isRtl) ? 'ltr' : 'auto' }}">{{ $pdfText($order->delivery_address) }}</div>
                    <div class="{{ $pdfRtlClass }}">{{ $pdfText($order->delivery_city) }}</div>
                    <div class="muted"><span class="{{ $pdfRtlClass }}">{{ $pdfText(__('invoice.phone')) }}</span>: {{ $order->delivery_phone }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="{{ $pdfRtlClass }}">{{ $pdfText(__('invoice.product_name')) }}</th>
                <th class="{{ $pdfRtlClass }}" style="width: 105px;">{{ $pdfText(__('invoice.sku')) }}</th>
                <th class="text-center {{ $pdfRtlClass }}" style="width: 70px;">{{ $pdfText(__('invoice.quantity')) }}</th>
                <th class="text-right {{ $pdfRtlClass }}" style="width: 105px;">{{ $pdfText(__('invoice.unit_price')) }}</th>
                <th class="text-right {{ $pdfRtlClass }}" style="width: 110px;">{{ $pdfText(__('invoice.total')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <div class="product-name {{ $pdfRtlClass }}" dir="{{ !empty($isRtl) ? 'ltr' : 'auto' }}">
                            {{ $pdfText($item->product?->localizedName($locale ?? app()->getLocale()) ?? __('invoice.product_unavailable')) }}
                        </div>
                        @if ($item->product?->brand)
                            <div class="sku" dir="{{ !empty($isRtl) ? 'ltr' : 'auto' }}"><span class="{{ $pdfRtlClass }}">{{ $pdfText(__('invoice.brand')) }}</span>: {{ $item->product->brand }}</div>
                        @endif
                    </td>
                    <td class="sku">{{ $item->product?->sku ?? $pdfText(__('invoice.not_available')) }}</td>
                    <td class="text-center">{{ number_format((int) $item->quantity) }}</td>
                    <td class="text-right">{{ number_format((float) $item->unit_price) }} {{ $currency }}</td>
                    <td class="text-right">{{ number_format((float) $item->subtotal) }} {{ $currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="summary-label {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.subtotal')) }}</td>
            <td class="text-right">{{ number_format((float) $subtotal) }} {{ $currency }}</td>
        </tr>
        <tr>
            <td class="summary-label {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.shipping')) }}</td>
            <td class="text-right">{{ number_format((float) $shipping) }} {{ $currency }}</td>
        </tr>
        @if (!empty($discount) && (float) $discount > 0)
            <tr>
                <td class="summary-label {{ $pdfRtlClass }}">{{ $pdfText(__('invoice.discount')) }}</td>
                <td class="text-right">- {{ number_format((float) $discount) }} {{ $currency }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td class="{{ $pdfRtlClass }}">{{ $pdfText(__('invoice.grand_total')) }}</td>
            <td class="text-right">{{ number_format((float) $grandTotal) }} {{ $currency }}</td>
        </tr>
    </table>

    <div class="print-note {{ $pdfRtlClass }}">
        <strong class="navy">{{ $pdfText(__('invoice.shipping_copy')) }}:</strong>
        {{ $pdfText(__('invoice.shipping_copy_note')) }}
    </div>

    <div class="invoice-policies">
        <p class="{{ $pdfRtlClass }}">
            <span class="invoice-policies-title">{{ $pdfText(__('invoice.return_exchange_title')) }}:</span>
            {{ $pdfText(__('invoice.return_exchange_note')) }}
        </p>
        <p class="{{ $pdfRtlClass }}">
            <span class="invoice-policies-title">{{ $pdfText(__('invoice.warranty_title')) }}:</span>
            {{ $pdfText(__('invoice.warranty_note')) }}
        </p>
    </div>

    <div class="footer {{ $pdfRtlClass }}">
        {{ $pdfText(__('invoice.thank_you')) }}<br>
        {{ $pdfText(__('invoice.generated_by')) }}
    </div>
</body>
</html>
