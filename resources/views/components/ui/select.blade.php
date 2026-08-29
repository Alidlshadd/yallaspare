@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'id' => null,
])

@php
    $selectId = $id ?: ($name ?: 'select-'.uniqid());
    $errorText = $error ?: ($name ? $errors->first($name) : null);
    $selected = old($name, $value);
@endphp

<div {{ $attributes->only('class')->class(['space-y-2']) }}>
    @if ($label)
        <label for="{{ $selectId }}" class="block text-sm font-medium text-app">{{ $label }}</label>
    @endif

    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        @if ($errorText) aria-invalid="true" aria-describedby="{{ $selectId }}-error" @elseif ($hint) aria-describedby="{{ $selectId }}-hint" @endif
        {{ $attributes->except('class')->class([
            'block w-full rounded-app border bg-surface-2 px-3 py-2.5 text-sm text-app',
            'border-app focus-ring',
            $errorText ? 'border-[var(--danger)]' : '',
        ]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hint && ! $errorText)
        <p id="{{ $selectId }}-hint" class="text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($errorText)
        <p id="{{ $selectId }}-error" class="text-xs font-medium text-[var(--danger)]">{{ $errorText }}</p>
    @endif
</div>
