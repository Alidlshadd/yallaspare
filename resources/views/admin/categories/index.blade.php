<x-app-layout>
    <x-slot name="header">{{ __('Categories Management') }}</x-slot>

    @php
        $importErrors = session('import_errors', []);
        $withProductsCount = max(0, $totalCategories - $emptyCategories);
        $stockUrl = function ($stockKey) {
            $params = request()->except('page', 'stock');
            if ($stockKey !== '') {
                $params['stock'] = $stockKey;
            }
            return route('admin.categories.index', $params);
        };
    @endphp

    <style>
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
        .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }

        /* Chips — same dialect as Products */
        .ychip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 11.5px; font-weight: 700; line-height: 1;
            border: 1px solid var(--border); background: var(--surface); color: var(--text-secondary);
            text-decoration: none;
            transition: all .15s ease;
        }
        .ychip:hover { background: var(--surface-sunk); border-color: var(--border); color: #04041f; }
        .ychip .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
        .ychip .cnt {
            background: rgba(15,23,42,0.06);
            padding: 1px 7px; border-radius: 999px;
            font-size: 10.5px; font-family: ui-monospace, 'JetBrains Mono', monospace;
            color: var(--text-secondary); font-weight: 700;
        }
        .ychip.on {
            background: #04041f; color: #ffb27a; border-color: #04041f;
            box-shadow: 0 6px 14px -8px rgba(4,4,42,0.40);
        }
        .ychip.on .cnt { background: rgba(252,211,77,0.18); color: #ffb27a; }
        .dark .ychip { background: var(--surface-sunk); border-color: var(--border); color: var(--text-secondary); }
        .dark .ychip .cnt { background: rgba(255,255,255,0.06); color: var(--text-secondary); }
        .dark .ychip:hover { background: linear-gradient(var(--hover-tint), var(--hover-tint)), var(--surface-sunk); color: var(--text); }
        .dark .ychip.on { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
        .dark .ychip.on .cnt { background: rgba(4,4,42,0.18); color: #04041f; }

        /* Category card — sibling of Products' prod-card */
        .cat-card {
            position: relative; overflow: hidden;
            background: var(--surface); border: 1px solid #e3e9f1; border-radius: 18px;
            padding: 14px;
            box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06);
            transition: all .2s ease;
            display: flex; flex-direction: column;
        }
        .cat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(7,7,64,0.10); border-color: #ffb27a; }
        .dark .cat-card { background: var(--surface); border-color: var(--border); }
        .dark .cat-card:hover { border-color: #ff8a3d; }
        .cat-card .img-wrap {
            position: relative;
            height: 120px; border-radius: 12px;
            background: linear-gradient(135deg, var(--surface-sunk), var(--surface-sunk));
            border: 1px solid #e3e9f1;
            display: grid; place-items: center;
            margin-bottom: 12px;
            overflow: hidden;
            padding: 16px;
        }
        .dark .cat-card .img-wrap { background: linear-gradient(135deg, var(--surface-sunk), var(--surface)); border-color: var(--border); }
        .cat-card .img-wrap img {
            max-width: 50%; max-height: 70%;
            width: auto; height: auto;
            object-fit: contain;
            object-position: center;
            display: block;
        }
        .cat-card .img-wrap .placeholder { color: var(--text-muted); font-size: 28px; }
        .cat-card .badge-tr { position: absolute; top: 8px; right: 8px; }
        [dir='rtl'] .cat-card .badge-tr { right: auto; left: 8px; }
        .cat-card .cname { font-weight: 700; color: #04041f; font-size: 14px; line-height: 1.3; }
        .dark .cat-card .cname { color: var(--text); }
        .cat-card .langs { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
        .dark .cat-card .langs { color: var(--text-muted); }
        .cat-card .meta-row {
            display: flex; justify-content: space-between; align-items: center; gap: 8px;
            margin-top: 10px; padding-top: 10px;
            border-top: 1px dashed #e3e9f1;
            font-size: 11px; color: var(--text-muted);
        }
        .dark .cat-card .meta-row { border-top-color: var(--border); color: var(--text-muted); }
        .cat-card .slug-chip {
            display: inline-block; font-family: ui-monospace, monospace; font-size: 10.5px; font-weight: 600;
            background: var(--surface-sunk); border: 1px solid #e3e9f1;
            padding: 3px 8px; border-radius: 7px; color: var(--text-muted);
            max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .dark .cat-card .slug-chip { background: var(--surface-sunk); border-color: var(--border); color: var(--text-muted); }
        .cat-card .acts { display: flex; gap: 6px; margin-top: 10px; }
        .cat-card .acts .btn {
            flex: 1; height: 34px; border-radius: 9px;
            background: var(--surface); border: 1px solid var(--border); color: var(--text-secondary);
            font-size: 11.5px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            text-decoration: none;
            transition: all .15s ease;
        }
        .cat-card .acts .btn:hover { transform: translateY(-1px); }
        .cat-card .acts .btn.primary { background: #04041f; color: #ffb27a; border-color: #04041f; }
        .cat-card .acts .btn.primary:hover { background: #070740; }
        .cat-card .acts .btn.danger { color: #b91c1c; border-color: #fca5a5; background: #fef2f2; }
        .cat-card .acts .btn.danger:hover { background: #fee2e2; }
        .cat-card .acts .btn.locked { color: var(--text-muted); background: var(--surface-sunk); border-color: var(--border); cursor: not-allowed; }
        .cat-card .acts .btn.locked:hover { transform: none; }
        .dark .cat-card .acts .btn { background: var(--surface-sunk); border-color: var(--border); color: var(--text-secondary); }
        .dark .cat-card .acts .btn:hover { background: linear-gradient(var(--hover-tint), var(--hover-tint)), var(--surface-sunk); }
        .dark .cat-card .acts .btn.primary { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
        .dark .cat-card .acts .btn.primary:hover { background: #e65c00; }
        .dark .cat-card .acts .btn.danger { color: #fca5a5; border-color: rgba(239,68,68,0.30); background: rgba(239,68,68,0.10); }
        .dark .cat-card .acts .btn.locked { color: var(--text-muted); background: var(--surface); border-color: var(--border); }

        /* Product-count pill */
        .count-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 700;
            border: 1px solid; backdrop-filter: blur(6px);
            text-decoration: none;
            transition: all .15s ease;
        }
        .count-pill::before { content: ""; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .count-pill.filled { background: rgba(220,252,231,0.92); color: #15803d; border-color: rgba(134,239,172,0.8); }
        a.count-pill.filled:hover { background: rgba(187,247,208,0.95); }
        .count-pill.empty { background: rgba(254,243,199,0.92); color: #b45309; border-color: rgba(253,230,138,0.8); }

        /* Pagination — same dialect as Products */
        .y-pagination nav { display: flex; }
        .y-pagination ul,
        .y-pagination .pagination {
            display: flex; flex-wrap: wrap; gap: 4px; list-style: none; margin: 0; padding: 0;
        }
        .y-pagination a,
        .y-pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 34px; padding: 0 10px;
            border-radius: 9px; background: var(--surface);
            border: 1px solid var(--border); color: var(--text-secondary);
            font-size: 12px; font-weight: 700; text-decoration: none;
            transition: all .15s ease;
        }
        .y-pagination a:hover { color: var(--text); border-color: var(--border); background: var(--surface-sunk); }
        .y-pagination .active span,
        .y-pagination span[aria-current="page"] {
            background: #04041f; color: #ffb27a; border-color: #04041f;
        }
        .y-pagination .disabled span,
        .y-pagination span[aria-disabled="true"] { opacity: 0.45; cursor: not-allowed; }
        .dark .y-pagination a,
        .dark .y-pagination span { background: var(--surface); border-color: var(--border); color: var(--text-secondary); }
        .dark .y-pagination a:hover { background: var(--surface-sunk); color: var(--text); border-color: rgb(var(--text-muted-rgb) / 0.55); }
        .dark .y-pagination .active span,
        .dark .y-pagination span[aria-current="page"] {
            background: #ff8a3d; color: #04041f; border-color: #ff8a3d;
        }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ─────────────── Flash + errors ─────────────── --}}
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ═════════════ Hero ═════════════ --}}
        <div class="relative overflow-hidden rounded-2xl mb-4 p-6 text-white"
             style="background: linear-gradient(135deg, #04041f 0%, #070740 50%, #070740 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
            <div class="absolute top-0 bottom-0 left-0 w-[3px]" style="background: linear-gradient(180deg, #ff8a3d 0%, #e65c00 100%);"></div>
            <div class="absolute -top-16 -right-16 h-64 w-64 rounded-full bg-accent/10 blur-[60px] pointer-events-none"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-mono text-[10px] font-bold uppercase tracking-[0.28em] text-accent">{{ __('Catalog · Taxonomy') }}</div>
                    <h1 class="text-3xl font-bold mt-2 leading-tight">{{ __('Categories') }}</h1>
                    <p class="text-sm text-white/65 mt-2">
                        {{ __(':total categories', ['total' => number_format($totalCategories)]) }}
                        @if($emptyCategories > 0)
                            <span class="text-white/35 mx-1">·</span>
                            <b class="text-accent">{{ __(':n empty', ['n' => number_format($emptyCategories)]) }}</b>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2" x-data="toggle">
                    <button type="button" @click="openNow()"
                            class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-bold text-white bg-white/10 border border-white/15 hover:bg-white/15 backdrop-blur-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M17 8l-5-5-5 5M12 3v12"/></svg>
                        {{ __('Import') }}
                    </button>
                    <a href="{{ route('admin.categories.export-excel') }}"
                       class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-bold text-white bg-white/10 border border-white/15 hover:bg-white/15 backdrop-blur-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        {{ __('Export Excel') }}
                    </a>
                    <a href="{{ route('admin.categories.create') }}"
                       class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-xs font-bold text-navy-deep shadow-md shadow-accent/30 transition hover:brightness-105"
                       style="background: linear-gradient(180deg, #ff8a3d, #e65c00);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Add Category') }}
                    </a>

                    {{-- Import modal --}}
                    <template x-teleport="body">
                        <div x-show="open" x-cloak x-transition.opacity
                             class="fixed inset-0 z-[100] grid place-items-center bg-black/60 backdrop-blur-sm p-4"
                             @click.self="close()"
                             @keydown.escape.window="close()">
                            <div x-show="open" x-transition
                                 class="w-full max-w-lg rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden text-slate-900">
                                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-200 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900/60 dark:to-slate-900">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-9 w-9 rounded-xl bg-navy-deep text-accent grid place-items-center">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold">{{ __('Bulk Import') }}</div>
                                            <div class="text-[11px] text-slate-500">{{ __('Upload CSV / XLSX to create categories') }}</div>
                                        </div>
                                    </div>
                                    <button type="button" @click="close()"
                                            class="h-8 w-8 rounded-lg grid place-items-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <form method="POST" action="{{ route('admin.categories.import') }}" enctype="multipart/form-data"
                                      class="p-5 space-y-3"
                                      data-loading-form
                                      data-loading-message="Uploading, please wait..."
                                      data-loading-button-text="Uploading...">
                                    @csrf

                                    <div>
                                        <label for="import-file" class="block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mb-1.5">{{ __('File') }}</label>
                                        <input id="import-file" type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" required
                                               class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 file:mr-3 file:rounded-md file:border-0 file:bg-slate-200 file:px-3 file:py-1 file:text-xs file:font-bold">
                                    </div>

                                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-[11px] leading-relaxed text-slate-600">
                                        <div class="font-bold uppercase text-[10px] tracking-widest text-slate-500 mb-1.5">{{ __('Requirements') }}</div>
                                        <p>
                                            {{ __('Supported files: CSV, TXT, XLS, XLSX.') }}
                                        </p>
                                        <p class="mt-1">
                                            <b>{{ __('Required columns:') }}</b>
                                            <span class="font-mono">name_en, name_ar, name_ku</span>
                                        </p>
                                        <p class="mt-1">
                                            <b>{{ __('Optional:') }}</b>
                                            <span class="font-mono">slug, description</span>
                                        </p>
                                    </div>

                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" @click="close()"
                                                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                            {{ __('Cancel') }}
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-sm font-bold text-accent bg-navy-deep hover:bg-navy transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M17 8l-5-5-5 5M12 3v12"/></svg>
                                            {{ __('Upload & Import') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ═════════════ Import error report ═════════════ --}}
        @if(count($importErrors) > 0)
            <div class="mb-4 bg-white border border-rose-200 rounded-2xl overflow-hidden bento-shadow">
                <div class="flex items-center gap-2 px-5 py-3 bg-rose-50 border-b border-rose-200 text-sm font-bold text-rose-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    {{ __('Import Error Report') }} ({{ count($importErrors) }} {{ __('rows') }})
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-widest">{{ __('Row') }}</th>
                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-widest">{{ __('Name (EN)') }}</th>
                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-widest">{{ __('Error') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($importErrors as $errorRow)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-3 font-mono text-slate-700">{{ $errorRow['row'] ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-slate-600">{{ $errorRow['name_en'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-rose-700">{{ $errorRow['message'] ?? __('Unknown error') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ═════════════ Search + quick filter band ═════════════ --}}
        <div class="bg-white border border-slate-200/70 rounded-2xl px-5 py-4 mb-4 bento-shadow flex flex-wrap items-center gap-4">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-1 min-w-[220px] max-w-md gap-2">
                @if($stockFilter !== '')
                    <input type="hidden" name="stock" value="{{ $stockFilter }}">
                @endif
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                    </span>
                    <input name="search" value="{{ $search }}"
                           placeholder="{{ __('Search by name, slug, or description...') }}"
                           class="h-10 w-full ps-10 pe-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-muted transition focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 focus:bg-white dark:focus:bg-slate-900">
                </div>
                <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-bold text-accent bg-navy-deep hover:bg-navy transition shrink-0">
                    {{ __('Search') }}
                </button>
                @if($search !== '')
                    <a href="{{ $stockUrl($stockFilter) }}"
                       class="inline-flex items-center h-10 px-4 rounded-xl text-sm font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition shrink-0">
                        {{ __('Clear') }}
                    </a>
                @endif
            </form>
            <div class="flex flex-wrap gap-1.5">
                <a href="{{ $stockUrl('') }}" class="ychip {{ $stockFilter === '' ? 'on' : '' }}">
                    <span class="dot" style="background: #04041f;"></span>
                    {{ __('All') }} <span class="cnt">{{ number_format($totalCategories) }}</span>
                </a>
                <a href="{{ $stockUrl('has_products') }}" class="ychip {{ $stockFilter === 'has_products' ? 'on' : '' }}">
                    <span class="dot" style="background: #16a34a;"></span>
                    {{ __('With products') }} <span class="cnt">{{ number_format($withProductsCount) }}</span>
                </a>
                <a href="{{ $stockUrl('empty') }}" class="ychip {{ $stockFilter === 'empty' ? 'on' : '' }}">
                    <span class="dot" style="background: #e65c00;"></span>
                    {{ __('Empty') }} <span class="cnt">{{ number_format($emptyCategories) }}</span>
                </a>
            </div>
        </div>

        {{-- ═════════════ Category card grid ═════════════ --}}
        @if($categories->count() === 0)
            <div class="bg-white border border-slate-200/70 rounded-2xl py-14 px-4 text-center bento-shadow">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center text-muted dark:text-slate-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </div>
                <div class="text-base font-bold text-slate-900">{{ __('No categories found') }}</div>
                @if($search !== '')
                    <div class="text-[13px] text-slate-500 mt-1.5">{{ __('No results for ":search".', ['search' => $search]) }}</div>
                    <a href="{{ $stockUrl($stockFilter) }}"
                       class="font-display inline-flex items-center gap-2 h-10 px-4 mt-4 rounded-xl text-sm font-bold text-accent bg-navy-deep hover:bg-navy transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        {{ __('Reset filters') }}
                    </a>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3" data-animate-stagger>
                @foreach($categories as $category)
                    <div class="cat-card" data-motion-lift>
                        <div class="img-wrap">
                            @if($category->image)
                                <img src="{{ asset('storage/' . ltrim((string) $category->image, '/')) }}"
                                     alt="{{ $category->name }}" loading="lazy">
                            @else
                                <i class="fas fa-folder-open placeholder"></i>
                            @endif
                            @if($category->products_count > 0)
                                <a href="{{ route('admin.products.index', ['category_id' => $category->id]) }}"
                                   class="badge-tr count-pill filled"
                                   title="{{ __('View products in this category') }}">
                                    {{ trans_choice(':count product|:count products', $category->products_count, ['count' => number_format($category->products_count)]) }}
                                </a>
                            @else
                                <span class="badge-tr count-pill empty">{{ __('0 products') }}</span>
                            @endif
                        </div>
                        <div class="cname">{{ $category->name }}</div>
                        <div class="langs">
                            <span dir="rtl" class="inline-block">{{ $category->name_ar }}</span>
                            <span class="mx-1">/</span>
                            <span dir="rtl" class="inline-block">{{ $category->name_ku }}</span>
                        </div>
                        <div class="meta-row">
                            <span class="slug-chip">{{ $category->slug }}</span>
                            <span class="shrink-0 font-mono text-[10.5px]">{{ $category->created_at?->format('d M Y') }}</span>
                        </div>
                        <div class="acts">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn primary" title="{{ __('Edit') }}">
                                <i class="fas fa-pen"></i> {{ __('Edit') }}
                            </a>
                            @if($category->products_count > 0)
                                <span class="btn locked" style="flex: 0 0 42px;" title="{{ __('Cannot delete category with assigned products') }}">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @else
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                      data-danger-confirm
                                      data-danger-title="{{ __('Delete Category') }}"
                                      data-danger-description="{{ __('This action is permanent. The selected category will be deleted and cannot be recovered.') }}"
                                      style="flex: 0 0 42px; display: flex;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn danger w-full" title="{{ __('Delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ═════════════ Pagination ═════════════ --}}
        @if($categories->hasPages())
            <div class="mt-6 flex flex-wrap justify-between items-center gap-3 bg-white border border-slate-200/70 rounded-2xl px-5 py-3.5 bento-shadow">
                <span class="text-[12px] text-slate-500">
                    {{ __('Showing :from–:to of :total categories', [
                        'from'  => $categories->firstItem() ?? 0,
                        'to'    => $categories->lastItem() ?? 0,
                        'total' => $categories->total(),
                    ]) }}
                </span>
                <div class="y-pagination">
                    {{ $categories->links() }}
                </div>
            </div>
        @endif

    </div>
    </div>
    </div>
</x-app-layout>
