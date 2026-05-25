@if (!empty($items))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
        @foreach ($items as $item)
            <tr>
                <td class="mobile-stack" style="padding:12px 14px;background:#f8fafc;border-bottom:{{ $loop->last ? '0' : '1px solid #e2e8f0' }};color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;width:38%;">
                    {{ $item['label'] ?? '' }}
                </td>
                <td class="mobile-stack" style="padding:12px 14px;border-bottom:{{ $loop->last ? '0' : '1px solid #e2e8f0' }};color:#0f172a;font-size:14px;font-weight:700;">
                    {{ $item['value'] ?? '' }}
                </td>
            </tr>
        @endforeach
    </table>
@endif
