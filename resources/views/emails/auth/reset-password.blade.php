@extends('emails.layouts.base', [
    'preheader'      => __('Reset your YallaSpare password using the secure link inside.'),
    'recipientEmail' => $email ?? null,
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#dc2626;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ __('Secure account action') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ __('Reset your password') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
        {{ __('We received a request to reset the password for your YallaSpare account. Click the button below to choose a new password.') }}
    </p>

    {{-- Meta info --}}
    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Account'), 'value' => $email ?? ''],
        ['label' => __('Expires'), 'value' => __('In :count minutes', ['count' => $expiresIn ?? 60])],
    ]])

    {{-- CTA Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0;">
    <tr><td>
        @include('emails.components.button', [
            'url'     => $actionUrl,
            'label'   => __('Reset Password'),
            'variant' => 'danger',
            'size'    => 'large',
        ])
    </td></tr>
    </table>

    {{-- Warning --}}
    @include('emails.components.alert', [
        'tone'    => 'warning',
        'message' => __('This link expires in :count minutes and can only be used once. Never forward this email or share this link with anyone.', ['count' => $expiresIn ?? 60]),
    ])

    {{-- Security notice --}}
    @include('emails.components.security-notice', [
        'message' => __('If you did not request a password reset, no action is required — your current password is unchanged. Consider enabling two-factor authentication for extra protection.'),
    ])

@endsection
