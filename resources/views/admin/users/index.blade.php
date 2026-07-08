<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-slate-100">{{ __('Users Management') }}</h2>
            <div class="text-sm text-gray-500 dark:text-slate-400">
                {{ __('Showing') }} <span class="font-semibold">{{ $users->count() }}</span> {{ __('of') }}
                <span class="font-semibold">{{ $users->total() }}</span>
            </div>
        </div>
    </x-slot>

    @php
        $managerRoleList = [
            \App\Models\User::ROLE_PRODUCT_MANAGER,
            \App\Models\User::ROLE_ORDER_MANAGER,
            \App\Models\User::ROLE_FINANCE_MANAGER,
            \App\Models\User::ROLE_INVENTORY_MANAGER,
            \App\Models\User::ROLE_SETTINGS_MANAGER,
        ];

        $roleMeta = function (?string $role) use ($managerRoleList) {
            if ($role === \App\Models\User::ROLE_SUPER_ADMIN) {
                return [
                    'label' => __('Super Admin'),
                    'chip' => 'border-violet-300 bg-violet-50 text-violet-700 dark:border-violet-400/40 dark:bg-violet-400/10 dark:text-violet-300',
                    'avatar' => 'bg-violet-100 text-violet-700 dark:bg-violet-400/15 dark:text-violet-300',
                ];
            }
            if ($role === \App\Models\User::ROLE_ADMIN) {
                return [
                    'label' => __('Admin'),
                    'chip' => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-400/40 dark:bg-rose-400/10 dark:text-rose-300',
                    'avatar' => 'bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300',
                ];
            }
            if ($role === \App\Models\User::ROLE_DEALER) {
                return [
                    'label' => __('Dealer'),
                    'chip' => 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-300',
                    'avatar' => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
                ];
            }
            if (in_array($role, $managerRoleList, true)) {
                return [
                    'label' => __(ucwords(str_replace('_', ' ', $role))),
                    'chip' => 'border-cyan-300 bg-cyan-50 text-cyan-700 dark:border-cyan-400/40 dark:bg-cyan-400/10 dark:text-cyan-300',
                    'avatar' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-400/15 dark:text-cyan-300',
                ];
            }

            return [
                'label' => __('User'),
                'chip' => 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-400/40 dark:bg-blue-400/10 dark:text-blue-300',
                'avatar' => 'bg-blue-100 text-blue-700 dark:bg-blue-400/15 dark:text-blue-300',
            ];
        };

        $roleFilters = [
            'all' => ['label' => __('All accounts'), 'count' => $totalUsers, 'swatch' => 'bg-slate-400 dark:bg-slate-300'],
            'super_admin' => ['label' => __('Super Admins'), 'count' => $superAdminUsers, 'swatch' => 'bg-violet-500'],
            'admin' => ['label' => __('Admins'), 'count' => $adminUsers, 'swatch' => 'bg-rose-500'],
            'manager' => ['label' => __('Managers'), 'count' => $managerUsers, 'swatch' => 'bg-cyan-500'],
            'dealer' => ['label' => __('Dealers'), 'count' => $dealerUsers, 'swatch' => 'bg-amber-400'],
            'user' => ['label' => __('Users'), 'count' => $regularUsers, 'swatch' => 'bg-blue-500'],
        ];

        $verifyFilters = [
            'verified' => ['label' => __('Verified email'), 'count' => $verifiedUsers],
            'unverified' => ['label' => __('Unverified'), 'count' => $unverifiedUsers],
        ];

        $filterUrl = fn (string $key) => route('admin.users.index', array_filter([
            'filter' => $key === 'all' ? null : $key,
            'search' => $search !== '' ? $search : null,
        ]));

        $sharePercent = fn (int $count) => $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0;

        $insightCards = [
            'all' => [
                'label' => __('Total Users'),
                'count' => $totalUsers,
                'caption' => __('All accounts'),
                'card' => 'border-gray-200 dark:border-slate-700/60',
                'accent' => 'text-gray-500 dark:text-slate-400',
                'number' => 'text-gray-900 dark:text-white',
            ],
            'super_admin' => [
                'label' => __('Super Admins'),
                'count' => $superAdminUsers,
                'caption' => __(':percent% of all accounts', ['percent' => $sharePercent($superAdminUsers)]),
                'card' => 'border-violet-300/70 dark:border-violet-400/35',
                'accent' => 'text-violet-600 dark:text-violet-300',
                'number' => 'text-violet-700 dark:text-violet-300',
            ],
            'admin' => [
                'label' => __('Admins'),
                'count' => $adminUsers,
                'caption' => __(':percent% of all accounts', ['percent' => $sharePercent($adminUsers)]),
                'card' => 'border-rose-300/70 dark:border-rose-400/35',
                'accent' => 'text-rose-600 dark:text-rose-300',
                'number' => 'text-rose-700 dark:text-rose-300',
            ],
            'manager' => [
                'label' => __('Managers'),
                'count' => $managerUsers,
                'caption' => __(':percent% of all accounts', ['percent' => $sharePercent($managerUsers)]),
                'card' => 'border-cyan-300/70 dark:border-cyan-400/35',
                'accent' => 'text-cyan-600 dark:text-cyan-300',
                'number' => 'text-cyan-700 dark:text-cyan-300',
            ],
            'dealer' => [
                'label' => __('Dealers'),
                'count' => $dealerUsers,
                'caption' => __(':percent% of all accounts', ['percent' => $sharePercent($dealerUsers)]),
                'card' => 'border-amber-300/70 dark:border-amber-400/35',
                'accent' => 'text-amber-600 dark:text-amber-300',
                'number' => 'text-amber-700 dark:text-amber-300',
            ],
            'user' => [
                'label' => __('Users'),
                'count' => $regularUsers,
                'caption' => __(':percent% of all accounts', ['percent' => $sharePercent($regularUsers)]),
                'card' => 'border-blue-300/70 dark:border-blue-400/35',
                'accent' => 'text-blue-600 dark:text-blue-300',
                'number' => 'text-blue-700 dark:text-blue-300',
            ],
        ];

        $exportParams = in_array($filter, ['super_admin', 'admin', 'dealer'], true) ? ['role' => $filter] : [];
    @endphp

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Insights --}}
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                @foreach($insightCards as $key => $card)
                    <a
                        href="{{ $filterUrl($key) }}"
                        @class([
                            'rounded-xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow dark:bg-slate-900',
                            $card['card'],
                            'ring-2 ring-amber-400/60' => $filter === $key,
                        ])
                    >
                        <p class="text-[11px] font-bold uppercase tracking-widest {{ $card['accent'] }}">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-extrabold tabular-nums {{ $card['number'] }}">{{ number_format($card['count']) }}</p>
                        <p class="mt-1 text-[11px] text-gray-400 dark:text-slate-500">{{ $card['caption'] }}</p>
                    </a>
                @endforeach
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-slate-400">
                    {{ __('Showing') }} <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $users->count() }}</span>
                    {{ __('of') }} <span class="font-semibold text-gray-800 dark:text-slate-100">{{ number_format($users->total()) }}</span>
                    @if($filter !== 'all')
                        · <span class="font-semibold text-amber-600 dark:text-amber-300">{{ ($roleFilters[$filter] ?? $verifyFilters[$filter])['label'] }}</span>
                    @endif
                    @if($search !== '')
                        · "{{ $search }}"
                    @endif
                </p>
                <a
                    href="{{ route('admin.users.export-excel', $exportParams) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-amber-300"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0 4-4m-4 4-4-4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 19h14" />
                    </svg>
                    {{ __('Export Excel') }}
                </a>
            </div>

            <div class="grid gap-4 lg:grid-cols-[240px_minmax(0,1fr)] items-start">
                {{-- Filter rail --}}
                <aside class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-4">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-3">
                        @if($filter !== 'all')
                            <input type="hidden" name="filter" value="{{ $filter }}">
                        @endif
                        <label for="users-search" class="sr-only">{{ __('Search') }}</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute inset-y-0 my-auto ms-3 h-4 w-4 text-gray-400 dark:text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" />
                                <path stroke-linecap="round" d="m20 20-3.5-3.5" />
                            </svg>
                            <input
                                id="users-search"
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="{{ __('Search by name, email, phone, id, or role...') }}"
                                class="w-full rounded-lg border-gray-300 bg-white ps-9 text-sm text-slate-900 placeholder:text-gray-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                            >
                        </div>
                        @if($search !== '')
                            <a href="{{ route('admin.users.index', array_filter(['filter' => $filter === 'all' ? null : $filter])) }}" class="mt-2 inline-block text-xs font-semibold text-amber-600 hover:text-amber-500 dark:text-amber-300 dark:hover:text-amber-200">
                                {{ __('Clear') }} ✕
                            </a>
                        @endif
                    </form>

                    <nav class="space-y-0.5" aria-label="{{ __('Role') }}">
                        @foreach($roleFilters as $key => $item)
                            <a
                                href="{{ $filterUrl($key) }}"
                                @class([
                                    'flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition',
                                    'border-s-2 border-amber-400 bg-amber-50 text-gray-900 dark:bg-amber-400/10 dark:text-white' => $filter === $key,
                                    'text-gray-600 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800/60' => $filter !== $key,
                                ])
                            >
                                <span class="flex items-center gap-2.5 min-w-0">
                                    <span class="h-2 w-2 shrink-0 rounded-full {{ $item['swatch'] }}"></span>
                                    <span class="truncate">{{ $item['label'] }}</span>
                                </span>
                                <span class="text-xs tabular-nums text-gray-400 dark:text-slate-500">{{ number_format($item['count']) }}</span>
                            </a>
                        @endforeach
                    </nav>

                    <hr class="my-3 border-gray-200 dark:border-slate-800">

                    <nav class="space-y-0.5" aria-label="{{ __('Email') }}">
                        @foreach($verifyFilters as $key => $item)
                            <a
                                href="{{ $filterUrl($key) }}"
                                @class([
                                    'flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition',
                                    'border-s-2 border-amber-400 bg-amber-50 text-gray-900 dark:bg-amber-400/10 dark:text-white' => $filter === $key,
                                    'text-gray-600 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800/60' => $filter !== $key,
                                ])
                            >
                                <span class="flex items-center gap-2.5 min-w-0">
                                    @if($key === 'verified')
                                        <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                                    @else
                                        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400 dark:text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7.5V12l2.5 2.5" /></svg>
                                    @endif
                                    <span class="truncate">{{ $item['label'] }}</span>
                                </span>
                                <span class="text-xs tabular-nums text-gray-400 dark:text-slate-500">{{ number_format($item['count']) }}</span>
                            </a>
                        @endforeach
                    </nav>
                </aside>

                {{-- User cards --}}
                <div class="space-y-3 min-w-0">
                    @forelse($users as $user)
                        @php
                            $role = $user->role;
                            $meta = $roleMeta($role);
                            $isSelf = $currentUserId === (int) $user->id;
                            $isLastSuperAdmin = $role === \App\Models\User::ROLE_SUPER_ADMIN && $superAdminUsers <= 1;
                        @endphp
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-extrabold {{ $meta['avatar'] }}">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $user->name }}</span>
                                        <span class="text-xs tabular-nums text-gray-400 dark:text-slate-500">#{{ $user->id }}</span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $meta['chip'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            {{ $meta['label'] }}
                                        </span>
                                        @if($user->email_verified_at)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400" title="{{ __('Verified email') }}">
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500 dark:text-slate-400">
                                        <span class="truncate">{{ $user->email }}</span>
                                        @if($user->phone)
                                            <span dir="ltr">{{ $user->phone }}</span>
                                        @endif
                                        <span>{{ __('Joined') }} {{ $user->created_at->format('d M Y') }}</span>
                                    </div>
                                </div>

                                @can('manage-users')
                                    <div class="flex flex-wrap items-center gap-2">
                                        <form method="POST" action="{{ route('admin.users.update-role', $user) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select
                                                name="role"
                                                class="rounded-lg border-gray-300 bg-white py-1.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                                @disabled($isSelf)
                                            >
                                                @foreach($roleOptions as $option)
                                                    <option value="{{ $option }}" @selected($role === $option)>
                                                        {{ __(ucwords(str_replace('_', ' ', $option))) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button
                                                type="submit"
                                                class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-slate-700 dark:hover:bg-slate-600"
                                                @disabled($isSelf || $isLastSuperAdmin)
                                            >
                                                {{ __('Update') }}
                                            </button>
                                        </form>

                                        <a
                                            href="{{ route('admin.users.show', $user) }}"
                                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            {{ __('View Details') }}
                                        </a>

                                        @if($isSelf || $isLastSuperAdmin)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-gray-400 dark:text-slate-500" title="{{ $isSelf ? __('Self-protection enabled.') : __('Last super admin is protected.') }}">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V8a4 4 0 0 1 8 0v3" />
                                                    <rect x="5" y="11" width="14" height="9" rx="2" />
                                                </svg>
                                                {{ __('Protected') }}
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-danger-confirm data-danger-title="{{ __('Delete User') }}" data-danger-description="{{ __('This action is permanent. The selected user account will be deleted and cannot be undone.') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-400/40 dark:bg-rose-400/10 dark:text-rose-300 dark:hover:bg-rose-400/20"
                                                >
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <a
                                        href="{{ route('admin.users.show', $user) }}"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        {{ __('View Details') }}
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-lg font-semibold text-gray-700 dark:text-slate-200">{{ __('No users found') }}</p>
                            @if($search !== '')
                                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ __('No results for ":search".', ['search' => $search]) }}</p>
                            @endif
                        </div>
                    @endforelse

                    @if($users->hasPages())
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
