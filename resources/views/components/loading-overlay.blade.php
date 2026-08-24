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
    <div class="ys-loading-card">
        {{-- One decorative group: a screen reader gets the message below,
             not an inventory of rings. --}}
        <div class="ys-loading-core" aria-hidden="true">
            {{-- Drawn as an SVG stroke rather than a masked gradient: the arc
                 stays crisp at any size and rotates on one transform. --}}
            <svg class="ys-loading-orbit" viewBox="0 0 120 120">
                <circle class="ys-loading-orbit-track" cx="60" cy="60" r="56" />
                <circle class="ys-loading-orbit-arc" cx="60" cy="60" r="56" />
            </svg>

            {{-- The logo sits straight on the navy, not on a light plate: the
                 mark is white, and a plate behind it would erase it. --}}
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
    </div>
</div>
