@extends('emails.layouts.base', [
    'preheader'      => __('Reset your YallaSpare password using the secure link inside.'),
    'recipientEmail' => $email ?? null,
    'specTag'        => 'SEC / RESET',
])

@section('content')

    {{-- Security label --}}
    <x-email-security-label :text="__('Secure account action')" />

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:30px;line-height:35px;font-weight:700;letter-spacing:-0.6px;">
        {{ __('Reset your password') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#4a4e63;font-size:15px;line-height:25px;">
        {{ __('We received a request to reset the password for your YallaSpare account. Click the button below to choose a new password.') }}
    </p>

    {{-- Meta info --}}
    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Account'), 'value' => $email ?? ''],
        ['label' => __('Expires'), 'value' => __('In :count minutes', ['count' => $expiresIn ?? 60])],
    ]])

    {{-- CTA Button (variant='danger' explicitly: this is a security flow, red is correct) --}}
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
