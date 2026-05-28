@extends('emails.layouts.base', [
    'preheader' => __('A new support request has been submitted via YallaSpare.'),
])

@section('content')

    {{-- Eyebrow --}}
    <p style="margin:0 0 10px;color:#7c3aed;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2.2px;">
        {{ __('Support request') }}
    </p>

    {{-- Headline --}}
    <h1 class="em-title" style="margin:0;color:#0f172a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">
        {{ __('New support request') }}
    </h1>

    {{-- Intro --}}
    <p class="em-copy" style="margin:16px 0 0;color:#475569;font-size:16px;line-height:27px;">
        {{ __('A visitor submitted a support request through the YallaSpare contact form. Details are below.') }}
    </p>

    {{-- Contact details --}}
    @include('emails.components.meta-grid', ['items' => array_filter([
        ['label' => __('Name'),    'value' => $name    ?? ''],
        ['label' => __('Email'),   'value' => $email   ?? ''],
        !empty($phone)   ? ['label' => __('Phone'),   'value' => $phone]   : null,
        !empty($topic)   ? ['label' => __('Topic'),   'value' => $topic]   : null,
        ['label' => __('Subject'), 'value' => $requestSubject ?? ''],
    ])])

    {{-- Message body --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:4px 0 0;">
    <tr>
        <td style="padding:14px 12px 6px;">
            <p style="margin:0 0 8px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.9px;">
                {{ __('Message') }}
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:0 0 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:18px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;color:#334155;font-size:15px;line-height:25px;">
                    {!! nl2br(e($messageText ?? '')) !!}
                </td>
            </tr>
            </table>
        </td>
    </tr>
    </table>

    {{-- Reply instruction --}}
    @include('emails.components.alert', [
        'tone'    => 'info',
        'message' => __('Reply directly to this email to respond to the customer — the reply-to address is set to their email.'),
    ])

@endsection
