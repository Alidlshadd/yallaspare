@props(['breadcrumbs' => []])

@php
    $trail = collect($breadcrumbs)->values();
@endphp

@if ($trail->isNotEmpty())
    <nav aria-label="{{ __('Breadcrumb') }}" class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500 sm:text-sm">
        @foreach ($trail as $index => $crumb)
            @if ($index > 0)
                <span aria-hidden="true">/</span>
            @endif

            @if ($loop->last)
                <span class="text-slate-700 dark:text-slate-300" aria-current="page">{{ $crumb['label'] }}</span>
            @else
                <a href="{{ $crumb['url'] }}" class="transition hover:text-slate-900 dark:hover:text-white">{{ $crumb['label'] }}</a>
            @endif
        @endforeach
    </nav>
@endif
