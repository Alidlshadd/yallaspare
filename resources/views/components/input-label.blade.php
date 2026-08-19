@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-app']) }}>
    {{ $value ?? $slot }}
</label>
