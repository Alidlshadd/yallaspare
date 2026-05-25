@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#2563eb;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ $eyebrow ?? __('Order update') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ $title ?? __('Order status updated') }}</h1>
    <p class="email-copy" style="margin:14px 0 0;color:#475569;font-size:16px;line-height:26px;">{{ $intro ?? $bodyText ?? '' }}</p>

    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])
    @include('emails.components.order-summary', ['orderRows' => $orderRows ?? []])

    @if (!empty($totals))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
            @foreach ($totals as $total)
                <tr>
                    <td style="padding:11px 16px;border-bottom:{{ $loop->last ? '0' : '1px solid #e2e8f0' }};color:#64748b;font-size:13px;font-weight:700;">{{ $total['label'] }}</td>
                    <td align="right" style="padding:11px 16px;border-bottom:{{ $loop->last ? '0' : '1px solid #e2e8f0' }};color:#0f172a;font-size:14px;font-weight:900;">{{ $total['value'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if (!empty($actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
            <tr>
                <td>
                    @include('emails.components.button', ['url' => $actionUrl, 'label' => $actionText ?? __('View order')])
                </td>
            </tr>
        </table>
    @endif

    @if (!empty($shippingAddress))
        @include('emails.components.alert', [
            'tone' => 'info',
            'message' => __('Shipping address: :address', ['address' => $shippingAddress]),
        ])
    @endif
@endsection
