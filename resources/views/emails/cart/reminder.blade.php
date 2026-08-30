@extends('emails.layouts.base', [
    'preheader'      => $preheader ?? __('The parts you picked are still waiting in your cart.'),
    'recipientEmail' => $recipientEmail ?? null,
    'recipientName'  => $recipientName  ?? null,
    'specTag'        => 'CART / REMINDER',
])

@section('content')

    <x-email-kicker :text="$eyebrow ?? __('Your cart')" />

    <x-email-title :text="$title ?? __('You left something in your cart')" />

    <x-email-copy>
        {{ $intro ?? $bodyText ?? '' }}
    </x-email-copy>

    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])

    {{-- The same row block the order mails use, so a cart and the order it
         becomes are recognisably the same list. --}}
    @include('emails.components.order-summary', ['orderRows' => $orderRows ?? []])

    @if (!empty($totals))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ App\Support\EmailStyle::isRtl() ? 'rtl' : 'ltr' }}" style="margin:0 0 26px;">
        @foreach ($totals as $total)
        <tr class="em-totals-row">
            <td class="em-totals-label" align="{{ App\Support\EmailStyle::start() }}"
                style="padding:14px 0 8px;border-top:1px solid #ebedf0;font-family:{{ App\Support\EmailStyle::display() }};color:#070740;font-size:15px;font-weight:700;">
                {{ $total['label'] }}
            </td>
            <td class="em-totals-val" align="{{ App\Support\EmailStyle::end() }}"
                style="padding:14px 0 8px;border-top:1px solid #ebedf0;font-family:{{ App\Support\EmailStyle::display() }};color:#070740;font-size:15px;font-weight:700;white-space:nowrap;">
                <span dir="ltr" style="unicode-bidi:isolate;">{{ $total['value'] }}</span>
            </td>
        </tr>
        @endforeach
    </table>
    @endif

    @if (!empty($actionUrl))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:4px 0 8px;">
    <tr><td>
        @include('emails.components.button', [
            'url'   => $actionUrl,
            'label' => $actionText ?? __('Return to your cart'),
        ])
    </td></tr>
    </table>
    @endif

    {{-- Said plainly, because it is true and because it is the reason to come
         back today rather than next week. --}}
    @include('emails.components.alert', [
        'tone'    => 'info',
        'message' => __('Nothing in a cart is reserved. Parts sell out.'),
    ])

    @include('emails.components.security-notice', [
        'message' => __('You are receiving this because you allowed marketing messages. You can turn them off any time in your account settings.'),
    ])

@endsection
