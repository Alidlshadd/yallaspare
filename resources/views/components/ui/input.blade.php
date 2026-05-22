@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'error' => null,
    'id' => null,
])

@php
    $inputId = $id ?: ($name ?: 'input-'.uniqid());
    $errorText = $error ?: ($name ? $errors->first($name) : null);
@endphp

<div {{ $attributes->only('class')->class(['space-y-2']) }}>
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-app">{{ $label }}</label>
    @endif

    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($errorText) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @elseif ($hint) aria-describedby="{{ $inputId }}-hint" @endif
        {{ $attributes->except('class')->class([
            'block w-full rounded-app border bg-surface-2 px-3 py-2.5 text-sm text-app placeholder:text-slate-400',
            'border-app focus-ring',
            $errorText ? 'border-[var(--danger)]' : '',
        ]) }}
    />

    @if ($hint && ! $errorText)
        <p id="{{ $inputId }}-hint" class="text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($errorText)
        <p id="{{ $inputId }}-error" class="text-xs font-medium text-[var(--danger)]">{{ $errorText }}</p>
    @endif
</div>
