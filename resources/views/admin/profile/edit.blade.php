@php
    $avatarInitial = strtoupper(substr((string) ($user->name ?: 'A'), 0, 1));
    $profilePhotoUrl = ! empty($user->profile_photo_path)
        ? asset('storage/' . ltrim((string) $user->profile_photo_path, '/'))
        : null;
    $roleLabel = ucfirst(str_replace('_', ' ', (string) $user->role));
    $isVerified = $user->email_verified_at !== null;
    $adminTwoFactorRequired = (bool) config('security.admin_two_factor.enabled');
    $permissionLabels = collect($permissionGroups ?? [])
        ->flatMap(fn (array $permissions) => $permissions)
        ->only($effectivePermissions ?? [])
        ->map(fn ($label, $key) => ['key' => (string) $key, 'label' => (string) $label])
        ->values();
    $visiblePermissions = $permissionLabels->take(7);
    $remainingPermissionCount = max(0, $permissionLabels->count() - $visiblePermissions->count());
    $accountAgeLabel = $user->created_at ? $user->created_at->diffForHumans(null, true) : '-';
    $updatedLabel = $user->updated_at ? $user->updated_at->format('d M Y, H:i') : '-';
@endphp

<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-5">
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="bg-[#04042a] px-5 py-5 text-white sm:px-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="relative shrink-0">
                            @if ($profilePhotoUrl)
                                <img src="{{ $profilePhotoUrl }}" alt="{{ __(':name profile photo', ['name' => $user->name]) }}" class="h-20 w-20 rounded-xl border border-white/15 object-cover">
                            @else
                                <div class="flex h-20 w-20 items-center justify-center rounded-xl border border-white/15 bg-[#04042a] text-2xl font-black text-amber-300">
                                    {{ $avatarInitial }}
                                </div>
                            @endif
                            <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-lg border border-[#04042a] {{ $isVerified ? 'bg-emerald-400 text-emerald-950' : 'bg-rose-400 text-rose-950' }}">
                                <i class="fas {{ $isVerified ? 'fa-check' : 'fa-exclamation' }} text-[10px]"></i>
                            </span>
                        </div>

                        <div class="min-w-0">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-white/50">{{ __('Admin Profile') }}</p>
                            <h1 class="mt-1 truncate text-2xl font-black tracking-tight sm:text-3xl">{{ $user->name }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-white/70">
                                <span class="truncate">{{ $user->email }}</span>
                                @if ($user->phone)
                                    <span class="hidden text-white/30 sm:inline">/</span>
                                    <span>{{ $user->phone }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-2 rounded-lg border border-white/15 bg-white/10 px-3 py-2 text-xs font-bold text-white">
                            <i class="fas fa-shield-halved text-white/70"></i>
                            {{ $roleLabel }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold {{ $isVerified ? 'border-emerald-300/30 bg-emerald-400/10 text-emerald-100' : 'border-rose-300/30 bg-rose-400/10 text-rose-100' }}">
                            <i class="fas {{ $isVerified ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                            {{ $isVerified ? __('Verified Email') : __('Email Pending') }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold {{ $adminTwoFactorRequired ? 'border-sky-300/30 bg-sky-400/10 text-sky-100' : 'border-slate-300/30 bg-white/10 text-slate-100' }}">
                            <i class="fas fa-lock"></i>
                            {{ $adminTwoFactorRequired ? __('Admin 2FA') : __('2FA Optional') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid divide-y divide-slate-200 dark:divide-slate-800 md:grid-cols-4 md:divide-x md:divide-y-0">
                <div class="px-5 py-4">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">{{ __('Role') }}</p>
                    <p class="mt-1 text-sm font-extrabold text-slate-900 dark:text-white">{{ $roleLabel }}</p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">{{ __('Permissions') }}</p>
                    <p class="mt-1 text-sm font-extrabold text-slate-900 dark:text-white">{{ number_format($permissionLabels->count()) }}</p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">{{ __('Account Age') }}</p>
                    <p class="mt-1 text-sm font-extrabold text-slate-900 dark:text-white">{{ $accountAgeLabel }}</p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">{{ __('Last Updated') }}</p>
                    <p class="mt-1 text-sm font-extrabold text-slate-900 dark:text-white">{{ $updatedLabel }}</p>
                </div>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
            <aside class="space-y-5">
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ __('Account Status') }}</p>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('Email') }}</span>
                            <span class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1 text-xs font-bold {{ $isVerified ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300' }}">
                                <i class="fas {{ $isVerified ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                                {{ $isVerified ? __('Verified') : __('Pending') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('Admin 2FA') }}</span>
                            <span class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1 text-xs font-bold {{ $adminTwoFactorRequired ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/30 dark:text-sky-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                <i class="fas fa-key text-[10px]"></i>
                                {{ $adminTwoFactorRequired ? __('Required') : __('Optional') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('Profile Photo') }}</span>
                            <span class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1 text-xs font-bold {{ $profilePhotoUrl ? 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                <i class="fas fa-image text-[10px]"></i>
                                {{ $profilePhotoUrl ? __('Set') : __('Initials') }}
                            </span>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ __('Access Scope') }}</p>
                    </div>
                    <div class="px-5 py-4">
                        @if ($permissionLabels->isEmpty())
                            <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
                                {{ __('No admin permissions assigned.') }}
                            </p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($visiblePermissions as $permission)
                                    <span class="inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                                        <i class="fas fa-check text-[10px] text-emerald-500"></i>
                                        <span class="truncate">{{ $permission['label'] }}</span>
                                    </span>
                                @endforeach
                                @if ($remainingPermissionCount > 0)
                                    <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-black text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        +{{ number_format($remainingPermissionCount) }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
            </aside>

            <div class="space-y-5">
                <section id="profile-details" class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ __('Profile Information') }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Identity, contact, and account photo') }}</p>
                        </div>
                        @if (session('status') === 'profile-updated')
                            <p x-data="reveal" x-show="show" x-transition x-init="autoHide()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <i class="fas fa-check"></i>
                                {{ __('Saved') }}
                            </p>
                        @endif
                    </div>

                    <form method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="p-5">
                        @csrf
                        @method('patch')

                        <div class="grid gap-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
                            <div class="space-y-3">
                                <label for="profile_photo" class="group block cursor-pointer">
                                    <span class="relative block h-40 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-950">
                                        @if ($profilePhotoUrl)
                                            <img src="{{ $profilePhotoUrl }}" alt="{{ __('Current profile photo') }}" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center bg-[#04042a] text-4xl font-black text-amber-300">{{ $avatarInitial }}</span>
                                        @endif
                                        <span class="absolute inset-x-3 bottom-3 inline-flex items-center justify-center gap-2 rounded-lg bg-slate-950/80 px-3 py-2 text-xs font-black text-white opacity-0 shadow-sm transition group-hover:opacity-100">
                                            <i class="fas fa-camera"></i>
                                            {{ __('Change Photo') }}
                                        </span>
                                    </span>
                                    <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
                                </label>

                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('JPG, PNG, or WebP up to 2 MB') }}</p>
                                    @if ($profilePhotoUrl)
                                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
                                            <input type="checkbox" name="remove_profile_photo" value="1" class="rounded border-slate-300 text-slate-400 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-500">
                                            <span>{{ __('Remove photo') }}</span>
                                        </label>
                                    @endif
                                    <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <x-input-label for="name" :value="__('Full Name')" class="text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                </div>

                                <div>
                                    <x-input-label for="email" :value="__('Email')" class="text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400" />
                                    <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" :value="old('email', $user->email)" required autocomplete="username" />
                                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                                </div>

                                <div>
                                    <x-input-label for="phone" :value="__('Phone')" class="text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400" />
                                    <x-text-input id="phone" name="phone" type="text" class="mt-1.5 block w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" :value="old('phone', $user->phone)" autocomplete="tel" placeholder="+964..." />
                                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                                </div>

                                <div class="md:col-span-2">
                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/60">
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ __('Email Verification') }}</p>
                                            <p class="mt-1 text-sm font-bold text-slate-700 dark:text-slate-200">{{ $isVerified ? __('Verified address') : __('Verification required after email changes') }}</p>
                                        </div>
                                        <span class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-black {{ $isVerified ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                            <i class="fas {{ $isVerified ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                                            {{ $isVerified ? __('Verified') : __('Pending') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-400 px-4 py-2.5 text-sm font-black text-slate-900 shadow-sm transition hover:bg-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                                <i class="fas fa-floppy-disk"></i>
                                {{ __('Save Profile') }}
                            </button>
                        </div>
                    </form>
                </section>

                <section id="security" class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ __('Password & Security') }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Password changes apply to this admin account') }}</p>
                        </div>
                        @if (session('status') === 'password-updated')
                            <p x-data="reveal" x-show="show" x-transition x-init="autoHide()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <i class="fas fa-check"></i>
                                {{ __('Saved') }}
                            </p>
                        @endif
                    </div>

                    <form method="post" action="{{ route('password.update') }}" class="p-5">
                        @csrf
                        @method('put')

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <x-input-label for="update_password_current_password" :value="__('Current Password')" class="text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400" />
                                <x-password-input id="update_password_current_password" name="current_password" container-class="mt-1.5" class="block w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" autocomplete="current-password" />
                                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="update_password_password" :value="__('New Password')" class="text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400" />
                                <x-password-input id="update_password_password" name="password" container-class="mt-1.5" class="block w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400" />
                                <x-password-input id="update_password_password_confirmation" name="password_confirmation" container-class="mt-1.5" class="block w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                <i class="fas fa-shield-halved text-emerald-500"></i>
                                <span>{{ __('Strong password rules are enforced.') }}</span>
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-black text-white transition hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-900/20 dark:bg-slate-100 dark:text-slate-950 dark:hover:bg-white">
                                <i class="fas fa-key"></i>
                                {{ __('Update Password') }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
