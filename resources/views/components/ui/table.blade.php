@props([
    'caption' => null,
])

<div {{ $attributes->class(['overflow-hidden rounded-app border border-app bg-surface-2 shadow-app']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[var(--border)] text-sm text-app">
            @if ($caption)
                <caption class="px-5 py-3 text-start text-xs font-medium text-muted">{{ $caption }}</caption>
            @endif
            {{ $slot }}
        </table>
    </div>
</div>
