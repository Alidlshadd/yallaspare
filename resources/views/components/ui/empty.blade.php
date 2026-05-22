@props([
    'title' => __('Nothing here yet'),
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->class(['rounded-app border border-dashed border-app bg-surface-2 px-6 py-10 text-center']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-surface-1 text-muted">
        @if ($icon)
            {{ $icon }}
        @else
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h7" />
            </svg>
        @endif
    </div>
    <h3 class="mt-4 text-base font-semibold text-app">{{ $title }}</h3>
    @if ($description)
        <p class="mt-2 text-sm text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-5">
            {{ $action }}
        </div>
    @endisset
</div>
