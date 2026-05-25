@php
    $variant = $variant ?? 'primary';
    $background = $variant === 'secondary' ? '#ffffff' : '#070740';
    $color = $variant === 'secondary' ? '#070740' : '#ffffff';
    $border = $variant === 'secondary' ? '#cbd5e1' : '#070740';
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" class="mobile-full" style="margin:0;">
    <tr>
        <td align="center" bgcolor="{{ $background }}" style="border-radius:12px;border:1px solid {{ $border }};background:{{ $background }};">
            <a href="{{ $url }}" class="email-button" style="display:inline-block;padding:14px 22px;border-radius:12px;color:{{ $color }};font-size:15px;font-weight:800;line-height:20px;text-decoration:none;text-align:center;min-width:190px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
