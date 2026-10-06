<!DOCTYPE html>
<html lang="{{ $locale ?? str_replace('_', '-', app()->getLocale()) }}" dir="{{ !empty($isRtl) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    @include('partials.brand-head')
    @include('admin.orders.partials.invoice-styles')
</head>
<body class="{{ !empty($isRtl) ? 'rtl' : 'ltr' }}">
    @php
        // A phone number or a date is read left to right whatever the page
        // is written in. Without the embedding marks an Arabic-script invoice
        // prints "+964…" with the plus trailing and the date back to front.
        $ltr = fn (?string $value): string => "\u{202A}".$value."\u{202C}";
        // The same for money: left alone, "88,000 IQD" comes out as "IQD 88,000"
        // and a discount's minus sign lands after the figure.
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
                <p class="invoice-meta"><span class="meta-label">{{ __('invoice.invoice_number') }}</span> <span class="value">{{ $invoice->number }}</span></p>
                <p class="invoice-meta"><span class="meta-label">{{ __('invoice.invoice_date') }}</span> <span class="value">{{ $ltr($invoice->invoice_date?->format('Y-m-d')) }}</span></p>
                @if ($invoice->isDraft())
                    <p class="invoice-meta"><span class="status-badge">{{ __('invoice.draft') }}</span></p>
                @elseif ($invoice->isVoid())
                    <p class="invoice-meta"><span class="status-badge">{{ __('invoice.void') }}</span></p>
                @endif
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
                        <div class="value">{{ $invoice->customer_name }}</div>
                        <div class="muted">{{ __('invoice.phone') }}: {{ $ltr($invoice->customer_phone) }}</div>
                        @if ($invoice->customer_address)
                            <div>{{ $invoice->customer_address }}</div>
                        @endif
                        <div>{{ collect([$invoice->customer_city, \App\Support\InternationalPhone::countryName($invoice->customer_country)])->filter()->implode(' · ') }}</div>
                    </td></tr>
                </table>
            </td>
            <td class="card-spacer" style="width: 2%;"></td>
            <td style="width: 49%;">
                <table class="info-card">
                    <tr><td class="card-title">{{ __('invoice.payment_information') }}</td></tr>
                    <tr><td class="info-card-body">
                        <div class="label">{{ __('invoice.payment_status') }}</div>
                        <div class="value">{{ __('invoice.payment_'.$invoice->payment_status) }}</div>
                        <div class="label" style="margin-top: 8px;">{{ __('invoice.grand_total') }}</div>
                        <div class="value">{{ $money((float) $invoice->total) }}</div>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('invoice.item_description') }}</th>
                <th style="width: 105px;">{{ __('invoice.sku') }}</th>
                <th class="text-center" style="width: 70px;">{{ __('invoice.quantity') }}</th>
                <th class="text-right" style="width: 105px;">{{ __('invoice.unit_price') }}</th>
                <th class="text-right" style="width: 110px;">{{ __('invoice.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td><div class="product-name">{{ $item->description }}</div></td>
                    <td class="sku">{{ $item->sku ?: __('invoice.not_available') }}</td>
                    <td class="text-center">{{ number_format((int) $item->quantity) }}</td>
                    <td class="text-right">{{ $money((float) $item->unit_price) }}</td>
                    <td class="text-right">{{ $money((float) $item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="summary-label">{{ __('invoice.subtotal') }}</td>
            <td class="text-right">{{ $money((float) $invoice->subtotal) }}</td>
        </tr>
        @if ((float) $invoice->discount_amount > 0)
            <tr>
                <td class="summary-label">{{ __('invoice.discount') }}</td>
                <td class="text-right">{{ $money((float) $invoice->discount_amount, '- ') }}</td>
            </tr>
        @endif
        @if ((float) $invoice->delivery_fee > 0)
            <tr>
                <td class="summary-label">{{ __('invoice.delivery_fee') }}</td>
                <td class="text-right">{{ $money((float) $invoice->delivery_fee) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>{{ __('invoice.grand_total') }}</td>
            <td class="text-right">{{ $money((float) $invoice->total) }}</td>
        </tr>
        @if ((float) $invoice->paid_amount > 0 && ! $invoice->isVoid())
            <tr>
                <td class="summary-label">{{ __('invoice.paid') }}</td>
                <td class="text-right">{{ $money((float) $invoice->paid_amount) }}</td>
            </tr>
            <tr>
                <td class="summary-label">{{ __('invoice.balance_due') }}</td>
                <td class="text-right">{{ $money($invoice->balance()) }}</td>
            </tr>
        @endif
    </table>

    @if ($invoice->notes)
        <div class="print-note">
            <p><strong class="navy">{{ __('invoice.notes') }}:</strong> {{ $invoice->notes }}</p>
        </div>
    @endif

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
