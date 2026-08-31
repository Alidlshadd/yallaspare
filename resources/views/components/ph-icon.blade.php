@props(['name', 'size' => 20])

@php
    $glyph = \App\Support\PhosphorIcons::get($name);
@endphp

@if ($glyph)
    {{--
        Both weights ship together and are cross-faded in CSS: `regular` at
        rest, `fill` on the current page. Doing it this way means changing page
        costs no script and no second request, and the swap can be animated.

        Decorative by definition — the link beside it carries the name — so the
        whole thing is hidden from assistive technology.
    --}}
    <span {{ $attributes->merge(['class' => 'ph']) }}
          style="--ph-size: {{ (int) $size }}px"
          data-ph="{{ $name }}"
          aria-hidden="true">
        <svg class="ph-glyph ph-regular" viewBox="{{ \App\Support\PhosphorIcons::VIEW_BOX }}" fill="currentColor" focusable="false">
            {!! $glyph['regular'] !!}
        </svg>
        <svg class="ph-glyph ph-fill" viewBox="{{ \App\Support\PhosphorIcons::VIEW_BOX }}" fill="currentColor" focusable="false">
            {!! $glyph['fill'] !!}
        </svg>
    </span>
@endif
