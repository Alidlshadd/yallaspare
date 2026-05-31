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
    <summary class="inline-flex cursor-pointer list-none items-center justify-center gap-2 bg-primary font-semibold text-white shadow-sm transition hover:bg-[#10105c] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden {{ $summaryClasses }}">
        <i class="fas fa-file-pdf"></i>
        <span>{{ __('Invoice') }}</span>
        <i class="fas fa-chevron-down text-[10px] transition group-open:rotate-180"></i>
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
