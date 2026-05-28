@php
    $status = strtolower($status ?? 'pending');
    $configs = [
        'placed'      => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1d4ed8', 'dot' => '#3b82f6', 'label' => __('Order Placed')],
        'pending'     => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#92400e', 'dot' => '#f59e0b', 'label' => __('Pending')],
        'processing'  => ['bg' => '#f0f9ff', 'border' => '#bae6fd', 'text' => '#075985', 'dot' => '#0ea5e9', 'label' => __('Processing')],
        'shipped'     => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#14532d', 'dot' => '#22c55e', 'label' => __('Shipped')],
        'delivered'   => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#14532d', 'dot' => '#16a34a', 'label' => __('Delivered')],
        'cancelled'   => ['bg' => '#fff1f2', 'border' => '#fecdd3', 'text' => '#7f1d1d', 'dot' => '#ef4444', 'label' => __('Cancelled')],
        'refunded'    => ['bg' => '#fdf4ff', 'border' => '#e9d5ff', 'text' => '#581c87', 'dot' => '#a855f7', 'label' => __('Refunded')],
        'completed'   => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#14532d', 'dot' => '#16a34a', 'label' => __('Completed')],
        'approved'    => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#14532d', 'dot' => '#16a34a', 'label' => __('Approved')],
        'rejected'    => ['bg' => '#fff1f2', 'border' => '#fecdd3', 'text' => '#7f1d1d', 'dot' => '#ef4444', 'label' => __('Rejected')],
    ];
    $cfg   = $configs[$status] ?? ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#374151', 'dot' => '#64748b', 'label' => ucfirst($status)];
    $label = $customLabel ?? $cfg['label'];
@endphp
<table role="presentation" cellpadding="0" cellspacing="0">
<tr>
    <td style="background:{{ $cfg['bg'] }};border:1px solid {{ $cfg['border'] }};border-radius:999px;padding:5px 14px;">
        <table role="presentation" cellpadding="0" cellspacing="0"><tr>
        <td valign="middle" width="8" style="padding-right:7px;">
            <div style="width:7px;height:7px;border-radius:50%;background:{{ $cfg['dot'] }};"></div>
        </td>
        <td valign="middle" style="color:{{ $cfg['text'] }};font-size:12px;font-weight:700;white-space:nowrap;letter-spacing:0.3px;">
            {{ $label }}
        </td>
        </tr></table>
    </td>
</tr>
</table>
