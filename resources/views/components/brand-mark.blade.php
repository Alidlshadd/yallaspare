@props([
    'logoUrl' => null,
    'brand' => 'YallaSpare',
    'wrapperClass' => '',
    'imgClass' => '',
    'fallbackClass' => '',
    'fallbackTextClass' => '',
    'alt' => null,
    'src' => null,
])

@php
    $resolvedLogoUrl = $logoUrl ?: ($src ?: data_get($systemSettings ?? [], 'site_logo_url'));
    $resolvedAlt = (string) ($alt ?: 'Yalla Spare logo');
    $initials = \App\Support\Branding::initials((string) $brand);
@endphp

<span class="{{ $wrapperClass }}" data-brand-mark>
    @if(!empty($resolvedLogoUrl))
        <img
            src="{{ $resolvedLogoUrl }}"
            alt="{{ $resolvedAlt }}"
            class="{{ $imgClass }}"
            onerror="this.style.display='none'; const fallback=this.nextElementSibling; if(fallback){fallback.style.display='inline-flex';}"
        >
    @endif
    <span class="{{ $fallbackClass }}" @if(!empty($resolvedLogoUrl)) style="display:none" @endif>
        <span class="{{ $fallbackTextClass }}">{{ $initials }}</span>
    </span>
</span>
