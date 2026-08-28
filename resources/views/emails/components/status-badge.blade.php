@php
    use App\Support\EmailStyle;

    $status = strtolower($status ?? 'pending');
    $configs = [
        'placed'      => ['border' => '#cdd9ee', 'accent' => '#1d4ed8', 'label' => __('Order Placed')],
        'pending'     => ['border' => '#fde68a', 'accent' => '#b45309', 'label' => __('Pending')],
        'processing'  => ['border' => '#bae6fd', 'accent' => '#075985', 'label' => __('Processing')],
        'shipped'     => ['border' => '#bbf7d0', 'accent' => '#14532d', 'label' => __('Shipped')],
        'delivered'   => ['border' => '#bbf7d0', 'accent' => '#14532d', 'label' => __('Delivered')],
        'cancelled'   => ['border' => '#fecdd3', 'accent' => '#b91c1c', 'label' => __('Cancelled')],
        'refunded'    => ['border' => '#e9d5ff', 'accent' => '#6b21a8', 'label' => __('Refunded')],
        'completed'   => ['border' => '#bbf7d0', 'accent' => '#14532d', 'label' => __('Completed')],
        'approved'    => ['border' => '#bbf7d0', 'accent' => '#14532d', 'label' => __('Approved')],
        'rejected'    => ['border' => '#fecdd3', 'accent' => '#b91c1c', 'label' => __('Rejected')],
    ];
    $cfg   = $configs[$status] ?? ['border' => '#ebedf0', 'accent' => '#4a4e63', 'label' => ucfirst($status)];
    $label = $customLabel ?? $cfg['label'];
@endphp
{{-- v2 badge: 1px outline + monospace caps. No background fill — cleaner against the
     white card. In ar/ku the caps and tracking come off and the label runs in the
     Arabic sans stack, otherwise the status word arrives disjointed. --}}
<table role="presentation" cellpadding="0" cellspacing="0">
<tr>
    <td style="background:#ffffff;border:1px solid {{ $cfg['border'] }};border-radius:3px;padding:4px 10px;color:{{ $cfg['accent'] }};font-family:{{ EmailStyle::mono() }};font-size:{{ EmailStyle::isRtl() ? '11.5px' : '10px' }};font-weight:700;white-space:nowrap;{{ EmailStyle::tracking('1.5px') }}{{ EmailStyle::caps() }}">
        {{ $label }}
    </td>
</tr>
</table>
