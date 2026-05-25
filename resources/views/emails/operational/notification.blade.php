@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#2563eb;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ $eyebrow ?? __('YallaSpare notification') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ $title ?? $subjectLine }}</h1>
    <div class="email-copy" style="margin:14px 0 0;color:#475569;font-size:16px;line-height:26px;">
        {!! nl2br(e($bodyText ?? '')) !!}
    </div>

    @include('emails.components.meta-grid', ['items' => $metaItems ?? []])

    @if (!empty($actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
            <tr>
                <td>
                    @include('emails.components.button', ['url' => $actionUrl, 'label' => $actionText ?? __('Open YallaSpare')])
                </td>
            </tr>
        </table>
    @endif

    @include('emails.components.security-notice', [
        'message' => __('YallaSpare sends consistent transactional emails so you can recognize legitimate account and order messages.'),
    ])
@endsection
