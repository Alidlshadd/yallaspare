@php
    $avatarInitial = strtoupper(substr((string) ($user->name ?: 'A'), 0, 1));
    $profilePhotoUrl = !empty($user->profile_photo_path)
        ? asset('storage/' . ltrim((string) $user->profile_photo_path, '/'))
        : null;
    $roleLabel = ucfirst(str_replace('_', ' ', (string) $user->role));
    $isVerified = $user->email_verified_at !== null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-slate-100">{{ __('Profile') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
            <div class="bg-slate-950 px-6 py-8 text-white sm:px-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        @if ($profilePhotoUrl)
                            <img
                                src="{{ $profilePhotoUrl }}"
                                alt="{{ __(':name profile photo', ['name' => $user->name]) }}"
                                class="h-20 w-20 rounded-full border border-white/20 object-cover"
                            >
                        @else
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-indigo-600 text-2xl font-semibold text-white">
                                {{ $avatarInitial }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-300">{{ __('Admin Account') }}</p>
                            <h3 class="mt-1 truncate text-3xl font-semibold tracking-[-0.03em]">{{ $user->name }}</h3>
                            <p class="mt-1 truncate text-sm text-slate-300">{{ $user->email }}</p>
                            @if ($user->phone)
                                <p class="mt-1 truncate text-sm text-slate-400">{{ $user->phone }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-sm font-medium text-white">
                            <i class="fas fa-shield-halved mr-2 text-slate-300"></i>
                            {{ $roleLabel }}
                        </span>
                        <span class="inline-flex items-center rounded-full border px-3 py-1.5 text-sm font-medium {{ $isVerified ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-100' : 'border-amber-300/30 bg-amber-300/10 text-amber-100' }}">
                            <i class="fas {{ $isVerified ? 'fa-circle-check' : 'fa-circle-exclamation' }} mr-2"></i>
                            {{ $isVerified ? __('Email Verified') : __('Unverified') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 divide-y divide-slate-200/80 dark:divide-slate-800 lg:grid-cols-3 lg:divide-x lg:divide-y-0">
                <div class="px-6 py-5 sm:px-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Role') }}</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ $roleLabel }}</p>
                </div>
                <div class="px-6 py-5 sm:px-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Created') }}</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ $user->created_at?->format('d M Y') ?? '-' }}</p>
                </div>
                <div class="px-6 py-5 sm:px-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Updated') }}</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ $user->updated_at?->format('d M Y') ?? '-' }}</p>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="space-y-4">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="px-3 pb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Account Sections') }}</p>
                    <nav class="space-y-1">
                        <a href="#profile-details" class="flex items-start gap-3 rounded-2xl bg-slate-100 px-3 py-3 text-slate-950 dark:bg-slate-800 dark:text-white">
                            <span class="mt-0.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-primary dark:bg-white"></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium">{{ __('Profile Information') }}</span>
                                <span class="mt-0.5 block text-xs text-slate-600 dark:text-slate-300">{{ __('Name') }} / {{ __('Email') }}</span>
                            </span>
                        </a>
                        <a href="#security" class="flex items-start gap-3 rounded-2xl px-3 py-3 text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                            <span class="mt-0.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium">{{ __('Security') }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Password') }}</span>
                            </span>
                        </a>
                    </nav>
                </div>
            </aside>

            <div class="space-y-6">
                <section id="profile-details" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
                    <div class="border-b border-slate-200/80 pb-6 dark:border-slate-800">
                        <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Profile Information') }}</h3>
                    </div>

                    <form method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                        @csrf
                        @method('patch')

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <label for="profile_photo" class="group relative inline-flex h-24 w-24 shrink-0 cursor-pointer overflow-hidden rounded-full border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-950">
                                @if ($profilePhotoUrl)
                                    <img src="{{ $profilePhotoUrl }}" alt="{{ __('Current profile photo') }}" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center bg-primary text-2xl font-semibold text-white">{{ $avatarInitial }}</span>
                                @endif
                                <span class="absolute inset-x-0 bottom-0 bg-slate-950/70 px-2 py-1 text-center text-[11px] font-semibold text-white opacity-0 transition group-hover:opacity-100">{{ __('Change') }}</span>
                                <input id="profile_photo" name="profile_photo" type="file" accept="image/*" class="sr-only">
                            </label>

                            <div class="space-y-2">
                                @if ($profilePhotoUrl)
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                        <input type="checkbox" name="remove_profile_photo" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                                        <span>{{ __('Remove photo') }}</span>
                                    </label>
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="name" :value="__('Name')" class="dark:text-slate-200" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-2xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('Email')" class="dark:text-slate-200" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-2xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" :value="old('email', $user->email)" required autocomplete="username" />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>

                            <div>
                                <x-input-label for="phone" :value="__('Phone')" class="dark:text-slate-200" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full rounded-2xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" :value="old('phone', $user->phone)" autocomplete="tel" placeholder="+964..." />
                                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#10106a] focus:outline-none focus:ring-4 focus:ring-primary/20">
                                <i class="fas fa-floppy-disk"></i>
                                {{ __('Save') }}
                            </button>

                            @if (session('status') === 'profile-updated')
                                <p
                                    x-data="{ show: true }"
                                    x-show="show"
                                    x-transition
                                    x-init="setTimeout(() => show = false, 2000)"
                                    class="text-sm text-slate-600 dark:text-slate-400"
                                >{{ __('Saved.') }}</p>
                            @endif
                        </div>
                    </form>
                </section>

                <section id="security" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
                    <div class="border-b border-slate-200/80 pb-6 dark:border-slate-800">
                        <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ __('Update Password') }}</h3>
                    </div>

                    <form method="post" action="{{ route('password.update') }}" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @csrf
                        @method('put')

                        <div class="md:col-span-2">
                            <x-input-label for="update_password_current_password" :value="__('Current Password')" class="dark:text-slate-200" />
                            <x-password-input id="update_password_current_password" name="current_password" container-class="mt-1" class="block w-full rounded-2xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" autocomplete="current-password" />
                            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="update_password_password" :value="__('New Password')" class="dark:text-slate-200" />
                            <x-password-input id="update_password_password" name="password" container-class="mt-1" class="block w-full rounded-2xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" autocomplete="new-password" />
                            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="dark:text-slate-200" />
                            <x-password-input id="update_password_password_confirmation" name="password_confirmation" container-class="mt-1" class="block w-full rounded-2xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" autocomplete="new-password" />
                            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4 md:col-span-2">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-900/20 dark:bg-slate-100 dark:text-slate-950 dark:hover:bg-white">
                                <i class="fas fa-key"></i>
                                {{ __('Save') }}
                            </button>

                            @if (session('status') === 'password-updated')
                                <p
                                    x-data="{ show: true }"
                                    x-show="show"
                                    x-transition
                                    x-init="setTimeout(() => show = false, 2000)"
                                    class="text-sm text-slate-600 dark:text-slate-400"
                                >{{ __('Saved.') }}</p>
                            @endif
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
