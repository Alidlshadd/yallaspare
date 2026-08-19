@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'inline-flex items-center border-b-2 border-accent px-1 pt-1 text-sm font-medium leading-5 text-app transition duration-150 focus:outline-none focus-visible:border-accent'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-muted transition duration-150 hover:border-app hover:text-app focus:outline-none focus-visible:border-accent focus-visible:text-app';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
