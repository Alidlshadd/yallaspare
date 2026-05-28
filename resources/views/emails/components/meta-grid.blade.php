@if (!empty($items))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
    @foreach ($items as $item)
    <tr class="em-meta-row" style="border-color:#e2e8f0;">
        <td class="em-meta-bg em-meta-label"
            style="padding:12px 16px;background:#f8fafc;border-bottom:{{ $loop->last ? '0' : '1px solid #e2e8f0' }};border-right:1px solid #e2e8f0;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.9px;width:36%;vertical-align:middle;">
            {{ $item['label'] ?? '' }}
        </td>
        <td class="em-meta-val"
            style="padding:12px 16px;border-bottom:{{ $loop->last ? '0' : '1px solid #e2e8f0' }};color:#0f172a;font-size:14px;font-weight:600;vertical-align:middle;">
            {{ $item['value'] ?? '' }}
        </td>
    </tr>
    @endforeach
</table>
@endif
