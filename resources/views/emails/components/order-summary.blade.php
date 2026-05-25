@if (!empty($orderRows))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
        <tr>
            <td colspan="3" style="padding:14px 16px;background:#f8fafc;color:#0f172a;font-size:14px;font-weight:900;">
                {{ __('Order summary') }}
            </td>
        </tr>
        @foreach ($orderRows as $row)
            <tr>
                <td style="padding:13px 16px;border-top:1px solid #e2e8f0;color:#0f172a;font-size:14px;line-height:20px;font-weight:800;">
                    {{ $row['name'] ?? __('Product') }}
                    @if (!empty($row['sku']))
                        <div style="margin-top:2px;color:#94a3b8;font-size:12px;font-weight:600;">{{ __('SKU') }}: {{ $row['sku'] }}</div>
                    @endif
                </td>
                <td align="center" style="padding:13px 10px;border-top:1px solid #e2e8f0;color:#475569;font-size:13px;font-weight:700;white-space:nowrap;">
                    x {{ $row['quantity'] ?? 1 }}
                </td>
                <td align="right" style="padding:13px 16px;border-top:1px solid #e2e8f0;color:#0f172a;font-size:14px;font-weight:800;white-space:nowrap;">
                    {{ $row['subtotal'] ?? '' }}
                </td>
            </tr>
        @endforeach
    </table>
@endif
