@props(['text'])

@php
    use App\Support\EmailStyle;
    $rtl = EmailStyle::isRtl();
@endphp

{{-- Security-only pre-headline used by reset-password, 2FA, security-alert emails.
     Red dot + caps text. Distinct from the long-form <security-notice> block.
     The dot's gap mirrors with the text direction so it always leads the label. --}}
<p class="em-sec-label" dir="{{ $rtl ? 'rtl' : 'ltr' }}" style="margin:0 0 14px;font-family:{{ EmailStyle::mono() }};font-size:{{ $rtl ? '11.5px' : '10.5px' }};font-weight:700;color:#b91c1c;{{ EmailStyle::tracking('2.5px') }}{{ EmailStyle::caps() }}unicode-bidi:isolate;">
    <span class="em-sec-dot" style="display:inline-block;width:6px;height:6px;background:#b91c1c;border-radius:50%;vertical-align:middle;margin-{{ EmailStyle::end() }}:8px;line-height:1;mso-hide:all;"></span>{{ $text }}
</p>
