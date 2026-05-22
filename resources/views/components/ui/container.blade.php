@props(['as' => 'div'])

<{{ $as }} {{ $attributes->class(['mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8']) }}>
    {{ $slot }}
</{{ $as }}>
