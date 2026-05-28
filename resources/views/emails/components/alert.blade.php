@php
    $tone = $tone ?? 'info';
    $icons = [
        'info'    => '&#x2139;',
        'success' => '&#x2713;',
        'warning' => '&#x26A0;',
        'danger'  => '&#x26D4;',
    ];
    $styles = [
        'info'    => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e3a8a', 'icon_bg' => '#2563eb', 'class' => 'em-alert-info'],
        'success' => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#14532d', 'icon_bg' => '#16a34a', 'class' => 'em-alert-success'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#78350f', 'icon_bg' => '#d97706', 'class' => 'em-alert-warn'],
        'danger'  => ['bg' => '#fff1f2', 'border' => '#fecdd3', 'text' => '#7f1d1d', 'icon_bg' => '#dc2626', 'class' => 'em-alert-danger'],
    ];
    $s = $styles[$tone] ?? $styles['info'];
    $icon = $icons[$tone] ?? $icons['info'];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
<tr>
    <td class="{{ $s['class'] }}" style="background:{{ $s['bg'] }};border:1px solid {{ $s['border'] }};border-radius:14px;padding:14px 16px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td valign="top" width="28" style="padding-right:12px;">
                <div style="width:24px;height:24px;border-radius:50%;background:{{ $s['icon_bg'] }};text-align:center;line-height:24px;font-size:13px;color:#ffffff;font-weight:900;">
                    {!! $icon !!}
                </div>
            </td>
            <td valign="middle" style="color:{{ $s['text'] }};font-size:13.5px;line-height:21px;font-weight:600;">
                {{ $message ?? '' }}
            </td>
        </tr>
        </table>
    </td>
</tr>
</table>
