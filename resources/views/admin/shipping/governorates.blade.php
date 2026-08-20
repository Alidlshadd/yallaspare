@php
    // The row shows the reader's language on top and English underneath, so a
    // fee is never set against a name the operator only half recognises. In
    // English there is nothing to repeat.
    $secondaryName = fn ($governorate) => $governorate->name === $governorate->name_en ? null : $governorate->name_en;

    $inputClasses = 'h-9 w-20 rounded-md border border-slate-300 bg-white px-2 text-center text-sm tabular-nums text-slate-900'
        .' focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40'
        .' dark:border-slate-700';

    $headingClasses = 'text-[11px] font-bold uppercase tracking-widest text-muted';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ __('Shipping by Governorate') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('Set the delivery time and shipping fee for each governorate.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
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

            <form
                method="POST"
                action="{{ route('admin.shipping.governorates.update') }}"
                x-data="governorateShipping"
                class="rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                @csrf
                @method('PUT')

                {{-- A table on a real screen; on a phone every row breaks into
                     its own block so nothing has to be scrolled sideways. --}}
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 hidden bg-slate-50 sm:table-header-group">
                        <tr class="border-b border-slate-200 dark:border-slate-800">
                            <th scope="col" class="px-4 py-2.5 text-start {{ $headingClasses }}">{{ __('Governorate') }}</th>
                            <th scope="col" class="w-28 px-2 py-2.5 text-center {{ $headingClasses }}">{{ __('Days') }}</th>
                            <th scope="col" class="w-32 px-2 py-2.5 text-center {{ $headingClasses }}">{{ __('Fee (IQD)') }}</th>
                        </tr>
                    </thead>
                    <tbody class="block sm:table-row-group">
                        @foreach($governorates as $index => $governorate)
                            <tr class="block border-b border-slate-100 transition-colors hover:bg-slate-50/70 sm:table-row dark:border-slate-800">
                                <td class="block px-4 pt-3 align-middle sm:table-cell sm:py-2.5">
                                    <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $governorate->id }}">
                                    <span class="block font-semibold text-slate-900">{{ $governorate->name }}</span>
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
                                        min="0"
                                        max="1000000"
                                        step="1"
                                        required
                                        inputmode="numeric"
                                        aria-label="{{ __('Fee (IQD)') }} — {{ $governorate->name }}"
                                        class="{{ $inputClasses }}"
                                        :class="isDirty('{{ $index }}-fee') && 'ring-2 ring-amber-400'"
                                        @input="mark('{{ $index }}-fee', $event.target.value, '{{ $governorate->shipping_fee }}')"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Stays in reach at the bottom of a nineteen-row list. --}}
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
        </div>
    </div>
</x-app-layout>
