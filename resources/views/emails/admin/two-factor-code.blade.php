@extends('emails.layouts.base', [
    'preheader'      => __('Your admin two-factor authentication code is ready.'),
    'recipientEmail' => $email ?? null,
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#dc2626;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ __('High security verification') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ __('Admin sign-in code') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
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
