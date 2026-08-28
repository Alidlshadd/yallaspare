@php
    use App\Support\EmailStyle;
@endphp

{{-- Lead paragraph under the headline. A <div> rather than a <p> so callers can
     pass nl2br'd operator text through the slot without nesting block elements.

     Arabic and Sorani sit higher in the em box than Latin, so ar/ku get a slightly
     looser line-height — 25px leaves the ascenders of ك and گ touching the line above. --}}
<div class="em-copy" style="margin:16px 0 0;font-family:{{ EmailStyle::sans() }};color:#4a4e63;font-size:15px;line-height:{{ EmailStyle::isRtl() ? '29px' : '25px' }};">
    {{ $slot }}
</div>
