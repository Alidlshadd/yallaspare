@extends('emails.layouts.base', [
    'preheader'      => __('Your verification code is ready. Use it to confirm your YallaSpare account.'),
    'recipientEmail' => $email ?? null,
    'specTag'        => 'SYS / VERIFY',
])

@section('content')

    {{-- Kicker --}}
    <x-email-kicker :text="__('Account verification')" />

    {{-- Headline --}}
    <x-email-title :text="__('Verify your email address')" />

    {{-- Body copy --}}
    <x-email-copy>
        {{ __('Enter this verification code on the YallaSpare verification screen to protect your account and unlock checkout, order tracking, and account settings.') }}
    </x-email-copy>

    {{-- Meta info --}}
    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Account'),  'value' => $email ?? ''],
        ['label' => __('Expires'),  'value' => __(':count minutes', ['count' => $expiresIn ?? 60])],
        ['label' => __('Use once'), 'value' => __('This code becomes invalid after first use.')],
    ]])

    {{-- OTP Code --}}
    @include('emails.components.verification-code', ['code' => $verificationCode ?? ''])

    {{-- Expiry alert --}}
    @include('emails.components.alert', [
        'tone'    => 'info',
        'message' => __('This one-time code expires automatically. Do not share it with anyone, including YallaSpare support.'),
    ])

    {{-- Security notice --}}
    @include('emails.components.security-notice', [
        'message' => __('If you did not create a YallaSpare account, you can safely ignore this email. No account will be activated without verification.'),
    ])

@endsection
