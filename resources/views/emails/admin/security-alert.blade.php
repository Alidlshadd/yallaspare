@extends('emails.layouts.base', [
    'preheader'      => __('Important security alert for your YallaSpare admin account.'),
    'recipientEmail' => $email ?? null,
    'specTag'        => 'SEC / ALERT',
])

@section('content')

    {{-- Security label --}}
    <x-email-security-label :text="__('Security alert')" />

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#070740;font-size:30px;line-height:35px;font-weight:700;letter-spacing:-0.6px;">
        {{ $title ?? __('Admin security alert') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#4a4e63;font-size:15px;line-height:25px;">
        {!! nl2br(e($bodyText ?? '')) !!}
    </p>

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
