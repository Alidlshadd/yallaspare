<x-app-layout>
    <x-slot name="header">{{ __('Vehicle Finder') }}</x-slot>

    @php
        $totalProducts = max(0, (int) ($stats['total_products'] ?? 0));
        $coveredProducts = max(0, (int) ($stats['covered_products'] ?? 0));
        $uncoveredProducts = max(0, $totalProducts - $coveredProducts);
        $coveragePct = $totalProducts > 0 ? (int) round($coveredProducts / $totalProducts * 100) : 0;
        $ringCircumference = 238.76; // 2 * pi * r(38)
        $ringDash = round($coveragePct / 100 * $ringCircumference, 2);

        $trackStart = 2000;
        $trackEnd = (int) now()->addYear()->year;
        $trackSpan = max(1, $trackEnd - $trackStart);

        $filterUrl = function (array $overrides = []) {
            $params = array_filter([
                'search' => request('search'),
                'brand' => request('brand'),
            ], fn ($value) => $value !== null && $value !== '');
            foreach ($overrides as $key => $value) {
                if ($value === null || $value === '') {
                    unset($params[$key]);
                } else {
                    $params[$key] = $value;
                }
            }
            return route('admin.vehicle-fitments.index', $params);
        };

        $brandModelMap = $brands
            ->mapWithKeys(fn ($brand) => [
                (string) $brand->id => $brand->models
                    ->map(fn ($model) => [
                        'id' => (int) $model->id,
                        'name' => $model->localizedName(),
                        'family_id' => (int) $model->vehicle_model_family_id,
                        'family_name' => (string) ($model->family?->localizedName() ?? ''),
                        'engines' => $model->engineTypes
                            ->map(fn ($engine) => ['value' => (string) $engine->name, 'label' => $engine->localizedName()])
                            ->values()
                            ->all(),
                        'year_from' => $model->production_start_year ? (int) $model->production_start_year : null,
                        'year_to' => $model->production_end_year ? (int) $model->production_end_year : null,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        $brandFamilyMap = $brands
            ->mapWithKeys(fn ($brand) => [
                (string) $brand->id => $brand->modelFamilies
                    ->map(fn ($family) => [
                        'id' => (int) $family->id,
                        'name' => $family->localizedName(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        $allEngineTypes = $brands
            ->flatMap(fn ($brand) => $brand->models->flatMap(fn ($model) => $model->engineTypes))
            ->unique(fn ($engine) => mb_strtolower((string) $engine->name))
            ->sortBy('name')
            ->map(fn ($engine) => ['value' => (string) $engine->name, 'label' => $engine->localizedName()])
            ->values();

        $fitmentRows = old('fitments');
        if (!is_array($fitmentRows) || $fitmentRows === []) {
            $fitmentRows = [[
                'vehicle_brand_id' => old('vehicle_brand_id'),
                'vehicle_model_family_id' => old('vehicle_model_family_id'),
                'vehicle_model_id' => old('vehicle_model_id'),
                'year_from' => old('year_from'),
                'year_to' => old('year_to'),
                'engine' => old('engine'),
                'notes' => old('notes'),
            ]];
        }

        $openFitmentPanel = $errors->any() || old('product_id') !== null || old('fitments') !== null;
    @endphp

    <style>
        [hidden] { display: none !important; }
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
        .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }

        /* Chips — same dialect as Products/Categories */
        .ychip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 11.5px; font-weight: 700; line-height: 1;
            border: 1px solid #e2e8f0; background: #fff; color: #475569;
            text-decoration: none;
            transition: all .15s ease;
        }
        .ychip:hover { background: #f8fafc; border-color: #cbd5e1; color: #04042a; }
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

        /* Status pills */
        .vf-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 800; border: 1px solid;
        }
        .vf-pill::before { content: ""; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .vf-pill.good { background: #dcfce7; color: #15803d; border-color: #86efac; }
        .vf-pill.warn { background: #fef3c7; color: #b45309; border-color: #fde68a; }
        .dark .vf-pill.good { background: rgba(34,197,94,0.12); color: #4ade80; border-color: rgba(74,222,128,0.35); }
        .dark .vf-pill.warn { background: rgba(245,158,11,0.12); color: #fbbf24; border-color: rgba(251,191,36,0.35); }

        .vf-mono-chip {
            display: inline-block; font-family: ui-monospace, monospace; font-size: 10.5px; font-weight: 600;
            background: #f8fafc; border: 1px solid #e3e9f1;
            padding: 3px 8px; border-radius: 7px; color: #64748b;
        }
        .dark .vf-mono-chip { background: #1e293b; border-color: #334155; color: #94a3b8; }

        /* Year-range timeline */
        .vf-range { display: flex; flex-direction: column; gap: 5px; min-width: 180px; }
        .vf-range .years {
            display: flex; justify-content: space-between; gap: 8px;
            font-family: ui-monospace, monospace; font-size: 10px; font-weight: 700; color: #94a3b8;
        }
        .vf-range .years .mid { font-weight: 800; color: #b45309; }
        .vf-range .years .mid.full { color: #15803d; }
        .dark .vf-range .years .mid { color: #fbbf24; }
        .dark .vf-range .years .mid.full { color: #4ade80; }
        .vf-track { position: relative; height: 6px; border-radius: 999px; background: #f1f5f9; border: 1px solid #e3e9f1; }
        .dark .vf-track { background: #1e293b; border-color: #334155; }
        .vf-fill {
            position: absolute; top: -1px; bottom: -1px; border-radius: 999px;
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
        }
        .vf-fill.full { background: linear-gradient(90deg, #34d399, #10b981); }

        /* Brand tree */
        .vf-brand { border: 1px solid #e3e9f1; border-radius: 12px; overflow: hidden; }
        .dark .vf-brand { border-color: #334155; }
        .vf-brand .bh {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 9px 12px; background: #f8fafc;
        }
        .dark .vf-brand .bh { background: #1e293b; }
        .vf-model {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 700; color: #64748b;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 999px; padding: 4px 9px;
        }
        .dark .vf-model { background: #0f172a; border-color: #334155; color: #94a3b8; }
        .vf-model button[type="submit"] { color: #b91c1c; font-weight: 800; line-height: 1; }
        .dark .vf-model button[type="submit"] { color: #fca5a5; }
        .vf-model .edit { color: #94a3b8; line-height: 1; transition: color .15s ease; }
        .vf-model .edit:hover { color: #f59e0b; }
        .vf-edit-inline { display: inline-flex; align-items: center; gap: 4px; }
        .vf-edit-inline .vf-inp { height: 30px; font-size: 11.5px; padding: 0 9px; width: 140px; }
        .vf-edit-inline .vf-engine-edit { width: 230px; }
        .vf-edit-inline .vf-year-edit { width: 88px; }
        .vf-model-stack { display: inline-flex; flex-direction: column; align-items: flex-start; gap: 4px; }
        .vf-engine-list { display: flex; flex-wrap: wrap; gap: 4px; padding-inline: 4px; }
        .vf-engine-chip {
            display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 999px;
            background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412;
            font-size: 9.5px; font-weight: 800; line-height: 1.25;
        }
        .dark .vf-engine-chip { background: rgba(245,158,11,.10); border-color: rgba(251,191,36,.28); color: #fbbf24; }
        .vf-year-chip {
            display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 999px;
            background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8;
            font-size: 9.5px; font-weight: 800; line-height: 1.25;
        }
        .dark .vf-year-chip { background: rgba(59,130,246,.10); border-color: rgba(96,165,250,.28); color: #93c5fd; }

        /* Buttons */
        .vf-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            height: 38px; padding: 0 16px; border-radius: 10px; border: 1px solid #e2e8f0;
            background: #fff; color: #475569; font-size: 12px; font-weight: 800; cursor: pointer;
            text-decoration: none; transition: all .15s ease;
        }
        .vf-btn:hover { transform: translateY(-1px); }
        .vf-btn.primary { background: #04042a; color: #fcd34d; border-color: #04042a; }
        .vf-btn.primary:hover { background: #07073a; }
        .vf-btn.gold {
            background: linear-gradient(180deg, #fbbf24, #f59e0b); color: #04042a; border-color: transparent;
            box-shadow: 0 6px 16px -6px rgba(245,158,11,0.5);
        }
        .vf-btn.gold:hover { filter: brightness(1.05); }
        .vf-btn.danger { background: #fef2f2; color: #b91c1c; border-color: #fca5a5; }
        .vf-btn.danger:hover { background: #fee2e2; }
        .vf-btn.sm { height: 30px; padding: 0 11px; font-size: 11px; border-radius: 8px; }
        .dark .vf-btn { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        .dark .vf-btn:hover { background: #334155; }
        .dark .vf-btn.primary { background: #fbbf24; color: #04042a; border-color: #fbbf24; }
        .dark .vf-btn.primary:hover { background: #f59e0b; }
        .dark .vf-btn.gold { background: linear-gradient(180deg, #fbbf24, #f59e0b); color: #04042a; }
        .dark .vf-btn.danger { background: rgba(239,68,68,0.10); color: #fca5a5; border-color: rgba(239,68,68,0.30); }

        /* Inputs */
        .vf-inp, .vf-sel {
            width: 100%; height: 38px; padding: 0 12px; font-size: 13px;
            border: 1px solid #e2e8f0; border-radius: 10px;
            background: #f8fafc; color: #0f172a;
        }
        .vf-inp:focus, .vf-sel:focus {
            outline: none; border-color: #fbbf24; background: #fff;
            box-shadow: 0 0 0 3px rgba(251,191,36,0.25);
        }
        .dark .vf-inp, .dark .vf-sel { background: #1e293b; border-color: #334155; color: #f1f5f9; }
        .dark .vf-inp:focus, .dark .vf-sel:focus { background: #0f172a; }
        .vf-tagbox {
            display: flex; flex-wrap: wrap; align-items: center; gap: 6px; min-height: 42px; padding: 6px 9px;
            border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; cursor: text;
        }
        .vf-tagbox:focus-within { border-color: #fbbf24; background: #fff; box-shadow: 0 0 0 3px rgba(251,191,36,.25); }
        .vf-tagbox input[data-engine-tag-input] {
            flex: 1 1 130px; min-width: 110px; height: 26px; padding: 0 3px; border: 0; outline: none;
            background: transparent; color: #0f172a; font-size: 12px; box-shadow: none;
        }
        .vf-tag {
            display: inline-flex; align-items: center; gap: 5px; padding: 4px 7px 4px 9px; border-radius: 999px;
            background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; font-size: 10.5px; font-weight: 800;
        }
        .vf-tag button { color: #c2410c; font-size: 14px; line-height: 1; }
        .dark .vf-tagbox { background: #1e293b; border-color: #334155; }
        .dark .vf-tagbox:focus-within { background: #0f172a; }
        .dark .vf-tagbox input[data-engine-tag-input] { color: #f1f5f9; }
        .dark .vf-tag { background: rgba(245,158,11,.10); border-color: rgba(251,191,36,.28); color: #fbbf24; }
        .dark .vf-tag button { color: #fcd34d; }
        .vf-fitment-card {
            padding: 14px; border: 1px solid #e2e8f0; border-radius: 14px;
            background: linear-gradient(180deg, #fff, #fbfcfe);
        }
        .vf-fitment-card + .vf-fitment-card { margin-top: 12px; }
        .vf-fitment-card-head {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0;
        }
        .vf-fitment-number {
            display: grid; place-items: center; width: 24px; height: 24px; border-radius: 8px;
            background: #04042a; color: #fcd34d; font: 800 11px/1 ui-monospace, monospace;
        }
        .dark .vf-fitment-card { background: linear-gradient(180deg, #0f172a, #111c2e); border-color: #334155; }
        .dark .vf-fitment-card-head { border-color: #334155; }
        .dark .vf-fitment-number { background: #fbbf24; color: #04042a; }
        .vf-lbl {
            display: block; font-size: 10px; font-weight: 800; text-transform: uppercase;
            letter-spacing: .12em; color: #64748b; margin-bottom: 5px;
        }
        .dark .vf-lbl { color: #94a3b8; }

        /* Fitment rule row */
        .vf-row {
            display: grid; grid-template-columns: minmax(210px, 1.2fr) minmax(150px, .8fr) minmax(220px, 1.2fr) auto;
            gap: 14px; align-items: center; padding: 13px 16px;
            border-bottom: 1px solid #eef1f6;
        }
        .dark .vf-row { border-bottom-color: #1e293b; }
        .vf-row:last-child { border-bottom: none; }
        .vf-row:hover { background: #fafbfd; }
        .dark .vf-row:hover { background: rgba(30,41,59,0.4); }
        @media (max-width: 900px) { .vf-row { grid-template-columns: 1fr; gap: 8px; } }

        .vf-thumb {
            width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid #e3e9f1; display: grid; place-items: center; color: #94a3b8;
            overflow: hidden;
        }
        .vf-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .dark .vf-thumb { background: linear-gradient(135deg, #1e293b, #0f172a); border-color: #334155; }

        /* Floating add button */
        .vf-fab {
            position: sticky; bottom: 18px; z-index: 30;
            margin-inline-start: auto; width: fit-content;
            display: flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 999px;
            background: #04042a; color: #fcd34d; font-weight: 800; font-size: 13px;
            box-shadow: 0 10px 28px rgba(4,4,42,0.35); cursor: pointer;
            border: 1px solid rgba(252,211,77,0.25);
            transition: all .15s ease;
        }
        .vf-fab:hover { transform: translateY(-2px); }
        .dark .vf-fab { background: #fbbf24; color: #04042a; border-color: #fbbf24; }

        /* Pagination — same dialect as Products/Categories */
        .y-pagination nav { display: flex; }
        .y-pagination ul,
        .y-pagination .pagination { display: flex; flex-wrap: wrap; gap: 4px; list-style: none; margin: 0; padding: 0; }
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
        .y-pagination span[aria-current="page"] { background: #04042a; color: #fcd34d; border-color: #04042a; }
        .y-pagination .disabled span,
        .y-pagination span[aria-disabled="true"] { opacity: 0.45; cursor: not-allowed; }
        .dark .y-pagination a,
        .dark .y-pagination span { background: #0f172a; border-color: #334155; color: #cbd5e1; }
        .dark .y-pagination a:hover { background: #1e293b; color: #fff; border-color: #475569; }
        .dark .y-pagination .active span,
        .dark .y-pagination span[aria-current="page"] { background: #fbbf24; color: #04042a; border-color: #fbbf24; }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-4">

        {{-- ─────────────── Flash + errors ─────────────── --}}
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ═════════════ Coverage board ═════════════ --}}
        <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)] items-stretch">

            {{-- Coverage ring --}}
            <div class="relative overflow-hidden rounded-2xl p-5 text-white flex flex-col gap-3"
                 style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
                <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
                <div class="absolute top-0 bottom-0 start-0 w-[3px]" style="background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);"></div>
                <div class="absolute -top-16 -end-16 h-52 w-52 rounded-full bg-amber-400/10 blur-[60px] pointer-events-none"></div>

                <div class="relative font-mono text-[10px] font-extrabold uppercase tracking-[0.28em] text-amber-300">{{ __('Catalog · Compatibility') }}</div>
                <h1 class="relative text-2xl font-black leading-tight -mt-1">{{ __('Vehicle Finder') }}</h1>

                <div class="relative flex items-center gap-4">
                    <svg width="92" height="92" viewBox="0 0 92 92" class="shrink-0" role="img" aria-label="{{ __('Coverage: :pct%', ['pct' => $coveragePct]) }}">
                        <circle cx="46" cy="46" r="38" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="9"/>
                        <circle cx="46" cy="46" r="38" fill="none" stroke="#fbbf24" stroke-width="9" stroke-linecap="round"
                                stroke-dasharray="{{ $ringDash }} {{ $ringCircumference }}" transform="rotate(-90 46 46)"/>
                        <text x="46" y="52" text-anchor="middle" fill="#fff" font-size="18" font-weight="900">{{ $coveragePct }}%</text>
                    </svg>
                    <div>
                        <div class="text-[26px] font-black leading-none">
                            {{ number_format($coveredProducts) }} <span class="text-[13px] font-bold text-amber-300">/ {{ number_format($totalProducts) }}</span>
                        </div>
                        <p class="text-[11.5px] text-white/60 mt-1.5 leading-snug">
                            {{ __(':n products have vehicle matches.', ['n' => number_format($coveredProducts)]) }}<br>
                            {{ __(':n products are not covered yet.', ['n' => number_format($uncoveredProducts)]) }}
                        </p>
                    </div>
                </div>

                <button type="button" data-vf-open-fitment
                        class="relative inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl text-xs font-bold text-[#04042a] transition hover:brightness-105 mt-auto"
                        style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add Fitment') }}
                </button>
            </div>

            {{-- Mini stats + brand distribution --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 bento-shadow flex flex-col justify-between gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl grid place-items-center bg-[#04042a] text-amber-300 shrink-0">
                            <i class="fas fa-car-side text-sm"></i>
                        </span>
                        <div>
                            <div class="text-xl font-black text-slate-900 dark:text-white leading-none">{{ number_format((int) $stats['brands']) }}</div>
                            <div class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-1">{{ __('Brands') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl grid place-items-center bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 shrink-0">
                            <i class="fas fa-layer-group text-sm"></i>
                        </span>
                        <div>
                            <div class="text-xl font-black text-slate-900 dark:text-white leading-none">{{ number_format((int) $stats['models']) }}</div>
                            <div class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-1">{{ __('Models') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl grid place-items-center bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 shrink-0">
                            <i class="fas fa-link text-sm"></i>
                        </span>
                        <div>
                            <div class="text-xl font-black text-slate-900 dark:text-white leading-none">{{ number_format((int) $stats['fitments']) }}</div>
                            <div class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-1">{{ __('Fitment Rules') }}</div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="vf-lbl">{{ __('Brand distribution') }}</div>
                    <div class="flex flex-wrap gap-1.5">
                        <a href="{{ $filterUrl(['brand' => null]) }}" class="ychip {{ $brandFilter === 0 ? 'on' : '' }}">
                            {{ __('All') }} <span class="cnt">{{ number_format((int) $stats['fitments']) }}</span>
                        </a>
                        @foreach($brands as $brand)
                            @if(($brandFitmentCounts[$brand->id] ?? 0) > 0)
                                <a href="{{ $filterUrl(['brand' => $brand->id]) }}" class="ychip {{ $brandFilter === (int) $brand->id ? 'on' : '' }}">
                                    {{ $brand->name }} <span class="cnt">{{ number_format((int) $brandFitmentCounts[$brand->id]) }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ═════════════ Vehicle data (brand → family → variant) ═════════════ --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white bento-shadow dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Vehicle Data') }}</h2>
                    <p class="mt-0.5 text-[11.5px] text-slate-500 dark:text-slate-400">{{ __('Organize compatibility as brand, model family, and variant.') }}</p>
                </div>
                <span class="vf-pill good">{{ number_format((int) $stats['brands']) }} {{ __('brands') }} · {{ number_format((int) $stats['families']) }} {{ __('families') }} · {{ number_format((int) $stats['models']) }} {{ __('variants') }}</span>
            </div>

            <div class="grid gap-5 p-5 xl:grid-cols-[350px_minmax(0,1fr)]">
                <div class="space-y-4">
                    <form method="POST" action="{{ route('admin.vehicle-fitments.brands.store') }}" class="space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/60">
                        @csrf
                        <label class="vf-lbl" for="vf-new-brand">{{ __('Add Vehicle Brand') }}</label>
                        <input id="vf-new-brand" name="name" required maxlength="120" placeholder="{{ __('SSANGYONG / KGM') }}" class="vf-inp">
                        <button class="vf-btn primary w-full">{{ __('Create Brand') }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.vehicle-fitments.models.store') }}" enctype="multipart/form-data" class="space-y-3 rounded-2xl border border-amber-200/70 bg-amber-50/40 p-4 dark:border-amber-500/20 dark:bg-amber-500/5" data-variant-create data-family-map='@json($brandFamilyMap)'>
                        @csrf
                        <div>
                            <p class="vf-lbl">{{ __('Add Vehicle Variant') }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('An image, years, and petrol engine types are optional.') }}</p>
                        </div>
                        <select id="vf-model-brand" name="vehicle_brand_id" required class="vf-sel" data-family-brand>
                            <option value="">{{ __('Select brand') }}</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('vehicle_brand_id') == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <select name="vehicle_model_family_id" class="vf-sel" data-family-select>
                            <option value="">{{ __('Select existing family') }}</option>
                        </select>
                        <div class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-widest text-slate-400"><span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>{{ __('or') }}<span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span></div>
                        <div class="grid gap-2">
                            <input name="new_family_name_en" value="{{ old('new_family_name_en', old('new_family_name')) }}" maxlength="120" placeholder="{{ __('Family Name — English') }}" class="vf-inp" data-new-family>
                            <div class="grid grid-cols-2 gap-2">
                                <input name="new_family_name_ar" value="{{ old('new_family_name_ar') }}" maxlength="120" dir="rtl" placeholder="{{ __('Family Name — Arabic') }}" class="vf-inp">
                                <input name="new_family_name_ku" value="{{ old('new_family_name_ku') }}" maxlength="120" dir="rtl" placeholder="{{ __('Family Name — Kurdish') }}" class="vf-inp">
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <input name="name_en" value="{{ old('name_en', old('name')) }}" required maxlength="120" placeholder="{{ __('Variant Name — English') }}" class="vf-inp" aria-label="{{ __('Variant Name — English') }}">
                            <div class="grid grid-cols-2 gap-2">
                                <input name="name_ar" value="{{ old('name_ar') }}" maxlength="120" dir="rtl" placeholder="{{ __('Variant Name — Arabic') }}" class="vf-inp">
                                <input name="name_ku" value="{{ old('name_ku') }}" maxlength="120" dir="rtl" placeholder="{{ __('Variant Name — Kurdish') }}" class="vf-inp">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="production_start_year" type="number" min="1900" max="2100" value="{{ old('production_start_year') }}" placeholder="{{ __('Year From') }}" class="vf-inp">
                            <input name="production_end_year" type="number" min="1900" max="2100" value="{{ old('production_end_year') }}" placeholder="{{ __('Year To') }}" class="vf-inp">
                        </div>
                        <div class="vf-tagbox" data-engine-tags data-max-tags="20" data-max-message="{{ __('You can add up to 20 engine types.') }}">
                            <span class="contents" data-engine-tag-list></span>
                            <input id="vf-model-engine-types" name="engine_types_text" maxlength="2000" value="{{ old('engine_types_text') }}" placeholder="{{ __('Petrol engines, separated by comma') }}" autocomplete="off" data-engine-tag-input>
                        </div>
                        <div>
                            <label class="vf-lbl" for="vf-variant-image">{{ __('Vehicle Image') }} <span class="normal-case tracking-normal">({{ __('Optional') }})</span></label>
                            <input id="vf-variant-image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-xs text-slate-500 file:me-3 file:rounded-lg file:border-0 file:bg-[#04042a] file:px-3 file:py-2 file:font-bold file:text-amber-300 dark:text-slate-400">
                        </div>
                        <button class="vf-btn gold w-full">{{ __('Create Variant') }}</button>
                    </form>
                </div>

                <div class="max-h-[760px] space-y-4 overflow-y-auto pe-1">
                    @forelse($brands as $brand)
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950/40">
                            <div class="flex items-center justify-between gap-3 bg-[#04042a] px-4 py-3 text-white dark:bg-slate-950">
                                <span class="flex items-center gap-2.5 text-sm font-black tracking-wide"><i class="fas fa-car-side text-amber-300"></i>{{ $brand->name }}</span>
                                <span class="font-mono text-[10px] text-slate-300">{{ $brand->modelFamilies->count() }} {{ __('families') }}</span>
                            </div>
                            <div class="space-y-2.5 p-3">
                                @forelse($brand->modelFamilies as $family)
                                    @php
                                        $familyFitments = $family->variants->sum('fitments_count');
                                    @endphp
                                    <details class="group overflow-hidden rounded-xl border border-slate-200 bg-slate-50 open:bg-white dark:border-slate-700 dark:bg-slate-900/70 dark:open:bg-slate-900" @if($loop->first) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 transition hover:bg-white dark:hover:bg-slate-800/70">
                                            <span>
                                                <span class="block text-[12px] font-black uppercase tracking-[.12em] text-slate-900 dark:text-white">{{ $family->localizedName() }}</span>
                                                <span class="mt-1 block font-mono text-[10px] text-slate-500 dark:text-slate-400">{{ $family->variants->count() }} {{ __('variants') }} · {{ $familyFitments }} {{ __('fitment rules') }}</span>
                                            </span>
                                            <i class="fas fa-chevron-down text-[10px] text-amber-500 transition duration-200 group-open:rotate-180"></i>
                                        </summary>
                                        <div class="grid gap-3 border-t border-slate-200 p-3 dark:border-slate-700 md:grid-cols-2 2xl:grid-cols-3">
                                            <form method="POST" action="{{ route('admin.vehicle-fitments.families.update', $family) }}" class="grid gap-2 rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-3 dark:border-amber-500/30 dark:bg-amber-500/5 md:col-span-2 2xl:col-span-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                                                @csrf @method('PATCH')
                                                <label class="block"><span class="vf-lbl">{{ __('Family Name — English') }}</span><input name="name_en" value="{{ $family->name_en ?: $family->name }}" required maxlength="120" class="vf-inp"></label>
                                                <label class="block"><span class="vf-lbl">{{ __('Family Name — Arabic') }}</span><input name="name_ar" value="{{ $family->name_ar }}" maxlength="120" dir="rtl" class="vf-inp"></label>
                                                <label class="block"><span class="vf-lbl">{{ __('Family Name — Kurdish') }}</span><input name="name_ku" value="{{ $family->name_ku }}" maxlength="120" dir="rtl" class="vf-inp"></label>
                                                <button class="vf-btn primary sm">{{ __('Save Family') }}</button>
                                            </form>
                                            @forelse($family->variants as $model)
                                                <article class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-950" data-vf-editable>
                                                    <div data-vf-edit-view>
                                                        <div class="flex gap-3">
                                                            @if($model->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($model->image_path))
                                                                <img src="{{ asset('storage/'.ltrim($model->image_path, '/')) }}" alt="{{ $model->localizedName() }}" class="h-16 w-20 rounded-lg border border-slate-200 bg-slate-50 object-cover dark:border-slate-700 dark:bg-slate-900">
                                                            @else
                                                                <span class="grid h-16 w-20 shrink-0 place-items-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-900"><i class="fas fa-car-side text-xl"></i></span>
                                                            @endif
                                                            <div class="min-w-0 flex-1">
                                                                <h4 class="truncate text-sm font-extrabold text-slate-900 dark:text-white">{{ $model->localizedName() }}</h4>
                                                                <p class="mt-1 font-mono text-[10px] text-slate-500">{{ $model->production_start_year || $model->production_end_year ? (($model->production_start_year ?: '…').'–'.($model->production_end_year ?: __('Present'))) : __('Years not specified') }}</p>
                                                                <div class="mt-2 flex flex-wrap gap-1">
                                                                    @forelse($model->engineTypes as $engineType)
                                                                        <span class="vf-engine-chip">{{ $engineType->localizedName() }}</span>
                                                                    @empty
                                                                        <span class="text-[10px] text-slate-400">{{ __('Engine not specified') }}</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3 flex justify-end gap-1.5 border-t border-slate-100 pt-2.5 dark:border-slate-800">
                                                            <button type="button" class="vf-btn sm" data-vf-edit-toggle><i class="fas fa-pen text-[9px]"></i> {{ __('Edit') }}</button>
                                                            <form method="POST" action="{{ route('admin.vehicle-fitments.models.destroy', $model) }}" data-danger-confirm data-danger-title="{{ __('Delete Vehicle Variant') }}" data-danger-description="{{ __('Variants used by product fitments cannot be deleted.') }}">
                                                                @csrf @method('DELETE')
                                                                <button class="vf-btn danger sm"><i class="fas fa-trash text-[9px]"></i></button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.vehicle-fitments.models.update', $model) }}" enctype="multipart/form-data" class="space-y-2" data-vf-edit-panel hidden>
                                                        @csrf @method('PATCH')
                                                        <select name="vehicle_model_family_id" required class="vf-sel">
                                                            @foreach($brand->modelFamilies as $availableFamily)
                                                                <option value="{{ $availableFamily->id }}" @selected($availableFamily->id === $family->id)>{{ $availableFamily->localizedName() }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label class="block"><span class="vf-lbl">{{ __('Variant Name — English') }}</span><input name="name_en" value="{{ $model->name_en ?: $model->name }}" required maxlength="120" class="vf-inp"></label>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <label class="block"><span class="vf-lbl">{{ __('Variant Name — Arabic') }}</span><input name="name_ar" value="{{ $model->name_ar }}" maxlength="120" dir="rtl" class="vf-inp"></label>
                                                            <label class="block"><span class="vf-lbl">{{ __('Variant Name — Kurdish') }}</span><input name="name_ku" value="{{ $model->name_ku }}" maxlength="120" dir="rtl" class="vf-inp"></label>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <input name="production_start_year" type="number" min="1900" max="2100" value="{{ $model->production_start_year }}" placeholder="{{ __('From') }}" class="vf-inp">
                                                            <input name="production_end_year" type="number" min="1900" max="2100" value="{{ $model->production_end_year }}" placeholder="{{ __('To') }}" class="vf-inp">
                                                        </div>
                                                        <input name="engine_types_text" value="{{ $model->engineTypes->pluck('name')->implode(', ') }}" maxlength="2000" placeholder="{{ __('Petrol engines, comma separated') }}" class="vf-inp">
                                                        <input name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-[10px] text-slate-500">
                                                            @if($model->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($model->image_path))
                                                            <label class="flex items-center gap-2 text-[11px] font-semibold text-rose-600"><input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300">{{ __('Remove current image') }}</label>
                                                        @endif
                                                        <div class="flex justify-end gap-1.5">
                                                            <button class="vf-btn primary sm">{{ __('Save') }}</button>
                                                            <button type="button" class="vf-btn sm" data-vf-edit-cancel>{{ __('Cancel') }}</button>
                                                        </div>
                                                    </form>
                                                </article>
                                            @empty
                                                <p class="col-span-full rounded-lg border border-dashed border-slate-300 px-3 py-5 text-center text-xs text-slate-500">{{ __('No variants in this family.') }}</p>
                                            @endforelse
                                        </div>
                                    </details>
                                @empty
                                    <p class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-xs text-slate-500 dark:border-slate-700">{{ __('No model families yet.') }}</p>
                                @endforelse
                            </div>
                        </section>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700">{{ __('No vehicle brands yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ═════════════ Add fitment panel (collapsible) ═════════════ --}}
        <div id="vf-fitment-panel" @if(!$openFitmentPanel) hidden @endif
             class="bg-white dark:bg-slate-900 border border-amber-300/60 dark:border-amber-500/30 rounded-2xl bento-shadow overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-b from-amber-50/60 to-white dark:from-amber-500/5 dark:to-slate-900">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center dark:bg-amber-400 dark:text-[#04042a]">
                        <i class="fas fa-link text-xs"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Add Product Fitment') }}</h2>
                        <p class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Connect one product to multiple vehicles and save every fitment at once.') }}</p>
                    </div>
                </div>
                <button type="button" data-vf-close-fitment class="vf-btn sm" aria-label="{{ __('Close') }}">
                    <i class="fas fa-times text-[10px]"></i> {{ __('Close') }}
                </button>
            </div>

            @if($products->isEmpty())
                <div class="m-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                    {{ __('No active products are available for new fitments.') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('admin.vehicle-fitments.store') }}"
                class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.65fr)]"
                data-admin-vehicle-fitment
                data-model-map='@json($brandModelMap)'
                data-family-map='@json($brandFamilyMap)'
                data-engine-types='@json($allEngineTypes)'
                data-any-model-label="{{ __('Any model') }}"
                data-no-model-label="{{ __('No models for this brand yet') }}"
                data-any-engine-label="{{ __('Any engine') }}"
                data-any-year-label="{{ __('Any year') }}"
                data-vehicle-label="{{ __('vehicle') }}"
                data-vehicles-label="{{ __('vehicles') }}"
                data-max-fitments="50"
                data-product-search-url="{{ route('admin.vehicle-fitments.products.search') }}"
            >
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="vf-lbl">{{ __('Product') }}</label>
                        <div class="grid gap-2 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                            <input
                                type="search"
                                placeholder="{{ __('Filter by product name, SKU, or brand') }}"
                                class="vf-inp"
                                data-admin-product-filter
                            >
                            <select name="product_id" required class="vf-sel" data-admin-product-select>
                                <option value="">{{ __('Select product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id) data-search="{{ Str::lower(trim($product->name . ' ' . $product->sku . ' ' . $product->brand)) }}">
                                        {{ $product->name }} @if($product->sku) ({{ $product->sku }}) @endif @if($product->brand) - {{ $product->brand }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div data-fitment-rows>
                        @foreach($fitmentRows as $fitmentIndex => $fitmentRow)
                            @include('admin.vehicle-fitments.partials.fitment-row', [
                                'fitmentIndex' => $fitmentIndex,
                                'fitmentRow' => $fitmentRow,
                            ])
                        @endforeach
                    </div>

                    <template data-fitment-row-template>
                        @include('admin.vehicle-fitments.partials.fitment-row', [
                            'fitmentIndex' => '__INDEX__',
                            'fitmentRow' => [],
                        ])
                    </template>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <button type="button" class="vf-btn" data-add-fitment-row>
                            <i class="fas fa-plus text-[10px]"></i>
                            {{ __('Add Another Vehicle') }}
                        </button>
                        <span class="vf-mono-chip" data-fitment-count>{{ count($fitmentRows) }} {{ __('vehicles') }}</span>
                    </div>

                    <button class="vf-btn gold w-full sm:w-auto px-6">
                        <i class="fas fa-link text-[10px]"></i>
                        {{ __('Save All Fitments') }}
                    </button>
                </div>

                {{-- Live preview plate --}}
                <aside class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 lg:sticky lg:top-24 lg:self-start bento-shadow">
                    <div class="flex items-center justify-between px-4 py-3 text-white" style="background: linear-gradient(135deg, #04042a, #0a0d3f);">
                        <span class="font-mono text-[10px] font-extrabold uppercase tracking-[0.22em] text-amber-300">{{ __('Fitment Preview') }}</span>
                        <span class="text-[10px] font-extrabold text-emerald-300" data-admin-preview-count>{{ count($fitmentRows) }} {{ __('vehicles') }}</span>
                    </div>
                    <div class="p-4 space-y-3 bg-white dark:bg-slate-900">
                        <div class="flex gap-3">
                            <span class="w-[74px] shrink-0 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 pt-0.5">{{ __('Product') }}</span>
                            <span class="text-[13px] font-extrabold text-slate-900 dark:text-slate-100" data-admin-preview-product>{{ __('Select product') }}</span>
                        </div>
                        <div class="flex gap-3">
                            <span class="w-[74px] shrink-0 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 pt-0.5">{{ __('Vehicle') }}</span>
                            <span class="text-[13px] font-extrabold text-slate-900 dark:text-slate-100" data-admin-preview-vehicle>{{ __('Select brand') }} / {{ __('Any model') }}</span>
                        </div>
                        <div class="flex gap-3">
                            <span class="w-[74px] shrink-0 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 pt-0.5">{{ __('Years') }}</span>
                            <span class="text-[13px] font-extrabold font-mono text-slate-900 dark:text-slate-100" data-admin-preview-years>{{ __('Any year') }}</span>
                        </div>
                        <div class="flex gap-3">
                            <span class="w-[74px] shrink-0 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 pt-0.5">{{ __('Engine') }}</span>
                            <span class="text-[13px] font-extrabold text-slate-900 dark:text-slate-100" data-admin-preview-engine>{{ __('Any engine') }}</span>
                        </div>
                    </div>
                </aside>
            </form>
        </div>

        {{-- ═════════════ Fitment rules list ═════════════ --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl bento-shadow overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Fitment Rules') }}</h2>
                    <p class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ __('Year coverage is drawn as a timeline — narrow and catch-all rules read at a glance.') }}
                    </p>
                </div>
                <form method="GET" action="{{ route('admin.vehicle-fitments.index') }}" class="flex gap-2 w-full sm:w-auto">
                    @if($brandFilter > 0)
                        <input type="hidden" name="brand" value="{{ $brandFilter }}">
                    @endif
                    <div class="relative flex-1 sm:w-72">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                        </span>
                        <input name="search" value="{{ $search }}" placeholder="{{ __('Search product, SKU, brand, model, engine...') }}"
                               class="vf-inp !ps-10">
                    </div>
                    <button type="submit" class="vf-btn primary shrink-0">{{ __('Search') }}</button>
                    @if($search !== '' || $brandFilter > 0)
                        <a href="{{ route('admin.vehicle-fitments.index') }}" class="vf-btn shrink-0">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            @forelse($fitments as $fitment)
                @php
                    $fitmentProductImage = $fitment->product?->image ? asset('storage/' . ltrim((string) $fitment->product->image, '/')) : null;
                    $yearFrom = $fitment->year_from ? (int) $fitment->year_from : null;
                    $yearTo = $fitment->year_to ? (int) $fitment->year_to : null;
                    $isAllYears = $yearFrom === null && $yearTo === null;
                    $barFrom = max($trackStart, min($trackEnd, $yearFrom ?? $trackStart));
                    $barTo = max($barFrom, min($trackEnd, $yearTo ?? $trackEnd));
                    $barStartPct = round(($barFrom - $trackStart) / $trackSpan * 100, 1);
                    $barWidthPct = max(3, round(($barTo - $barFrom + 1) / $trackSpan * 100, 1));
                    if ($isAllYears) { $barStartPct = 0; $barWidthPct = 100; }
                    $yearLabel = $isAllYears
                        ? __('All years')
                        : (($yearFrom ?? '*') . '–' . ($yearTo ?? '*'));
                @endphp
                <div class="vf-row">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="vf-thumb">
                            @if($fitmentProductImage)
                                <img src="{{ $fitmentProductImage }}" alt="{{ $fitment->product?->name ?? __('Product') }}" loading="lazy">
                            @else
                                <i class="fas fa-image text-sm"></i>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-extrabold text-slate-900 dark:text-slate-100">{{ $fitment->product?->name ?? '-' }}</p>
                            <p class="truncate font-mono text-[10.5px] text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $fitment->product?->sku ?: __('No SKU') }}@if($fitment->product?->brand) · {{ $fitment->product->brand }}@endif
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <span class="vf-pill good">{{ $fitment->brand?->name ?? __('Any brand') }}</span>
                        @if($fitment->model)
                            <span class="vf-pill good">{{ $fitment->model->localizedName() }}</span>
                        @else
                            <span class="vf-pill warn">{{ __('Any model') }}</span>
                        @endif
                    </div>

                    <div class="vf-range">
                        <div class="years">
                            <span>{{ $trackStart }}</span>
                            <span class="mid {{ $isAllYears ? 'full' : '' }}">
                                {{ $yearLabel }} · {{ $fitment->engine ? \App\Support\VehicleLocalization::engine($fitment->engine) : __('Any engine') }}
                            </span>
                            <span>{{ $trackEnd }}</span>
                        </div>
                        <div class="vf-track">
                            <div class="vf-fill {{ $isAllYears ? 'full' : '' }}" style="inset-inline-start: {{ $barStartPct }}%; width: {{ $barWidthPct }}%;"></div>
                        </div>
                        @if($fitment->notes)
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate" title="{{ $fitment->notes }}">{{ Str::limit($fitment->notes, 80) }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.vehicle-fitments.destroy', $fitment) }}"
                          data-danger-confirm
                          data-danger-title="{{ __('Delete Fitment') }}"
                          data-danger-description="{{ __('This product compatibility row will be removed.') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="vf-btn danger sm" title="{{ __('Delete') }}">
                            <i class="fas fa-trash text-[10px]"></i>
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center text-slate-400 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                        <i class="fas fa-link"></i>
                    </div>
                    <p class="text-base font-bold text-slate-900 dark:text-white">{{ __('No fitments found.') }}</p>
                    <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1.5">{{ __('Create a product fitment or adjust your search.') }}</p>
                    @if($search !== '' || $brandFilter > 0)
                        <a href="{{ route('admin.vehicle-fitments.index') }}"
                           class="vf-btn primary mt-4 inline-flex">{{ __('Reset filters') }}</a>
                    @endif
                </div>
            @endforelse

            @if($fitments->hasPages())
                <div class="flex flex-wrap justify-between items-center gap-3 border-t border-slate-100 dark:border-slate-800 px-5 py-3.5">
                    <span class="text-[12px] text-slate-500 dark:text-slate-400">
                        {{ __('Showing :from–:to of :total fitments', [
                            'from'  => $fitments->firstItem() ?? 0,
                            'to'    => $fitments->lastItem() ?? 0,
                            'total' => $fitments->total(),
                        ]) }}
                    </span>
                    <div class="y-pagination">{{ $fitments->links() }}</div>
                </div>
            @endif
        </div>

        {{-- Floating add button --}}
        <button type="button" class="vf-fab" data-vf-open-fitment>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('Add Fitment') }}
        </button>

    </div>
    </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        // ── Multi-value engine input for model creation ──
        document.querySelectorAll('[data-engine-tags]').forEach((box) => {
            const input = box.querySelector('[data-engine-tag-input]');
            const list = box.querySelector('[data-engine-tag-list]');
            const form = box.closest('form');
            const maxTags = Number(box.dataset.maxTags || 20);
            const maxMessage = box.dataset.maxMessage || `You can add up to ${maxTags} engine types.`;

            if (!input || !list || !form) return;

            const tags = () => Array.from(list.querySelectorAll('[data-engine-tag]'));
            const normalized = (value) => value.trim().toLocaleLowerCase();

            const bindRemove = (tag) => {
                tag.querySelector('[data-engine-tag-remove]')?.addEventListener('click', () => {
                    tag.remove();
                    input.setCustomValidity('');
                    input.focus();
                });
            };

            const addTag = (value) => {
                const label = value.trim();
                if (!label) return true;

                if (tags().some((tag) => tag.dataset.engineTag === normalized(label))) return true;
                if (tags().length >= maxTags) {
                    input.setCustomValidity(maxMessage);
                    input.reportValidity();
                    return false;
                }

                input.setCustomValidity('');
                const tag = document.createElement('span');
                tag.className = 'vf-tag';
                tag.dataset.engineTag = normalized(label);

                const text = document.createElement('span');
                text.textContent = label;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.dataset.engineTagRemove = '';
                remove.setAttribute('aria-label', `Remove ${label}`);
                remove.textContent = '×';
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'engine_types[]';
                hidden.value = label;

                tag.append(text, remove, hidden);
                list.appendChild(tag);
                bindRemove(tag);
                return true;
            };

            const commitInput = () => {
                const values = input.value.split(/[,;\n]+/).map((value) => value.trim()).filter(Boolean);
                let accepted = true;
                values.forEach((value) => { if (!addTag(value)) accepted = false; });
                if (accepted) input.value = '';
                return accepted;
            };

            tags().forEach(bindRemove);
            box.addEventListener('click', (event) => {
                if (event.target === box) input.focus();
            });
            input.addEventListener('keydown', (event) => {
                if (['Enter', ',', ';'].includes(event.key)) {
                    event.preventDefault();
                    commitInput();
                }
            });
            input.addEventListener('input', () => {
                input.setCustomValidity('');
                if (/[,;\n]/.test(input.value)) commitInput();
            });
            input.addEventListener('blur', commitInput);
            form.addEventListener('submit', (event) => {
                if (!commitInput()) event.preventDefault();
                if (tags().some((tag) => /\bdiesel\b/i.test(tag.textContent || ''))) {
                    event.preventDefault();
                    input.setCustomValidity(@json(__('Diesel engines are not supported.')));
                    input.reportValidity();
                }
            });
        });

        document.querySelectorAll('[data-variant-create]').forEach((form) => {
            const brand = form.querySelector('[data-family-brand]');
            const family = form.querySelector('[data-family-select]');
            const newFamily = form.querySelector('[data-new-family]');
            const familyMap = JSON.parse(form.dataset.familyMap || '{}');
            if (!brand || !family || !newFamily) return;

            const renderFamilies = () => {
                const previous = family.value;
                const choices = familyMap[brand.value] || [];
                family.innerHTML = `<option value="">${@json(__('Select existing family'))}</option>`;
                choices.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    family.appendChild(option);
                });
                if (choices.some((item) => String(item.id) === String(previous))) family.value = previous;
                family.disabled = !brand.value || choices.length === 0;
            };

            family.addEventListener('change', () => {
                if (family.value) newFamily.value = '';
            });
            newFamily.addEventListener('input', () => {
                if (newFamily.value.trim()) family.value = '';
            });
            brand.addEventListener('change', renderFamilies);
            renderFamilies();
        });

        // ── Add-fitment panel toggle ──
        (() => {
            const panel = document.getElementById('vf-fitment-panel');
            if (!panel) return;
            document.querySelectorAll('[data-vf-open-fitment]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    panel.hidden = false;
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    panel.querySelector('[data-admin-product-filter]')?.focus({ preventScroll: true });
                });
            });
            document.querySelectorAll('[data-vf-close-fitment]').forEach((btn) => {
                btn.addEventListener('click', () => { panel.hidden = true; });
            });
        })();

        // ── Inline rename for brands and models ──
        (() => {
            document.querySelectorAll('[data-vf-edit-toggle]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const wrap = btn.closest('[data-vf-editable]');
                    const panel = wrap?.querySelector('[data-vf-edit-panel]');
                    const view = wrap?.querySelector('[data-vf-edit-view]');
                    if (!panel) return;
                    panel.hidden = false;
                    if (view) view.hidden = true;
                    const input = panel.querySelector('input[name="name"]');
                    input?.focus();
                    input?.select();
                });
            });
            document.querySelectorAll('[data-vf-edit-cancel]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const wrap = btn.closest('[data-vf-editable]');
                    const panel = wrap?.querySelector('[data-vf-edit-panel]');
                    const view = wrap?.querySelector('[data-vf-edit-view]');
                    if (panel) {
                        panel.hidden = true;
                        const input = panel.querySelector('input[name="name"]');
                        if (input) input.value = input.defaultValue;
                    }
                    if (view) view.hidden = false;
                });
            });
        })();

        // Legacy single-row initializer retained for markup compatibility.
        document.querySelectorAll('[data-admin-vehicle-fitment-legacy]').forEach((form) => {
            const productFilter = form.querySelector('[data-admin-product-filter]');
            const productSelect = form.querySelector('[data-admin-product-select]');
            const brandSelect = form.querySelector('[data-admin-vehicle-brand]');
            const modelSelect = form.querySelector('[data-admin-vehicle-model]');
            const yearFrom = form.querySelector('[data-admin-year-from]');
            const yearTo = form.querySelector('[data-admin-year-to]');
            const engineInput = form.querySelector('[data-admin-engine]');
            const engineOptions = form.querySelector('[data-admin-engine-options]');
            const previewProduct = form.querySelector('[data-admin-preview-product]');
            const previewVehicle = form.querySelector('[data-admin-preview-vehicle]');
            const previewYears = form.querySelector('[data-admin-preview-years]');
            const previewEngine = form.querySelector('[data-admin-preview-engine]');

            if (!brandSelect || !modelSelect) {
                return;
            }

            const modelMap = JSON.parse(form.dataset.modelMap || '{}');
            const familyMap = JSON.parse(form.dataset.familyMap || '{}');
            const anyModelLabel = form.dataset.anyModelLabel || 'Any model';
            const noModelLabel = form.dataset.noModelLabel || 'No models for this brand yet';
            const anyEngineLabel = form.dataset.anyEngineLabel || 'Any engine';
            const anyYearLabel = form.dataset.anyYearLabel || 'Any year';

            const selectedOptionLabel = (select, fallback) => {
                const option = select?.selectedOptions?.[0];
                if (!option || option.value === '') {
                    return fallback;
                }

                return option.textContent.trim();
            };

            const updatePreview = () => {
                const productLabel = selectedOptionLabel(productSelect, productSelect?.querySelector('option[value=""]')?.textContent.trim() || 'Select product');
                const brandLabel = selectedOptionLabel(brandSelect, brandSelect?.querySelector('option[value=""]')?.textContent.trim() || 'Select brand');
                const modelLabel = selectedOptionLabel(modelSelect, anyModelLabel);
                const from = yearFrom?.value?.trim() || '';
                const to = yearTo?.value?.trim() || '';
                const engine = engineInput?.value?.trim() || '';

                if (previewProduct) {
                    previewProduct.textContent = productLabel;
                }

                if (previewVehicle) {
                    previewVehicle.textContent = `${brandLabel} / ${modelLabel}`;
                }

                if (previewYears) {
                    previewYears.textContent = from || to ? `${from || '*'} - ${to || '*'}` : anyYearLabel;
                }

                if (previewEngine) {
                    previewEngine.textContent = engine || anyEngineLabel;
                }
            };

            // Hybrid product filter: client-side hide for the initial 100
            // rendered options (instant feedback) + debounced AJAX fetch
            // against the search endpoint so operators can find any product
            // in the catalog without the legacy limit(500) cap.
            const productSearchUrl = form.dataset.productSearchUrl || '';
            let productSearchTimer = null;
            let productSearchAbort = null;

            const filterRenderedOptions = (needle) => {
                Array.from(productSelect.options).forEach((option) => {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }
                    option.hidden = needle !== ''
                        && !(option.dataset.search || option.textContent).toLowerCase().includes(needle);
                });
            };

            const mergeAjaxResults = (results) => {
                if (!Array.isArray(results)) return;
                const previousValue = productSelect.value;
                const existingIds = new Set(
                    Array.from(productSelect.options).map((o) => o.value)
                );
                results.forEach((row) => {
                    if (existingIds.has(String(row.id))) return;
                    const labelParts = [row.name];
                    if (row.sku) labelParts.push(`(${row.sku})`);
                    if (row.brand) labelParts.push(`- ${row.brand}`);
                    const label = labelParts.join(' ');
                    const searchAttr = (row.name + ' ' + row.sku + ' ' + row.brand).toLowerCase();
                    const opt = document.createElement('option');
                    opt.value = String(row.id);
                    opt.dataset.search = searchAttr;
                    opt.textContent = label;
                    productSelect.appendChild(opt);
                });
                if (previousValue) productSelect.value = previousValue;
            };

            const filterProducts = () => {
                if (!productFilter || !productSelect) return;
                const needle = productFilter.value.trim().toLowerCase();

                // Always run the local filter first so the user sees instant feedback.
                filterRenderedOptions(needle);

                if (!productSearchUrl) return;

                if (productSearchTimer) clearTimeout(productSearchTimer);
                if (productSearchAbort) productSearchAbort.abort();

                productSearchTimer = setTimeout(() => {
                    const trimmed = productFilter.value.trim();
                    if (trimmed === '') return;

                    productSearchAbort = new AbortController();
                    fetch(`${productSearchUrl}?q=${encodeURIComponent(trimmed)}&per_page=30`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        signal: productSearchAbort.signal,
                    })
                        .then((res) => res.ok ? res.json() : null)
                        .then((data) => {
                            if (!data) return;
                            mergeAjaxResults(data.results || []);
                            filterRenderedOptions(needle);
                        })
                        .catch(() => { /* aborted or network — ignore */ });
                }, 250);
            };

            const setModelOptions = () => {
                const brandId = brandSelect.value;
                const models = brandId ? (modelMap[brandId] || []) : [];
                const previousValue = modelSelect.value;
                modelSelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = models.length > 0 || brandId === '' ? anyModelLabel : noModelLabel;
                modelSelect.appendChild(placeholder);

                models.forEach((model) => {
                    const option = document.createElement('option');
                    option.value = model.id;
                    option.textContent = model.name;
                    option.dataset.engines = JSON.stringify(model.engines || []);
                    option.dataset.yearFrom = model.year_from || '';
                    option.dataset.yearTo = model.year_to || '';
                    modelSelect.appendChild(option);
                });

                if (models.some((model) => String(model.id) === String(previousValue))) {
                    modelSelect.value = previousValue;
                }

                modelSelect.disabled = brandId !== '' && models.length === 0;
                updateEngineOptions();
                updateModelYearHints();
                updatePreview();
            };

            const updateEngineOptions = () => {
                if (!engineOptions) return;
                const selected = modelSelect.selectedOptions?.[0];
                const modelEngines = selected?.dataset.engines ? JSON.parse(selected.dataset.engines) : [];
                const choices = modelEngines.length > 0 ? modelEngines : allEngineTypes;
                engineOptions.innerHTML = '';
                choices.forEach((engine) => {
                    const option = document.createElement('option');
                    option.value = typeof engine === 'object' ? engine.value : engine;
                    option.label = typeof engine === 'object' ? engine.label : engine;
                    engineOptions.appendChild(option);
                });
            };

            const updateModelYearHints = () => {
                const selected = modelSelect.selectedOptions?.[0];
                const modelYearFrom = selected?.dataset.yearFrom || '';
                const modelYearTo = selected?.dataset.yearTo || '';
                if (yearFrom) yearFrom.placeholder = modelYearFrom || @json(__('Any'));
                if (yearTo) yearTo.placeholder = modelYearTo || @json(__('Any'));
            };

            productFilter?.addEventListener('input', filterProducts);
            productSelect?.addEventListener('change', updatePreview);
            brandSelect.addEventListener('change', setModelOptions);
            modelSelect.addEventListener('change', () => {
                updateEngineOptions();
                updateModelYearHints();
                updatePreview();
            });
            yearFrom?.addEventListener('input', updatePreview);
            yearTo?.addEventListener('input', updatePreview);
            engineInput?.addEventListener('input', updatePreview);
            filterProducts();
            setModelOptions();
            updatePreview();
        });

        // ── Batch fitment form: one product + multiple independent vehicle rows ──
        document.querySelectorAll('[data-admin-vehicle-fitment]').forEach((form) => {
            const productFilter = form.querySelector('[data-admin-product-filter]');
            const productSelect = form.querySelector('[data-admin-product-select]');
            const rowsContainer = form.querySelector('[data-fitment-rows]');
            const rowTemplate = form.querySelector('[data-fitment-row-template]');
            const addRowButton = form.querySelector('[data-add-fitment-row]');
            const countLabels = form.querySelectorAll('[data-fitment-count], [data-admin-preview-count]');
            const previewProduct = form.querySelector('[data-admin-preview-product]');
            const previewVehicle = form.querySelector('[data-admin-preview-vehicle]');
            const previewYears = form.querySelector('[data-admin-preview-years]');
            const previewEngine = form.querySelector('[data-admin-preview-engine]');

            if (!productSelect || !rowsContainer || !rowTemplate) return;

            const modelMap = JSON.parse(form.dataset.modelMap || '{}');
            const allEngineTypes = JSON.parse(form.dataset.engineTypes || '[]');
            const anyModelLabel = form.dataset.anyModelLabel || 'Any model';
            const noModelLabel = form.dataset.noModelLabel || 'No models for this brand yet';
            const anyEngineLabel = form.dataset.anyEngineLabel || 'Any engine';
            const anyYearLabel = form.dataset.anyYearLabel || 'Any year';
            const vehicleLabel = form.dataset.vehicleLabel || 'vehicle';
            const vehiclesLabel = form.dataset.vehiclesLabel || 'vehicles';
            const maxFitments = Number(form.dataset.maxFitments || 50);
            let activeRow = null;
            let nextRowIndex = Math.max(
                0,
                ...Array.from(rowsContainer.querySelectorAll('[data-fitment-row]'))
                    .map((row) => Number(row.dataset.fitmentIndex))
                    .filter(Number.isFinite)
            ) + 1;

            const rows = () => Array.from(rowsContainer.querySelectorAll('[data-fitment-row]'));
            const selectedOptionLabel = (select, fallback) => {
                const option = select?.selectedOptions?.[0];
                return !option || option.value === '' ? fallback : option.textContent.trim();
            };

            const refreshRowState = () => {
                const currentRows = rows();
                const count = currentRows.length;
                const countText = `${count} ${count === 1 ? vehicleLabel : vehiclesLabel}`;
                countLabels.forEach((label) => { label.textContent = countText; });
                currentRows.forEach((row, index) => {
                    const number = row.querySelector('[data-fitment-row-number]');
                    const remove = row.querySelector('[data-remove-fitment-row]');
                    if (number) number.textContent = String(index + 1);
                    if (remove) remove.hidden = count === 1;
                });
                if (addRowButton) addRowButton.disabled = count >= maxFitments;
            };

            const updatePreview = (row = activeRow || rows()[0]) => {
                if (!row) return;
                activeRow = row;
                const brandSelect = row.querySelector('[data-admin-vehicle-brand]');
                const familySelect = row.querySelector('[data-admin-vehicle-family]');
                const modelSelect = row.querySelector('[data-admin-vehicle-model]');
                const yearFrom = row.querySelector('[data-admin-year-from]');
                const yearTo = row.querySelector('[data-admin-year-to]');
                const engineInput = row.querySelector('[data-admin-engine]');
                const productFallback = productSelect.querySelector('option[value=""]')?.textContent.trim() || 'Select product';
                const brandFallback = brandSelect?.querySelector('option[value=""]')?.textContent.trim() || 'Select brand';
                const productLabel = selectedOptionLabel(productSelect, productFallback);
                const brandLabel = selectedOptionLabel(brandSelect, brandFallback);
                const modelLabel = selectedOptionLabel(modelSelect, anyModelLabel);
                const from = yearFrom?.value?.trim() || '';
                const to = yearTo?.value?.trim() || '';
                const engine = selectedOptionLabel(engineInput, anyEngineLabel);

                if (previewProduct) previewProduct.textContent = productLabel;
                const familyLabel = selectedOptionLabel(familySelect, '');
                if (previewVehicle) previewVehicle.textContent = [brandLabel, familyLabel, modelLabel].filter(Boolean).join(' / ');
                if (previewYears) previewYears.textContent = from || to ? `${from || '*'} - ${to || '*'}` : anyYearLabel;
                if (previewEngine) previewEngine.textContent = engine || anyEngineLabel;
            };

            const configureRow = (row) => {
                if (row.dataset.fitmentReady === 'true') return;
                row.dataset.fitmentReady = 'true';

                const brandSelect = row.querySelector('[data-admin-vehicle-brand]');
                const familySelect = row.querySelector('[data-admin-vehicle-family]');
                const modelSelect = row.querySelector('[data-admin-vehicle-model]');
                const yearFrom = row.querySelector('[data-admin-year-from]');
                const yearTo = row.querySelector('[data-admin-year-to]');
                const engineInput = row.querySelector('[data-admin-engine]');
                const removeButton = row.querySelector('[data-remove-fitment-row]');
                if (!brandSelect || !familySelect || !modelSelect) return;

                const updateEngineOptions = () => {
                    if (!engineInput) return;
                    const selected = modelSelect.selectedOptions?.[0];
                    const modelEngines = selected?.dataset.engines ? JSON.parse(selected.dataset.engines) : [];
                    const previousValue = engineInput.value;
                    engineInput.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = @json(__('Any configured petrol engine'));
                    engineInput.appendChild(placeholder);
                    modelEngines.forEach((engine) => {
                        const option = document.createElement('option');
                        option.value = typeof engine === 'object' ? engine.value : engine;
                        option.textContent = typeof engine === 'object' ? engine.label : engine;
                        engineInput.appendChild(option);
                    });
                    if (modelEngines.some((engine) => String(typeof engine === 'object' ? engine.value : engine) === String(previousValue))) {
                        engineInput.value = previousValue;
                    }
                    engineInput.disabled = !modelSelect.value;
                };

                const updateModelYearHints = () => {
                    const selected = modelSelect.selectedOptions?.[0];
                    if (yearFrom) yearFrom.placeholder = selected?.dataset.yearFrom || @json(__('Any'));
                    if (yearTo) yearTo.placeholder = selected?.dataset.yearTo || @json(__('Any'));
                };

                const setModelOptions = () => {
                    const brandId = brandSelect.value;
                    const familyId = familySelect.value;
                    const models = brandId && familyId
                        ? (modelMap[brandId] || []).filter((model) => String(model.family_id) === String(familyId))
                        : [];
                    const previousValue = modelSelect.value;
                    modelSelect.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = models.length > 0 ? @json(__('Select variant')) : noModelLabel;
                    modelSelect.appendChild(placeholder);

                    models.forEach((model) => {
                        const option = document.createElement('option');
                        option.value = model.id;
                        option.textContent = model.name;
                        option.dataset.engines = JSON.stringify(model.engines || []);
                        option.dataset.yearFrom = model.year_from || '';
                        option.dataset.yearTo = model.year_to || '';
                        modelSelect.appendChild(option);
                    });

                    if (models.some((model) => String(model.id) === String(previousValue))) {
                        modelSelect.value = previousValue;
                    }
                    modelSelect.disabled = brandId !== '' && models.length === 0;
                    updateEngineOptions();
                    updateModelYearHints();
                    updatePreview(row);
                };

                const setFamilyOptions = () => {
                    const brandId = brandSelect.value;
                    const families = brandId ? (familyMap[brandId] || []) : [];
                    const previousValue = familySelect.value;
                    familySelect.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = @json(__('Select family'));
                    familySelect.appendChild(placeholder);
                    families.forEach((family) => {
                        const option = document.createElement('option');
                        option.value = family.id;
                        option.textContent = family.name;
                        familySelect.appendChild(option);
                    });
                    if (families.some((family) => String(family.id) === String(previousValue))) {
                        familySelect.value = previousValue;
                    }
                    familySelect.disabled = !brandId || families.length === 0;
                    setModelOptions();
                };

                const activate = () => updatePreview(row);
                brandSelect.addEventListener('change', setFamilyOptions);
                familySelect.addEventListener('change', setModelOptions);
                modelSelect.addEventListener('change', () => {
                    updateEngineOptions();
                    updateModelYearHints();
                    activate();
                });
                yearFrom?.addEventListener('input', activate);
                yearTo?.addEventListener('input', activate);
                engineInput?.addEventListener('input', activate);
                row.addEventListener('focusin', activate);
                removeButton?.addEventListener('click', () => {
                    if (rows().length <= 1) return;
                    const wasActive = activeRow === row;
                    row.remove();
                    if (wasActive) activeRow = rows()[0] || null;
                    refreshRowState();
                    updatePreview();
                });

                setFamilyOptions();
            };

            addRowButton?.addEventListener('click', () => {
                if (rows().length >= maxFitments) return;
                const rowIndex = nextRowIndex++;
                rowsContainer.insertAdjacentHTML(
                    'beforeend',
                    rowTemplate.innerHTML.replaceAll('__INDEX__', String(rowIndex))
                );
                const newRow = rowsContainer.querySelector(`[data-fitment-index="${rowIndex}"]`);
                if (!newRow) return;
                configureRow(newRow);
                activeRow = newRow;
                refreshRowState();
                updatePreview(newRow);
                newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                newRow.querySelector('[data-admin-vehicle-brand]')?.focus({ preventScroll: true });
            });

            // Product search remains shared because the product is selected once
            // and applied to every vehicle row in this batch.
            const productSearchUrl = form.dataset.productSearchUrl || '';
            let productSearchTimer = null;
            let productSearchAbort = null;

            const filterRenderedOptions = (needle) => {
                Array.from(productSelect.options).forEach((option) => {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }
                    option.hidden = needle !== ''
                        && !(option.dataset.search || option.textContent).toLowerCase().includes(needle);
                });
            };

            const mergeAjaxResults = (results) => {
                if (!Array.isArray(results)) return;
                const previousValue = productSelect.value;
                const existingIds = new Set(Array.from(productSelect.options).map((option) => option.value));
                results.forEach((item) => {
                    if (existingIds.has(String(item.id))) return;
                    const labelParts = [item.name];
                    if (item.sku) labelParts.push(`(${item.sku})`);
                    if (item.brand) labelParts.push(`- ${item.brand}`);
                    const option = document.createElement('option');
                    option.value = String(item.id);
                    option.dataset.search = `${item.name} ${item.sku} ${item.brand}`.toLowerCase();
                    option.textContent = labelParts.join(' ');
                    productSelect.appendChild(option);
                });
                if (previousValue) productSelect.value = previousValue;
            };

            const filterProducts = () => {
                if (!productFilter) return;
                const needle = productFilter.value.trim().toLowerCase();
                filterRenderedOptions(needle);
                if (!productSearchUrl) return;
                if (productSearchTimer) clearTimeout(productSearchTimer);
                if (productSearchAbort) productSearchAbort.abort();

                productSearchTimer = setTimeout(() => {
                    const query = productFilter.value.trim();
                    if (query === '') return;
                    productSearchAbort = new AbortController();
                    fetch(`${productSearchUrl}?q=${encodeURIComponent(query)}&per_page=30`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        signal: productSearchAbort.signal,
                    })
                        .then((response) => response.ok ? response.json() : null)
                        .then((data) => {
                            if (!data) return;
                            mergeAjaxResults(data.results || []);
                            filterRenderedOptions(needle);
                        })
                        .catch(() => { /* aborted or network unavailable */ });
                }, 250);
            };

            productFilter?.addEventListener('input', filterProducts);
            productSelect.addEventListener('change', () => updatePreview());
            rows().forEach(configureRow);
            refreshRowState();
            filterProducts();
            updatePreview();
        });
    </script>
</x-app-layout>
