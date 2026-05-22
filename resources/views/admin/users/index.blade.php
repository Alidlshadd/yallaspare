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

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-400">{{ __('Total Users') }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-slate-100">{{ number_format($totalUsers) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-violet-100 p-4 dark:border-violet-900/50 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-wide text-violet-700 dark:text-violet-300">{{ __('Super Admins') }}</p>
                    <p class="mt-2 text-2xl font-bold text-violet-700 dark:text-violet-300">{{ number_format($superAdminUsers) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-red-100 p-4 dark:border-red-900/50 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-wide text-red-600 dark:text-red-300">{{ __('Admins') }}</p>
                    <p class="mt-2 text-2xl font-bold text-red-700 dark:text-red-300">{{ number_format($adminUsers) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-amber-100 p-4 dark:border-amber-900/50 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-wide text-amber-600 dark:text-amber-300">{{ __('Dealers') }}</p>
                    <p class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ number_format($dealerUsers) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4 dark:border-blue-900/50 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-wide text-blue-600 dark:text-blue-300">{{ __('Users') }}</p>
                    <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($regularUsers) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 dark:border-slate-800 dark:bg-slate-900">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('Search by name, email, phone, id, or role...') }}"
                        class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    >
                    <button
                        type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition"
                    >
                        {{ __('Search') }}
                    </button>
                    @if($search !== '')
                        <a
                            href="{{ route('admin.users.index') }}"
                            class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition text-center dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            {{ __('Clear') }}
                        </a>
                    @endif
                </form>
            </div>

            @can('manage-users')
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/20 dark:text-blue-300">
                    {{ __('You are signed in as') }} <strong>{{ __('super_admin') }}</strong>{{ __('. You can update roles and delete users with safeguards.') }}
                </div>
            @endcan

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-slate-800/70 dark:text-slate-300">
                            <tr>
                                <th class="p-4 font-semibold">#</th>
                                <th class="p-4 font-semibold">{{ __('User') }}</th>
                                <th class="p-4 font-semibold">{{ __('Email') }}</th>
                                <th class="p-4 font-semibold">{{ __('Role') }}</th>
                                <th class="p-4 font-semibold">{{ __('Joined') }}</th>
                                @can('manage-users')
                                    <th class="p-4 font-semibold text-right">{{ __('Actions') }}</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                            @forelse($users as $user)
                                @php
                                    $role = $user->role;
                                    $isSelf = $currentUserId === (int) $user->id;
                                    $isLastSuperAdmin = $role === \App\Models\User::ROLE_SUPER_ADMIN && $superAdminUsers <= 1;
                                @endphp
                                <tr class="hover:bg-gray-50 transition dark:hover:bg-slate-800/60">
                                    <td class="p-4 font-medium dark:text-slate-100">#{{ $user->id }}</td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-800 dark:text-slate-100">{{ $user->name }}</div>
                                                @if($user->phone)
                                                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ $user->phone }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-gray-600 dark:text-slate-300">{{ $user->email }}</td>
                                    <td class="p-4">
                                        @if($role === \App\Models\User::ROLE_SUPER_ADMIN)
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-violet-100 text-violet-700 border border-violet-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                                                {{ __('Super Admin') }}
                                            </span>
                                        @elseif($role === \App\Models\User::ROLE_ADMIN)
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                {{ __('Admin') }}
                                            </span>
                                        @elseif($role === \App\Models\User::ROLE_DEALER)
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                {{ __('Dealer') }}
                                            </span>
                                        @elseif(in_array($role, [
                                            \App\Models\User::ROLE_PRODUCT_MANAGER,
                                            \App\Models\User::ROLE_ORDER_MANAGER,
                                            \App\Models\User::ROLE_FINANCE_MANAGER,
                                            \App\Models\User::ROLE_INVENTORY_MANAGER,
                                            \App\Models\User::ROLE_SETTINGS_MANAGER,
                                        ], true))
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-cyan-100 text-cyan-700 border border-cyan-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                                                {{ __(ucwords(str_replace('_', ' ', $role))) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 border border-blue-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                {{ __('User') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-gray-500 dark:text-slate-400">{{ $user->created_at->format('d M Y') }}</td>

                                    @can('manage-users')
                                        <td class="p-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <a
                                                    href="{{ route('admin.users.show', $user) }}"
                                                    class="px-3 py-1.5 rounded-md bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700"
                                                >
                                                    {{ __('View Details') }}
                                                </a>

                                                <form method="POST" action="{{ route('admin.users.update-role', $user) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select
                                                        name="role"
                                                        class="rounded-md border-gray-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
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
                                                        class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 disabled:opacity-60"
                                                        @disabled($isSelf || $isLastSuperAdmin)
                                                    >
                                                        {{ __('Update') }}
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-danger-confirm data-danger-title="{{ __('Delete User') }}" data-danger-description="{{ __('This action is permanent. The selected user account will be deleted and cannot be undone.') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="px-3 py-1.5 rounded-md bg-red-600 text-white text-xs font-semibold hover:bg-red-700 disabled:opacity-60"
                                                        @disabled($isSelf || $isLastSuperAdmin)
                                                    >
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </div>
                                            @if($isSelf)
                                                <p class="mt-1 text-[11px] text-gray-500 text-right dark:text-slate-400">{{ __('Self-protection enabled.') }}</p>
                                            @elseif($isLastSuperAdmin)
                                                <p class="mt-1 text-[11px] text-gray-500 text-right dark:text-slate-400">{{ __('Last super admin is protected.') }}</p>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <p class="text-lg font-semibold text-gray-700 dark:text-slate-200">{{ __('No users found') }}</p>
                                            @if($search !== '')
                                                <p class="text-sm">{{ __('No results for ":search".', ['search' => $search]) }}</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-slate-800">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
