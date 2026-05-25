@php
    $tone = $tone ?? 'info';
    $styles = [
        'info' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e3a8a'],
        'success' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#065f46'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#92400e'],
        'danger' => ['bg' => '#fff1f2', 'border' => '#fecdd3', 'text' => '#9f1239'],
    ][$tone] ?? ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#334155'];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;">
    <tr>
        <td style="background:{{ $styles['bg'] }};border:1px solid {{ $styles['border'] }};border-radius:14px;padding:14px 16px;color:{{ $styles['text'] }};font-size:14px;line-height:22px;font-weight:600;">
            {{ $message ?? '' }}
        </td>
    </tr>
</table>
