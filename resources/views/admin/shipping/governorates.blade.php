@php
    // The row shows the reader's language on top and English underneath, so a
    // fee is never set against a name the operator only half recognises. In
    // English there is nothing to repeat.
    $secondaryName = fn ($governorate) => $governorate->name === $governorate->name_en ? null : $governorate->name_en;

    $inputClasses = 'h-9 w-20 rounded-md border border-slate-300 bg-white px-2 text-center text-sm tabular-nums text-slate-900'
        .' focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40'
        .' dark:border-slate-700';

    $panelInput = 'h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900'
        .' focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40'
        .' dark:border-slate-700';

    $headingClasses = 'text-[11px] font-bold uppercase tracking-widest text-muted';

    $free = $governorates->where('shipping_fee', 0)->count();
    $average = $governorates->count() > 0 ? (int) round($governorates->avg('shipping_fee')) : 0;
    $highest = (int) $governorates->max('shipping_fee');

    $openPanel = $errors->hasAny(['name_en', 'name_ar', 'name_ku']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ __('Shipping by Governorate') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('Set the delivery time and shipping fee for each governorate.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-semibold text-muted tabular-nums dark:border-slate-700">
                    <span class="text-slate-900">{{ $free }}</span> {{ __('free') }}
                </span>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-semibold text-muted tabular-nums dark:border-slate-700">
                    {{ __('average') }} <span class="text-slate-900">{{ number_format($average) }}</span>
                </span>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-semibold text-muted tabular-nums dark:border-slate-700">
                    {{ __('highest') }} <span class="text-slate-900">{{ number_format($highest) }}</span>
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8"
            x-data="governorateShipping"
            @if($openPanel) x-init="adding = true" @endif
        >
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- ============================ add a governorate ==================== --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ __('Add a governorate') }}</p>
                        <p class="text-xs text-muted">{{ __('For a destination that is not on the standard list.') }}</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-muted hover:text-slate-900 dark:border-slate-700"
                        :aria-expanded="adding ? 'true' : 'false'"
                        @click="adding = ! adding"
                    >
                        <span x-text="adding ? @js(__('Cancel')) : @js(__('New governorate'))"></span>
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.shipping.governorates.store') }}"
                    class="border-t border-slate-100 px-4 py-4 dark:border-slate-800"
                    x-show="adding"
                    x-cloak
                >
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label for="new-name-en" class="mb-1 block {{ $headingClasses }}">{{ __('Name (English)') }}</label>
                            <input id="new-name-en" name="name_en" value="{{ old('name_en') }}" required maxlength="64" class="{{ $panelInput }}">
                        </div>
                        <div>
                            <label for="new-name-ar" class="mb-1 block {{ $headingClasses }}">{{ __('Name (Arabic)') }}</label>
                            <input id="new-name-ar" name="name_ar" value="{{ old('name_ar') }}" required maxlength="64" dir="rtl" class="{{ $panelInput }}">
                        </div>
                        <div>
                            <label for="new-name-ku" class="mb-1 block {{ $headingClasses }}">{{ __('Name (Kurdish)') }}</label>
                            <input id="new-name-ku" name="name_ku" value="{{ old('name_ku') }}" required maxlength="64" dir="rtl" class="{{ $panelInput }}">
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-end gap-3">
                        <div>
                            <label for="new-days" class="mb-1 block {{ $headingClasses }}">{{ __('Days') }}</label>
                            <input id="new-days" type="number" name="delivery_days" value="{{ old('delivery_days', 3) }}" min="1" max="60" step="1" required inputmode="numeric" class="{{ $inputClasses }}">
                        </div>
                        <div>
                            <label for="new-fee" class="mb-1 block {{ $headingClasses }}">{{ __('Fee (IQD)') }}</label>
                            <input id="new-fee" type="number" name="shipping_fee" value="{{ old('shipping_fee', 0) }}" min="0" max="1000000" step="250" required inputmode="numeric" class="{{ $inputClasses }}">
                        </div>
                        <button type="submit" class="ms-auto inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-primary-hover">
                            {{ __('Add') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- ============================ the table ============================ --}}
            <form
                method="POST"
                action="{{ route('admin.shipping.governorates.update') }}"
                class="rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                @csrf
                @method('PUT')

                {{-- bulk bar --}}
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <label for="gov-search" class="sr-only">{{ __('Search') }}</label>
                    <input
                        id="gov-search"
                        type="search"
                        x-model="search"
                        placeholder="{{ __('Search') }}"
                        class="h-9 w-40 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40 dark:border-slate-700"
                    >

                    <span class="mx-1 hidden h-5 w-px bg-slate-200 sm:block dark:bg-slate-700"></span>

                    <label for="bulk-days" class="{{ $headingClasses }}">{{ __('Days') }}</label>
                    <input id="bulk-days" type="number" min="1" max="60" step="1" inputmode="numeric" x-model="bulkDays" class="{{ $inputClasses }} w-16">

                    <label for="bulk-fee" class="{{ $headingClasses }}">{{ __('Fee (IQD)') }}</label>
                    <input id="bulk-fee" type="number" min="0" max="1000000" step="250" inputmode="numeric" x-model="bulkFee" class="{{ $inputClasses }} w-24">

                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-primary px-3 py-2 text-sm font-bold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="(bulkDays === '' && bulkFee === '') || targetCount === 0"
                        @click="applyBulk()"
                    >
                        <span x-text="selectedCount > 0
                            ? @js(__('Apply to selected')) + ' (' + selectedCount + ')'
                            : @js(__('Apply to all')) + ' (' + targetCount + ')'"></span>
                    </button>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-muted hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700"
                        :disabled="targetCount === 0"
                        @click="makeFree()"
                    >
                        {{ __('Make free') }}
                    </button>

                    <button
                        type="button"
                        class="ms-auto rounded-lg px-3 py-2 text-sm font-semibold text-muted transition hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="changedRows === 0"
                        @click="revert()"
                    >
                        {{ __('Revert') }}
                    </button>
                </div>

                {{-- A table on a real screen; on a phone every row breaks into
                     its own block so nothing has to be scrolled sideways. --}}
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 hidden bg-slate-50 sm:table-header-group">
                        <tr class="border-b border-slate-200 dark:border-slate-800">
                            <th scope="col" class="w-10 px-4 py-2.5 text-start">
                                <input
                                    type="checkbox"
                                    aria-label="{{ __('Select all') }}"
                                    class="rounded border-slate-300 text-accent focus:ring-accent dark:border-slate-700"
                                    :checked="allVisibleSelected"
                                    @change="toggleAll()"
                                >
                            </th>
                            <th scope="col" class="px-2 py-2.5 text-start {{ $headingClasses }}">{{ __('Governorate') }}</th>
                            <th scope="col" class="w-28 px-2 py-2.5 text-center {{ $headingClasses }}">{{ __('Days') }}</th>
                            <th scope="col" class="w-32 px-2 py-2.5 text-center {{ $headingClasses }}">{{ __('Fee (IQD)') }}</th>
                            <th scope="col" class="w-12 px-2 py-2.5"><span class="sr-only">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="block sm:table-row-group">
                        @foreach($governorates as $index => $governorate)
                            <tr
                                class="block border-b border-slate-100 transition-colors hover:bg-slate-50/70 sm:table-row dark:border-slate-800"
                                data-governorate-row="{{ $governorate->id }}"
                                data-governorate-search="{{ $governorate->name }} {{ $governorate->name_en }} {{ $governorate->name_ar }} {{ $governorate->name_ku }}"
                                {{-- Read from the attribute rather than interpolated into the
                                     expression: a name carrying an apostrophe would end the
                                     string and break the whole page. --}}
                                x-show="matches($el.dataset.governorateSearch)"
                            >
                                <td class="hidden px-4 py-2.5 align-middle sm:table-cell">
                                    <input
                                        type="checkbox"
                                        aria-label="{{ __('Select') }} — {{ $governorate->name }}"
                                        class="rounded border-slate-300 text-accent focus:ring-accent dark:border-slate-700"
                                        x-model="selected['{{ $governorate->id }}']"
                                    >
                                </td>

                                <td class="block px-4 pt-3 align-middle sm:table-cell sm:px-2 sm:py-2.5">
                                    <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $governorate->id }}">
                                    <span class="block font-semibold text-slate-900">
                                        {{ $governorate->name }}
                                        @if($governorate->shipping_fee === 0)
                                            <span class="ms-1.5 rounded-full border border-accent/40 px-1.5 py-px text-[10px] font-bold uppercase tracking-wide text-accent-ink">{{ __('free') }}</span>
                                        @endif
                                    </span>
                                    @if($secondaryName($governorate))
                                        <span class="block text-xs text-muted">{{ $secondaryName($governorate) }}</span>
                                    @endif
                                </td>

                                <td class="inline-block w-1/2 px-4 py-3 align-middle sm:table-cell sm:w-28 sm:px-2 sm:py-2.5 sm:text-center">
                                    <span class="mb-1 block {{ $headingClasses }} sm:hidden">{{ __('Days') }}</span>
                                    <input
                                        type="number"
                                        name="rows[{{ $index }}][delivery_days]"
                                        value="{{ old('rows.'.$index.'.delivery_days', $governorate->delivery_days) }}"
                                        data-days-input
                                        data-original="{{ $governorate->delivery_days }}"
                                        min="1"
                                        max="60"
                                        step="1"
                                        required
                                        inputmode="numeric"
                                        aria-label="{{ __('Days') }} — {{ $governorate->name }}"
                                        class="{{ $inputClasses }}"
                                        :class="isDirty('{{ $index }}-days') && 'ring-2 ring-amber-400'"
                                        @input="mark('{{ $index }}-days', $event.target.value, '{{ $governorate->delivery_days }}')"
                                    >
                                </td>

                                <td class="inline-block w-1/2 px-4 py-3 align-middle sm:table-cell sm:w-32 sm:px-2 sm:py-2.5 sm:text-center">
                                    <span class="mb-1 block {{ $headingClasses }} sm:hidden">{{ __('Fee (IQD)') }}</span>
                                    <input
                                        type="number"
                                        name="rows[{{ $index }}][shipping_fee]"
                                        value="{{ old('rows.'.$index.'.shipping_fee', $governorate->shipping_fee) }}"
                                        data-fee-input
                                        data-original="{{ $governorate->shipping_fee }}"
                                        min="0"
                                        max="1000000"
                                        step="250"
                                        required
                                        inputmode="numeric"
                                        aria-label="{{ __('Fee (IQD)') }} — {{ $governorate->name }}"
                                        class="{{ $inputClasses }}"
                                        :class="isDirty('{{ $index }}-fee') && 'ring-2 ring-amber-400'"
                                        @input="mark('{{ $index }}-fee', $event.target.value, '{{ $governorate->shipping_fee }}')"
                                    >
                                </td>

                                <td class="hidden px-2 py-2.5 text-center align-middle sm:table-cell">
                                    @unless($governorate->isStandard())
                                        <button
                                            type="submit"
                                            form="remove-{{ $governorate->id }}"
                                            aria-label="{{ __('Remove') }} — {{ $governorate->name }}"
                                            class="rounded-md px-2 py-1 text-muted transition hover:text-rose-600"
                                        >
                                            <i class="fas fa-trash text-xs" aria-hidden="true"></i>
                                        </button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Stays in reach at the bottom of a long list. --}}
                <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-b-xl border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-slate-800">
                    <span class="text-xs text-muted" x-show="changedRows > 0" x-cloak>
                        <span x-text="changedRows"></span> {{ __('rows changed') }}
                    </span>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-primary"
                        :disabled="changedRows === 0"
                    >
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </form>

            {{-- Removal posts on its own; a form cannot sit inside the table's
                 form. The confirmation hook reads data-danger-confirm off the
                 form, not off the button that submits it. --}}
            @foreach($governorates as $governorate)
                @unless($governorate->isStandard())
                    <form
                        id="remove-{{ $governorate->id }}"
                        method="POST"
                        action="{{ route('admin.shipping.governorates.destroy', $governorate) }}"
                        class="hidden"
                        data-danger-confirm
                        data-danger-title="{{ __('Remove') }} — {{ $governorate->name }}"
                        data-danger-description="{{ __('The governorate is removed from the shipping table.') }}"
                    >
                        @csrf
                        @method('DELETE')
                    </form>
                @endunless
            @endforeach
        </div>
    </div>
</x-app-layout>
