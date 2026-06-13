@props([
    'message' => null,
    'variant' => 'compact',
    'showLogo' => true,
])

@php
    $variant = in_array($variant, ['compact', 'full'], true) ? $variant : 'compact';
    $brand = (string) ($systemSettings['site_name'] ?? 'YallaSpare');
    $messageText = (string) ($message ?: __('Loading'));
@endphp

<div
    {{ $attributes->merge(['class' => 'ys-loading-overlay ys-loading-overlay-' . $variant . ' is-hidden']) }}
    data-loading-overlay
    role="status"
    aria-live="polite"
    aria-hidden="true"
>
    <div class="ys-loading-card">
        @if ($showLogo)
            <div class="ys-loading-brand">
                <x-brand-mark
                    :logo-url="$systemSettings['site_logo_url'] ?? null"
                    :brand="$brand"
                    wrapper-class="ys-loading-logo"
                    img-class="ys-loading-logo-image"
                    fallback-class="ys-loading-logo-fallback"
                    fallback-text-class="ys-loading-logo-fallback-text"
                    :alt="$brand . ' logo'"
                />
                <span class="ys-loading-brand-name">{{ $brand }}</span>
            </div>
        @endif

        <div class="ys-loading-ring" aria-hidden="true"></div>
        <p class="ys-loading-message" data-loading-message>{{ $messageText }}</p>
        <span class="sr-only">{{ __('Loading') }}</span>
    </div>
</div>
