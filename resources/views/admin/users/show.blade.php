<x-app-layout>
    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
        $selectedPermissions = old('permissions', $user->effectivePermissions());
        $reviewAverage = $userReviews->avg('rating');
    @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-slate-100">{{ __('User Details') }}</h2>
                <p class="text-sm text-gray-500 mt-1 dark:text-slate-400">{{ __('Extended user insights (super admin only)') }}</p>
            </div>
            <a
                href="{{ route('admin.users.index') }}"
                class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            >
                {{ __('Back to Users') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @php
                $dateOfBirth = $user->date_of_birth;
                $userAge = $dateOfBirth && $dateOfBirth->isPast() ? $dateOfBirth->age : null;
            @endphp

            @if (session('success'))
                <div class="rounded-[1.4rem] border border-emerald-200/80 bg-emerald-50/90 px-5 py-4 text-sm text-emerald-700 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-[1.4rem] border border-rose-200/80 bg-rose-50/90 px-5 py-4 text-sm text-rose-700 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-[1.4rem] border border-rose-200/80 bg-rose-50/90 px-5 py-4 text-sm text-rose-700 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.12),transparent_30%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] px-5 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))] sm:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Admin Controls') }}</p>
                            <h3 class="mt-1 text-xl font-semibold tracking-[-0.01em] text-slate-900 dark:text-slate-100">{{ __('Editable user profile') }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Change account identity, role posture, dealer settings, and verification state from one section.') }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">User #{{ $user->id }}</span>
                            <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/20 dark:text-blue-300">{{ ucwords(str_replace('_', ' ', $user->role)) }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 p-5 lg:grid-cols-[1.15fr_0.85fr] sm:p-6">
                    <form method="POST" action="{{ route('admin.users.update-details', $user) }}" class="space-y-5 rounded-[1.6rem] border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/80">
                        @csrf
                        @method('PATCH')

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="name" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Full Name') }}</label>
                                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div>
                                <label for="phone" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Phone') }}</label>
                                <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div class="md:col-span-2">
                                <label for="email" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Email') }}</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div>
                                <label for="date_of_birth" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Date Of Birth') }}</label>
                                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $dateOfBirth?->format('Y-m-d')) }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div>
                                <label for="role" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Role') }}</label>
                                <select id="role" name="role" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    @foreach ($roleOptions as $option)
                                        <option value="{{ $option }}" @selected(old('role', $user->role) === $option)>{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="dealer_status" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Dealer Status') }}</label>
                                <select id="dealer_status" name="dealer_status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    @foreach ($dealerStatuses as $dealerStatus)
                                        <option value="{{ $dealerStatus }}" @selected(old('dealer_status', $user->dealer_status) === $dealerStatus)>{{ ucwords(str_replace('_', ' ', $dealerStatus)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="dealer_discount" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Dealer Discount (%)') }}</label>
                                <input id="dealer_discount" type="number" name="dealer_discount" min="0" max="100" step="0.01" value="{{ old('dealer_discount', number_format((float) $user->dealer_discount, 2, '.', '')) }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                        </div>

                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-200">
                            <input type="checkbox" name="email_verified" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(old('email_verified', $user->email_verified_at !== null))>
                            <span>{{ __('Mark this account as email verified') }}</span>
                        </label>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/80">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Module Permissions') }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Super admin always has every permission. Other roles can be narrowed or expanded here.') }}</p>
                                </div>
                                @if ($user->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                    <span class="rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-violet-700 dark:border-violet-900/60 dark:bg-violet-950/20 dark:text-violet-300">{{ __('All Access') }}</span>
                                @endif
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach ($permissionGroups as $group => $permissions)
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950">
                                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ __($group) }}</p>
                                        <div class="mt-3 space-y-2">
                                            @foreach ($permissions as $permission => $label)
                                                <label class="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-300">
                                                    <input
                                                        type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permission }}"
                                                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
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
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('If the selected role is not `Dealer`, dealer status and discount will be reset automatically.') }}</p>
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                                {{ __('Save User Details') }}
                            </button>
                        </div>
                    </form>

                    <div class="space-y-4">
                        <div class="rounded-[1.6rem] border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/80">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Current Snapshot') }}</p>
                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">{{ __('Age') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $userAge !== null ? $userAge : '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">{{ __('Email Verified') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y H:i') : 'No' }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">{{ __('Created') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $user->created_at?->format('d M Y H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">{{ __('Updated') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $user->updated_at?->format('d M Y H:i') }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">{{ __('Last Order') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $stats['last_order_at'] ? $stats['last_order_at']->format('d M Y H:i') : '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.6rem] border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/80">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Admin Notes') }}</p>
                            <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                                <p>{{ __('Role changes respect the existing super admin safety rules.') }}</p>
                                <p>{{ __('Dealer status and discount are only meaningful when the role is set to `Dealer`.') }}</p>
                                <p>{{ __('Email verification can be toggled directly without opening another workflow.') }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.users.update-password', $user) }}" class="space-y-4 rounded-[1.6rem] border border-amber-200/90 bg-amber-50/80 p-5 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                            @csrf
                            @method('PATCH')

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">{{ __('Password Reset') }}</p>
                                <h4 class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Set a new password') }}</h4>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Existing passwords are encrypted and cannot be viewed. Super admins can replace the password from here.') }}</p>
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('New Password') }}</label>
                                <x-password-input id="password" name="password" autocomplete="new-password" class="w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-amber-900/60 dark:bg-slate-950 dark:text-slate-100" />
                                @error('password')
                                    <p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Confirm New Password') }}</label>
                                <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-amber-900/60 dark:bg-slate-950 dark:text-slate-100" />
                            </div>

                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-amber-700">
                                {{ __('Update Password') }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-400">{{ __('Total Orders') }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-slate-100">{{ number_format($stats['orders_total']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-emerald-100 p-4 dark:border-emerald-900/40 dark:bg-emerald-900/10">
                    <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Delivered Orders') }}</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-200">{{ number_format($stats['orders_delivered']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-rose-100 p-4 dark:border-rose-900/40 dark:bg-rose-900/10">
                    <p class="text-xs uppercase tracking-wide text-rose-700 dark:text-rose-300">{{ __('Cancelled Orders') }}</p>
                    <p class="mt-2 text-2xl font-bold text-rose-700 dark:text-rose-200">{{ number_format($stats['orders_cancelled']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4 dark:border-blue-900/40 dark:bg-blue-900/10">
                    <p class="text-xs uppercase tracking-wide text-blue-700 dark:text-blue-300">{{ __('Total Spent') }}</p>
                    <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-200">{{ number_format($stats['spent_total'], $currencyDecimals) }} {{ $currencyLabel }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:border-slate-800 dark:bg-slate-900">
                <div class="p-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 dark:border-slate-800">
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-slate-100">{{ __('Customer Reviews') }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                            {{ __('All product reviews written by this user.') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ number_format($userReviews->count()) }} {{ __('reviews') }}
                        </span>
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                            {{ $reviewAverage ? number_format((float) $reviewAverage, 1) : '0.0' }} / 5
                        </span>
                        @can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE)
                            <a
                                href="{{ route('admin.reviews.index', ['search' => $user->email]) }}"
                                class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-700"
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
                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950">
                                    @if($review->product?->image)
                                        <img src="{{ asset('storage/' . ltrim((string) $review->product->image, '/')) }}" alt="{{ $review->product->name }}" class="h-full w-full object-contain">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
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
                                            <a href="{{ route('admin.products.edit', $review->product) }}" class="block truncate text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                                                {{ $review->product->name }}
                                            </a>
                                        @else
                                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $review->product->name }}</p>
                                        @endcan
                                        <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ __('SKU:') }} {{ $review->product->sku ?: '-' }}</p>
                                    @else
                                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('Product unavailable') }}</p>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                                        {{ (int) $review->rating }} / 5
                                    </span>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $review->is_approved ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $review->is_approved ? __('Approved') : __('Hidden') }}
                                    </span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ optional($review->reviewed_at ?? $review->created_at)->format('M d, Y') ?: '-' }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $review->title ?: __('Customer review') }}</p>
                                @if($review->comment)
                                    <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $review->comment }}</p>
                                @endif
                            </div>

                            @can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE)
                                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('{{ __('Delete this review?') }}');" class="lg:text-right">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('No reviews found for this user.') }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Reviews will appear here after this customer rates purchased items.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:border-slate-800 dark:bg-slate-900">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between dark:border-slate-800">
                    <h3 class="font-semibold text-gray-800 dark:text-slate-100">{{ __('Recent Orders') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Last {{ $recentOrders->count() }} order(s)</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-slate-800/70 dark:text-slate-300">
                            <tr>
                                <th class="p-4 font-semibold">{{ __('Order') }}</th>
                                <th class="p-4 font-semibold">{{ __('Status') }}</th>
                                <th class="p-4 font-semibold">{{ __('Amount') }}</th>
                                <th class="p-4 font-semibold">{{ __('Date') }}</th>
                                <th class="p-4 font-semibold text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                            @forelse($recentOrders as $order)
                                <tr class="hover:bg-gray-50 transition dark:hover:bg-slate-800/60">
                                    <td class="p-4 font-medium text-slate-900 dark:text-slate-100">{{ $order->order_number }}</td>
                                    <td class="p-4 text-slate-700 dark:text-slate-300">{{ ucwords(str_replace('_', ' ', $order->status)) }}</td>
                                    <td class="p-4 text-slate-900 dark:text-slate-100">{{ number_format((float) $order->total_amount, $currencyDecimals) }} {{ $currencyLabel }}</td>
                                    <td class="p-4 text-gray-500 dark:text-slate-400">{{ $order->created_at?->format('d M Y H:i') }}</td>
                                    <td class="p-4 text-right">
                                        <a
                                            href="{{ route('admin.orders.show', $order) }}"
                                            class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800"
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
        </div>
    </div>
</x-app-layout>
