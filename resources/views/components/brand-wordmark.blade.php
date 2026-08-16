@props(['brand' => null])

@php
    $name = trim((string) ($brand ?? ''));
    $name = $name !== '' ? $name : 'YallaSpare';

    // Matches the logo artwork: the first word takes the surrounding colour,
    // the rest takes the brand orange, and the two are set tight against each
    // other — "Yalla" + "Spare" reads as one word. A single-word site name just
    // renders plain, with nothing to accent.
    $parts = preg_split('/\s+/', $name, 2) ?: [$name];
    $lead = $parts[0] ?? $name;
    $accent = $parts[1] ?? '';
@endphp

{{-- Kept on one line on purpose: a newline between the two spans would render
     as a visible gap and split the wordmark. --}}
<span {{ $attributes }}>{{ $lead }}@if ($accent !== '')<span class="brand-wordmark-accent">{{ $accent }}</span>@endif</span>
