@php
    $tone = $tone ?? 'info';
    $styles = [
        'info'    => ['bg' => '#f1f5fb', 'border' => '#cdd9ee', 'accent' => '#1d4ed8', 'text' => '#1e3a8a', 'label' => __('Info'),    'class' => 'em-alert-info'],
        'success' => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'accent' => '#16a34a', 'text' => '#14532d', 'label' => __('Success'), 'class' => 'em-alert-success'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'accent' => '#b45309', 'text' => '#78350f', 'label' => __('Warning'), 'class' => 'em-alert-warn'],
        'danger'  => ['bg' => '#fef2f2', 'border' => '#fecaca', 'accent' => '#b91c1c', 'text' => '#7f1d1d', 'label' => __('Alert'),   'class' => 'em-alert-danger'],
    ];
    $s = $styles[$tone] ?? $styles['info'];
@endphp
{{-- v2 alert: left accent border + monospace caps tone label + message. No icon
     character — keeps it clean across email clients that ship different emoji sets. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
<tr>
    <td class="{{ $s['class'] }}"
        style="background:{{ $s['bg'] }};border:1px solid {{ $s['border'] }};border-left:3px solid {{ $s['accent'] }};border-radius:4px;padding:14px 18px;color:{{ $s['text'] }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:13.5px;line-height:21px;font-weight:500;">
        <span style="display:block;margin-bottom:4px;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:9.5px;font-weight:700;color:{{ $s['accent'] }};letter-spacing:1.8px;text-transform:uppercase;">{{ $s['label'] }}</span>
        {{ $message ?? '' }}
    </td>
</tr>
</table>
