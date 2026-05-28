@if (!empty($orderRows))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">

    {{-- Header --}}
    <tr>
        <td colspan="3" class="em-order-hdr"
            style="padding:13px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td style="color:#0f172a;font-size:13px;font-weight:800;letter-spacing:0.3px;">
                &#x1F6D2; {{ __('Order summary') }}
            </td>
            <td align="right" style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.8px;">
                {{ count($orderRows) }} {{ __('item(s)') }}
            </td>
            </tr></table>
        </td>
    </tr>

    {{-- Items --}}
    @foreach ($orderRows as $row)
    <tr class="em-order-row">
        <td style="padding:14px 18px;border-top:1px solid #e2e8f0;vertical-align:top;width:55%;">
            <span class="em-strong" style="display:block;color:#0f172a;font-size:14px;font-weight:700;line-height:20px;">
                {{ $row['name'] ?? __('Product') }}
            </span>
            @if (!empty($row['sku']))
            <span class="em-order-sku" style="display:block;margin-top:3px;color:#94a3b8;font-size:11px;font-weight:600;letter-spacing:0.3px;">
                SKU: {{ $row['sku'] }}
            </span>
            @endif
        </td>
        <td align="center" style="padding:14px 10px;border-top:1px solid #e2e8f0;color:#64748b;font-size:13px;font-weight:600;white-space:nowrap;vertical-align:middle;">
            &times;&nbsp;{{ $row['quantity'] ?? 1 }}
        </td>
        <td align="right" style="padding:14px 18px;border-top:1px solid #e2e8f0;color:#0f172a;font-size:14px;font-weight:800;white-space:nowrap;vertical-align:middle;">
            {{ $row['subtotal'] ?? '' }}
        </td>
    </tr>
    @endforeach

</table>
@endif
