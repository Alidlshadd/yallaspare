@props([
    'logoUrl' => null,
    'brand' => 'YallaSpare',
    'wrapperClass' => '',
    'imgClass' => '',
    'fallbackClass' => '',
    'fallbackTextClass' => '',
    'alt' => null,
])

@php
    $resolvedAlt = (string) ($alt ?: 'Yalla Spare logo');
    $initials = \App\Support\Branding::initials((string) $brand);
@endphp

<span class="{{ $wrapperClass }}" data-brand-mark>
    @if(!empty($logoUrl))
        <img
            src="{{ $logoUrl }}"
            alt="{{ $resolvedAlt }}"
            class="{{ $imgClass }}"
            onerror="this.style.display='none'; const fallback=this.nextElementSibling; if(fallback){fallback.style.display='inline-flex';}"
        >
    @endif
    <span class="{{ $fallbackClass }}" @if(!empty($logoUrl)) style="display:none" @endif>
        <span class="{{ $fallbackTextClass }}">{{ $initials }}</span>
    </span>
</span>
