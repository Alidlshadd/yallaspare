@php
    $isEditing = $address->exists;
    $action = $isEditing ? route('account.addresses.update', $address) : route('account.addresses.store');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-8">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-primary">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 1.75a5.75 5.75 0 0 0-5.75 5.75c0 4.17 4.54 8.95 5.24 9.67a.75.75 0 0 0 1.08 0c.7-.72 5.24-5.5 5.24-9.67A5.75 5.75 0 0 0 10 1.75Zm0 7.75a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z" clip-rule="evenodd" />
                </svg>
            </span>
            <div>
                <h2 class="text-lg font-semibold tracking-[-0.02em] text-slate-950">{{ __('Delivery Address Details') }}</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('Save a clean delivery destination that you can reuse at checkout and update later if needed.') }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-slate-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Status') }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $isEditing ? 'Editing Address' : 'New Address' }}</p>
        </div>
    </div>

    <div class="border-t border-slate-200/70"></div>

    <div class="space-y-6">
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="label" class="block text-sm font-medium text-slate-700">{{ __('Label') }}</label>
                <input id="label" name="label" type="text" value="{{ old('label', $address->label) }}" placeholder="{{ __('Home, Work, Shop') }}" class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $errors->has('label') ? 'border-rose-300' : 'border-slate-200' }}">
                @error('label')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('Phone') }}</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $address->phone) }}" placeholder="+964..." class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $errors->has('phone') ? 'border-rose-300' : 'border-slate-200' }}">
                <p class="mt-2 text-xs text-slate-500">{{ __('Optional. Include country code if needed.') }}</p>
                @error('phone')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="country" class="block text-sm font-medium text-slate-700">{{ __('Country') }}</label>
                <input id="country" name="country" type="text" value="{{ old('country', $address->country) }}" placeholder="{{ __('Iraq') }}" class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $errors->has('country') ? 'border-rose-300' : 'border-slate-200' }}" required>
                @error('country')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="city" class="block text-sm font-medium text-slate-700">{{ __('City') }}</label>
                <input id="city" name="city" type="text" value="{{ old('city', $address->city) }}" placeholder="{{ __('Baghdad') }}" class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $errors->has('city') ? 'border-rose-300' : 'border-slate-200' }}" required>
                @error('city')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="address_line1" class="block text-sm font-medium text-slate-700">{{ __('Address Line 1') }}</label>
            <input id="address_line1" name="address_line1" type="text" value="{{ old('address_line1', $address->address_line1) }}" placeholder="{{ __('Street, district, building') }}" class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $errors->has('address_line1') ? 'border-rose-300' : 'border-slate-200' }}" required>
            @error('address_line1')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="address_line2" class="block text-sm font-medium text-slate-700">{{ __('Address Line 2') }}</label>
            <input id="address_line2" name="address_line2" type="text" value="{{ old('address_line2', $address->address_line2) }}" placeholder="{{ __('Apartment, floor, landmark') }}" class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $errors->has('address_line2') ? 'border-rose-300' : 'border-slate-200' }}">
            @error('address_line2')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-slate-700">{{ __('Notes') }}</label>
            <textarea id="notes" name="notes" rows="5" class="mt-2 block min-h-32 w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $errors->has('notes') ? 'border-rose-300' : 'border-slate-200' }}">{{ old('notes', $address->notes) }}</textarea>
            @error('notes')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default)) class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30">
            <span class="text-sm font-medium text-slate-700">{{ __('Set as default address') }}</span>
        </label>
    </div>

    <div class="border-t border-slate-200/70 pt-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">{{ __('You can edit this address later.') }}</p>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('account.addresses.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition duration-200 hover:opacity-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                    {{ $isEditing ? 'Update Address' : 'Save Address' }}
                </button>
            </div>
        </div>
    </div>
</form>
