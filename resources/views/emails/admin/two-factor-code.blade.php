@extends('emails.layouts.base', [
    'preheader'      => __('Your admin two-factor authentication code is ready.'),
    'recipientEmail' => $email ?? null,
    'specTag'        => 'SEC / 2FA',
])

@section('content')

    {{-- Security label --}}
    <x-email-security-label :text="__('High security verification')" />

    {{-- Headline --}}
    <x-email-title :text="__('Admin sign-in code')" />

    {{-- Body copy --}}
    <x-email-copy>
        {{ __('Use this one-time code to complete your sign-in to the YallaSpare admin panel. This code is only valid for this session.') }}
    </x-email-copy>

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
