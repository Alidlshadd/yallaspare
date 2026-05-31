@extends('emails.layouts.base', [
    'preheader'      => __('Your admin two-factor authentication code is ready.'),
    'recipientEmail' => $email ?? null,
    'specTag'        => 'SEC / 2FA',
])

@section('content')

    {{-- Security label --}}
    <x-email-security-label :text="__('High security verification')" />

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:30px;line-height:35px;font-weight:700;letter-spacing:-0.6px;">
        {{ __('Admin sign-in code') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#4a4e63;font-size:15px;line-height:25px;">
        {{ __('Use this one-time code to complete your sign-in to the YallaSpare admin panel. This code is only valid for this session.') }}
    </p>

    {{-- OTP Code --}}
    @include('emails.components.verification-code', ['code' => $code])

    {{-- Meta info — empty values are filtered out by the meta-grid component. --}}
    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Account'), 'value' => $email ?? ''],
        ['label' => __('Expires'), 'value' => __('In :count minutes', ['count' => $ttlMinutes ?? 10])],
    ]])

    {{-- Danger alert --}}
    @include('emails.components.alert', [
        'tone'    => 'danger',
        'message' => __('YallaSpare staff will NEVER ask for this code by phone, chat, or email. If you receive such a request, it is a scam.'),
    ])

    {{-- Security notice --}}
    @include('emails.components.security-notice', [
        'message' => __('If you did not attempt to sign in to the admin panel, change your password immediately and contact your system administrator.'),
    ])

@endsection
