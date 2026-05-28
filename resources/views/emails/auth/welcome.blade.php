@extends('emails.layouts.base', [
    'preheader'      => __('Welcome to YallaSpare — your account is ready.'),
    'recipientEmail' => $email ?? null,
    'recipientName'  => $name  ?? null,
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#16a34a;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ __('Account created') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ __('Welcome to YallaSpare') }}{{ !empty($name) ? ', ' . e($name) : '' }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
        {{ __('Your account is ready. You can now browse thousands of auto parts, place orders, track deliveries, and manage your account — all in one place.') }}
    </p>

    {{-- Feature highlights --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
        @foreach ([
            ['icon' => '&#x1F6CD;', 'title' => __('Shop parts'), 'desc' => __('Thousands of genuine & aftermarket parts.')],
            ['icon' => '&#x1F4E6;', 'title' => __('Track orders'), 'desc' => __('Real-time updates from warehouse to door.')],
            ['icon' => '&#x1F512;', 'title' => __('Secure checkout'), 'desc' => __('Protected payments and order history.')],
        ] as $i => $feat)
        <tr>
            <td style="padding:14px 18px;border-bottom:{{ $i === 2 ? '0' : '1px solid #e2e8f0' }};">
                <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                <td valign="middle" style="font-size:22px;padding-right:14px;">{!! $feat['icon'] !!}</td>
                <td valign="middle">
                    <span style="display:block;color:#0f172a;font-size:14px;font-weight:700;">{{ $feat['title'] }}</span>
                    <span style="display:block;color:#64748b;font-size:13px;margin-top:2px;">{{ $feat['desc'] }}</span>
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
