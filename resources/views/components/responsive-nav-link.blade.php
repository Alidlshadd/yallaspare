@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'block w-full border-s-4 border-accent bg-accent/10 py-2 pe-4 ps-3 text-start text-base font-medium text-app transition duration-150 focus:outline-none focus-visible:bg-accent/15'
        : 'block w-full border-s-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-muted transition duration-150 hover:border-app hover:bg-surface-1 hover:text-app focus:outline-none focus-visible:border-accent focus-visible:bg-surface-1 focus-visible:text-app';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
