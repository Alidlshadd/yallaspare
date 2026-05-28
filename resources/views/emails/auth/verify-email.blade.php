@extends('emails.layouts.base', [
    'preheader'      => __('Your verification code is ready. Use it to confirm your YallaSpare account.'),
    'recipientEmail' => $email ?? null,
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#4f46e5;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ __('Account verification') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ __('Verify your email address') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
        {{ __('Enter this verification code on the YallaSpare verification screen to protect your account and unlock checkout, order tracking, and account settings.') }}
    </p>

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
