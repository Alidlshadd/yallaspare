@props([
    'storage' => 'user-theme',
    'persistUrl' => null,
    'size' => 'md',
])

@php
    // Sized to line up with the 36px chips beside it; 'auto' follows the header
    // chips that shrink below the sm breakpoint.
    $shellClasses = $size === 'auto' ? 'h-8 w-8 rounded-lg sm:h-9 sm:w-9 sm:rounded-xl' : 'h-9 w-9 rounded-xl';
    $iconClasses = $size === 'auto' ? 'h-4 w-4 sm:h-[1.05rem] sm:w-[1.05rem]' : 'h-[1.05rem] w-[1.05rem]';
@endphp

<button
    type="button"
    {{ $attributes->merge(['class' => 'theme-switch inline-flex shrink-0 items-center justify-center border border-white/10 bg-white/10 text-white/80 transition duration-200 hover:bg-white/15 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25 ' . $shellClasses]) }}
    aria-label="{{ __('Toggle dark mode') }}"
    aria-pressed="false"
    title="{{ __('Dark mode') }}"
    data-theme-toggle
    data-theme-storage="{{ $storage }}"
    data-theme-label-dark="{{ __('Dark mode') }}"
    data-theme-label-light="{{ __('Light mode') }}"
    @if ($persistUrl) data-theme-persist="{{ $persistUrl }}" @endif
>
    {{-- data-theme-icon-for names the theme the icon belongs to: the moon is on
         screen while the page is light, hinting at what a click switches to.
         Both sit in the same slot and CSS cross-fades them on aria-pressed. --}}
    <svg data-theme-icon-for="light" class="theme-switch-icon {{ $iconClasses }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
    </svg>

    <svg data-theme-icon-for="dark" class="theme-switch-icon {{ $iconClasses }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="4" />
        <path stroke-linecap="round" d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
    </svg>
</button>
