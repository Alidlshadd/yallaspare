@props([
    'padding' => 'md',
])

@php
    $paddingClasses = [
        'sm' => 'p-4',
        'md' => 'p-5 sm:p-6',
        'lg' => 'p-6 sm:p-7',
    ];
@endphp

<section {{ $attributes->class(['overflow-hidden rounded-app border border-app bg-surface-2 shadow-app']) }}>
    @isset($header)
        <div class="border-b border-app px-5 py-4 sm:px-6">
            {{ $header }}
        </div>
    @endisset

    <div class="{{ $paddingClasses[$padding] ?? $paddingClasses['md'] }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-app px-5 py-4 sm:px-6">
            {{ $footer }}
        </div>
    @endisset
</section>
