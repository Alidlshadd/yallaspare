@extends('emails.layouts.base', [
    'preheader'      => $preheader ?? ($subjectLine ?? __('A notification from YallaSpare.')),
    'recipientEmail' => $recipientEmail ?? null,
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#2563eb;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ $eyebrow ?? __('YallaSpare notification') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ $title ?? $subjectLine }}
    </h1>

    {{-- Body copy --}}
    <div class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
        {!! nl2br(e($bodyText ?? '')) !!}
    </div>

    {{-- Meta grid --}}
    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])

    {{-- CTA --}}
    @if (!empty($actionUrl))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 8px;">
    <tr><td>
        @include('emails.components.button', [
            'url'   => $actionUrl,
            'label' => $actionText ?? __('Open YallaSpare'),
        ])
    </td></tr>
    </table>
    @endif

    {{-- Security notice --}}
    @include('emails.components.security-notice', [
        'message' => __('YallaSpare sends consistent transactional emails so you can recognise legitimate account and order messages.'),
    ])

@endsection
