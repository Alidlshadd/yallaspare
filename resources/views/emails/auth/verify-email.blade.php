@extends('emails.layouts.base', [
    'preheader'      => __('Your verification code is ready. Use it to confirm your YallaSpare account.'),
    'recipientEmail' => $email ?? null,
    'specTag'        => 'SYS / VERIFY',
])

@section('content')

    {{-- Kicker --}}
    <x-email-kicker :text="__('Account verification')" />

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:30px;line-height:35px;font-weight:700;letter-spacing:-0.6px;">
        {{ __('Verify your email address') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#4a4e63;font-size:15px;line-height:25px;">
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
