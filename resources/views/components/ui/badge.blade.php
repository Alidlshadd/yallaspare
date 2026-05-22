@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'neutral' => 'border-app bg-surface-1 text-app',
        'primary' => 'border-transparent bg-[rgba(7,11,31,0.08)] text-primary',
        'success' => 'border-transparent bg-[rgba(21,128,61,0.10)] text-[var(--success)]',
        'warning' => 'border-transparent bg-[rgba(180,83,9,0.12)] text-[var(--warning)]',
        'danger' => 'border-transparent bg-[rgba(180,35,24,0.1)] text-[var(--danger)]',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold', $variants[$variant] ?? $variants['neutral']]) }}>
    {{ $slot }}
</span>
