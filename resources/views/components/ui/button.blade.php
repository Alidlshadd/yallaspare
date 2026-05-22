@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-app font-medium transition duration-150 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60';
    $sizes = [
        'sm' => 'h-9 px-3 text-sm',
        'md' => 'h-10 px-4 text-sm',
    ];
    $variants = [
        'primary' => 'border border-transparent bg-[var(--primary)] text-white hover:bg-[var(--primary-hover)] focus:ring-4 ring-focus',
        'secondary' => 'border border-[var(--border)] bg-[var(--surface-2)] text-[var(--text)] hover:bg-[var(--surface-1)] focus:ring-4 ring-focus',
        'ghost' => 'border border-transparent bg-transparent text-[var(--text)] hover:bg-[var(--surface-1)] focus:ring-4 ring-focus',
        'danger' => 'border border-transparent bg-[var(--danger)] text-white hover:opacity-95 focus:ring-4 focus:ring-[rgba(180,35,24,0.16)]',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->class([$base, $sizes[$size] ?? $sizes['md'], $variants[$variant] ?? $variants['primary']]) }}>
    {{ $slot }}
</button>
