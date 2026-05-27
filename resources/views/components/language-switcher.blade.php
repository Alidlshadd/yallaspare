@props([
    'variant' => 'light',
])

@php
    $currentLocale = app()->getLocale();
    $isRtl = in_array($currentLocale, ['ar', 'ku'], true);
    $locales = [
        'en' => ['label' => 'English', 'code' => 'EN'],
        'ar' => ['label' => "\u{0627}\u{0644}\u{0639}\u{0631}\u{0628}\u{064A}\u{0629}", 'code' => 'AR'],
        'ku' => ['label' => "\u{06A9}\u{0648}\u{0631}\u{062F}\u{06CC}", 'code' => 'KU'],
    ];
    $currentLabel = $locales[$currentLocale] ?? $locales['en'];
    $triggerClasses = $variant === 'dark'
        ? 'border border-white/10 bg-white/10 text-white hover:bg-white/15 focus-visible:ring-white/25'
        : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus-visible:ring-slate-300';
    $menuClasses = $variant === 'dark'
        ? 'border border-slate-200/80 bg-white text-slate-900 shadow-2xl shadow-slate-900/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:shadow-black/30'
        : 'border border-slate-200 bg-white text-slate-900 shadow-xl shadow-slate-900/10';
    $itemClasses = 'flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium transition duration-200 hover:bg-slate-100 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:hover:bg-slate-900 dark:hover:text-white';
@endphp

<div
    {{ $attributes->merge(['class' => 'relative inline-flex']) }}
    data-header-dropdown
>
    <button
        type="button"
        class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2 text-xs font-medium transition duration-200 focus-visible:outline-none focus-visible:ring-2 sm:h-9 sm:gap-2 sm:rounded-xl sm:px-3 sm:text-sm {{ $triggerClasses }}"
        data-header-dropdown-trigger
        aria-expanded="false"
        aria-haspopup="menu"
        aria-label="{{ __('Language') }}"
    >
        <span class="hidden min-[380px]:inline">{{ __($currentLabel['label']) }}</span>
        <span class="text-xs font-semibold uppercase opacity-70">{{ $currentLabel['code'] }}</span>
        <svg class="h-4 w-4 opacity-70 transition-transform" data-header-dropdown-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.126l3.71-3.895a.75.75 0 1 1 1.08 1.04l-4.25 4.46a.75.75 0 0 1-1.08 0l-4.25-4.46a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        data-header-dropdown-menu
        class="absolute {{ $isRtl ? 'left-0' : 'right-0' }} top-full z-50 mt-2 hidden w-44 rounded-2xl p-2 {{ $menuClasses }}"
        role="menu"
        aria-label="{{ __('Language') }}"
    >
        @foreach ($locales as $locale => $language)
            <form method="POST" action="{{ route('language.switch', $locale) }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                <button
                    type="submit"
                    class="{{ $itemClasses }} {{ $currentLocale === $locale ? 'bg-slate-100 text-[#070740] dark:bg-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300' }}"
                    @if ($currentLocale === $locale) aria-current="true" @endif
                    role="menuitem"
                >
                    <span>{{ __($language['label']) }}</span>
                    <span class="text-xs font-semibold uppercase opacity-60">{{ $language['code'] }}</span>
                </button>
            </form>
        @endforeach
    </div>
</div>
