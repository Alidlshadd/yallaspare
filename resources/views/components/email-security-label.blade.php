@props(['text'])

{{-- Security-only pre-headline used by reset-password, 2FA, security-alert emails.
     Red dot + caps text. Distinct from the long-form <security-notice> block. --}}
<p dir="ltr" style="margin:0 0 14px;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:10.5px;font-weight:700;color:#b91c1c;letter-spacing:2.5px;text-transform:uppercase;unicode-bidi:isolate;">
    <span style="display:inline-block;width:6px;height:6px;background:#b91c1c;border-radius:50%;vertical-align:middle;margin-right:8px;line-height:1;mso-hide:all;"></span>{{ $text }}
</p>
