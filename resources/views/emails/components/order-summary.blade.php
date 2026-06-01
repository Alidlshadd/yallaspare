{{-- v2 order rows: top-bordered list, hairline dividers, monospace SKU, bold price.
     Header strip is minimal monospace caps instead of emoji + colored title. --}}
@if (!empty($orderRows))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">

    {{-- Minimal header --}}
    <tr>
        <td class="em-order-hdr"
            style="padding:0 0 12px;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:10px;color:#9aa0b5;letter-spacing:1.8px;text-transform:uppercase;font-weight:700;border-bottom:1px solid #ebedf0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td align="left" style="font-family:inherit;font-size:inherit;color:inherit;letter-spacing:inherit;font-weight:inherit;">
                {{ __('Order') }}
            </td>
            <td align="right" style="font-family:inherit;font-size:inherit;color:inherit;letter-spacing:inherit;font-weight:inherit;">
                {{ count($orderRows) }} {{ count($orderRows) === 1 ? __('item') : __('items') }}
            </td>
            </tr></table>
        </td>
    </tr>

    {{-- Items --}}
    @foreach ($orderRows as $row)
    <tr class="em-order-row">
        <td style="padding:0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:14px 0;border-bottom:1px solid #ebedf0;vertical-align:top;width:62%;">
                    <span class="em-strong" style="display:block;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:13.5px;font-weight:600;line-height:1.4;">
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
                <td class="em-strong" align="right" style="padding:14px 0;border-bottom:1px solid #ebedf0;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:14px;font-weight:700;white-space:nowrap;vertical-align:middle;">
                    <span dir="ltr" style="unicode-bidi:isolate;">{{ $row['subtotal'] ?? '' }}</span>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    @endforeach

</table>
@endif
