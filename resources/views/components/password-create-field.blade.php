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

    $inputClass = 'block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition duration-200 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-100 dark:placeholder:text-slate-500';
    $mutedClass = 'text-slate-500 dark:text-slate-400';

    // No colour of its own: lengthRuleClass and friends set the state colour
    // outright. Leaving a static one here would put both in the class list at
    // once, and stylesheet order rather than the binding would pick the winner.
    $ruleClass = 'flex items-center gap-2 transition-colors duration-200';
    $markClass = 'grid h-4 w-4 shrink-0 place-items-center rounded-full border transition-colors duration-200';
@endphp

<div class="space-y-4" x-data="passwordField(@js(__('Done')), @js(__('Not yet')), @js(__('Use this password')))">
    <div>
        <x-input-label :for="$name" :value="$label" class="text-sm font-medium" />
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

        {{-- The rules themselves stay in the DOM without JS, so the requirements
             are always readable; Alpine only adds the live state on top. The
             ticks are cloaked so nothing looks satisfied before it is. --}}
        <ul id="{{ $rulesId }}" class="mt-2.5 space-y-1.5 text-xs leading-5 {{ $mutedClass }}">
            <li class="{{ $ruleClass }}" :class="lengthRuleClass">
                <span class="{{ $markClass }}" :class="lengthMarkClass" aria-hidden="true">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.4" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2 6.3 2.8 2.8L10 3.4" />
                    </svg>
                </span>
                <span>{{ __('At least 8 characters') }}</span>
                <span class="sr-only" x-text="lengthRuleState"></span>
            </li>
            <li class="{{ $ruleClass }}" :class="letterRuleClass">
                <span class="{{ $markClass }}" :class="letterMarkClass" aria-hidden="true">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.4" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2 6.3 2.8 2.8L10 3.4" />
                    </svg>
                </span>
                <span>{{ __('Contains a letter') }}</span>
                <span class="sr-only" x-text="letterRuleState"></span>
            </li>
            <li class="{{ $ruleClass }}" :class="digitRuleClass">
                <span class="{{ $markClass }}" :class="digitMarkClass" aria-hidden="true">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.4" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2 6.3 2.8 2.8L10 3.4" />
                    </svg>
                </span>
                <span>{{ __('Contains a number') }}</span>
                <span class="sr-only" x-text="digitRuleState"></span>
            </li>
        </ul>

        <x-input-error :messages="$errors->get($name)" class="mt-2 text-sm text-red-600 dark:text-red-400" />
    </div>

    {{-- Suggestions are generated in the browser, so nothing renders without JS. --}}
    <div x-show="hasSuggestions" x-cloak>
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-medium {{ $mutedClass }}">{{ __('Suggestions') }}</span>
            <button
                type="button"
                @click="refreshSuggestions"
                class="rounded px-1 py-0.5 text-sm transition duration-200 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:hover:text-red-400 {{ $mutedClass }}"
            >
                {{ __('New suggestions') }}
            </button>
        </div>

        <div class="mt-2 flex flex-wrap gap-2">
            <template x-for="suggestion in suggestions" :key="suggestion">
                <button
                    type="button"
                    @click="applySuggestion(suggestion)"
                    class="rounded-md border border-slate-300 bg-slate-100 px-2.5 py-1.5 font-mono text-sm tracking-wide text-slate-700 transition duration-200 hover:border-red-400 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:border-slate-600 dark:bg-slate-500/15 dark:text-slate-200 dark:hover:border-red-400/60 dark:hover:bg-red-500/15 dark:hover:text-red-200"
                    :aria-label="suggestionLabel(suggestion)"
                    x-text="suggestion"
                ></button>
            </template>
        </div>
    </div>

    <div>
        <x-input-label :for="$confirmName" :value="$confirmLabel" class="text-sm font-medium" />
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
        <x-input-error :messages="$errors->get($confirmName)" class="mt-2 text-sm text-red-600 dark:text-red-400" />
    </div>
</div>
