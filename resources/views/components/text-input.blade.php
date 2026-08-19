@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'block w-full rounded-app border border-app bg-surface-2 px-3 py-2.5 text-sm text-app shadow-sm transition duration-150 placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/35 disabled:cursor-not-allowed disabled:opacity-60']) !!}>
