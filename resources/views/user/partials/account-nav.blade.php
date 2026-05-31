@php
    $items = [
        ['label' => __('Overview'), 'description' => __('All account sections'), 'route' => 'user.account.edit'],
        ['label' => __('Personal Info'), 'description' => __('Identity and contact details'), 'route' => 'user.account.personal'],
        ['label' => __('Address Book'), 'description' => __('Saved delivery addresses'), 'route' => 'user.account.addresses'],
        ['label' => __('Security'), 'description' => __('Password and protection'), 'route' => 'user.account.security'],
        ['label' => __('Activity'), 'description' => __('Orders and recent history'), 'route' => 'user.account.activity'],
        ['label' => __('Account Actions'), 'description' => __('Export, freeze, and delete'), 'route' => 'user.account.actions'],
        ['label' => __('Settings'), 'description' => __('Theme and preferences'), 'route' => 'user.settings.edit'],
    ];
@endphp

<div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
    <p class="px-3 pb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Account Sections') }}</p>
    <nav class="space-y-1">
        @foreach ($items as $item)
            @php
                $isActive = request()->routeIs($item['route']);
            @endphp
            <a
                href="{{ route($item['route']) }}"
                class="flex items-start gap-3 rounded-2xl px-3 py-3 transition duration-200 {{ $isActive ? 'bg-slate-100 text-slate-950 dark:bg-slate-800 dark:text-white' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}"
            >
                <span class="mt-0.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $isActive ? 'bg-primary dark:bg-white' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium">{{ $item['label'] }}</span>
                    <span class="mt-0.5 block text-xs {{ $isActive ? 'text-slate-600 dark:text-slate-300' : 'text-slate-500 dark:text-slate-400' }}">{{ $item['description'] }}</span>
                </span>
            </a>
        @endforeach
    </nav>
</div>
