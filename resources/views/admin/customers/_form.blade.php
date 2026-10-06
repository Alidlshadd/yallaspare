@php
    $isEdit = isset($customer);
    $inputBase = 'h-11 w-full px-3 rounded-xl border bg-slate-50 text-sm text-slate-900 placeholder-muted transition focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900';
    $areaBase = 'w-full px-3 py-2.5 rounded-xl border bg-slate-50 text-sm text-slate-900 placeholder-muted transition focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900';
    $inputOk = 'border-slate-200';
    $inputErr = 'border-rose-300 dark:border-rose-500/50';
    $labelClass = 'block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mb-1.5';
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.customers.update', $customer) : route('admin.customers.store') }}"
      class="bg-white border border-slate-200/70 rounded-2xl p-5 sm:p-6 bento-shadow space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label for="name" class="{{ $labelClass }}">{{ __('Full name or business name') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required maxlength="160"
                   class="{{ $inputBase }} {{ $errors->has('name') ? $inputErr : $inputOk }}">
            @error('name')<p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="phone" class="{{ $labelClass }}">{{ __('Phone') }}</label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" required dir="ltr" inputmode="tel"
                   placeholder="0770 123 4567"
                   class="{{ $inputBase }} {{ $errors->has('phone') ? $inputErr : $inputOk }}">
            <p class="text-[11px] text-slate-500 mt-1.5">{{ __('Local (0770…) or international (+964…) — both are saved the same way.') }}</p>
            @error('phone')<p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="whatsapp" class="{{ $labelClass }}">{{ __('WhatsApp number') }}</label>
            <input id="whatsapp" type="tel" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp ?? '') }}" dir="ltr" inputmode="tel"
                   class="{{ $inputBase }} {{ $errors->has('whatsapp') ? $inputErr : $inputOk }}">
            <p class="text-[11px] text-slate-500 mt-1.5">{{ __('Leave empty if it is the same as the phone number.') }}</p>
            @error('whatsapp')<p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="city" class="{{ $labelClass }}">{{ __('City / Governorate') }}</label>
            <input id="city" type="text" name="city" value="{{ old('city', $customer->city ?? '') }}" maxlength="120" list="customer-cities"
                   class="{{ $inputBase }} {{ $errors->has('city') ? $inputErr : $inputOk }}">
            <datalist id="customer-cities">
                @foreach ($cities as $city)
                    <option value="{{ $city }}"></option>
                @endforeach
            </datalist>
            @error('city')<p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="address" class="{{ $labelClass }}">{{ __('Full address') }}</label>
            <input id="address" type="text" name="address" value="{{ old('address', $customer->address ?? '') }}" maxlength="1000"
                   class="{{ $inputBase }} {{ $errors->has('address') ? $inputErr : $inputOk }}">
            @error('address')<p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2">
            <label for="notes" class="{{ $labelClass }}">{{ __('Note (optional)') }}</label>
            <textarea id="notes" name="notes" rows="3" maxlength="2000"
                      class="{{ $areaBase }} {{ $errors->has('notes') ? $inputErr : $inputOk }}">{{ old('notes', $customer->notes ?? '') }}</textarea>
            @error('notes')<p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:hover:bg-slate-800">{{ __('Cancel') }}</a>
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:text-slate-900 dark:hover:bg-slate-100">
            {{ $isEdit ? __('Update Customer') : __('Save Customer') }}
        </button>
    </div>
</form>
