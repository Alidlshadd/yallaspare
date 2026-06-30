<x-app-layout>
    <x-slot name="header">{{ __('Products') }}</x-slot>

    @php
        $importErrors = session('import_errors', []);
        $currentProductsUrl = request()->fullUrl();
        $currentSort = $sort ?? request('sort', 'id');
        $currentDir = $direction ?? request('dir', 'desc');
        $currentStatus = $status ?? request('status', 'all');
        $sortUrl = function ($field) use ($currentSort, $currentDir) {
            $dir = $currentSort === $field && $currentDir === 'asc' ? 'desc' : 'asc';
            return route('admin.products.index', array_merge(request()->except('page'), [
                'sort' => $field,
                'dir' => $dir,
            ]));
        };
        $statusUrl = function ($statusKey) {
            return route('admin.products.index', array_merge(request()->except('page', 'status', 'low_stock'), [
                'status' => $statusKey,
            ]));
        };

        // Status visual map for chips
        $statusVisual = [
            'all'          => ['hex' => '#04042a', 'dot' => 'bg-slate-800'],
            'active'       => ['hex' => '#16a34a', 'dot' => 'bg-emerald-500'],
            'inactive'     => ['hex' => '#64748b', 'dot' => 'bg-slate-500'],
            'low_stock'    => ['hex' => '#f59e0b', 'dot' => 'bg-amber-500'],
            'out_of_stock' => ['hex' => '#dc2626', 'dot' => 'bg-rose-500'],
        ];

        $hasActiveFilters = request()->hasAny(['search', 'category_id', 'brand']);
    @endphp

    <style>
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
        .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
        .num-display { font-feature-settings: "tnum" 1, "lnum" 1; letter-spacing: -0.025em; }

        @keyframes ys-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(251,191,36,0.55); }
            100% { box-shadow: 0 0 0 8px rgba(251,191,36,0); }
        }
        .ys-pulse-dot { animation: ys-pulse 1.6s ease-out infinite; }

        /* Stat tile — same dialect as Orders / Returns */
        .ptile {
            position: relative; overflow: hidden;
            background: #fff; border: 1px solid #e3e9f1; border-radius: 24px;
            padding: 18px;
            box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .ptile:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(7,7,64,0.10); }
        .ptile .strip { position: absolute; top: 0; bottom: 0; left: 0; width: 3px; }
        .ptile .ic {
            height: 40px; width: 40px; border-radius: 12px;
            display: grid; place-items: center;
        }
        .ptile .row1 { display: flex; align-items: center; gap: 12px; }
        .ptile .lbl { font-size: 10px; font-weight: 800; letter-spacing: 0.10em; text-transform: uppercase; color: #64748b; }
        .ptile .val { font-size: 28px; font-weight: 900; color: #04042a; margin-top: 12px; letter-spacing: -0.02em; }
        .ptile .foot { font-size: 11px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 6px; }
        .ptile .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; }
        .dark .ptile { background: #0f172a; border-color: #1e293b; }
        .dark .ptile .val { color: #f8fafc; }
        .dark .ptile .lbl, .dark .ptile .foot { color: #94a3b8; }
        /* Variants */
        .ptile.t-all      .strip { background: linear-gradient(180deg, #1e293b, #04042a); }
        .ptile.t-all      .ic { background: #f1f5f9; color: #04042a; }
        .ptile.t-all      .dot { background: #04042a; }
        .dark .ptile.t-all .ic { background: #1e293b; color: #fcd34d; }
        .ptile.t-active   .strip { background: linear-gradient(180deg, #4ade80, #16a34a); }
        .ptile.t-active   .ic { background: #dcfce7; color: #15803d; }
        .ptile.t-active   .dot { background: #16a34a; }
        .dark .ptile.t-active .ic { background: rgba(16,163,74,0.15); color: #4ade80; }
        .ptile.t-inactive .strip { background: linear-gradient(180deg, #cbd5e1, #64748b); }
        .ptile.t-inactive .ic { background: #f1f5f9; color: #475569; }
        .ptile.t-inactive .dot { background: #64748b; }
        .dark .ptile.t-inactive .ic { background: #1e293b; color: #cbd5e1; }
        .ptile.t-low      .strip { background: linear-gradient(180deg, #fbbf24, #f59e0b); }
        .ptile.t-low      .ic { background: #fef3c7; color: #b45309; }
        .ptile.t-low      .dot { background: #f59e0b; }
        .dark .ptile.t-low .ic { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .ptile.t-out      .strip { background: linear-gradient(180deg, #f87171, #dc2626); }
        .ptile.t-out      .ic { background: #fee2e2; color: #b91c1c; }
        .ptile.t-out      .dot { background: #dc2626; }
        .dark .ptile.t-out .ic { background: rgba(220,38,38,0.15); color: #f87171; }

        /* Chips */
        .ychip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 11.5px; font-weight: 700; line-height: 1;
            border: 1px solid #e2e8f0; background: #fff; color: #475569;
            text-decoration: none;
            transition: all .15s ease;
        }
        .ychip:hover { background: #f8fafc; border-color: #cbd5e1; color: #04042a; }
        .ychip .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
        .ychip .cnt {
            background: rgba(15,23,42,0.06);
            padding: 1px 7px; border-radius: 999px;
            font-size: 10.5px; font-family: ui-monospace, 'JetBrains Mono', monospace;
            color: #475569; font-weight: 800;
        }
        .ychip.on {
            background: #04042a; color: #fcd34d; border-color: #04042a;
            box-shadow: 0 6px 14px -8px rgba(4,4,42,0.40);
        }
        .ychip.on .cnt { background: rgba(252,211,77,0.18); color: #fcd34d; }
        .dark .ychip { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        .dark .ychip .cnt { background: rgba(255,255,255,0.06); color: #cbd5e1; }
        .dark .ychip:hover { background: #334155; color: #fff; }
        .dark .ychip.on { background: #fbbf24; color: #04042a; border-color: #fbbf24; }
        .dark .ychip.on .cnt { background: rgba(4,4,42,0.18); color: #04042a; }

        /* Custom select chevron amber */
        .y-select {
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23f59e0b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat; background-position: right 14px center;
            padding-right: 40px;
        }
        [dir='rtl'] .y-select { background-position: left 14px center; padding-right: 14px; padding-left: 40px; }

        /* Product card */
        .prod-card {
            position: relative; overflow: hidden;
            background: #fff; border: 1px solid #e3e9f1; border-radius: 18px;
            padding: 14px;
            box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06);
            transition: all .2s ease;
            display: flex; flex-direction: column;
        }
        .prod-card:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(7,7,64,0.10); border-color: #fcd34d; }
        .dark .prod-card { background: #0f172a; border-color: #1e293b; }
        .dark .prod-card:hover { border-color: #fbbf24; }
        .prod-card .img-wrap {
            position: relative;
            height: 160px; border-radius: 12px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid #e3e9f1;
            display: grid; place-items: center;
            margin-bottom: 12px;
            overflow: hidden;
            padding: 22px;
        }
        .dark .prod-card .img-wrap { background: linear-gradient(135deg, #1e293b, #0f172a); border-color: #334155; }
        .prod-card .img-wrap img {
            width: 100%; height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
        }
        .prod-card .img-wrap .placeholder { color: #94a3b8; font-size: 32px; }
        .prod-card .badge-tl { position: absolute; top: 8px; left: 8px; }
        .prod-card .badge-tr { position: absolute; top: 8px; right: 8px; }
        .prod-card .pname {
            font-weight: 700; color: #04042a; font-size: 13.5px;
            line-height: 1.3;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 35px;
        }
        .dark .prod-card .pname { color: #f8fafc; }
        .prod-card .sku { font-family: ui-monospace, monospace; font-size: 10.5px; color: #64748b; }
        .prod-card .brand-row { font-size: 11px; color: #64748b; margin-top: 4px; }
        .prod-card .brand-row .pid {
            display: inline-block; font-family: ui-monospace, monospace;
            color: #94a3b8; margin-left: 6px;
        }
        [dir='rtl'] .prod-card .brand-row .pid { margin-left: 0; margin-right: 6px; }
        .prod-card .price-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 10px; padding-top: 10px;
            border-top: 1px dashed #e3e9f1;
        }
        .dark .prod-card .price-row { border-top-color: #334155; }
        .prod-card .price {
            font-size: 15px; font-weight: 900; color: #04042a;
            font-variant-numeric: tabular-nums;
        }
        .dark .prod-card .price { color: #fcd34d; }
        .prod-card .price .cy { font-size: 10px; color: #94a3b8; font-weight: 600; margin-right: 3px; }
        [dir='rtl'] .prod-card .price .cy { margin-right: 0; margin-left: 3px; }
        .prod-card .dealer-row { font-size: 11px; color: #64748b; margin-top: 4px; }
        .prod-card .dealer-row .dp { font-weight: 700; color: #6d28d9; }
        .dark .prod-card .dealer-row .dp { color: #c4b5fd; }
        .prod-card .acts { display: flex; gap: 6px; margin-top: 10px; }
        .prod-card .acts .btn {
            flex: 1; height: 34px; border-radius: 9px;
            background: #fff; border: 1px solid #e2e8f0; color: #475569;
            font-size: 11.5px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            text-decoration: none;
            transition: all .15s ease;
        }
        .prod-card .acts .btn:hover { transform: translateY(-1px); }
        .prod-card .acts .btn.primary { background: #04042a; color: #fcd34d; border-color: #04042a; }
        .prod-card .acts .btn.primary:hover { background: #07073a; }
        .prod-card .acts .btn.danger { color: #b91c1c; border-color: #fca5a5; background: #fef2f2; }
        .prod-card .acts .btn.danger:hover { background: #fee2e2; }
        .dark .prod-card .acts .btn { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        .dark .prod-card .acts .btn:hover { background: #334155; }
        .dark .prod-card .acts .btn.primary { background: #fbbf24; color: #04042a; border-color: #fbbf24; }
        .dark .prod-card .acts .btn.primary:hover { background: #f59e0b; }
        .dark .prod-card .acts .btn.danger { color: #fca5a5; border-color: rgba(239,68,68,0.30); background: rgba(239,68,68,0.10); }

        /* Stock badge */
        .stock-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10.5px; font-weight: 800;
            padding: 4px 9px; border-radius: 7px;
            font-family: ui-monospace, monospace;
            white-space: nowrap;
        }
        .stock-badge.in   { background: #dcfce7; color: #15803d; }
        .stock-badge.low  { background: #fef3c7; color: #b45309; }
        .stock-badge.out  { background: #fee2e2; color: #b91c1c; }
        .dark .stock-badge.in  { background: rgba(16,163,74,0.15); color: #4ade80; }
        .dark .stock-badge.low { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .dark .stock-badge.out { background: rgba(220,38,38,0.15); color: #f87171; }

        /* Status badge on image */
        .status-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 8px; border-radius: 999px;
            font-size: 9.5px; font-weight: 800;
            border: 1px solid; backdrop-filter: blur(6px);
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        .status-pill::before { content: ""; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .status-pill.active   { background: rgba(220,252,231,0.92); color: #15803d; border-color: rgba(134,239,172,0.8); }
        .status-pill.inactive { background: rgba(241,245,249,0.92); color: #475569; border-color: rgba(203,213,225,0.8); }

        /* Margin warning chip on dealer */
        .margin-warn {
            display: inline-flex; align-items: center; gap: 4px;
            background: #fef3c7; color: #92400e;
            padding: 2px 7px; border-radius: 6px;
            font-size: 9.5px; font-weight: 800;
            font-family: ui-monospace, monospace;
            border: 1px solid #fde68a;
        }

        /* Pagination */
        .y-pagination nav { display: flex; }
        .y-pagination ul,
        .y-pagination .pagination {
            display: flex; flex-wrap: wrap; gap: 4px; list-style: none; margin: 0; padding: 0;
        }
        .y-pagination a,
        .y-pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 34px; padding: 0 10px;
            border-radius: 9px; background: #fff;
            border: 1px solid #e2e8f0; color: #475569;
            font-size: 12px; font-weight: 700; text-decoration: none;
            transition: all .15s ease;
        }
        .y-pagination a:hover { color: #0f172a; border-color: #cbd5e1; background: #f8fafc; }
        .y-pagination .active span,
        .y-pagination span[aria-current="page"] {
            background: #04042a; color: #fcd34d; border-color: #04042a;
        }
        .y-pagination .disabled span,
        .y-pagination span[aria-disabled="true"] { opacity: 0.45; cursor: not-allowed; }
        .dark .y-pagination a,
        .dark .y-pagination span { background: #0f172a; border-color: #334155; color: #cbd5e1; }
        .dark .y-pagination a:hover { background: #1e293b; color: #fff; border-color: #475569; }
        .dark .y-pagination .active span,
        .dark .y-pagination span[aria-current="page"] {
            background: #fbbf24; color: #04042a; border-color: #fbbf24;
        }

        /* Tools panel (Bulk Import + Export) collapsible */
        details.tools > summary {
            list-style: none; cursor: pointer;
            user-select: none;
        }
        details.tools > summary::-webkit-details-marker { display: none; }
        details.tools[open] summary .chev { transform: rotate(180deg); }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ─────────────── Flash + errors ─────────────── --}}
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                {{ session('warning') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ═════════════ Hero ═════════════ --}}
        <div class="relative overflow-hidden rounded-2xl mb-4 p-6 text-white"
             style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
            <div class="absolute top-0 bottom-0 left-0 w-[3px]" style="background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);"></div>
            <div class="absolute -top-16 -right-16 h-64 w-64 rounded-full bg-amber-400/10 blur-[60px] pointer-events-none"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-mono text-[10px] font-extrabold uppercase tracking-[0.28em] text-amber-300">{{ __('Catalog · Inventory') }}</div>
                    <h1 class="text-3xl font-black mt-2 leading-tight">{{ __('Products') }}</h1>
                    <p class="text-sm text-white/65 mt-2">
                        {{ __(':total products', ['total' => number_format((int) ($statusTabs['all']['count'] ?? 0))]) }}
                        @if($lowStockCount > 0)
                            <span class="text-white/35 mx-1">·</span>
                            <b class="text-amber-300">{{ __(':n low stock', ['n' => number_format((int) $lowStockCount)]) }}</b>
                        @endif
                        @if(($statusTabs['out_of_stock']['count'] ?? 0) > 0)
                            <span class="text-white/35 mx-1">·</span>
                            <b class="text-rose-300">{{ __(':n out of stock', ['n' => number_format((int) $statusTabs['out_of_stock']['count'])]) }}</b>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2" x-data="{ importOpen: false }">
                    <button type="button" @click="importOpen = true"
                            class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-xs font-bold text-white bg-white/10 border border-white/15 hover:bg-white/15 backdrop-blur-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M17 8l-5-5-5 5M12 3v12"/></svg>
                        {{ __('Import') }}
                    </button>
                    <a href="{{ route('admin.products.export-excel') }}"
                       class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-xs font-bold text-white bg-white/10 border border-white/15 hover:bg-white/15 backdrop-blur-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        {{ __('Export Excel') }}
                    </a>
                    <a href="{{ route('admin.products.create', ['return_to' => $currentProductsUrl]) }}"
                       class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-xs font-bold text-[#04042a] shadow-md shadow-amber-500/30 transition hover:brightness-105"
                       style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Add Product') }}
                    </a>

                    {{-- Import modal --}}
                    <template x-teleport="body">
                        <div x-show="importOpen" x-cloak x-transition.opacity
                             class="fixed inset-0 z-[100] grid place-items-center bg-black/60 backdrop-blur-sm p-4"
                             @click.self="importOpen = false"
                             @keydown.escape.window="importOpen = false">
                            <div x-show="importOpen" x-transition
                                 class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden text-slate-900 dark:text-slate-100">
                                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-200 dark:border-slate-800 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900/60 dark:to-slate-900">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-extrabold">{{ __('Bulk Import') }}</div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Upload CSV / XLSX to update or create products') }}</div>
                                        </div>
                                    </div>
                                    <button type="button" @click="importOpen = false"
                                            class="h-8 w-8 rounded-lg grid place-items-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data"
                                      class="p-5 space-y-3"
                                      data-loading-form
                                      data-loading-message="Uploading, please wait..."
                                      data-loading-button-text="Uploading...">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ $currentProductsUrl }}">

                                    <div>
                                        <label for="import-file" class="block text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5">{{ __('File') }}</label>
                                        <input id="import-file" type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" required
                                               class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 file:mr-3 file:rounded-md file:border-0 file:bg-slate-200 file:px-3 file:py-1 file:text-xs file:font-bold dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                    </div>

                                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 p-3 text-[11px] leading-relaxed text-slate-600 dark:text-slate-300">
                                        <div class="font-extrabold uppercase text-[10px] tracking-widest text-slate-500 dark:text-slate-400 mb-1.5">{{ __('Requirements') }}</div>
                                        <p>
                                            {{ __('Supported files: CSV, TXT, XLS, XLSX.') }}
                                        </p>
                                        <p class="mt-1">
                                            <b>{{ __('Required columns:') }}</b>
                                            <span class="font-mono">name_en, name_ar, name_ku, price, stock_quantity</span>
                                        </p>
                                        <p class="mt-1">
                                            <b>{{ __('Category:') }}</b>
                                            {{ __('Use one of') }} <span class="font-mono">category_id</span>, <span class="font-mono">category_slug</span> {{ __('or') }} <span class="font-mono">category_name</span>.
                                        </p>
                                    </div>

                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" @click="importOpen = false"
                                                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                                            {{ __('Cancel') }}
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-xs font-bold text-amber-300 bg-[#04042a] hover:bg-[#07073a] transition">
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

        {{-- ═════════════ Stat tiles (left-stripe dialect) ═════════════ --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
            @php
                $statTiles = [
                    [
                        'class' => 't-all',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
                        'lbl'   => __('Total Products'),
                        'val'   => $statusTabs['all']['count'] ?? 0,
                        'foot'  => __('Across all statuses'),
                    ],
                    [
                        'class' => 't-active',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        'lbl'   => __('Active'),
                        'val'   => $statusTabs['active']['count'] ?? 0,
                        'foot'  => __('Visible in storefront'),
                    ],
                    [
                        'class' => 't-inactive',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>',
                        'lbl'   => __('Inactive'),
                        'val'   => $statusTabs['inactive']['count'] ?? 0,
                        'foot'  => __('Hidden from customers'),
                    ],
                    [
                        'class' => 't-low',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                        'lbl'   => __('Low Stock'),
                        'val'   => $statusTabs['low_stock']['count'] ?? 0,
                        'foot'  => __('Below threshold (:n)', ['n' => $lowStockThreshold]),
                        'foot_class' => 'text-amber-700 dark:text-amber-300 font-semibold',
                    ],
                    [
                        'class' => 't-out',
                        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>',
                        'lbl'   => __('Out of Stock'),
                        'val'   => $statusTabs['out_of_stock']['count'] ?? 0,
                        'foot'  => __('Restock urgently'),
                        'foot_class' => 'text-rose-700 dark:text-rose-300 font-semibold',
                    ],
                ];
            @endphp

            @foreach($statTiles as $s)
                <div class="ptile {{ $s['class'] }}">
                    <div class="strip"></div>
                    <div class="row1">
                        <div class="ic">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $s['icon'] !!}</svg>
                        </div>
                        <div class="lbl">{{ $s['lbl'] }}</div>
                    </div>
                    <div class="val num-display">{{ number_format((int) $s['val']) }}</div>
                    <div class="foot {{ $s['foot_class'] ?? '' }}">
                        <span class="dot"></span> {{ $s['foot'] }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Bulk Import & Export card removed — actions now live in the hero (Import modal + Export Excel button). --}}

        {{-- ═════════════ Import error report ═════════════ --}}
        @if(count($importErrors) > 0)
            <div class="mb-4 bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-500/30 rounded-2xl overflow-hidden bento-shadow">
                <div class="flex items-center gap-2 px-5 py-3 bg-rose-50 dark:bg-rose-500/10 border-b border-rose-200 dark:border-rose-500/30 text-sm font-bold text-rose-700 dark:text-rose-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    {{ __('Import Error Report') }} ({{ count($importErrors) }} {{ __('rows') }})
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-widest">{{ __('Row') }}</th>
                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-widest">{{ __('SKU') }}</th>
                                <th class="px-4 py-3 text-[10px] font-bold uppercase tracking-widest">{{ __('Error') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($importErrors as $errorRow)
                                <tr class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="px-4 py-3 font-mono text-slate-700 dark:text-slate-200">{{ $errorRow['row'] ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">{{ $errorRow['sku'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-rose-700 dark:text-rose-300">{{ $errorRow['message'] ?? __('Unknown error') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ═════════════ Filter card ═════════════ --}}
        <form method="GET" action="{{ route('admin.products.index') }}" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 bento-shadow mb-4">
            <input type="hidden" name="status" value="{{ $currentStatus }}">

            <div class="flex items-center gap-2.5 mb-4">
                <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Filter Products') }}</h3>
            </div>

            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto] items-end">
                <div>
                    <label for="filter-search" class="block text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5">{{ __('Search') }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                        </span>
                        <input id="filter-search" name="search" value="{{ request('search') }}"
                               placeholder="{{ __('Product name, SKU, brand...') }}"
                               class="h-11 w-full ps-10 pe-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder:text-slate-400 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900">
                    </div>
                </div>

                <div>
                    <label for="filter-category" class="block text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5">{{ __('Category') }}</label>
                    <select id="filter-category" name="category_id"
                            class="y-select h-11 w-full px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:focus:bg-slate-900">
                        <option value="">{{ __('All Categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-brand" class="block text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5">{{ __('Brand') }}</label>
                    <select id="filter-brand" name="brand"
                            class="y-select h-11 w-full px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:focus:bg-slate-900">
                        <option value="">{{ __('All Brands') }}</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand }}" @selected((string) request('brand') === (string) $brand)>
                                {{ $brand }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2 justify-end">
                    @if($hasActiveFilters)
                        <a href="{{ $statusUrl($currentStatus) }}"
                           class="inline-flex items-center gap-2 h-11 px-4 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            {{ __('Reset') }}
                        </a>
                    @endif
                    <button type="submit" class="inline-flex items-center gap-2 h-11 px-5 rounded-xl text-xs font-bold text-amber-300 bg-[#04042a] hover:bg-[#07073a] transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        {{ __('Apply') }}
                    </button>
                </div>
            </div>
        </form>

        {{-- ═════════════ Status quick-filter band ═════════════ --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl px-5 py-4 mb-4 bento-shadow flex flex-wrap items-center gap-4">
            <span class="inline-flex items-center gap-2 font-mono text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400 shrink-0">
                <span class="relative inline-flex h-1.5 w-1.5">
                    <span class="absolute inset-0 rounded-full bg-amber-500 ys-pulse-dot"></span>
                    <span class="relative h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                </span>
                {{ __('Quick Filter · Status') }}
            </span>
            <div class="flex flex-wrap gap-1.5">
                @foreach($statusTabs as $statusKey => $tab)
                    @php
                        $isSelected = $currentStatus === $statusKey;
                        $visual = $statusVisual[$statusKey] ?? ['hex' => '#94a3b8', 'dot' => 'bg-slate-400'];
                    @endphp
                    <a href="{{ $statusUrl($statusKey) }}" class="ychip {{ $isSelected ? 'on' : '' }}">
                        <span class="dot" style="background: {{ $visual['hex'] }};"></span>
                        {{ $tab['label'] }} <span class="cnt">{{ number_format((int) $tab['count']) }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ═════════════ Sort + Result count ═════════════ --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 px-2">
            <div class="text-[12.5px] text-slate-600 dark:text-slate-300">
                {{ $statusTabs[$currentStatus]['label'] ?? __('All Products') }}
                <span class="text-slate-400 dark:text-slate-500 ms-1">
                    ({{ __('showing :from–:to of :total', [
                        'from'  => $products->firstItem() ?? 0,
                        'to'    => $products->lastItem() ?? 0,
                        'total' => $products->total(),
                    ]) }})
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Sort') }}</span>
                <a href="{{ $sortUrl('id') }}" class="ychip {{ $currentSort === 'id' ? 'on' : '' }}">ID @if($currentSort === 'id') <i class="text-[9px]">{{ $currentDir === 'asc' ? '↑' : '↓' }}</i> @endif</a>
                <a href="{{ $sortUrl('name_en') }}" class="ychip {{ $currentSort === 'name_en' ? 'on' : '' }}">{{ __('Name') }} @if($currentSort === 'name_en') <i class="text-[9px]">{{ $currentDir === 'asc' ? '↑' : '↓' }}</i> @endif</a>
                <a href="{{ $sortUrl('price') }}" class="ychip {{ $currentSort === 'price' ? 'on' : '' }}">{{ __('Price') }} @if($currentSort === 'price') <i class="text-[9px]">{{ $currentDir === 'asc' ? '↑' : '↓' }}</i> @endif</a>
                <a href="{{ $sortUrl('stock_quantity') }}" class="ychip {{ $currentSort === 'stock_quantity' ? 'on' : '' }}">{{ __('Stock') }} @if($currentSort === 'stock_quantity') <i class="text-[9px]">{{ $currentDir === 'asc' ? '↑' : '↓' }}</i> @endif</a>
            </div>
        </div>

        {{-- ═════════════ Product card grid ═════════════ --}}
        @if($products->count() === 0)
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl py-14 px-4 text-center bento-shadow">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center text-slate-400 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div class="text-base font-bold text-slate-900 dark:text-white">{{ $statusTabs[$currentStatus]['empty'] ?? __('No products found.') }}</div>
                <div class="text-[13px] text-slate-500 dark:text-slate-400 mt-1.5">{{ __('Try changing the filters or status above.') }}</div>
                @if($hasActiveFilters)
                    <a href="{{ $statusUrl($currentStatus) }}"
                       class="inline-flex items-center gap-2 h-10 px-4 mt-4 rounded-xl text-xs font-bold text-amber-300 bg-[#04042a] hover:bg-[#07073a] transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        {{ __('Reset filters') }}
                    </a>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                @foreach($products as $product)
                    @php
                        $isLow = $product->stock_quantity > 0 && $product->stock_quantity <= $lowStockThreshold;
                        $isOut = $product->stock_quantity === 0;
                        $stockClass = $isOut ? 'out' : ($isLow ? 'low' : 'in');
                        $stockLabel = $isOut ? __('Out') : ($isLow ? __(':n low', ['n' => $product->stock_quantity]) : __(':n', ['n' => $product->stock_quantity]));
                        $viewsCount = (int) optional($product->analytics)->views_count;
                        $lastViewedAt = optional($product->analytics)->last_viewed_at;
                    @endphp
                    <div class="prod-card">
                        <div class="img-wrap">
                            @if($product->image)
                                <img src="{{ asset('storage/' . ltrim((string) $product->image, '/')) }}"
                                     alt="{{ $product->name }}" loading="lazy">
                            @else
                                <i class="fas fa-image placeholder"></i>
                            @endif
                            <span class="badge-tr status-pill {{ $product->is_active ? 'active' : 'inactive' }}">
                                {{ $product->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </div>
                        <div class="sku">{{ $product->sku ?? '—' }}</div>
                        <div class="pname mt-1">{{ $product->name }}</div>
                        <div class="brand-row">
                            {{ $product->brand ?? '—' }}<span class="pid">· #{{ $product->id }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-2 text-[11px] font-semibold text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1.5">
                                <i class="fas fa-eye text-amber-500"></i>
                                {{ __(':count views', ['count' => number_format($viewsCount)]) }}
                            </span>
                            @if($lastViewedAt)
                                <span class="font-mono text-[10px] text-slate-400">{{ $lastViewedAt->diffForHumans() }}</span>
                            @endif
                        </div>
                        <div class="price-row">
                            <div class="price">
                                <span class="cy">{{ $currencyLabel }}</span>{{ number_format($product->price, $currencyDecimals) }}
                            </div>
                            <span class="stock-badge {{ $stockClass }}">
                                {{ $stockLabel }}
                                <span class="sr-only">{{ __(':count units', ['count' => $product->stock_quantity]) }}</span>
                            </span>
                        </div>
                        @if($product->dealer_price !== null)
                            <div class="dealer-row">
                                {{ __('Dealer:') }}
                                <span class="dp">{{ $currencyLabel }} {{ number_format($product->dealer_price, $currencyDecimals) }}</span>
                                @if((float) $product->dealer_price >= (float) $product->price)
                                    <span class="margin-warn ms-1">{{ __('Margin') }}</span>
                                @endif
                            </div>
                        @endif
                        <div class="acts">
                            <a href="{{ route('admin.products.edit', ['product' => $product, 'return_to' => request()->fullUrl()]) }}"
                               class="btn primary" title="{{ __('Edit') }}">
                                <i class="fas fa-pen"></i> {{ __('Edit') }}
                            </a>
                            <form action="{{ route('admin.products.destroy', $product) }}"
                                  method="POST"
                                  data-danger-confirm
                                  data-danger-title="{{ __('Delete Product') }}"
                                  data-danger-description="{{ __('This action is permanent. The selected product will be removed and cannot be restored.') }}"
                                  class="flex-1">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_to" value="{{ $currentProductsUrl }}">
                                <button type="submit" class="btn danger w-full" title="{{ __('Delete') }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ═════════════ Pagination ═════════════ --}}
        @if($products->hasPages())
            <div class="mt-6 flex flex-wrap justify-between items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl px-5 py-3.5 bento-shadow">
                <span class="text-[12px] text-slate-500 dark:text-slate-400">
                    {{ __('Showing :from–:to of :total products', [
                        'from'  => $products->firstItem() ?? 0,
                        'to'    => $products->lastItem() ?? 0,
                        'total' => $products->total(),
                    ]) }}
                </span>
                <div class="y-pagination">
                    {{ $products->links() }}
                </div>
            </div>
        @endif

    </div>
    </div>
    </div>
</x-app-layout>
