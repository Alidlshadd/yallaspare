@extends('emails.layouts.base', [
    'preheader'      => $preheader ?? __('A dealer account update from YallaSpare.'),
    'recipientEmail' => $recipientEmail ?? null,
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#0891b2;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ __('Dealer notification') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ $title ?? __('Dealer account update') }}
    </h1>

    {{-- Body copy --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
        {!! nl2br(e($bodyText ?? '')) !!}
    </p>

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
