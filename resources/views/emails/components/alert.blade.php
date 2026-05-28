@php
    $tone = $tone ?? 'info';
    $icons = [
        'info'    => 'ℹ',
        'success' => '✓',
        'warning' => '⚠',
        'danger'  => '⛔',
    ];
    $styles = [
        'info'    => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e3a8a', 'class' => 'em-alert-info'],
        'success' => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#14532d', 'class' => 'em-alert-success'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#78350f', 'class' => 'em-alert-warn'],
        'danger'  => ['bg' => '#fef2f2', 'border' => '#fecaca', 'text' => '#7f1d1d', 'class' => 'em-alert-danger'],
    ];
    $s = $styles[$tone] ?? $styles['info'];
    $icon = $icons[$tone] ?? $icons['info'];
@endphp
{{-- Single-cell alert: emoji-like Unicode glyph + message. No nested border-radius
     icon (Outlook can't render rounded-only-on-shape). One row, one cell, one border. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
<tr>
    <td class="{{ $s['class'] }}"
        style="background:{{ $s['bg'] }};border:1px solid {{ $s['border'] }};border-left:4px solid {{ $s['text'] }};border-radius:12px;padding:14px 18px;color:{{ $s['text'] }};font-size:13.5px;line-height:21px;font-weight:600;">
        <span style="display:inline-block;font-size:16px;font-weight:700;margin-right:8px;color:{{ $s['text'] }};">{{ $icon }}</span>{{ $message ?? '' }}
    </td>
</tr>
</table>
