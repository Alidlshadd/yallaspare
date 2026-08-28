@props(['text'])

@php
    use App\Support\EmailStyle;
@endphp

{{-- The one headline style for every transactional email: navy, display face,
     tight tracking. Was copy-pasted into ten templates, four of which never got
     the navy rebrand and were still slate #0f172a in the body font.

     Space Grotesk has no Arabic glyphs and the negative tracking pulls a cursive
     script apart, so ar/ku fall back to the Arabic sans stack with tracking off. --}}
<h1 class="em-title" style="margin:0;font-family:{{ EmailStyle::display() }};color:#070740;font-size:30px;line-height:{{ EmailStyle::isRtl() ? '42px' : '35px' }};font-weight:700;{{ EmailStyle::tracking('-0.6px') }}">
    {{ $text }}
</h1>
