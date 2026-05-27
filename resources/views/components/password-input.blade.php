@props([
    'disabled' => false,
    'containerClass' => '',
    'showLabel' => __('Show password'),
    'hideLabel' => __('Hide password'),
])

<span
    class="{{ trim('relative block ' . $containerClass) }}"
    x-data="passwordVisibility({
        showLabel: @js($showLabel),
        hideLabel: @js($hideLabel),
    })"
>
    <input
        {{ $disabled ? 'disabled' : '' }}
        type="password"
        x-bind:type="visible ? 'text' : 'password'"
        {!! $attributes->except('type')->merge(['class' => 'password-input-control']) !!}
    >

    <button
        type="button"
        class="password-input-toggle"
        x-bind:aria-label="label"
        x-bind:title="label"
        aria-label="{{ $showLabel }}"
        title="{{ $showLabel }}"
        x-on:click="toggle()"
        {{ $disabled ? 'disabled' : '' }}
    >
        <svg x-show="!visible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>

        <svg x-cloak x-show="visible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.6 10.6 0 0 1 12 5c6 0 9.75 7 9.75 7a18.5 18.5 0 0 1-4.13 4.79" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.12 14.12A3 3 0 0 1 9.88 9.88" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61A18.6 18.6 0 0 0 2.25 12S6 19 12 19c1.7 0 3.25-.47 4.6-1.16" />
        </svg>
    </button>
</span>
