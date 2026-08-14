@props([
    'name' => 'password',
    'confirmName' => 'password_confirmation',
    'label' => null,
    'placeholder' => null,
    'confirmLabel' => null,
    'confirmPlaceholder' => null,
])

@php
    $rulesId = $name . '-rules';
    $label ??= __('Password');
    $confirmLabel ??= __('Confirm Password');
    $placeholder ??= __('Create password');
    $confirmPlaceholder ??= __('Confirm password');

    $labelClass = 'text-sm font-medium text-slate-300';
    $inputClass = 'block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition duration-200 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-100 dark:placeholder:text-slate-500';
@endphp

<div class="space-y-4" x-data="passwordField(@js(__('Done')), @js(__('Not yet')), @js(__('Use this password')))">
    <div>
        <x-input-label :for="$name" :value="$label" :class="$labelClass" />
        <x-password-input
            :id="$name"
            container-class="mt-2"
            :class="$inputClass"
            :name="$name"
            required
            autocomplete="new-password"
            :placeholder="$placeholder"
            :aria-describedby="$rulesId"
            x-model="value"
        />

        {{-- Rules stay in the DOM without JS, so the requirements are always
             readable; Alpine only adds the live tick state on top. --}}
        <ul id="{{ $rulesId }}" class="mt-2.5 space-y-1.5 text-xs leading-5">
            <li class="flex items-center gap-2 text-slate-400 transition-colors duration-200" :class="lengthRuleClass">
                <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full border text-transparent border-slate-500 transition-colors duration-200" :class="lengthMarkClass" aria-hidden="true">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2 6.3 2.8 2.8L10 3.4" />
                    </svg>
                </span>
                <span>{{ __('At least 8 characters') }}</span>
                <span class="sr-only" x-text="lengthRuleState"></span>
            </li>
            <li class="flex items-center gap-2 text-slate-400 transition-colors duration-200" :class="letterRuleClass">
                <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full border text-transparent border-slate-500 transition-colors duration-200" :class="letterMarkClass" aria-hidden="true">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2 6.3 2.8 2.8L10 3.4" />
                    </svg>
                </span>
                <span>{{ __('Contains a letter') }}</span>
                <span class="sr-only" x-text="letterRuleState"></span>
            </li>
            <li class="flex items-center gap-2 text-slate-400 transition-colors duration-200" :class="digitRuleClass">
                <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full border text-transparent border-slate-500 transition-colors duration-200" :class="digitMarkClass" aria-hidden="true">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2 6.3 2.8 2.8L10 3.4" />
                    </svg>
                </span>
                <span>{{ __('Contains a number') }}</span>
                <span class="sr-only" x-text="digitRuleState"></span>
            </li>
        </ul>

        <x-input-error :messages="$errors->get($name)" class="mt-2 text-sm text-red-400" />
    </div>

    {{-- Suggestions are generated in the browser, so nothing renders without JS. --}}
    <div x-show="hasSuggestions" x-cloak>
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-medium text-slate-400">{{ __('Suggestions') }}</span>
            <button
                type="button"
                @click="refreshSuggestions"
                class="rounded px-1 py-0.5 text-xs text-slate-400 transition duration-200 hover:text-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
            >
                {{ __('New suggestions') }}
            </button>
        </div>

        <div class="mt-2 flex flex-wrap gap-2">
            <template x-for="suggestion in suggestions" :key="suggestion">
                <button
                    type="button"
                    @click="applySuggestion(suggestion)"
                    class="rounded-md border border-slate-600/60 bg-slate-500/15 px-2.5 py-1.5 font-mono text-[13px] tracking-wide text-slate-200 transition duration-200 hover:border-red-400/60 hover:bg-red-500/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                    :aria-label="suggestionLabel(suggestion)"
                    x-text="suggestion"
                ></button>
            </template>
        </div>
    </div>

    <div>
        <x-input-label :for="$confirmName" :value="$confirmLabel" :class="$labelClass" />
        <x-password-input
            :id="$confirmName"
            container-class="mt-2"
            :class="$inputClass"
            :name="$confirmName"
            required
            autocomplete="new-password"
            :placeholder="$confirmPlaceholder"
            x-model="confirmation"
        />
        <x-input-error :messages="$errors->get($confirmName)" class="mt-2 text-sm text-red-400" />
    </div>
</div>
