@props([
    'lines' => 3,
])

<div {{ $attributes->class(['space-y-3']) }} aria-hidden="true">
    @for ($i = 0; $i < $lines; $i++)
        <div
            class="h-4 animate-pulse rounded-full bg-[linear-gradient(90deg,rgba(226,232,240,0.7),rgba(241,245,249,1),rgba(226,232,240,0.7))]"
            style="width: {{ max(35, 100 - ($i * 12)) }}%;"
        ></div>
    @endfor
</div>
