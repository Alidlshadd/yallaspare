@php
    $variant = $variant ?? 'primary';
    $size    = $size    ?? 'default';

    $configs = [
        'primary'   => ['bg' => '#070740', 'text' => '#ffffff', 'shadow' => 'rgba(7,7,64,0.45)'],
        'danger'    => ['bg' => '#dc2626', 'text' => '#ffffff', 'shadow' => 'rgba(220,38,38,0.4)'],
        'success'   => ['bg' => '#16a34a', 'text' => '#ffffff', 'shadow' => 'rgba(22,163,74,0.4)'],
        'secondary' => ['bg' => '#ffffff', 'text' => '#070740', 'shadow' => 'rgba(0,0,0,0.08)'],
        'warning'   => ['bg' => '#d97706', 'text' => '#ffffff', 'shadow' => 'rgba(217,119,6,0.4)'],
    ];
    $cfg = $configs[$variant] ?? $configs['primary'];

    $padding = $size === 'large' ? '18px 40px' : '15px 32px';
    $fontSize = $size === 'large' ? '17px' : '15px';
@endphp
<table role="presentation" class="em-btn-wrap" cellpadding="0" cellspacing="0" style="margin:0;">
<tr>
    <td align="center" bgcolor="{{ $cfg['bg'] }}"
        style="border-radius:14px;background:{{ $cfg['bg'] }};box-shadow:0 6px 20px {{ $cfg['shadow'] }};">
        <a href="{{ $url }}"
           class="em-btn"
           style="display:inline-block;padding:{{ $padding }};border-radius:14px;color:{{ $cfg['text'] }};font-size:{{ $fontSize }};font-weight:700;line-height:1.25;text-decoration:none;text-align:center;letter-spacing:-0.1px;min-width:180px;">
            {{ $label }}
        </a>
    </td>
</tr>
</table>
