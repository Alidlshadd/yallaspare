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
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label for="phone" class="block text-sm font-medium text-app">{{ __('user.phone') }}</label>
                                @if ($user->phone_verified_at)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" title="{{ __('user.phone_verified') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 0 1 3.172 4.75a.75.75 0 0 0-.438.607v4.034c0 4.382 2.987 7.578 6.927 8.66a1.25 1.25 0 0 0 .678 0c3.94-1.082 6.927-4.278 6.927-8.66V5.357a.75.75 0 0 0-.438-.607A11.954 11.954 0 0 1 10 1.944Zm3.03 5.526a.75.75 0 0 1 0 1.06l-3.5 3.5a.75.75 0 0 1-1.06 0l-1.5-1.5a.75.75 0 0 1 1.06-1.06L9 10.44l2.97-2.97a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('user.verified') }}
                                    </span>
                                @elseif ($user->phone)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.493-1.647-1.743-2.98l5.58-9.921ZM10 6.75a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3a.75.75 0 0 1 .75-.75Zm0 6.75a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('user.unverified') }}
                                    </span>
                                @endif
                            </div>
                            <input
                                id="phone"
                                name="phone"
                                type="text"
                                value="{{ old('phone', $user->phone) }}"
                                aria-describedby="phone-hint"
                                class="block w-full rounded-app border bg-surface-2 px-3 py-2.5 text-sm text-app placeholder:text-slate-400 focus-ring @error('phone') border-[var(--danger)] @else border-app @enderror"
                            >
                            @error('phone')
                                <p class="text-xs font-medium text-[var(--danger)]">{{ $message }}</p>
                            @else
                                <p id="phone-hint" class="text-xs text-muted">{{ __('user.phone_help') }}</p>
                            @enderror
                        </div>
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

            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-semibold text-app">
                            <svg class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M2 3.5A1.5 1.5 0 0 1 3.5 2h2.132a1.5 1.5 0 0 1 1.423 1.026l.8 2.4a1.5 1.5 0 0 1-.724 1.8l-1.004.502a11.04 11.04 0 0 0 6.145 6.145l.502-1.004a1.5 1.5 0 0 1 1.8-.724l2.4.8A1.5 1.5 0 0 1 18 14.368V16.5a1.5 1.5 0 0 1-1.5 1.5H16C8.268 18 2 11.732 2 4v-.5Z" />
                            </svg>
                            {{ __('user.phone_verification') }}
                        </h2>
                        <p class="mt-1 text-sm text-muted">{{ __('user.phone_verification_hint') }}</p>
                    </div>
                </x-slot>

                @if (session('phone_verification_success'))
                    <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">
                        {{ session('phone_verification_success') }}
                    </div>
                @endif

                @if (session('phone_verification_sent'))
                    <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm font-medium text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-200">
                        {{ session('phone_verification_sent') }}
                    </div>
                @endif

                @error('phone_verification')
                    <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
                        {{ $message }}
                    </div>
                @enderror

                @if ($user->phone_verified_at)
                    <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-7.5 10a.75.75 0 0 1-1.127.075l-5-5a.75.75 0 0 1 1.06-1.06l4.39 4.389 6.982-9.313a.75.75 0 0 1 1.052-.143Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <p class="font-semibold text-emerald-900 dark:text-emerald-100">{{ __('user.phone_verified') }}</p>
                            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">{{ __('user.phone_verified_description') }}</p>
                        </div>
                    </div>
                @elseif ($user->phone)
                    <div class="grid gap-4 lg:grid-cols-[auto_minmax(0,1fr)] lg:items-end">
                        <form method="POST" action="{{ route('user.account.phone-verification.send') }}">
                            @csrf
                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-primary px-5 text-sm font-semibold text-white transition duration-200 hover:bg-[#10106a] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 lg:w-auto">
                                {{ __('user.send_verification_code') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('user.account.phone-verification.verify') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                            @csrf
                            <div class="space-y-2">
                                <label for="verification_code" class="block text-sm font-medium text-app">{{ __('user.verification_code') }}</label>
                                <input
                                    id="verification_code"
                                    name="verification_code"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    class="block w-full rounded-app border bg-surface-2 px-3 py-2.5 text-sm tracking-[0.3em] text-app placeholder:text-slate-400 focus-ring @error('verification_code') border-[var(--danger)] @else border-app @enderror"
                                    placeholder="000000"
                                >
                                @error('verification_code')
                                    <p class="text-xs font-medium text-[var(--danger)]">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('user.verify_phone') }}
                            </button>
                        </form>
                    </div>
                    <p class="mt-4 text-xs text-muted">{{ __('user.phone_verification_saved_number_notice', ['phone' => $user->phone]) }}</p>
                @else
                    <p class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
                        {{ __('user.phone_required_for_verification') }}
                    </p>
                @endif
            </x-ui.card>
        </div>
    </div>

@endsection
