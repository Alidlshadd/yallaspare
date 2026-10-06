{{-- A phone number as two controls: the country it belongs to and the number
     the way it is written there. $field is the input name ("phone" or
     "whatsapp"); $stored is the saved E.164 value, if any. Passing $nameless
     leaves the name attributes off, for the copy inside the invoice form that
     is sent by script rather than submitted. --}}
@php
    $parts = \App\Support\InternationalPhone::split($stored ?? null);
    $nameless = ! empty($nameless);
    $selectedCountry = $nameless ? $parts['country'] : old($field.'_country', $parts['country']);
    $shownNumber = $nameless ? $parts['number'] : old($field, $parts['number']);
    $hasError = ! $nameless && $errors->has($field);
@endphp
<div class="flex gap-2" dir="ltr">
    <select id="{{ $id }}Country" @unless ($nameless) name="{{ $field }}_country" @endunless
            aria-label="{{ __('Country code for :field', ['field' => $label]) }}"
            class="h-11 w-36 shrink-0 px-2 rounded-xl border bg-slate-50 text-sm text-slate-900 transition focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900 {{ $hasError ? 'border-rose-300 dark:border-rose-500/50' : 'border-slate-200' }}">
        @foreach (\App\Support\InternationalPhone::countries() as $iso => $country)
            <option value="{{ $iso }}" @selected($selectedCountry === $iso)>+{{ $country['dial'] }} {{ $country['name'] }}</option>
        @endforeach
    </select>
    <input id="{{ $id }}" type="tel" @unless ($nameless) name="{{ $field }}" @endunless value="{{ $shownNumber }}"
           inputmode="tel" autocomplete="off" maxlength="32" @if (! empty($required)) required @endif
           placeholder="{{ $placeholder ?? '' }}"
           class="h-11 w-full min-w-0 px-3 rounded-xl border bg-slate-50 text-sm text-slate-900 placeholder-muted transition focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900 {{ $hasError ? 'border-rose-300 dark:border-rose-500/50' : 'border-slate-200' }}">
</div>
