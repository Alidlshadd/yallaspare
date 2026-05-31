@extends('emails.layouts.base', [
    'preheader'      => __('Welcome to YallaSpare — your account is ready.'),
    'recipientEmail' => $email ?? null,
    'recipientName'  => $name  ?? null,
    'specTag'        => 'SYS / WELCOME',
])

@section('content')

    {{-- Kicker --}}
    <x-email-kicker :text="__('Account created')" />

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:30px;line-height:35px;font-weight:700;letter-spacing:-0.6px;">
        {{ __('Welcome to YallaSpare') }}{{ !empty($name) ? ', ' . e($name) : '' }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#4a4e63;font-size:15px;line-height:25px;">
        {{ __('Your account is ready. You can now browse thousands of auto parts, place orders, track deliveries, and manage your account — all in one place.') }}
    </p>

    {{-- Feature highlights --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:26px 0;border-top:1px solid #ebedf0;border-bottom:1px solid #ebedf0;">
        @foreach ([
            ['icon' => '&#x1F6CD;', 'title' => __('Shop parts'), 'desc' => __('Thousands of genuine & aftermarket parts.')],
            ['icon' => '&#x1F4E6;', 'title' => __('Track orders'), 'desc' => __('Real-time updates from warehouse to door.')],
            ['icon' => '&#x1F512;', 'title' => __('Secure checkout'), 'desc' => __('Protected payments and order history.')],
        ] as $i => $feat)
        <tr>
            <td style="padding:14px 0;border-bottom:{{ $i === 2 ? '0' : '1px solid #ebedf0' }};">
                <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                <td valign="middle" style="font-size:18px;padding-right:14px;width:30px;">{!! $feat['icon'] !!}</td>
                <td valign="middle">
                    <span style="display:block;font-family:'Space Grotesk','Inter',sans-serif;color:#070740;font-size:13.5px;font-weight:700;">{{ $feat['title'] }}</span>
                    <span style="display:block;color:#64748b;font-size:12.5px;margin-top:2px;">{{ $feat['desc'] }}</span>
                </td>
                </tr></table>
            </td>
        </tr>
        @endforeach
    </table>

    {{-- CTA --}}
    @if (!empty($actionUrl))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 4px;">
    <tr><td>
        @include('emails.components.button', [
            'url'   => $actionUrl,
            'label' => $actionText ?? __('Open Your Account'),
            'size'  => 'large',
        ])
    </td></tr>
    </table>
    @endif

@endsection
