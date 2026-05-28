@extends('emails.layouts.base', [
    'preheader'      => __('Important security alert for your YallaSpare admin account.'),
    'recipientEmail' => $email ?? null,
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#dc2626;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ __('Security alert') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ $title ?? __('Admin security alert') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
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

    @if (!empty($actionUrl))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
    <tr><td>
        @include('emails.components.button', [
            'url'     => $actionUrl,
            'label'   => $actionText ?? __('Review account security'),
            'variant' => 'danger',
        ])
    </td></tr>
    </table>
    @endif

    {{-- Security notice --}}
    @include('emails.components.security-notice')

@endsection
