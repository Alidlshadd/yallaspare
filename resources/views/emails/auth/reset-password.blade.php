@extends('emails.layouts.base', [
    'preheader'      => __('Reset your YallaSpare password using the secure link inside.'),
    'recipientEmail' => $email ?? null,
    'specTag'        => 'SEC / RESET',
])

@section('content')

    {{-- Security label --}}
    <x-email-security-label :text="__('Secure account action')" />

    {{-- Headline --}}
    <x-email-title :text="__('Reset your password')" />

    {{-- Body copy --}}
    <x-email-copy>
        {{ __('We received a request to reset the password for your YallaSpare account. Click the button below to choose a new password.') }}
    </x-email-copy>

    {{-- Meta info --}}
    @include('emails.components.meta-grid', ['items' => [
        ['label' => __('Account'), 'value' => $email ?? ''],
        ['label' => __('Expires'), 'value' => __('In :count minutes', ['count' => $expiresIn ?? 60])],
    ]])

    {{-- CTA Button — navy primary per spec. Red is reserved for the security label only. --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0;">
    <tr><td>
        @include('emails.components.button', [
            'url'   => $actionUrl,
            'label' => __('Reset Password'),
            'size'  => 'large',
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
