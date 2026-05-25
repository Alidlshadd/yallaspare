@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#dc2626;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ __('High security verification') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ __('Admin verification required') }}</h1>
    <p class="email-copy" style="margin:14px 0 0;color:#475569;font-size:16px;line-height:26px;">
        {{ __('Use this one-time code to complete your admin sign-in. This code should only be used inside the YallaSpare admin panel.') }}
    </p>

    @include('emails.components.verification-code', ['code' => $code])

    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Expires'), 'value' => __(':count minutes', ['count' => $ttlMinutes ?? 10])],
        ['label' => __('Account'), 'value' => $email ?? ''],
    ]])

    @include('emails.components.alert', [
        'tone' => 'danger',
        'message' => __('If this sign-in was not yours, change your password and review admin access immediately.'),
    ])

    @include('emails.components.security-notice', [
        'message' => __('YallaSpare staff will never ask for this code. Do not share it by phone, chat, email, or screenshot.'),
    ])
@endsection
