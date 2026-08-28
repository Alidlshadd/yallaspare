@extends('emails.layouts.base', [
    'preheader'      => $preheader ?? ($subjectLine ?? __('A notification from YallaSpare.')),
    'recipientEmail' => $recipientEmail ?? null,
    'specTag'        => 'SYS / NOTICE',
])

@section('content')

    {{-- Kicker --}}
    <x-email-kicker :text="$eyebrow ?? __('YallaSpare notification')" />

    {{-- Headline --}}
    <x-email-title :text="$title ?? $subjectLine" />

    {{-- Body copy --}}
    <x-email-copy>
        {!! nl2br(e($bodyText ?? '')) !!}
    </x-email-copy>

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
