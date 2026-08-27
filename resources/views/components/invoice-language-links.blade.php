@props([
    'order',
    'routeName',
    'align' => 'right',
    'mode' => 'menu',
    'size' => 'sm',
])

@php
    $invoiceLocales = [
        'en' => 'en',
        'ar' => 'ar',
        'ku' => 'ku',
    ];

    $summaryClasses = $size === 'xs'
        ? 'rounded-md px-3 py-1.5 text-xs'
        : 'rounded-lg px-3.5 py-2 text-xs';
    $menuAlignment = $align === 'left' ? 'left-0' : 'right-0';
@endphp

<details {{ $attributes->merge(['class' => 'group relative inline-block text-left']) }}>
    <summary class="inline-flex cursor-pointer list-none items-center justify-center gap-2 whitespace-nowrap bg-primary font-semibold text-white shadow-sm transition hover:bg-navy-raised focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden {{ $summaryClasses }}">
        {{-- Inline SVG: this control also renders on the storefront account
             pages, and layouts/user does not load Font Awesome, so the glyphs
             were simply missing there. --}}
        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 3H7a1.5 1.5 0 0 0-1.5 1.5v15A1.5 1.5 0 0 0 7 21h10a1.5 1.5 0 0 0 1.5-1.5V7.5z" />
            <path d="M14 3v4.5h4.5" />
            <path d="M9 15.5h6" />
        </svg>
        <span>{{ __('Invoice') }}</span>
        <svg class="h-2.5 w-2.5 shrink-0 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m5 8.5 7 7 7-7" />
        </svg>
    </summary>
    <div class="invisible absolute {{ $menuAlignment }} z-50 mt-1 flex min-w-full overflow-hidden rounded-md border border-slate-200 bg-white opacity-0 shadow-lg transition group-open:visible group-open:opacity-100 group-hover:visible group-hover:opacity-100 dark:border-slate-700 dark:bg-slate-900">
        @foreach($invoiceLocales as $localeCode => $localeLabel)
            <a
                href="{{ route($routeName, ['order' => $order, 'lang' => $localeCode]) }}"
                title="{{ __('Invoice PDF') }} - {{ strtoupper($localeLabel) }}"
                class="border-r border-slate-200 px-3 py-2 text-xs font-bold uppercase text-slate-700 transition last:border-r-0 hover:bg-primary hover:text-white dark:border-slate-700 dark:text-slate-200"
            >
                {{ $localeLabel }}
            </a>
        @endforeach
    </div>
</details>
