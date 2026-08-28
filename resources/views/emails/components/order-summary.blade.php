@php
    use App\Support\EmailStyle;

    $rtl      = EmailStyle::isRtl();
    $priceEdge = EmailStyle::end();
@endphp
{{-- v2 order rows: top-bordered list, hairline dividers, monospace SKU, bold price.
     Header strip is minimal monospace caps instead of emoji + colored title.
     The whole block mirrors in ar/ku: name leads, price trails. --}}
@if (!empty($orderRows))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $rtl ? 'rtl' : 'ltr' }}" style="margin:24px 0;">

    {{-- Minimal header --}}
    <tr>
        <td class="em-order-hdr"
            style="padding:0 0 12px;font-family:{{ EmailStyle::mono() }};font-size:{{ $rtl ? '11px' : '10px' }};color:#9aa0b5;{{ EmailStyle::tracking('1.8px') }}{{ EmailStyle::caps() }}font-weight:700;border-bottom:1px solid #ebedf0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td align="{{ EmailStyle::start() }}" style="font-family:inherit;font-size:inherit;color:inherit;{{ EmailStyle::tracking('inherit') }}font-weight:inherit;">
                {{ __('Order') }}
            </td>
            <td align="{{ $priceEdge }}" style="font-family:inherit;font-size:inherit;color:inherit;{{ EmailStyle::tracking('inherit') }}font-weight:inherit;">
                {{ count($orderRows) }} {{ count($orderRows) === 1 ? __('item') : __('items') }}
            </td>
            </tr></table>
        </td>
    </tr>

    {{-- Items --}}
    @foreach ($orderRows as $row)
    <tr class="em-order-row">
        <td style="padding:0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
            <tr>
                <td align="{{ EmailStyle::start() }}" style="padding:14px 0;border-bottom:1px solid #ebedf0;vertical-align:top;width:62%;">
                    <span class="em-strong" style="display:block;font-family:{{ EmailStyle::display() }};color:#070740;font-size:13.5px;font-weight:600;line-height:1.4;">
                        {{ $row['name'] ?? __('Product') }}
                    </span>
                    @if (!empty($row['sku']))
                    <span class="em-order-sku" dir="ltr" style="display:block;margin-top:3px;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;color:#9aa0b5;font-size:10.5px;font-weight:500;letter-spacing:0.5px;unicode-bidi:isolate;">
                        SKU {{ $row['sku'] }}
                    </span>
                    @endif
                </td>
                <td class="em-muted" align="center" style="padding:14px 10px;border-bottom:1px solid #ebedf0;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;color:#8a8ea3;font-size:11px;font-weight:600;white-space:nowrap;vertical-align:middle;letter-spacing:0.5px;">
                    <span dir="ltr" style="unicode-bidi:isolate;">&times;&nbsp;{{ $row['quantity'] ?? 1 }}</span>
                </td>
                <td class="em-strong" align="{{ $priceEdge }}" style="padding:14px 0;border-bottom:1px solid #ebedf0;font-family:{{ EmailStyle::display() }};color:#070740;font-size:14px;font-weight:700;white-space:nowrap;vertical-align:middle;">
                    <span dir="ltr" style="unicode-bidi:isolate;">{{ $row['subtotal'] ?? '' }}</span>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    @endforeach

</table>
@endif
