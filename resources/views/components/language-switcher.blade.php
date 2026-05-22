@props([
    'variant' => 'light',
])

@php
    $currentLocale = app()->getLocale();
    $locales = [
        'en' => 'English',
        'ar' => 'Arabic',
        'ku' => 'Kurdish',
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
    x-data="{ languageOpen: false }"
    @mouseenter="languageOpen = true"
    @mouseleave="languageOpen = false"
    @click.outside="languageOpen = false"
>
    <button
        type="button"
        class="inline-flex h-9 items-center gap-2 rounded-xl px-3 text-sm font-medium transition duration-200 focus-visible:outline-none focus-visible:ring-2 {{ $triggerClasses }}"
        @click="languageOpen = !languageOpen"
        :aria-expanded="languageOpen.toString()"
        aria-haspopup="menu"
        aria-label="{{ __('Language') }}"
    >
        <span>{{ __($currentLabel) }}</span>
        <span class="text-xs uppercase opacity-70">{{ $currentLocale }}</span>
        <svg class="h-4 w-4 opacity-70 transition-transform" :class="languageOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.126l3.71-3.895a.75.75 0 1 1 1.08 1.04l-4.25 4.46a.75.75 0 0 1-1.08 0l-4.25-4.46a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-cloak
        x-show="languageOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="absolute right-0 top-full z-50 mt-2 w-44 rounded-2xl p-2 {{ $menuClasses }}"
        role="menu"
        aria-label="{{ __('Language') }}"
    >
        @foreach ($locales as $locale => $label)
            <form method="POST" action="{{ route('language.switch', $locale) }}">
                @csrf
                <button
                    type="submit"
                    class="{{ $itemClasses }} {{ $currentLocale === $locale ? 'bg-slate-100 text-[#070740] dark:bg-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300' }}"
                    @if ($currentLocale === $locale) aria-current="true" @endif
                    role="menuitem"
                >
                    <span>{{ __($label) }}</span>
                    <span class="text-xs uppercase opacity-60">{{ $locale }}</span>
                </button>
            </form>
        @endforeach
    </div>
</div>
