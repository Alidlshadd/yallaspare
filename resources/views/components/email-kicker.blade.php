@props(['text'])

{{-- Pre-headline label used across transactional emails (Welcome, Order Status, Verify).
     Monospace caps, muted navy-grey. Replaces ad-hoc <p style="color:#...;..."> patterns. --}}
<p dir="ltr" style="margin:0 0 14px;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:10.5px;font-weight:700;color:#8a8ea3;letter-spacing:2.2px;text-transform:uppercase;unicode-bidi:isolate;">
    {{ $text }}
</p>
