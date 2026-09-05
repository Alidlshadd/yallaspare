<x-app-layout>
    <x-slot name="header">{{ __('Vehicle Finder') }}</x-slot>

    {{-- Loaded only here: no other admin screen has a product picker. --}}
    @push('scripts')
        @vite('resources/js/admin-product-picker.js')
    @endpush

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
                        // What tells two same-named variants apart. Built by the
                        // model so the server-rendered row, this map and the
                        // storefront cannot drift into three different formats.
                        'label' => $model->shortSelectionLabel(),
                        'long_label' => $model->selectionLabel(),
                        'years' => $model->productionYears(),
                        'engine_labels' => $model->engineLabels(),
                        'search' => $model->selectionHaystack(),
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
            border: 1px solid var(--border); background: var(--surface); color: var(--text-secondary);
            text-decoration: none;
            transition: all .15s ease;
        }
        .ychip:hover { background: var(--surface-sunk); border-color: var(--border); color: #04041f; }
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

        /* Status pills */
        .vf-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 700; border: 1px solid;
        }
        .vf-pill::before { content: ""; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .vf-pill.good { background: #dcfce7; color: #15803d; border-color: #86efac; }
        .vf-pill.warn { background: #fef3c7; color: #b45309; border-color: #ffc9a3; }
        .dark .vf-pill.good { background: rgba(34,197,94,0.12); color: #4ade80; border-color: rgba(74,222,128,0.35); }
        .dark .vf-pill.warn { background: rgba(245,158,11,0.12); color: #ff8a3d; border-color: rgb(255 138 61 / 0.35); }

        .vf-mono-chip {
            display: inline-block; font-family: ui-monospace, monospace; font-size: 10.5px; font-weight: 600;
            background: var(--surface-sunk); border: 1px solid #e3e9f1;
            padding: 3px 8px; border-radius: 7px; color: var(--text-muted);
        }
        .dark .vf-mono-chip { background: var(--surface-sunk); border-color: var(--border); color: var(--text-muted); }

        /* Year-range timeline */
        .vf-range { display: flex; flex-direction: column; gap: 5px; min-width: 180px; }
        .vf-range .years {
            display: flex; justify-content: space-between; gap: 8px;
            font-family: ui-monospace, monospace; font-size: 10px; font-weight: 700; color: var(--text-muted);
        }
        .vf-range .years .mid { font-weight: 700; color: #b45309; }
        .vf-range .years .mid.full { color: #15803d; }
        .dark .vf-range .years .mid { color: #ff8a3d; }
        .dark .vf-range .years .mid.full { color: #4ade80; }
        .vf-track { position: relative; height: 6px; border-radius: 999px; background: var(--surface-sunk); border: 1px solid #e3e9f1; }
        .dark .vf-track { background: var(--surface-sunk); border-color: var(--border); }
        .vf-fill {
            position: absolute; top: -1px; bottom: -1px; border-radius: 999px;
            background: linear-gradient(90deg, #ff8a3d, #e65c00);
        }
        .vf-fill.full { background: linear-gradient(90deg, #34d399, #10b981); }

        /* Brand tree */
        .vf-brand { border: 1px solid #e3e9f1; border-radius: 12px; overflow: hidden; }
        .dark .vf-brand { border-color: var(--border); }
        .vf-brand .bh {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 9px 12px; background: var(--surface-sunk);
        }
        .dark .vf-brand .bh { background: var(--surface-sunk); }
        .vf-model {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 700; color: var(--text-muted);
            background: var(--surface); border: 1px solid var(--border); border-radius: 999px; padding: 4px 9px;
        }
        .dark .vf-model { background: var(--surface); border-color: var(--border); color: var(--text-muted); }
        .vf-model button[type="submit"] { color: #b91c1c; font-weight: 700; line-height: 1; }
        .dark .vf-model button[type="submit"] { color: #fca5a5; }
        .vf-model .edit { color: var(--text-muted); line-height: 1; transition: color .15s ease; }
        .vf-model .edit:hover { color: #e65c00; }
        .vf-edit-inline { display: inline-flex; align-items: center; gap: 4px; }
        .vf-edit-inline .vf-inp { height: 30px; font-size: 11.5px; padding: 0 9px; width: 140px; }
        .vf-edit-inline .vf-engine-edit { width: 230px; }
        .vf-edit-inline .vf-year-edit { width: 88px; }
        .vf-model-stack { display: inline-flex; flex-direction: column; align-items: flex-start; gap: 4px; }
        .vf-engine-list { display: flex; flex-wrap: wrap; gap: 4px; padding-inline: 4px; }
        @include('admin.vehicle-fitments.partials.controls-css')
        .vf-fitment-card {
            padding: 14px; border: 1px solid var(--border); border-radius: 14px;
            background: linear-gradient(180deg, var(--surface), var(--bg));
        }
        .vf-fitment-card + .vf-fitment-card { margin-top: 12px; }
        .vf-fitment-card-head {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px dashed var(--border);
        }
        .vf-fitment-number {
            display: grid; place-items: center; width: 24px; height: 24px; border-radius: 8px;
            background: #04041f; color: #ffb27a; font: 800 11px/1 ui-monospace, monospace;
        }
        .dark .vf-fitment-card { background: linear-gradient(180deg, var(--surface), var(--surface-sunk)); border-color: var(--border); }
        .dark .vf-fitment-card-head { border-color: var(--border); }
        .dark .vf-fitment-number { background: #ff8a3d; color: #04041f; }
        /* Fitment rule row */
        .vf-row {
            display: grid; grid-template-columns: minmax(210px, 1.2fr) minmax(150px, .8fr) minmax(220px, 1.2fr) auto;
            gap: 14px; align-items: center; padding: 13px 16px;
            border-bottom: 1px solid #eef1f6;
        }
        .dark .vf-row { border-bottom-color: var(--border); }
        .vf-row:last-child { border-bottom: none; }
        .vf-row:hover { background: #fafbfd; }
        .dark .vf-row:hover { background: rgba(30,41,59,0.4); }
        @media (max-width: 900px) { .vf-row { grid-template-columns: 1fr; gap: 8px; } }

        .vf-thumb {
            width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, var(--bg), var(--surface-sunk));
            border: 1px solid #e3e9f1; display: grid; place-items: center; color: var(--text-muted);
            overflow: hidden;
        }
        .vf-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .dark .vf-thumb { background: linear-gradient(135deg, var(--surface-sunk), var(--surface)); border-color: var(--border); }

        /* Floating add button */
        .vf-fab {
            position: sticky; bottom: 18px; z-index: 30;
            margin-inline-start: auto; width: fit-content;
            display: flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 999px;
            background: #04041f; color: #ffb27a; font-weight: 700; font-size: 13px;
            box-shadow: 0 10px 28px rgba(4,4,42,0.35); cursor: pointer;
            border: 1px solid rgba(252,211,77,0.25);
            transition: all .15s ease;
        }
        .vf-fab:hover { transform: translateY(-2px); }
        .dark .vf-fab { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }

        /* Pagination — same dialect as Products/Categories */
        .y-pagination nav { display: flex; }
        .y-pagination ul,
        .y-pagination .pagination { display: flex; flex-wrap: wrap; gap: 4px; list-style: none; margin: 0; padding: 0; }
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
        .y-pagination span[aria-current="page"] { background: #04041f; color: #ffb27a; border-color: #04041f; }
        .y-pagination .disabled span,
        .y-pagination span[aria-disabled="true"] { opacity: 0.45; cursor: not-allowed; }
        .dark .y-pagination a,
        .dark .y-pagination span { background: var(--surface); border-color: var(--border); color: var(--text-secondary); }
        .dark .y-pagination a:hover { background: var(--surface-sunk); color: var(--text); border-color: rgb(var(--text-muted-rgb) / 0.55); }
        .dark .y-pagination .active span,
        .dark .y-pagination span[aria-current="page"] { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-4">

        {{-- ─────────────── Flash + errors ─────────────── --}}
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ═════════════ Coverage board ═════════════ --}}
        <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)] items-stretch">

            {{-- Coverage ring --}}
            <div class="relative overflow-hidden rounded-2xl p-5 text-white flex flex-col gap-3"
                 style="background: linear-gradient(135deg, #04041f 0%, #070740 50%, #070740 100%);">
                <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
                <div class="absolute top-0 bottom-0 start-0 w-[3px]" style="background: linear-gradient(180deg, #ff8a3d 0%, #e65c00 100%);"></div>
                <div class="absolute -top-16 -end-16 h-52 w-52 rounded-full bg-accent/10 blur-[60px] pointer-events-none"></div>

                <div class="relative font-mono text-[10px] font-bold uppercase tracking-[0.28em] text-accent">{{ __('Catalog · Compatibility') }}</div>
                <h1 class="relative text-2xl font-bold leading-tight -mt-1">{{ __('Vehicle Finder') }}</h1>

                <div class="relative flex items-center gap-4">
                    <svg width="92" height="92" viewBox="0 0 92 92" class="shrink-0" role="img" aria-label="{{ __('Coverage: :pct%', ['pct' => $coveragePct]) }}">
                        <circle cx="46" cy="46" r="38" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="9"/>
                        <circle cx="46" cy="46" r="38" fill="none" stroke="#ff8a3d" stroke-width="9" stroke-linecap="round"
                                stroke-dasharray="{{ $ringDash }} {{ $ringCircumference }}" transform="rotate(-90 46 46)"/>
                        <text x="46" y="52" text-anchor="middle" fill="#fff" font-size="18" font-weight="900">{{ $coveragePct }}%</text>
                    </svg>
                    <div>
                        <div class="text-[26px] font-bold leading-none">
                            {{ number_format($coveredProducts) }} <span class="text-[13px] font-bold text-accent">/ {{ number_format($totalProducts) }}</span>
                        </div>
                        <p class="text-[11.5px] text-white/60 mt-1.5 leading-snug">
                            {{ __(':n products have vehicle matches.', ['n' => number_format($coveredProducts)]) }}<br>
                            {{ __(':n products are not covered yet.', ['n' => number_format($uncoveredProducts)]) }}
                        </p>
                    </div>
                </div>

                <button type="button" data-vf-open-fitment
                        class="relative inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl text-sm font-bold text-navy-deep transition hover:brightness-105 mt-auto"
                        style="background: linear-gradient(180deg, #ff8a3d, #e65c00);">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add Fitment') }}
                </button>
            </div>

            {{-- Mini stats + brand distribution --}}
            <div class="bg-white border border-slate-200/70 rounded-2xl p-5 bento-shadow flex flex-col justify-between gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl grid place-items-center bg-navy-deep text-accent shrink-0">
                            <i class="fas fa-car-side text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <div class="text-xl font-bold text-slate-900 leading-none">{{ number_format((int) $stats['brands']) }}</div>
                            <div class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mt-1">{{ __('Brands') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl grid place-items-center bg-emerald-50 text-emerald-600 shrink-0">
                            <i class="fas fa-layer-group text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <div class="text-xl font-bold text-slate-900 leading-none">{{ number_format((int) $stats['models']) }}</div>
                            <div class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mt-1">{{ __('Models') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl grid place-items-center bg-amber-50 text-amber-600 shrink-0">
                            <i class="fas fa-link text-sm" aria-hidden="true"></i>
                        </span>
                        <div>
                            <div class="text-xl font-bold text-slate-900 leading-none">{{ number_format((int) $stats['fitments']) }}</div>
                            <div class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500 mt-1">{{ __('Fitment Rules') }}</div>
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
        <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white bento-shadow">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">{{ __('Vehicle Data') }}</h2>
                    <p class="mt-0.5 text-[11.5px] text-slate-500">{{ __('Organize compatibility as brand, model family, and variant.') }}</p>
                </div>
                <span class="vf-pill good">{{ number_format((int) $stats['brands']) }} {{ __('brands') }} · {{ number_format((int) $stats['families']) }} {{ __('families') }} · {{ number_format((int) $stats['models']) }} {{ __('variants') }}</span>
            </div>

            <div class="grid gap-5 p-5 xl:grid-cols-[350px_minmax(0,1fr)]">
                <div class="space-y-4">
                    <form method="POST" action="{{ route('admin.vehicle-fitments.brands.store') }}" class="space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        @csrf
                        <label class="vf-lbl" for="vf-new-brand">{{ __('Add Vehicle Brand') }}</label>
                        <input id="vf-new-brand" name="name" required maxlength="120" placeholder="{{ __('SSANGYONG / KGM') }}" class="vf-inp">
                        <button class="vf-btn primary w-full">{{ __('Create Brand') }}</button>
                    </form>

                    <div class="space-y-3 rounded-2xl border border-amber-200/70 bg-amber-50/40 p-4 dark:border-amber-500/20 dark:bg-amber-500/5">
                        <div>
                            <p class="vf-lbl">{{ __('Add Vehicle Variant') }}</p>
                            <p class="text-[11px] text-slate-500">{{ __('Names, production years, engines and an image are set on the variant page.') }}</p>
                        </div>
                        <a href="{{ route('admin.vehicle-fitments.models.create') }}" class="vf-btn gold w-full">
                            <i class="fas fa-plus text-[10px]" aria-hidden="true"></i> {{ __('Create Variant') }}
                        </a>
                    </div>
                </div>

                <div class="max-h-[760px] space-y-4 overflow-y-auto pe-1">
                    @forelse($brands as $brand)
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <div class="flex items-center justify-between gap-3 bg-navy-deep px-4 py-3 text-white dark:bg-slate-950">
                                <span class="flex items-center gap-2.5 text-sm font-bold tracking-wide"><i class="fas fa-car-side text-accent" aria-hidden="true"></i>{{ $brand->name }}</span>
                                <span class="font-mono text-[10px] text-slate-300">{{ $brand->modelFamilies->count() }} {{ __('families') }}</span>
                            </div>
                            <div class="space-y-2.5 p-3">
                                @forelse($brand->modelFamilies as $family)
                                    @php
                                        $familyFitments = $family->variants->sum('fitments_count');
                                    @endphp
                                    <details class="group overflow-hidden rounded-xl border border-slate-200 bg-slate-50 open:bg-white dark:open:bg-slate-900" @if($loop->first) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 transition hover:bg-white dark:hover:bg-slate-800/70">
                                            <span>
                                                <span class="block text-[12px] font-bold uppercase tracking-[.12em] text-slate-900">{{ $family->localizedName() }}</span>
                                                <span class="mt-1 block font-mono text-[10px] text-slate-500">{{ $family->variants->count() }} {{ __('variants') }} · {{ $familyFitments }} {{ __('fitment rules') }}</span>
                                            </span>
                                            <i class="fas fa-chevron-down text-[10px] text-accent transition duration-200 group-open:rotate-180" aria-hidden="true"></i>
                                        </summary>
                                        <div class="grid gap-3 border-t border-slate-200 p-3 md:grid-cols-2 2xl:grid-cols-3">
                                            <form method="POST" action="{{ route('admin.vehicle-fitments.families.update', $family) }}" class="grid gap-2 rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-3 dark:bg-amber-500/5 md:col-span-2 2xl:col-span-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                                                @csrf @method('PATCH')
                                                <label class="block"><span class="vf-lbl">{{ __('Family Name — English') }}</span><input name="name_en" value="{{ $family->name_en ?: $family->name }}" required maxlength="120" class="vf-inp"></label>
                                                <label class="block"><span class="vf-lbl">{{ __('Family Name — Arabic') }}</span><input name="name_ar" value="{{ $family->name_ar }}" maxlength="120" dir="rtl" class="vf-inp"></label>
                                                <label class="block"><span class="vf-lbl">{{ __('Family Name — Kurdish') }}</span><input name="name_ku" value="{{ $family->name_ku }}" maxlength="120" dir="rtl" class="vf-inp"></label>
                                                <button class="vf-btn primary sm">{{ __('Save Family') }}</button>
                                            </form>
                                            @forelse($family->variants as $model)
                                                <article class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                                    <div>
                                                        <div class="flex gap-3">
                                                            @if($model->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($model->image_path))
                                                                <img src="{{ asset('storage/'.ltrim($model->image_path, '/')) }}" alt="{{ $model->localizedName() }}" class="h-16 w-20 rounded-lg border border-slate-200 bg-slate-50 object-cover">
                                                            @else
                                                                <span class="grid h-16 w-20 shrink-0 place-items-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-muted dark:border-slate-700"><i class="fas fa-car-side text-xl" aria-hidden="true"></i></span>
                                                            @endif
                                                            <div class="min-w-0 flex-1">
                                                                <h4 class="truncate text-sm font-bold text-slate-900">{{ $model->localizedName() }}</h4>
                                                                <p class="mt-1 font-mono text-[10px] text-slate-500">{{ $model->production_start_year || $model->production_end_year ? (($model->production_start_year ?: '…').'–'.($model->production_end_year ?: __('Present'))) : __('Years not specified') }}</p>
                                                                <div class="mt-2 flex flex-wrap gap-1">
                                                                    @forelse($model->engineTypes as $engineType)
                                                                        <span class="vf-engine-chip">{{ $engineType->localizedName() }}</span>
                                                                    @empty
                                                                        <span class="text-[10px] text-muted">{{ __('Engine not specified') }}</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3 flex justify-end gap-1.5 border-t border-slate-100 pt-2.5">
                                                            <a href="{{ route('admin.vehicle-fitments.models.edit', $model) }}" class="vf-btn sm"><i class="fas fa-pen text-[9px]" aria-hidden="true"></i> {{ __('Edit') }}</a>
                                                            <form method="POST" action="{{ route('admin.vehicle-fitments.models.destroy', $model) }}" data-danger-confirm data-danger-title="{{ __('Delete Vehicle Variant') }}" data-danger-description="{{ __('Variants used by product fitments cannot be deleted.') }}">
                                                                @csrf @method('DELETE')
                                                                <button class="vf-btn danger sm" aria-label="{{ __('Delete Vehicle Variant') }}"><i class="fas fa-trash text-[9px]" aria-hidden="true"></i></button>
                                                            </form>
                                                        </div>
                                                    </div>
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
                        <div class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">{{ __('No vehicle brands yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ═════════════ Add fitment panel (collapsible) ═════════════ --}}
        <div id="vf-fitment-panel" @if(!$openFitmentPanel) hidden @endif
             class="bg-white border border-accent/60 dark:border-accent/30 rounded-2xl bento-shadow overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 bg-gradient-to-b from-accent/60 to-white dark:from-accent/5 dark:to-slate-900">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-navy-deep text-accent grid place-items-center dark:bg-accent dark:text-navy-deep">
                        <i class="fas fa-link text-xs" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">{{ __('Add Product Fitment') }}</h2>
                        <p class="text-[11.5px] text-slate-500 mt-0.5">{{ __('Connect one product to multiple vehicles and save every fitment at once.') }}</p>
                    </div>
                </div>
                <button type="button" data-vf-close-fitment class="vf-btn sm" aria-label="{{ __('Close') }}">
                    <i class="fas fa-times text-[10px]" aria-hidden="true"></i> {{ __('Close') }}
                </button>
            </div>

            @if($products->isEmpty())
                <div class="m-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
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
                data-no-family-label="{{ __('No families for this brand yet') }}"
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
                        {{-- One control instead of a filter box beside a native
                             dropdown. Several products share a name, so the
                             results carry the photo and the numbers. The native
                             control below is still what submits, and still posts
                             a product id; only the current selection is rendered
                             into it, the rest arrive from the search endpoint. --}}
                        <div
                            data-product-picker
                            data-search-url="{{ route('admin.vehicle-fitments.products.search') }}"
                            data-placeholder-label="{{ __('Search and select a product') }}"
                            data-search-label="{{ __('Search products') }}"
                            data-searching-label="{{ __('Searching products...') }}"
                            data-empty-label="{{ __('No matching products found') }}"
                            data-empty-hint-label="{{ __('Try a product name, SKU or OEM number.') }}"
                            data-initial-label="{{ __('Start typing to find a product') }}"
                            data-error-label="{{ __('Products could not be loaded. Try again.') }}"
                            data-change-label="{{ __('Change') }}"
                            data-clear-label="{{ __('Clear') }}"
                            data-sku-label="{{ __('SKU') }}"
                            data-oem-label="{{ __('OEM') }}"
                            data-units-label="{{ __('units') }}"
                        >
                            <select aria-label="{{ __('Product') }}" name="product_id" required class="vf-sel" data-product-picker-select>
                                <option value="">{{ __('Select product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
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
                            <i class="fas fa-plus text-[10px]" aria-hidden="true"></i>
                            {{ __('Add Another Vehicle') }}
                        </button>
                        <span class="vf-mono-chip" data-fitment-count>{{ count($fitmentRows) }} {{ __('vehicles') }}</span>
                    </div>

                    <button class="vf-btn gold w-full sm:w-auto px-6">
                        <i class="fas fa-link text-[10px]" aria-hidden="true"></i>
                        {{ __('Save All Fitments') }}
                    </button>
                </div>

                {{-- Live preview plate --}}
                <aside class="rounded-2xl overflow-hidden border border-slate-200 lg:sticky lg:top-24 lg:self-start bento-shadow">
                    <div class="flex items-center justify-between px-4 py-3 text-white" style="background: linear-gradient(135deg, #04041f, #070740);">
                        <span class="font-mono text-[10px] font-bold uppercase tracking-[0.22em] text-accent">{{ __('Fitment Preview') }}</span>
                        <span class="text-[10px] font-bold text-emerald-300" data-admin-preview-count>{{ count($fitmentRows) }} {{ __('vehicles') }}</span>
                    </div>
                    <div class="p-4 space-y-3 bg-white">
                        <div class="flex gap-3">
                            <span class="w-[74px] shrink-0 text-[10px] font-bold uppercase tracking-widest text-muted pt-0.5">{{ __('Product') }}</span>
                            <span class="min-w-0 text-[13px] font-bold text-slate-900" data-admin-preview-product data-empty-label="{{ __('Select product') }}">{{ __('Select product') }}</span>
                        </div>
                        {{-- The same picture the picker shows, so the part being
                             worked on is visible without opening the dropdown. --}}
                        <div class="ys-picker-preview" data-admin-preview-product-media hidden></div>
                        <div class="flex gap-3">
                            <span class="w-[74px] shrink-0 text-[10px] font-bold uppercase tracking-widest text-muted pt-0.5">{{ __('Vehicle') }}</span>
                            <span class="text-[13px] font-bold text-slate-900" data-admin-preview-vehicle>{{ __('Select brand') }} / {{ __('Any model') }}</span>
                        </div>
                        <div class="flex gap-3">
                            <span class="w-[74px] shrink-0 text-[10px] font-bold uppercase tracking-widest text-muted pt-0.5">{{ __('Years') }}</span>
                            <span class="text-[13px] font-bold font-mono text-slate-900" data-admin-preview-years>{{ __('Any year') }}</span>
                        </div>
                        <div class="flex gap-3">
                            <span class="w-[74px] shrink-0 text-[10px] font-bold uppercase tracking-widest text-muted pt-0.5">{{ __('Engine') }}</span>
                            <span class="text-[13px] font-bold text-slate-900" data-admin-preview-engine>{{ __('Any engine') }}</span>
                        </div>
                    </div>
                </aside>
            </form>
        </div>

        {{-- ═════════════ Fitment rules list ═════════════ --}}
        <div class="bg-white border border-slate-200/70 rounded-2xl bento-shadow overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">{{ __('Fitment Rules') }}</h2>
                    <p class="text-[11.5px] text-slate-500 mt-0.5">
                        {{ __('Year coverage is drawn as a timeline — narrow and catch-all rules read at a glance.') }}
                    </p>
                </div>
                <form method="GET" action="{{ route('admin.vehicle-fitments.index') }}" class="flex gap-2 w-full sm:w-auto">
                    @if($brandFilter > 0)
                        <input type="hidden" name="brand" value="{{ $brandFilter }}">
                    @endif
                    <div class="relative flex-1 sm:w-72">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-muted">
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
                                <i class="fas fa-image text-sm" aria-hidden="true"></i>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-bold text-slate-900">{{ $fitment->product?->name ?? '-' }}</p>
                            <p class="truncate font-mono text-[10.5px] text-slate-500 mt-0.5">
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
                            <p class="text-[11px] text-slate-500 truncate" title="{{ $fitment->notes }}">{{ Str::limit($fitment->notes, 80) }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.vehicle-fitments.destroy', $fitment) }}"
                          data-danger-confirm
                          data-danger-title="{{ __('Delete Fitment') }}"
                          data-danger-description="{{ __('This product compatibility row will be removed.') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="vf-btn danger sm" title="{{ __('Delete') }}">
                            <i class="fas fa-trash text-[10px]" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center text-muted dark:text-slate-500">
                        <i class="fas fa-link" aria-hidden="true"></i>
                    </div>
                    <p class="text-base font-bold text-slate-900">{{ __('No fitments found.') }}</p>
                    <p class="text-[13px] text-slate-500 mt-1.5">{{ __('Create a product fitment or adjust your search.') }}</p>
                    @if($search !== '' || $brandFilter > 0)
                        <a href="{{ route('admin.vehicle-fitments.index') }}"
                           class="vf-btn primary mt-4 inline-flex">{{ __('Reset filters') }}</a>
                    @endif
                </div>
            @endforelse

            @if($fitments->hasPages())
                <div class="flex flex-wrap justify-between items-center gap-3 border-t border-slate-100 px-5 py-3.5">
                    <span class="text-[12px] text-slate-500">
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
        // ── Add-fitment panel toggle ──
        (() => {
            const panel = document.getElementById('vf-fitment-panel');
            if (!panel) return;
            document.querySelectorAll('[data-vf-open-fitment]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    panel.hidden = false;
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    panel.querySelector('.ys-picker-trigger')?.focus({ preventScroll: true });
                });
            });
            document.querySelectorAll('[data-vf-close-fitment]').forEach((btn) => {
                btn.addEventListener('click', () => { panel.hidden = true; });
            });
        })();

        // ── Batch fitment form: one product + multiple independent vehicle rows ──
        document.querySelectorAll('[data-admin-vehicle-fitment]').forEach((form) => {
            // The product picker owns this control now; the preview still
            // listens to it, because it is still what holds the chosen id.
            const productSelect = form.querySelector('[data-product-picker-select]');
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
            // Read here as well as in the row helpers below: each of these
            // blocks is its own scope, and setFamilyOptions reaches for this.
            const familyMap = JSON.parse(form.dataset.familyMap || '{}');
            const allEngineTypes = JSON.parse(form.dataset.engineTypes || '[]');
            const anyModelLabel = form.dataset.anyModelLabel || 'Any model';
            const noModelLabel = form.dataset.noModelLabel || 'No models for this brand yet';
            const noFamilyLabel = form.dataset.noFamilyLabel || 'No families for this brand yet';
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

                const engineHelp = row.querySelector('[data-admin-engine-help]');
                const summary = row.querySelector('[data-admin-variant-summary]');
                const summaryName = row.querySelector('[data-admin-variant-summary-name]');
                const summaryYears = row.querySelector('[data-admin-variant-summary-years]');
                const summaryEngines = row.querySelector('[data-admin-variant-summary-engines]');
                const variantFilter = row.querySelector('[data-admin-variant-filter]');

                // Confirms which of two same-named cars is attached, and lists
                // every engine the short option label had to summarise.
                const updateVariantSummary = () => {
                    if (!summary) return;
                    const selected = modelSelect.selectedOptions?.[0];

                    if (!selected || selected.value === '') {
                        summary.hidden = true;
                        return;
                    }

                    const engines = selected.dataset.engineLabels || '';
                    if (summaryName) summaryName.textContent = selected.dataset.name || selected.textContent.trim();
                    if (summaryYears) summaryYears.textContent = selected.dataset.years || '';
                    if (summaryEngines) {
                        summaryEngines.textContent = engines === ''
                            ? @json(__('No engines recorded for this variant'))
                            : @json(__('Available engines:')) + ' ' + engines;
                    }
                    summary.hidden = false;
                };

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
                    // A previous choice survives only if the new variant offers
                    // it too; otherwise the field falls back to the placeholder
                    // rather than carrying an engine this car never had.
                    if (modelEngines.some((engine) => String(typeof engine === 'object' ? engine.value : engine) === String(previousValue))) {
                        engineInput.value = previousValue;
                    }
                    engineInput.disabled = !modelSelect.value;

                    if (engineHelp) {
                        engineHelp.hidden = Boolean(modelSelect.value);
                    }
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
                        option.textContent = model.label || model.name;
                        option.dataset.engines = JSON.stringify(model.engines || []);
                        option.dataset.name = model.name || '';
                        option.dataset.years = model.years || '';
                        option.dataset.engineLabels = (model.engine_labels || []).join(', ');
                        option.dataset.search = model.search || '';
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
                    updateVariantSummary();
                    filterVariants();
                    updatePreview(row);
                };

                const setFamilyOptions = () => {
                    const brandId = brandSelect.value;
                    const families = brandId ? (familyMap[brandId] || []) : [];
                    const previousValue = familySelect.value;
                    familySelect.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    // A brand with nothing under it yet leaves this control
                    // disabled, so it has to say why rather than look broken.
                    placeholder.textContent = !brandId || families.length > 0
                        ? @json(__('Select family'))
                        : noFamilyLabel;
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

                // Same shape as the product filter above: hide what does not
                // match rather than pull in a select library for one field.
                const filterVariants = () => {
                    if (!variantFilter) return;
                    const needle = variantFilter.value.trim().toLowerCase();

                    Array.from(modelSelect.options).forEach((option) => {
                        if (option.value === '') {
                            option.hidden = false;
                            return;
                        }

                        const haystack = option.dataset.search || option.textContent.toLowerCase();
                        option.hidden = needle !== '' && !haystack.includes(needle);
                    });

                    // Never let the filter hide what is already chosen.
                    const selected = modelSelect.selectedOptions?.[0];
                    if (selected) selected.hidden = false;
                };

                const activate = () => updatePreview(row);
                brandSelect.addEventListener('change', setFamilyOptions);
                familySelect.addEventListener('change', setModelOptions);
                variantFilter?.addEventListener('input', filterVariants);
                modelSelect.addEventListener('change', () => {
                    updateEngineOptions();
                    updateModelYearHints();
                    updateVariantSummary();
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
                updateVariantSummary();
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

            productSelect.addEventListener('change', () => updatePreview());
            rows().forEach(configureRow);
            refreshRowState();
            updatePreview();
        });
    </script>
</x-app-layout>
