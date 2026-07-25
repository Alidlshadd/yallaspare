@props([
    'storage' => 'user-theme',
    'persistUrl' => null,
    'size' => 'md',
])

@php
    // The shell height is pinned so the switch lines up with the 36px chips
    // next to it; 'auto' follows the header chips that shrink below sm.
    $shellClasses = $size === 'auto' ? 'h-8 rounded-lg sm:h-9 sm:rounded-xl' : 'h-9 rounded-xl';
    $buttonClasses = $size === 'auto' ? 'h-6 w-6 rounded-md sm:h-7 sm:w-7 sm:rounded-lg' : 'h-7 w-7 rounded-lg';
    $iconClasses = $size === 'auto' ? 'h-3.5 w-3.5 sm:h-4 sm:w-4' : 'h-4 w-4';
    // Both headers stay navy in either theme, so the active knob is pinned to
    // literal white/navy — the dark-mode `.bg-white` and `.text-primary`
    // surface overrides in app.css would otherwise wash it out.
    $stateClasses = 'inline-flex items-center justify-center text-white/65 transition duration-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/30 aria-pressed:bg-[#ffffff] aria-pressed:text-[#070740] aria-pressed:shadow-sm';
@endphp

<div
    {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-0.5 border border-white/10 bg-white/10 p-0.5 ' . $shellClasses]) }}
    role="group"
    aria-label="{{ __('Toggle dark mode') }}"
    data-theme-toggle
    data-theme-storage="{{ $storage }}"
    @if ($persistUrl) data-theme-persist="{{ $persistUrl }}" @endif
>
    <button
        type="button"
        data-theme-value="light"
        aria-pressed="false"
        aria-label="{{ __('Light mode') }}"
        title="{{ __('Light mode') }}"
        class="{{ $buttonClasses }} {{ $stateClasses }}"
    >
        <svg class="{{ $iconClasses }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path stroke-linecap="round" d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
    </button>

    <button
        type="button"
        data-theme-value="dark"
        aria-pressed="false"
        aria-label="{{ __('Dark mode') }}"
        title="{{ __('Dark mode') }}"
        class="{{ $buttonClasses }} {{ $stateClasses }}"
    >
        <svg class="{{ $iconClasses }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
        </svg>
    </button>
</div>
