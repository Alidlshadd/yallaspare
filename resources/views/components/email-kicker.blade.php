@props(['text'])

@php
    use App\Support\EmailStyle;
    $rtl = EmailStyle::isRtl();
@endphp

{{-- Pre-headline label used across transactional emails (Welcome, Order Status, Verify).
     Monospace caps, muted navy-grey. Replaces ad-hoc <p style="color:#...;..."> patterns.
     In ar/ku it drops to the Arabic sans stack with no tracking or caps — monospace
     carries no Arabic glyphs and letter-spacing breaks the cursive joins. --}}
<p class="em-kicker" dir="{{ $rtl ? 'rtl' : 'ltr' }}" style="margin:0 0 14px;font-family:{{ EmailStyle::mono() }};font-size:{{ $rtl ? '11.5px' : '10.5px' }};font-weight:700;color:#8a8ea3;{{ EmailStyle::tracking('2.2px') }}{{ EmailStyle::caps() }}unicode-bidi:isolate;">
    {{ $text }}
</p>
