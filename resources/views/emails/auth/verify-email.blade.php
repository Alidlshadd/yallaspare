@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#2563eb;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ __('Account verification') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ __('Verify your email address') }}</h1>
    <p class="email-copy" style="margin:14px 0 0;color:#475569;font-size:16px;line-height:26px;">
        {{ __('Enter this verification code on the YallaSpare verification screen to protect your account and unlock checkout, orders, saved addresses, and account settings.') }}
    </p>

    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Account'), 'value' => $email ?? ''],
        ['label' => __('Expires'), 'value' => __(':count minutes', ['count' => $expiresIn ?? 60])],
    ]])

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;width:100%;">
        <tr>
            <td style="border:1px solid #dbeafe;background:#eff6ff;border-radius:16px;padding:22px;text-align:center;">
                <p style="margin:0 0 10px;color:#2563eb;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ __('Verification code') }}</p>
                <p style="margin:0;color:#070740;font-size:34px;line-height:42px;font-weight:900;letter-spacing:8px;">{{ $verificationCode ?? '' }}</p>
            </td>
        </tr>
    </table>

    @include('emails.components.alert', [
        'tone' => 'info',
        'message' => __('This one-time code expires automatically to protect your session.'),
    ])

    @include('emails.components.security-notice', [
        'message' => __('If you did not create a YallaSpare account, ignore this message. No account access is granted unless this email is verified.'),
    ])
@endsection
