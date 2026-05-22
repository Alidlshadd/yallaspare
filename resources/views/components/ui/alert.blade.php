@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $variants = [
        'info' => 'border-[rgba(7,11,31,0.12)] bg-[rgba(7,11,31,0.04)] text-app',
        'success' => 'border-[rgba(21,128,61,0.14)] bg-[rgba(21,128,61,0.06)] text-[var(--success)]',
        'warn' => 'border-[rgba(180,83,9,0.18)] bg-[rgba(180,83,9,0.08)] text-[var(--warning)]',
        'danger' => 'border-[rgba(180,35,24,0.16)] bg-[rgba(180,35,24,0.06)] text-[var(--danger)]',
    ];
@endphp

<div {{ $attributes->class(['rounded-app border px-4 py-3 text-sm', $variants[$variant] ?? $variants['info']]) }} role="alert">
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
    @endif
    <div class="{{ $title ? 'mt-1' : '' }}">
        {{ $slot }}
    </div>
</div>
