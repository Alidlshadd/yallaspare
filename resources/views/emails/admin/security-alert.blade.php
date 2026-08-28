@extends('emails.layouts.base', [
    'preheader'      => __('Important security alert for your YallaSpare admin account.'),
    'recipientEmail' => $email ?? null,
    'specTag'        => 'SEC / ALERT',
])

@section('content')

    {{-- Security label --}}
    <x-email-security-label :text="__('Security alert')" />

    {{-- Headline --}}
    <x-email-title :text="$title ?? __('Admin security alert')" />

    {{-- Body copy --}}
    <x-email-copy>
        {!! nl2br(e($bodyText ?? '')) !!}
    </x-email-copy>

    @if (!empty($metaItems))
        @include('emails.components.meta-grid', ['items' => $metaItems])
    @endif

    {{-- Danger alert --}}
    @include('emails.components.alert', [
        'tone'    => 'danger',
        'message' => __('If you do not recognise this activity, change your admin password immediately and review active sessions.'),
    ])

    {{-- CTA — navy primary per spec. Red is reserved for the security label only. --}}
    @if (!empty($actionUrl))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
    <tr><td>
        @include('emails.components.button', [
            'url'   => $actionUrl,
            'label' => $actionText ?? __('Review account security'),
        ])
    </td></tr>
    </table>
    @endif

    {{-- Security notice --}}
    @include('emails.components.security-notice')

@endsection
