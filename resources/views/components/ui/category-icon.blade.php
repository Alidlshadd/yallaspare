@props([
    'name' => 'tools',
])

@switch($name)
    @case('truck')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5a1.5 1.5 0 0 1 1.5-1.5h8A1.5 1.5 0 0 1 14 7.5V15H3V7.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 9h3.586a1.5 1.5 0 0 1 1.06.44l1.914 1.914A1.5 1.5 0 0 1 21 12.414V15h-7V9Z" />
            <circle cx="7.5" cy="17.5" r="1.5" />
            <circle cx="17.5" cy="17.5" r="1.5" />
        </svg>
        @break

    @case('motorcycle')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <circle cx="6" cy="17" r="3" />
            <circle cx="18" cy="17" r="3" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 17l4-8h4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 9l2 4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 9H8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 9l3-2" />
        </svg>
        @break

    @case('tyre')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <circle cx="12" cy="12" r="7" />
            <circle cx="12" cy="12" r="2.25" />
            <path stroke-linecap="round" d="M12 5v2M12 17v2M5 12h2M17 12h2M7.2 7.2l1.4 1.4M15.4 15.4l1.4 1.4M16.8 7.2l-1.4 1.4M8.6 15.4l-1.4 1.4" />
        </svg>
        @break

    @case('wheel')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <circle cx="12" cy="12" r="7" />
            <circle cx="12" cy="12" r="2" />
            <path stroke-linecap="round" d="M12 5v5M12 14v5M5 12h5M14 12h5M8.3 8.3l2.6 2.6M13.1 13.1l2.6 2.6M15.7 8.3l-2.6 2.6M10.9 13.1l-2.6 2.6" />
        </svg>
        @break

    @case('tools')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="m14 7 3-3 3 3-3 3-3-3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20l8-8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 20h8" />
        </svg>
        @break

    @case('accessories')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.9 4.8L19 9.7l-4.1 3.1 1.5 5.2L12 15l-4.4 3 1.5-5.2L5 9.7l5.1-1.9L12 3Z" />
        </svg>
        @break

    @case('oil')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4c2.8 3.3 5 6.1 5 8.9a5 5 0 1 1-10 0C7 10.1 9.2 7.3 12 4Z" />
        </svg>
        @break

    @case('filters')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16l-6 7v5l-4-2v-3L4 6Z" />
        </svg>
        @break

    @case('brakes')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <circle cx="12" cy="12" r="7" />
            <circle cx="12" cy="12" r="2.25" />
            <path stroke-linecap="round" d="M12 5v2M12 17v2M5 12h2M17 12h2" />
        </svg>
        @break

    @default
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16" />
        </svg>
@endswitch
