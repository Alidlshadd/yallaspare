@extends('emails.layouts.base', [
    'preheader'      => $preheader ?? __('A dealer account update from YallaSpare.'),
    'recipientEmail' => $recipientEmail ?? null,
    'specTag'        => 'DLR / UPDATE',
])

@section('content')

    {{-- Kicker --}}
    <x-email-kicker :text="__('Dealer notification')" />

    {{-- Headline --}}
    <x-email-title :text="$title ?? __('Dealer account update')" />

    {{-- Body copy --}}
    <x-email-copy>
        {!! nl2br(e($bodyText ?? '')) !!}
    </x-email-copy>

    {{-- Meta grid --}}
    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])

    {{-- Status badge (for approval/rejection emails) --}}
    @if (!empty($dealerStatus))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 0;">
    <tr><td>
        @include('emails.components.status-badge', [
            'status'      => $dealerStatus,
            'customLabel' => $statusLabel ?? null,
        ])
    </td></tr>
    </table>
    @endif

    {{-- CTA --}}
    @if (!empty($actionUrl))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 8px;">
    <tr><td>
        @include('emails.components.button', [
            'url'   => $actionUrl,
            'label' => $actionText ?? __('View dealer dashboard'),
        ])
    </td></tr>
    </table>
    @endif

    {{-- Security notice --}}
    @include('emails.components.security-notice', [
        'message' => __('This notification was sent to your registered dealer account email. Contact support@yallaspare.com if you have questions.'),
    ])

@endsection
