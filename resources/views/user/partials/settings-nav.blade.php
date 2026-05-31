@php
    $items = [
        ['label' => __('Overview'), 'description' => __('All settings sections'), 'route' => 'user.settings.edit'],
        ['label' => __('Appearance'), 'description' => __('Theme mode'), 'route' => 'user.settings.appearance'],
        ['label' => __('Language'), 'description' => __('Interface language'), 'route' => 'user.settings.language'],
        ['label' => __('Notifications'), 'description' => __('Alerts and updates'), 'route' => 'user.settings.notifications'],
        ['label' => __('Security'), 'description' => __('Login protection'), 'route' => 'user.settings.security'],
        ['label' => __('Communication'), 'description' => __('Email and mobile consent'), 'route' => 'user.settings.communication'],
        ['label' => __('Checkout'), 'description' => __('Default checkout behavior'), 'route' => 'user.settings.checkout'],
        ['label' => __('Accessibility'), 'description' => __('Comfort and visibility'), 'route' => 'user.settings.accessibility'],
    ];
@endphp

<div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
    <p class="px-3 pb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Settings Sections') }}</p>
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