<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('Email Center') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Configure, broadcast, audit, and preview every email the system sends.') }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                <i class="fas fa-envelope-open-text text-red-500"></i>
                {{ __('Admin mail tools') }}
            </span>
        </div>
    </x-slot>

    <div class="py-8" x-data="emailCenter()" x-init="init()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @php
                $tabs = [
                    ['key' => 'settings',  'label' => __('Settings'),         'icon' => 'fa-gear',             'visible' => true],
                    ['key' => 'broadcast', 'label' => __('Broadcast'),        'icon' => 'fa-paper-plane',      'visible' => $canBroadcast],
                    ['key' => 'history',   'label' => __('History'),          'icon' => 'fa-clock-rotate-left','visible' => $canBroadcast],
                    ['key' => 'preview',   'label' => __('Template Preview'), 'icon' => 'fa-eye',              'visible' => true],
                ];
            @endphp

            <nav role="tablist" class="mb-6 inline-flex gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1 dark:bg-slate-900">
                @foreach ($tabs as $tab)
                    @if ($tab['visible'])
                        <button type="button"
                                @click="setTab('{{ $tab['key'] }}')"
                                :class="tab === '{{ $tab['key'] }}'
                                    ? 'bg-red-600 text-white shadow shadow-red-900/20'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                                class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition">
                            <i class="fas {{ $tab['icon'] }}"></i>
                            {{ $tab['label'] }}
                        </button>
                    @endif
                @endforeach
            </nav>

            <div x-show="tab === 'settings'"  x-cloak role="tabpanel">
                @include('admin.email.partials._settings')
            </div>
            @if ($canBroadcast)
                <div x-show="tab === 'broadcast'" x-cloak role="tabpanel">
                    @include('admin.email.partials._broadcast')
                </div>
                <div x-show="tab === 'history'"   x-cloak role="tabpanel">
                    @include('admin.email.partials._history')
                </div>
            @endif
            <div x-show="tab === 'preview'"   x-cloak role="tabpanel">
                @include('admin.email.partials._preview')
            </div>
        </div>
    </div>

    <script>
        function emailCenter() {
            return {
                tab: 'settings',
                validTabs: @json(collect($tabs)->where('visible', true)->pluck('key')->values()),
                init() {
                    const hash = window.location.hash.replace('#', '');
                    if (this.validTabs.includes(hash)) {
                        this.tab = hash;
                    }
                },
                setTab(name) {
                    if (!this.validTabs.includes(name)) return;
                    this.tab = name;
                    history.replaceState(null, '', '#' + name);
                },
            };
        }
    </script>
</x-app-layout>
