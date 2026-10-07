{{-- What a customer sees when they open the link staff sent them. A page of its
     own on purpose: no storefront navigation, no account menu, and nothing on
     it that leads anywhere but this one invoice. --}}
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>{{ __('invoice.title') }} {{ $invoice->number }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,700|ibm-plex-sans-arabic:400,700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        /* One stack for every language: fallback is per glyph, so Latin lands on
           Inter and Arabic script on Plex without a rule per direction. */
        body { margin: 0; background: #f3f4f7; color: #111827; font-family: Inter, "IBM Plex Sans Arabic", system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif; font-size: 15px; line-height: 1.6; }
        .page { max-width: 760px; margin: 0 auto; padding: 20px 16px 40px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 14px; }
        .head { background: #04042a; color: #fff; border-color: #04042a; }
        .head h1 { margin: 0; font-size: 22px; }
        .head p { margin: 4px 0 0; color: rgba(255,255,255,.72); font-size: 13px; }
        .number { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; direction: ltr; unicode-bidi: isolate; display: inline-block; }
        .label { color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        /* Tracking pulls joined Arabic-script letters apart. */
        [dir="rtl"] .label { letter-spacing: 0; }
        .grid { display: grid; gap: 14px; grid-template-columns: 1fr; }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .row:last-child { border-bottom: 0; }
        .row .desc { min-width: 0; }
        .row .desc small { display: block; color: #64748b; }
        /* Money and codes read left to right in every language; without this an
           RTL page prints "IQD 88,000" and moves the minus sign. */
        .amount, .ltr { direction: ltr; unicode-bidi: isolate; }
        .amount { white-space: nowrap; font-weight: 700; }
        [dir="rtl"] div.amount { text-align: right; }
        .ltr { display: inline-block; }
        .total { border-top: 2px solid #04042a; margin-top: 6px; padding-top: 12px; font-size: 18px; }
        .badge { display: inline-block; border-radius: 999px; padding: 3px 10px; font-size: 12px; font-weight: 700; background: #e2e8f0; color: #334155; }
        .badge.paid { background: #d1fae5; color: #047857; }
        .badge.unpaid { background: #ffe4e6; color: #be123c; }
        .badge.partial { background: #fef3c7; color: #92400e; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 20px; border-radius: 12px; font-weight: 700; font-size: 14px; text-decoration: none; background: #fbbf24; color: #04042a; }
        .button.ghost { background: #fff; color: #04042a; border: 1px solid #cbd5e1; }
        .langs { font-size: 13px; color: #64748b; }
        .langs a { color: #04042a; font-weight: 700; text-decoration: none; margin-inline-end: 12px; }
        .langs a[aria-current] { text-decoration: underline; }
        @media (min-width: 640px) { .grid { grid-template-columns: 1fr 1fr; } .page { padding-top: 32px; } }
        @media print { body { background: #fff; } .no-print { display: none; } .card { border: 0; padding: 8px 0; } .head { color: #111827; background: #fff; } .head p { color: #475569; } }
    </style>
</head>
<body>
    <main class="page">
        <header class="card head">
            <h1>{{ $service->businessName() }}</h1>
            <p>{{ __('invoice.title') }} <span class="number">{{ $invoice->number }}</span> · <span class="ltr">{{ $invoice->invoice_date?->format('Y-m-d') }}</span></p>
        </header>

        <nav class="card langs no-print" aria-label="{{ __('Language') }}">
            @foreach (['en' => 'English', 'ar' => 'العربية', 'ku' => 'کوردی'] as $code => $name)
                <a href="{{ route('invoices.shared.show', ['token' => $token, 'lang' => $code]) }}" lang="{{ $code }}" @if ($locale === $code) aria-current="true" @endif>{{ $name }}</a>
            @endforeach
        </nav>

        <div class="grid">
            <section class="card">
                <div class="label">{{ __('invoice.customer_information') }}</div>
                <div><strong>{{ $invoice->customer_name }}</strong></div>
                <div class="number">{{ $invoice->customer_phone }}</div>
                <div>{{ collect([$invoice->customer_address, $invoice->customer_city, \App\Support\InternationalPhone::countryName($invoice->customer_country)])->filter()->implode(' · ') }}</div>
            </section>
            <section class="card">
                <div class="label">{{ __('invoice.payment_status') }}</div>
                <span class="badge {{ $invoice->payment_status }}">{{ __('invoice.payment_'.$invoice->payment_status) }}</span>
                <div class="label" style="margin-top: 12px;">{{ __('invoice.grand_total') }}</div>
                <div class="amount" style="font-size: 20px;">{{ $service->money((float) $invoice->total) }}</div>
            </section>
        </div>

        <section class="card">
            @foreach ($invoice->items as $item)
                <div class="row">
                    <div class="desc">
                        <strong>{{ $item->descriptionFor($locale) }}</strong>
                        <small>
                            @if ($item->sku)<span class="number">{{ $item->sku }}</span> · @endif
                            <span class="ltr">{{ number_format($item->quantity) }} × {{ $service->money((float) $item->unit_price) }}</span>
                        </small>
                    </div>
                    <div class="amount">{{ $service->money((float) $item->line_total) }}</div>
                </div>
            @endforeach

            <div class="row"><span>{{ __('invoice.subtotal') }}</span><span class="amount">{{ $service->money((float) $invoice->subtotal) }}</span></div>
            @if ((float) $invoice->discount_amount > 0)
                <div class="row"><span>{{ __('invoice.discount') }}</span><span class="amount">- {{ $service->money((float) $invoice->discount_amount) }}</span></div>
            @endif
            @if ((float) $invoice->delivery_fee > 0)
                <div class="row"><span>{{ __('invoice.delivery_fee') }}</span><span class="amount">{{ $service->money((float) $invoice->delivery_fee) }}</span></div>
            @endif
            <div class="row total"><strong>{{ __('invoice.grand_total') }}</strong><span class="amount">{{ $service->money((float) $invoice->total) }}</span></div>
            @if ((float) $invoice->paid_amount > 0)
                <div class="row"><span>{{ __('invoice.paid') }}</span><span class="amount">{{ $service->money((float) $invoice->paid_amount) }}</span></div>
                <div class="row"><strong>{{ __('invoice.balance_due') }}</strong><span class="amount">{{ $service->money($invoice->balance()) }}</span></div>
            @endif

            @if ($invoice->notes)
                <p style="margin: 14px 0 0; color: #475569;"><strong>{{ __('invoice.notes') }}:</strong> {{ $invoice->notes }}</p>
            @endif
        </section>

        <div class="card actions no-print">
            <a class="button" href="{{ route('invoices.shared.pdf', ['token' => $token, 'lang' => $locale]) }}">{{ __('Download PDF') }}</a>
            <a class="button ghost" href="{{ route('invoices.shared.pdf', ['token' => $token, 'lang' => $locale, 'inline' => 1]) }}" target="_blank" rel="noopener">{{ __('Print') }}</a>
        </div>

        <p style="text-align: center; color: #64748b; font-size: 13px;">{{ __('invoice.thank_you') }}</p>
    </main>
</body>
</html>
