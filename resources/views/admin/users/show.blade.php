<x-app-layout>
    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
        $selectedPermissions = old('permissions', $user->effectivePermissions());
        $reviewAverage = $userReviews->avg('rating');
        $dateOfBirth = $user->date_of_birth;
        $userAge = $dateOfBirth && $dateOfBirth->isPast() ? $dateOfBirth->age : null;
        $canManageBan = auth()->user()?->isSuperAdmin() || auth()->user()?->role === \App\Models\User::ROLE_ADMIN;

        $managerRoleList = [
            \App\Models\User::ROLE_PRODUCT_MANAGER,
            \App\Models\User::ROLE_ORDER_MANAGER,
            \App\Models\User::ROLE_FINANCE_MANAGER,
            \App\Models\User::ROLE_INVENTORY_MANAGER,
            \App\Models\User::ROLE_SETTINGS_MANAGER,
        ];

        if ($user->role === \App\Models\User::ROLE_SUPER_ADMIN) {
            $roleMeta = [
                'label' => __('Super Admin'),
                'chip' => 'border-violet-300 bg-violet-50 text-violet-700 dark:border-violet-400/40 dark:bg-violet-400/10 dark:text-violet-300',
                'avatar' => 'bg-violet-100 text-violet-700 dark:bg-violet-400/15 dark:text-violet-300',
            ];
        } elseif ($user->role === \App\Models\User::ROLE_ADMIN) {
            $roleMeta = [
                'label' => __('Admin'),
                'chip' => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-400/40 dark:bg-rose-400/10 dark:text-rose-300',
                'avatar' => 'bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300',
            ];
        } elseif ($user->role === \App\Models\User::ROLE_DEALER) {
            $roleMeta = [
                'label' => __('Dealer'),
                'chip' => 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-300',
                'avatar' => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
            ];
        } elseif (in_array($user->role, $managerRoleList, true)) {
            $roleMeta = [
                'label' => __(ucwords(str_replace('_', ' ', $user->role))),
                'chip' => 'border-cyan-300 bg-cyan-50 text-cyan-700 dark:border-cyan-400/40 dark:bg-cyan-400/10 dark:text-cyan-300',
                'avatar' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-400/15 dark:text-cyan-300',
            ];
        } else {
            $roleMeta = [
                'label' => __('User'),
                'chip' => 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-400/40 dark:bg-blue-400/10 dark:text-blue-300',
                'avatar' => 'bg-blue-100 text-blue-700 dark:bg-blue-400/15 dark:text-blue-300',
            ];
        }

        $inputClasses = 'w-full rounded-lg border-gray-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100';
        $labelClasses = 'mb-1.5 block text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-slate-400';

        $orderStatusChip = function (string $status) {
            return match ($status) {
                'delivered' => 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-400/10 dark:text-emerald-300',
                'cancelled' => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-400/40 dark:bg-rose-400/10 dark:text-rose-300',
                'pending' => 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-300',
                'processing' => 'border-cyan-300 bg-cyan-50 text-cyan-700 dark:border-cyan-400/40 dark:bg-cyan-400/10 dark:text-cyan-300',
                'shipped' => 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-400/40 dark:bg-blue-400/10 dark:text-blue-300',
                default => 'border-gray-300 bg-gray-50 text-gray-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300',
            };
        };
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-slate-100">{{ __('User Details') }}</h2>
                <p class="text-sm text-gray-500 mt-1 dark:text-slate-400">{{ __('Extended user insights (super admin only)') }}</p>
            </div>
            <a
                href="{{ route('admin.users.index') }}"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
            >
                ← {{ __('Back to Users') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Identity header --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-2xl font-extrabold {{ $roleMeta['avatar'] }}">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                            <span class="text-sm tabular-nums text-gray-400 dark:text-slate-500">#{{ $user->id }}</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $roleMeta['chip'] }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                {{ $roleMeta['label'] }}
                            </span>
                            @if($user->isBanned())
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-black {{ $user->isPermanentlyBanned() ? 'border-slate-950 bg-slate-950 text-white dark:border-black dark:bg-black' : 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-300' }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $user->isPermanentlyBanned() ? __('Permanent Ban') : __('Temporarily Banned') }}
                                </span>
                            @endif
                            @if ($user->email_verified_at)
                                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-400/10 dark:text-emerald-300">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                                    {{ __('Verified email') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-gray-300 bg-gray-50 px-2.5 py-0.5 text-[11px] font-bold text-gray-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    {{ __('Unverified') }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-gray-500 dark:text-slate-400">
                            <span class="truncate">{{ $user->email }}</span>
                            @if ($user->phone)
                                <span dir="ltr">{{ $user->phone }}</span>
                            @endif
                            <span>{{ __('Joined') }} {{ $user->created_at?->format('d M Y') }}</span>
                            @if ($userAge !== null)
                                <span>{{ __('Age') }}: {{ $userAge }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm sm:text-end">
                        <span class="text-[11px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Last Order') }}</span>
                        <span class="text-[11px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Updated') }}</span>
                        <span class="font-semibold tabular-nums text-gray-800 dark:text-slate-100">{{ $stats['last_order_at'] ? $stats['last_order_at']->format('d M Y') : '—' }}</span>
                        <span class="font-semibold tabular-nums text-gray-800 dark:text-slate-100">{{ $user->updated_at?->format('d M Y') }}</span>
                    </div>
                </div>
            </section>

            {{-- Insights --}}
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700/60 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-slate-400">{{ __('Total Orders') }}</p>
                    <p class="mt-2 text-2xl font-extrabold tabular-nums text-gray-900 dark:text-white">{{ number_format($stats['orders_total']) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-300/70 bg-white p-4 shadow-sm dark:border-emerald-400/35 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-300">{{ __('Delivered Orders') }}</p>
                    <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700 dark:text-emerald-300">{{ number_format($stats['orders_delivered']) }}</p>
                </div>
                <div class="rounded-xl border border-rose-300/70 bg-white p-4 shadow-sm dark:border-rose-400/35 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-rose-600 dark:text-rose-300">{{ __('Cancelled Orders') }}</p>
                    <p class="mt-2 text-2xl font-extrabold tabular-nums text-rose-700 dark:text-rose-300">{{ number_format($stats['orders_cancelled']) }}</p>
                </div>
                <div class="rounded-xl border border-blue-300/70 bg-white p-4 shadow-sm dark:border-blue-400/35 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-blue-600 dark:text-blue-300">{{ __('Total Spent') }}</p>
                    <p class="mt-2 text-2xl font-extrabold tabular-nums text-blue-700 dark:text-blue-300">{{ number_format($stats['spent_total'], $currencyDecimals) }} <span class="text-sm font-bold">{{ $currencyLabel }}</span></p>
                </div>
                <div class="rounded-xl border border-amber-300/70 bg-white p-4 shadow-sm dark:border-amber-400/35 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-amber-600 dark:text-amber-300">{{ __('Customer Reviews') }}</p>
                    <p class="mt-2 text-2xl font-extrabold tabular-nums text-amber-700 dark:text-amber-300">{{ $reviewAverage ? number_format((float) $reviewAverage, 1) : '0.0' }}<span class="text-sm font-bold text-gray-400 dark:text-slate-500"> / 5 · {{ number_format($userReviews->count()) }}</span></p>
                </div>
            </div>

            {{-- Edit + side column --}}
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)] items-start">
                <form method="POST" action="{{ route('admin.users.update-details', $user) }}" class="space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    @csrf
                    @method('PATCH')

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-amber-600 dark:text-amber-300">{{ __('Admin Controls') }}</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ __('Editable user profile') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ __('Change account identity, role posture, dealer settings, and verification state from one section.') }}</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="name" class="{{ $labelClasses }}">{{ __('Full Name') }}</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="{{ $inputClasses }}">
                        </div>
                        <div>
                            <label for="phone" class="{{ $labelClasses }}">{{ __('Phone') }}</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="{{ $inputClasses }}">
                        </div>
                        <div class="md:col-span-2">
                            <label for="email" class="{{ $labelClasses }}">{{ __('Email') }}</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $inputClasses }}">
                        </div>
                        <div>
                            <label for="date_of_birth" class="{{ $labelClasses }}">{{ __('Date Of Birth') }}</label>
                            <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $dateOfBirth?->format('Y-m-d')) }}" class="{{ $inputClasses }}">
                        </div>
                        <div>
                            <label for="role" class="{{ $labelClasses }}">{{ __('Role') }}</label>
                            <select id="role" name="role" class="{{ $inputClasses }}">
                                @foreach ($roleOptions as $option)
                                    <option value="{{ $option }}" @selected(old('role', $user->role) === $option)>{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="dealer_status" class="{{ $labelClasses }}">{{ __('Dealer Status') }}</label>
                            <select id="dealer_status" name="dealer_status" class="{{ $inputClasses }}">
                                @foreach ($dealerStatuses as $dealerStatus)
                                    <option value="{{ $dealerStatus }}" @selected(old('dealer_status', $user->dealer_status) === $dealerStatus)>{{ ucwords(str_replace('_', ' ', $dealerStatus)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="dealer_discount" class="{{ $labelClasses }}">{{ __('Dealer Discount (%)') }}</label>
                            <input id="dealer_discount" type="number" name="dealer_discount" min="0" max="100" step="0.01" value="{{ old('dealer_discount', number_format((float) $user->dealer_discount, 2, '.', '')) }}" class="{{ $inputClasses }}">
                        </div>
                    </div>

                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200">
                        <input type="checkbox" name="email_verified" value="1" class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400" @checked(old('email_verified', $user->email_verified_at !== null))>
                        <span>{{ __('Mark this account as email verified') }}</span>
                    </label>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-slate-400">{{ __('Module Permissions') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ __('Super admin always has every permission. Other roles can be narrowed or expanded here.') }}</p>
                            </div>
                            @if ($user->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-violet-300 bg-violet-50 px-2.5 py-0.5 text-[11px] font-bold text-violet-700 dark:border-violet-400/40 dark:bg-violet-400/10 dark:text-violet-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ __('All Access') }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach ($permissionGroups as $group => $permissions)
                                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950">
                                    <p class="text-xs font-bold text-gray-800 dark:text-slate-100">{{ __($group) }}</p>
                                    <div class="mt-3 space-y-2">
                                        @foreach ($permissions as $permission => $label)
                                            <label class="flex items-start gap-3 text-sm text-gray-600 dark:text-slate-300">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission }}"
                                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400"
                                                    @checked(in_array($permission, $selectedPermissions, true) || $user->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                                    @disabled($user->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                                >
                                                <span>{{ __($label) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('If the selected role is not `Dealer`, dealer status and discount will be reset automatically.') }}</p>
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-amber-300">
                            {{ __('Save User Details') }}
                        </button>
                    </div>
                </form>

                <div class="space-y-4">
                    <section class="rounded-xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-900/50 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-widest text-rose-600 dark:text-rose-300">{{ __('Account Access') }}</p>
                                <h4 class="mt-1 text-base font-bold text-gray-900 dark:text-white">{{ __('Ban Management') }}</h4>
                            </div>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-black {{ $user->isBanned() ? ($user->isPermanentlyBanned() ? 'border-slate-950 bg-slate-950 text-white dark:border-black dark:bg-black' : 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-300') : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-400/10 dark:text-emerald-300' }}">
                                {{ $user->isBanned() ? ($user->isPermanentlyBanned() ? __('Permanent Ban') : __('Temporary Ban')) : __('Active') }}
                            </span>
                        </div>

                        @if($user->isBanned())
                            <dl class="mt-4 space-y-3 text-sm">
                                @if($user->banned_until)
                                    <div>
                                        <dt class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-slate-500">{{ __('Banned Until') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900 dark:text-slate-100">{{ $user->banned_until->format('d M Y H:i') }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-slate-500">{{ __('Reason') }}</dt>
                                    <dd class="mt-1 text-gray-700 dark:text-slate-300">{{ $user->ban_reason ?: __('No reason provided.') }}</dd>
                                </div>
                            </dl>

                            @if($canManageBan)
                                <form method="POST" action="{{ route('admin.users.destroy-ban', $user) }}" class="mt-4" data-danger-confirm data-danger-title="{{ __('Remove User Ban') }}" data-danger-description="{{ __('This user will be able to sign in again immediately.') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-500">
                                        {{ __('Remove Ban') }}
                                    </button>
                                </form>
                            @endif
                        @elseif($canManageBan)
                            <form method="POST" action="{{ route('admin.users.update-ban', $user) }}" class="mt-4 space-y-4" data-danger-confirm data-danger-title="{{ __('Ban User') }}" data-danger-description="{{ __('The user will be signed out from all devices and blocked from signing in.') }}">
                                @csrf
                                @method('PATCH')

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-400/25 dark:bg-amber-400/5">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-400 text-slate-950 shadow-sm">
                                            <i class="fas fa-clock" aria-hidden="true"></i>
                                        </div>
                                        <p class="mt-3 text-sm font-black text-slate-900 dark:text-white">{{ __('Temporary Ban') }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Locks the account for a selected period.') }}</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3 text-white shadow-sm dark:border-black dark:bg-black">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-white/10 text-white">
                                            <i class="fas fa-lock" aria-hidden="true"></i>
                                        </div>
                                        <p class="mt-3 text-sm font-black">{{ __('Permanent Ban') }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-400">{{ __('Keeps the account locked until an admin removes it.') }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="duration_days" class="{{ $labelClasses }}">{{ __('Temporary Ban Duration') }}</label>
                                    <select id="duration_days" name="duration_days" class="{{ $inputClasses }}">
                                        <option value="1">{{ __('1 day') }}</option>
                                        <option value="7" selected>{{ __('7 days') }}</option>
                                        <option value="30">{{ __('30 days') }}</option>
                                        <option value="90">{{ __('90 days') }}</option>
                                        <option value="365">{{ __('365 days') }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="ban_reason" class="{{ $labelClasses }}">{{ __('Reason') }}</label>
                                    <textarea id="ban_reason" name="ban_reason" rows="3" maxlength="500" required class="{{ $inputClasses }}" placeholder="{{ __('Explain why this account is being banned...') }}">{{ old('ban_reason') }}</textarea>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <button
                                        type="submit"
                                        name="ban_type"
                                        value="temporary"
                                        data-danger-title="{{ __('Temporary Ban') }}"
                                        data-danger-description="{{ __('The account will be locked for the selected duration and signed out from all devices.') }}"
                                        class="group inline-flex min-h-12 items-center justify-between gap-3 rounded-xl bg-amber-400 px-4 py-3 text-start text-slate-950 shadow-sm transition hover:bg-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-400/25"
                                    >
                                        <span>
                                            <span class="block text-sm font-black">{{ __('Apply Temporary Ban') }}</span>
                                            <span class="block text-[11px] font-semibold text-amber-950/65">{{ __('Uses selected duration') }}</span>
                                        </span>
                                        <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="submit"
                                        name="ban_type"
                                        value="permanent"
                                        data-danger-title="{{ __('Permanent Ban') }}"
                                        data-danger-description="{{ __('The account will remain locked until a super admin or admin removes the ban.') }}"
                                        class="group inline-flex min-h-12 items-center justify-between gap-3 rounded-xl border border-black bg-slate-950 px-4 py-3 text-start text-white shadow-lg shadow-slate-950/20 transition hover:bg-black focus:outline-none focus:ring-4 focus:ring-slate-950/20 dark:bg-black"
                                    >
                                        <span>
                                            <span class="block text-sm font-black">{{ __('Apply Permanent Ban') }}</span>
                                            <span class="block text-[11px] font-semibold text-slate-400">{{ __('No automatic expiry') }}</span>
                                        </span>
                                        <i class="fas fa-lock text-xs text-slate-300" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </form>
                        @else
                            <p class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                                {{ __('Only super admins and admins can change account ban status.') }}
                            </p>
                        @endif
                    </section>

                    <form method="POST" action="{{ route('admin.users.update-password', $user) }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        @csrf
                        @method('PATCH')

                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-widest text-amber-600 dark:text-amber-300">{{ __('Password Reset') }}</p>
                            <h4 class="mt-1 text-base font-bold text-gray-900 dark:text-white">{{ __('Set a new password') }}</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ __('Existing passwords are encrypted and cannot be viewed. Super admins can replace the password from here.') }}</p>
                        </div>

                        <div>
                            <label for="password" class="{{ $labelClasses }}">{{ __('New Password') }}</label>
                            <x-password-input id="password" name="password" autocomplete="new-password" class="{{ $inputClasses }}" />
                            @error('password')
                                <p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="{{ $labelClasses }}">{{ __('Confirm New Password') }}</label>
                            <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="{{ $inputClasses }}" />
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-amber-300">
                            {{ __('Update Password') }}
                        </button>
                    </form>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-slate-400">{{ __('Current Snapshot') }}</p>
                        <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Email Verified') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y H:i') : __('Unverified') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Created') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $user->created_at?->format('d M Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Updated') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $user->updated_at?->format('d M Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Last Order') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $stats['last_order_at'] ? $stats['last_order_at']->format('d M Y H:i') : '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-slate-400">{{ __('Admin Notes') }}</p>
                        <ul class="mt-3 space-y-2 text-sm text-gray-600 dark:text-slate-300">
                            <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400"></span>{{ __('Role changes respect the existing super admin safety rules.') }}</li>
                            <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400"></span>{{ __('Dealer status and discount are only meaningful when the role is set to `Dealer`.') }}</li>
                            <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400"></span>{{ __('Email verification can be toggled directly without opening another workflow.') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Recent Orders --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-slate-800">
                    <h3 class="font-bold text-gray-800 dark:text-slate-100">{{ __('Recent Orders') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Last :count orders', ['count' => $recentOrders->count()]) }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-slate-800/70 dark:text-slate-300">
                            <tr>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-widest">{{ __('Order') }}</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-widest">{{ __('Status') }}</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-widest">{{ __('Amount') }}</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-widest">{{ __('Date') }}</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-widest text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                            @forelse($recentOrders as $order)
                                <tr class="hover:bg-gray-50 transition dark:hover:bg-slate-800/60">
                                    <td class="p-4 font-semibold text-slate-900 dark:text-slate-100">{{ $order->order_number }}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $orderStatusChip((string) $order->status) }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    </td>
                                    <td class="p-4 tabular-nums text-slate-900 dark:text-slate-100">{{ number_format((float) $order->total_amount, $currencyDecimals) }} {{ $currencyLabel }}</td>
                                    <td class="p-4 tabular-nums text-gray-500 dark:text-slate-400">{{ $order->created_at?->format('d M Y H:i') }}</td>
                                    <td class="p-4 text-right">
                                        <a
                                            href="{{ route('admin.orders.show', $order) }}"
                                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            {{ __('Open Order') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-10 text-center text-gray-500 dark:text-slate-400">{{ __('No orders found for this user.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Reviews --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-slate-100">{{ __('Customer Reviews') }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ __('All product reviews written by this user.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-gray-300 bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ number_format($userReviews->count()) }} {{ __('reviews') }}
                        </span>
                        <span class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-300">
                            ★ {{ $reviewAverage ? number_format((float) $reviewAverage, 1) : '0.0' }} / 5
                        </span>
                        @can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE)
                            <a
                                href="{{ route('admin.reviews.index', ['search' => $user->email]) }}"
                                class="inline-flex items-center rounded-lg bg-amber-400 px-3 py-1.5 text-xs font-bold text-slate-900 transition hover:bg-amber-300"
                            >
                                {{ __('Open Review Manager') }}
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-slate-800">
                    @forelse($userReviews as $review)
                        <div class="grid gap-4 p-4 lg:grid-cols-[minmax(220px,0.8fr)_minmax(0,1.2fr)_auto]">
                            <div class="flex items-center gap-3">
                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-950">
                                    @if($review->product?->image)
                                        <img src="{{ asset('storage/' . ltrim((string) $review->product->image, '/')) }}" alt="{{ $review->product->name }}" class="h-full w-full object-contain">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-gray-400 dark:text-slate-500">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16 9 11l4 4 3-3 4 4" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    @if($review->product)
                                        @can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE)
                                            <a href="{{ route('admin.products.edit', $review->product) }}" class="block truncate text-sm font-semibold text-amber-700 hover:text-amber-600 dark:text-amber-300 dark:hover:text-amber-200">
                                                {{ $review->product->name }}
                                            </a>
                                        @else
                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $review->product->name }}</p>
                                        @endcan
                                        <p class="mt-1 truncate text-xs text-gray-500 dark:text-slate-400">{{ __('SKU:') }} {{ $review->product->sku ?: '-' }}</p>
                                    @else
                                        <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">{{ __('Product unavailable') }}</p>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-300">
                                        ★ {{ (int) $review->rating }} / 5
                                    </span>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $review->is_approved ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-400/10 dark:text-emerald-300' : 'border-gray-300 bg-gray-50 text-gray-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $review->is_approved ? __('Approved') : __('Hidden') }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-slate-400">
                                        {{ optional($review->reviewed_at ?? $review->created_at)->format('M d, Y') ?: '-' }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $review->title ?: __('Customer review') }}</p>
                                @if($review->comment)
                                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-slate-300">{{ $review->comment }}</p>
                                @endif
                            </div>

                            @can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE)
                                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" data-confirm="{{ __('Delete this review?') }}" class="lg:text-right">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-400/40 dark:bg-rose-400/10 dark:text-rose-300 dark:hover:bg-rose-400/20">
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <p class="text-sm font-semibold text-gray-700 dark:text-slate-200">{{ __('No reviews found for this user.') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ __('Reviews will appear here after this customer rates purchased items.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
