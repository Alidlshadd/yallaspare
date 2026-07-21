<x-app-layout>
    <x-slot name="header">{{ __('Popups') }}</x-slot>

    <style>
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
        .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═════════════ Hero ═════════════ --}}
        <div class="relative overflow-hidden rounded-2xl mb-4 p-6 text-white"
             style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
            <div class="absolute top-0 bottom-0 left-0 w-[3px]" style="background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);"></div>
            <div class="absolute -top-16 -right-16 h-64 w-64 rounded-full bg-amber-400/10 blur-[60px] pointer-events-none"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-mono text-[10px] font-extrabold uppercase tracking-[0.28em] text-amber-300">{{ __('Marketing · Announcements') }}</div>
                    <h1 class="text-2xl font-black mt-2 leading-tight">{{ __('Popups') }}</h1>
                    <p class="text-sm text-white/65 mt-1.5">{{ __('Promotional and informational popups shown on the storefront.') }}</p>
                </div>
                <a href="{{ route('admin.popups.create') }}"
                   class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-xs font-bold text-[#04042a] shadow-md shadow-amber-500/30 transition hover:brightness-105"
                   style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ __('New Popup') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if($popups->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-10 text-center bento-shadow">
                <div class="mx-auto h-12 w-12 rounded-2xl bg-[#04042a] text-amber-300 grid place-items-center mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('No popups yet') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">{{ __('Create your first popup to announce campaigns on the storefront.') }}</p>
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl overflow-hidden bento-shadow">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/70 dark:border-slate-800 text-left rtl:text-right">
                                <th class="px-5 py-3.5 text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Popup') }}</th>
                                <th class="px-5 py-3.5 text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Status') }}</th>
                                <th class="px-5 py-3.5 text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Schedule') }}</th>
                                <th class="px-5 py-3.5 text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Pages') }}</th>
                                <th class="px-5 py-3.5 text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 text-right rtl:text-left">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($popups as $popup)
                                @php
                                    $state = $popup->scheduleState();
                                    $stateBadge = match ($state) {
                                        'running' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                                        'scheduled' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
                                        'expired' => 'bg-slate-200 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300',
                                        default => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                                    };
                                    $stateLabel = match ($state) {
                                        'running' => __('Live'),
                                        'scheduled' => __('Scheduled'),
                                        'expired' => __('Expired'),
                                        default => __('Inactive'),
                                    };
                                    $pageLabels = collect(is_array($popup->pages) ? $popup->pages : [])->map(fn ($p) => match ($p) {
                                        'all' => __('All pages'),
                                        'home' => __('Home'),
                                        'shop' => __('Shop'),
                                        'product' => __('Product detail'),
                                        'cart' => __('Cart'),
                                        'checkout' => __('Checkout'),
                                        default => $p,
                                    })->implode(', ');
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="h-12 w-16 shrink-0 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 grid place-items-center"
                                                 style="background: linear-gradient(135deg, #0a1533, #35558f);">
                                                @if(!empty($popup->image_path))
                                                    <img src="{{ asset('storage/' . ltrim($popup->image_path, '/')) }}" alt="" class="h-full w-full object-cover">
                                                @else
                                                    <svg class="w-4 h-4 text-white/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-900 dark:text-white truncate max-w-[220px]">{{ $popup->title_en }}</div>
                                                @if($popup->hasButton())
                                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[220px]">→ {{ $popup->button_url }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold {{ $stateBadge }}">{{ $stateLabel }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-[12px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                        @if($popup->starts_at || $popup->ends_at)
                                            {{ $popup->starts_at?->format('d M Y') ?? '—' }} → {{ $popup->ends_at?->format('d M Y') ?? '∞' }}
                                        @else
                                            {{ __('Always on') }}
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-[12px] text-slate-600 dark:text-slate-300 max-w-[180px]">
                                        <span class="line-clamp-2">{{ $pageLabels }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-end rtl:justify-start gap-1.5">
                                            <form method="POST" action="{{ route('admin.popups.toggle', $popup) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="inline-flex items-center h-9 px-3 rounded-lg text-[11px] font-bold border transition {{ $popup->is_active ? 'text-slate-600 bg-white border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' : 'text-emerald-700 bg-emerald-50 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30' }}">
                                                    {{ $popup->is_active ? __('Deactivate') : __('Activate') }}
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.popups.edit', $popup) }}"
                                               class="inline-flex items-center h-9 px-3 rounded-lg text-[11px] font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 transition">
                                                {{ __('Edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('admin.popups.destroy', $popup) }}"
                                                  data-danger-confirm
                                                  data-danger-title="{{ __('Delete Popup') }}"
                                                  data-danger-description="{{ __('This action is permanent. The selected popup will be deleted and cannot be recovered.') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center h-9 px-3 rounded-lg text-[11px] font-bold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/30 transition">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($popups->hasPages())
                <div class="mt-4">{{ $popups->links() }}</div>
            @endif
        @endif

    </div>
    </div>
    </div>
</x-app-layout>
