@extends('emails.layouts.base', [
    'preheader'      => $preheader ?? __('Your YallaSpare order has been updated.'),
    'recipientEmail' => $recipientEmail ?? null,
    'recipientName'  => $recipientName  ?? null,
])

@section('content')

    {{-- Eyebrow + Status badge --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 12px;">
    <tr>
        <td valign="middle">
            <p style="margin:0;color:#2563eb;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
                {{ $eyebrow ?? __('Order update') }}
            </p>
        </td>
        @if (!empty($orderStatus))
        <td align="right" valign="middle">
            @include('emails.components.status-badge', ['status' => $orderStatus])
        </td>
        @endif
    </tr>
    </table>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ $title ?? __('Order status updated') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
        {{ $intro ?? $bodyText ?? '' }}
    </p>

    {{-- Meta grid --}}
    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])

    {{-- Order summary --}}
    @include('emails.components.order-summary', ['orderRows' => $orderRows ?? []])

    {{-- Totals --}}
    @if (!empty($totals))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        @foreach ($totals as $total)
        <tr class="em-totals-row">
            <td class="em-totals-label"
                style="padding:12px 18px;border-top:{{ $loop->first ? '0' : '1px solid #e2e8f0' }};color:{{ $loop->last ? '#0f172a' : '#64748b' }};font-size:{{ $loop->last ? '15px' : '13px' }};font-weight:{{ $loop->last ? '800' : '600' }};">
                {{ $total['label'] }}
            </td>
            <td class="em-totals-val" align="right"
                style="padding:12px 18px;border-top:{{ $loop->first ? '0' : '1px solid #e2e8f0' }};color:{{ $loop->last ? '#070740' : '#0f172a' }};font-size:{{ $loop->last ? '17px' : '14px' }};font-weight:{{ $loop->last ? '900' : '700' }};white-space:nowrap;">
                {{ $total['value'] }}
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
