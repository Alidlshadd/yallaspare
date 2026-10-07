{{-- The invoice as a picture.

     Some customers want an image in their chat rather than a PDF, and it has
     to be the same document: so this page lays out the very markup and
     styles the PDF is made from, at the width of an A4 sheet, and the script
     turns that sheet into a PNG in the browser.

     Used by staff (from the invoice screen) and by the customer (from the
     share link); only where its links lead differs.

     Expects: $invoice, $locale, $isRtl, $currency, $logoUrl, $links
     ['languages' => [code => url], 'pdf' => url, 'back' => url|null], $auto. --}}
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    {{-- An A4 sheet does not reflow; a phone shows the whole of it scaled. --}}
    <meta name="viewport" content="width=860">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>{{ __('invoice.title') }} {{ $invoice->number }}</title>
    @include('admin.orders.partials.invoice-styles')
    <style>
        /* What the PDF engine does with its page, done here for a screen:
           the sheet is the paper, the body is the desk it lies on. */
        html { background: #e5e7eb; }
        body { background: #e5e7eb; padding: 0 0 40px; font-family: "Invoice Plex", "DejaVu Sans", "Segoe UI", Tahoma, system-ui, sans-serif; }
        .sheet { background: #ffffff; width: 794px; margin: 16px auto 0; padding: 26px 30px 24px; box-shadow: 0 2px 14px rgba(15, 23, 42, .12); }
        /* On paper the footer is pinned to the foot of the page; a picture
           has no page, so it simply closes the document. */
        .sheet .footer { position: static; margin-top: 22px; text-align: center !important; }
        /* Two places where a browser and the PDF engine read the same rules
           differently; these put the picture where the PDF puts them. The
           totals sit on the right in every language, as printed. */
        .sheet .summary-table { margin-left: auto !important; margin-right: 0 !important; }
        .sheet .logo-img { height: auto; width: auto; }

        .toolbar { width: 794px; margin: 16px auto 0; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; font-family: "Segoe UI", Tahoma, system-ui, sans-serif; font-size: 14px; }
        .toolbar, .toolbar * { direction: {{ $isRtl ? 'rtl' : 'ltr' }} !important; }
        .toolbar .group { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .toolbar a.lang { color: #04042a; font-weight: 700; text-decoration: none; }
        .toolbar a.lang[aria-current] { text-decoration: underline; }
        .toolbar .button { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 22px; border-radius: 12px; border: 0; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; background: #fbbf24; color: #04042a; text-align: center !important; }
        .toolbar .button.ghost { background: #ffffff; border: 1px solid #cbd5e1; }
        .toolbar .button[disabled] { opacity: .6; cursor: progress; }
    </style>
    @vite('resources/js/invoice-image.js')
</head>
<body class="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="toolbar">
        <div class="group">
            <button type="button" class="button"
                    data-invoice-image
                    data-filename="{{ $invoice->number }}-{{ $locale }}"
                    data-busy-label="{{ __('Preparing the image…') }}"
                    data-failed-label="{{ __('The image could not be made. Try the PDF instead.') }}"
                    @if (! empty($auto)) data-auto="1" @endif>{{ __('Save as image') }}</button>
            <a class="button ghost" href="{{ $links['pdf'] }}">{{ __('Download PDF') }}</a>
            @if (! empty($links['back']))
                <a class="button ghost" href="{{ $links['back'] }}">{{ __('Back') }}</a>
            @endif
        </div>
        <nav class="group" aria-label="{{ __('Language') }}">
            @foreach (['en' => 'English', 'ar' => 'العربية', 'ku' => 'کوردی'] as $code => $name)
                <a class="lang" href="{{ $links['languages'][$code] }}" lang="{{ $code }}" @if ($locale === $code) aria-current="true" @endif>{{ $name }}</a>
            @endforeach
        </nav>
    </div>

    <div class="sheet" data-invoice-sheet>
        @include('admin.manual-invoices.partials.document', ['logoPath' => $logoUrl])
    </div>
</body>
</html>
