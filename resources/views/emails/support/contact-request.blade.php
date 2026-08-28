@extends('emails.layouts.base', [
    'preheader' => __('A new support request has been submitted via YallaSpare.'),
    'specTag'   => 'SUP / REQUEST',
])

@section('content')

    {{-- Kicker --}}
    <x-email-kicker :text="__('Support request')" />

    {{-- Headline --}}
    <x-email-title :text="__('New support request')" />

    {{-- Intro --}}
    <x-email-copy>
        {{ __('A visitor submitted a support request through the YallaSpare contact form. Details are below.') }}
    </x-email-copy>

    {{-- Contact details --}}
    @include('emails.components.meta-grid', ['items' => array_filter([
        ['label' => __('Name'),    'value' => $name    ?? ''],
        ['label' => __('Email'),   'value' => $email   ?? ''],
        !empty($phone)   ? ['label' => __('Phone'),   'value' => $phone]   : null,
        !empty($topic)   ? ['label' => __('Topic'),   'value' => $topic]   : null,
        ['label' => __('Subject'), 'value' => $requestSubject ?? ''],
    ])])

    {{-- Message body — inset card on the v2 hairline palette (#fafbfc / #ebedf0,
         4px radius), matching <security-notice>. It was on the pre-rebrand slate
         card with a 14px radius. The visitor's text is whatever language they
         typed, so it keeps its own direction rather than the email's. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0 8px;">
    <tr>
        <td class="em-sec-notice" align="{{ App\Support\EmailStyle::start() }}" style="padding:16px 18px;background:#fafbfc;border:1px solid #ebedf0;border-radius:4px;">
            <p class="em-sec-title" style="margin:0 0 6px;font-family:{{ App\Support\EmailStyle::mono() }};color:#9aa0b5;font-size:{{ App\Support\EmailStyle::isRtl() ? '11.5px' : '10px' }};font-weight:700;{{ App\Support\EmailStyle::tracking('1.8px') }}{{ App\Support\EmailStyle::caps() }}">
                {{ __('Message') }}
            </p>
            <div class="em-sec-text" dir="{{ preg_match('/\p{Arabic}/u', (string) ($messageText ?? '')) ? 'rtl' : 'ltr' }}" style="font-family:{{ App\Support\EmailStyle::sans() }};color:#4a4e63;font-size:14px;line-height:24px;unicode-bidi:isolate;">
                {!! nl2br(e($messageText ?? '')) !!}
            </div>
        </td>
    </tr>
    </table>

    {{-- Reply instruction --}}
    @include('emails.components.alert', [
        'tone'    => 'info',
        'message' => __('Reply directly to this email to respond to the customer — the reply-to address is set to their email.'),
    ])

@endsection
