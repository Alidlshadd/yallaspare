@extends('emails.layouts.base', [
    'preheader'      => $preheader ?? __('Your YallaSpare order has been updated.'),
    'recipientEmail' => $recipientEmail ?? null,
    'recipientName'  => $recipientName  ?? null,
    'specTag'        => 'ORD / STATUS',
])

@section('content')

    {{-- Kicker --}}
    <x-email-kicker :text="$eyebrow ?? __('Order update')" />

    {{-- Headline + optional status badge --}}
    <h1 class="em-title" style="margin:0;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:30px;line-height:35px;font-weight:700;letter-spacing:-0.6px;">
        {{ $title ?? __('Order status updated') }}
    </h1>

    @if (!empty($orderStatus))
    <div style="margin:14px 0 0;">
        @include('emails.components.status-badge', ['status' => $orderStatus])
    </div>
    @endif

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#4a4e63;font-size:15px;line-height:25px;">
        {{ $intro ?? $bodyText ?? '' }}
    </p>

    {{-- Meta grid --}}
    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])

    {{-- Order summary --}}
    @include('emails.components.order-summary', ['orderRows' => $orderRows ?? []])

    {{-- Totals --}}
    @if (!empty($totals))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 26px;">
        @foreach ($totals as $total)
        <tr class="em-totals-row">
            <td class="em-totals-label"
                style="padding:8px 0;border-top:{{ $loop->first ? '1px solid #ebedf0' : '0' }};{{ $loop->last ? 'border-top:1px solid #ebedf0;padding-top:14px;' : '' }}font-family:{{ $loop->last ? "'Space Grotesk','Inter',sans-serif" : "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif" }};color:{{ $loop->last ? '#070740' : '#4a4e63' }};font-size:{{ $loop->last ? '15px' : '13px' }};font-weight:{{ $loop->last ? '700' : '500' }};">
                {{ $total['label'] }}
            </td>
            <td class="em-totals-val" align="right"
                style="padding:8px 0;border-top:{{ $loop->first ? '1px solid #ebedf0' : '0' }};{{ $loop->last ? 'border-top:1px solid #ebedf0;padding-top:14px;' : '' }}font-family:'Space Grotesk','Inter',sans-serif;color:{{ $loop->last ? '#070740' : '#070740' }};font-size:{{ $loop->last ? '15px' : '14px' }};font-weight:700;white-space:nowrap;">
                <span dir="ltr" style="unicode-bidi:isolate;">{{ $total['value'] }}</span>
            </td>
        </tr>
        @endforeach
    </table>
    @endif

    {{-- CTA --}}
    @if (!empty($actionUrl))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:4px 0 8px;">
    <tr><td>
        @include('emails.components.button', [
            'url'   => $actionUrl,
            'label' => $actionText ?? __('View order'),
        ])
    </td></tr>
    </table>
    @endif

    {{-- Shipping address --}}
    @if (!empty($shippingAddress))
    @include('emails.components.alert', [
        'tone'    => 'info',
        'message' => __('Shipping to: :address', ['address' => $shippingAddress]),
    ])
    @endif

    {{-- Security notice --}}
    @include('emails.components.security-notice', [
        'message' => __('YallaSpare sends order updates to keep you informed. If this order was not placed by you, contact support@yallaspare.com immediately.'),
    ])

@endsection
