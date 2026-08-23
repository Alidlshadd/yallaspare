@props([
    'message' => null,
    'variant' => 'compact',
    'showLogo' => true,
])

@php
    $variant = in_array($variant, ['compact', 'full'], true) ? $variant : 'compact';
    $variantClass = $variant === 'full' ? 'ys-loading-overlay-full' : 'ys-loading-overlay-compact';
    $brand = (string) ($systemSettings['site_name'] ?? 'YallaSpare');
    $messageText = (string) ($message ?: __('Preparing your workspace'));
@endphp

<div
    {{ $attributes->merge(['class' => 'ys-loading-overlay ' . $variantClass . ' is-hidden']) }}
    data-loading-overlay
    role="status"
    aria-live="polite"
    aria-hidden="true"
>
    <div class="ys-loading-grid" aria-hidden="true"></div>

    <div class="ys-loading-card">
        {{-- The rings are the object here, so they are one decorative group:
             a screen reader gets the message below, not a ring inventory. --}}
        <div class="ys-loading-core" aria-hidden="true">
            <span class="ys-loading-ring"></span>
            <span class="ys-loading-ring ys-loading-ring-inner"></span>
            <span class="ys-loading-sweep"></span>

            <span class="ys-loading-badge">
                @if ($showLogo)
                    <x-brand-mark
                        :logo-url="$systemSettings['site_logo_url'] ?? null"
                        :brand="$brand"
                        wrapper-class="ys-loading-logo"
                        img-class="ys-loading-logo-image"
                        fallback-class="ys-loading-logo-fallback"
                        fallback-text-class="ys-loading-logo-fallback-text"
                        :alt="$brand . ' logo'"
                    />
                @else
                    <span class="ys-loading-logo-fallback">
                        <span class="ys-loading-logo-fallback-text">YS</span>
                    </span>
                @endif
            </span>
        </div>

        <div class="ys-loading-copy">
            <span class="ys-loading-brand-name">{{ $brand }}</span>
            {{-- Visible, not sr-only: the wait is what the screen is for, and
                 the JS retargets this node when it swaps the message. --}}
            <p class="ys-loading-message" data-loading-message>{{ $messageText }}</p>
        </div>

        <div class="ys-loading-track" aria-hidden="true">
            <span class="ys-loading-bar"></span>
        </div>

        <span class="ys-loading-tag" aria-hidden="true">{{ __('Secure access') }}</span>
    </div>
</div>
