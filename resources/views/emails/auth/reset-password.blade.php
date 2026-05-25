@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#b45309;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ __('Secure account action') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ __('Reset your password') }}</h1>
    <p class="email-copy" style="margin:14px 0 0;color:#475569;font-size:16px;line-height:26px;">
        {{ __('We received a request to reset the password for your YallaSpare account. Use the secure button below to continue.') }}
    </p>

    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Account'), 'value' => $email ?? ''],
        ['label' => __('Expires'), 'value' => __(':count minutes', ['count' => $expiresIn ?? 60])],
    ]])

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
        <tr>
            <td>
                @include('emails.components.button', ['url' => $actionUrl, 'label' => __('Reset Password')])
            </td>
        </tr>
    </table>

    @include('emails.components.alert', [
        'tone' => 'warning',
        'message' => __('Password reset links expire automatically. Do not forward this email or share this link.'),
    ])

    @include('emails.components.security-notice', [
        'message' => __('If you did not request a password reset, no further action is required. Your current password remains unchanged.'),
    ])
@endsection
