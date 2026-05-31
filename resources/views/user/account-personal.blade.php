@extends('layouts.user')

@section('title', __('Personal Info'))
@section('subtitle', __('Manage your identity, contact details, and primary delivery information'))
@section('actions')
    <div class="flex flex-wrap items-center justify-end gap-3">
        <a
            href="{{ route('user.account.edit') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            {{ __('Account') }}
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </a>
        <x-ui.button type="submit" form="profile-form">
            {{ __('user.save_changes') }}
        </x-ui.button>
    </div>
@endsection

@section('content')
    @php
        $selectedDobDay = (int) old('dob_day', optional($user->date_of_birth)->day);
        $selectedDobMonth = (int) old('dob_month', optional($user->date_of_birth)->month);
        $selectedDobYear = (int) old('dob_year', optional($user->date_of_birth)->year);
        $currentYear = now()->year;
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    @endphp

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            @include('user.partials.account-nav')
        </aside>

        <div class="space-y-6">
            @if (session('success'))
                <x-ui.alert variant="success" :title="__('user.success')">
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            @if ($errors->any())
                <x-ui.alert variant="danger" :title="__('user.please_review')">
                    {{ __('user.validation_summary') }}
                </x-ui.alert>
            @endif

            <form id="profile-form" method="POST" action="{{ route('user.account.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PATCH')

                <x-ui.card>
                    <x-slot name="header">
                        <div>
                            <h2 class="text-base font-semibold text-app">{{ __('Personal Info') }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ __('Core account details used across your profile and support requests.') }}</p>
                        </div>
                    </x-slot>

                    <div class="mb-4 flex justify-start">
                        <label for="profile_photo" class="group relative inline-flex cursor-pointer">
                            @if (!empty($user->profile_photo_path))
                                <img
                                    src="{{ asset('storage/' . ltrim((string) $user->profile_photo_path, '/')) }}"
                                    alt="{{ __('Current profile photo') }}"
                                    class="h-24 w-24 rounded-full object-cover border border-slate-200 dark:border-slate-700"
                                >
                            @else
                                <span class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-primary text-2xl font-semibold text-white">
                                    {{ strtoupper(substr((string) ($firstName ?: $user->name ?: 'U'), 0, 1)) }}
                                </span>
                            @endif
                            <input
                                id="profile_photo"
                                name="profile_photo"
                                type="file"
                                accept="image/*"
                                class="sr-only"
                            >
                        </label>
                    </div>
                    @error('profile_photo')
                        <p class="-mt-2 mb-4 text-center text-xs font-medium text-[var(--danger)]">{{ $message }}</p>
                    @enderror

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui.input name="first_name" :label="__('user.first_name')" :value="old('first_name', $firstName)" />
                        <x-ui.input name="last_name" :label="__('user.last_name')" :value="old('last_name', $lastName)" />
                        <x-ui.input name="email" type="email" :label="__('user.email')" :value="$user->email" readonly />
                        <x-ui.input name="phone" :label="__('user.phone')" :value="old('phone', $user->phone)" :hint="__('user.phone_help')" />
                        <div class="space-y-2 md:col-span-2">
                            <p class="block text-sm font-medium text-app">{{ __('Date of Birth') }}</p>
                            <p class="text-xs text-muted">{{ __('Select day, month, and year separately.') }}</p>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="space-y-2">
                                    <label for="dob_day" class="block text-xs font-medium text-muted">{{ __('Day') }}</label>
                                    <select
                                        id="dob_day"
                                        name="dob_day"
                                        class="block w-full rounded-app border border-app bg-surface-2 px-3 py-2.5 text-sm text-app focus-ring @error('dob_day') border-[var(--danger)] @enderror"
                                    >
                                        <option value="">{{ __('Day') }}</option>
                                        @for ($day = 1; $day <= 31; $day++)
                                            <option value="{{ $day }}" @selected($selectedDobDay === $day)>{{ $day }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label for="dob_month" class="block text-xs font-medium text-muted">{{ __('Month') }}</label>
                                    <select
                                        id="dob_month"
                                        name="dob_month"
                                        class="block w-full rounded-app border border-app bg-surface-2 px-3 py-2.5 text-sm text-app focus-ring @error('dob_month') border-[var(--danger)] @enderror"
                                    >
                                        <option value="">{{ __('Month') }}</option>
                                        @foreach ($monthNames as $monthNumber => $monthName)
                                            <option value="{{ $monthNumber }}" @selected($selectedDobMonth === $monthNumber)>{{ $monthName }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label for="dob_year" class="block text-xs font-medium text-muted">{{ __('Year') }}</label>
                                    <select
                                        id="dob_year"
                                        name="dob_year"
                                        class="block w-full rounded-app border border-app bg-surface-2 px-3 py-2.5 text-sm text-app focus-ring @error('dob_year') border-[var(--danger)] @enderror"
                                    >
                                        <option value="">{{ __('Year') }}</option>
                                        @for ($year = $currentYear; $year >= 1900; $year--)
                                            <option value="{{ $year }}" @selected($selectedDobYear === $year)>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            @if ($errors->has('dob_day') || $errors->has('dob_month') || $errors->has('dob_year'))
                                <p class="text-xs font-medium text-[var(--danger)]">
                                    {{ $errors->first('dob_day') ?: ($errors->first('dob_month') ?: $errors->first('dob_year')) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <x-slot name="header">
                        <div>
                            <h2 class="text-base font-semibold text-app">{{ __('Primary Delivery Info') }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ __('Quick access to your default delivery address for fast edits.') }}</p>
                        </div>
                    </x-slot>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui.input name="country" :label="__('user.country')" :value="old('country', $address?->country)" />
                        <x-ui.input name="city" :label="__('user.city')" :value="old('city', $address?->city)" />
                        <div class="md:col-span-2">
                            <x-ui.input name="address_line1" :label="__('user.address_line')" :value="old('address_line1', $address?->address_line1)" />
                        </div>
                        <x-ui.input name="address_line2" :label="__('user.building_apartment')" :value="old('address_line2', $address?->address_line2)" />
                        <x-ui.input name="notes" :label="__('user.notes')" :value="old('notes', $address?->notes)" :hint="__('user.notes_help')" />
                    </div>
                </x-ui.card>

                <div class="flex flex-col gap-3 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Save your personal and delivery changes before leaving this page.') }}</p>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                        <a
                            href="{{ route('user.account.edit') }}"
                            class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            {{ __('Cancel') }}
                        </a>
                        <button
                            type="submit"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-white transition duration-200 hover:bg-[#10106a] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M5.5 3.5A2.5 2.5 0 0 0 3 6v8a2.5 2.5 0 0 0 2.5 2.5h9A2.5 2.5 0 0 0 17 14V7.62a2.5 2.5 0 0 0-.73-1.77l-2.12-2.12A2.5 2.5 0 0 0 12.38 3H5.5v.5ZM6 5h5v3H6V5Zm1 8a2 2 0 1 1 4 0 2 2 0 0 1-4 0Z" />
                            </svg>
                            {{ __('user.save_changes') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
