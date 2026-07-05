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
                    ->map(fn ($model) => ['id' => (int) $model->id, 'name' => (string) $model->name])
                    ->values()
                    ->all(),
            ])
            ->all();

        $openFitmentPanel = $errors->any() || old('product_id') !== null;
    @endphp

    <style>
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
        .vf-model button { color: #b91c1c; font-weight: 800; line-height: 1; }
        .dark .vf-model button { color: #fca5a5; }

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

        {{-- ═════════════ Vehicle data (brands + models) ═════════════ --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl bento-shadow overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Vehicle Data') }}</h2>
                    <p class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Manage the brands and models used in fitment rules.') }}</p>
                </div>
                <span class="vf-pill good">{{ number_format((int) $stats['brands']) }} {{ __('brands') }} · {{ number_format((int) $stats['models']) }} {{ __('models') }}</span>
            </div>

            <div class="grid gap-5 p-5 lg:grid-cols-[280px_minmax(0,1fr)]">
                {{-- Add forms --}}
                <div class="space-y-4">
                    <form method="POST" action="{{ route('admin.vehicle-fitments.brands.store') }}" class="space-y-2">
                        @csrf
                        <label class="vf-lbl" for="vf-new-brand">{{ __('Add Vehicle Brand') }}</label>
                        <input id="vf-new-brand" name="name" required maxlength="120" placeholder="{{ __('Toyota') }}" class="vf-inp">
                        <button class="vf-btn primary w-full">{{ __('Create Brand') }}</button>
                    </form>

                    <div class="border-t border-dashed border-slate-200 dark:border-slate-700"></div>

                    <form method="POST" action="{{ route('admin.vehicle-fitments.models.store') }}" class="space-y-2">
                        @csrf
                        <label class="vf-lbl" for="vf-model-brand">{{ __('Add Vehicle Model') }}</label>
                        <select id="vf-model-brand" name="vehicle_brand_id" required class="vf-sel">
                            <option value="">{{ __('Select brand') }}</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <input name="name" required maxlength="120" placeholder="{{ __('Corolla') }}" class="vf-inp" aria-label="{{ __('Model Name') }}">
                        <button class="vf-btn primary w-full">{{ __('Create Model') }}</button>
                    </form>
                </div>

                {{-- Brand / model tree --}}
                <div class="max-h-80 overflow-y-auto pe-1 space-y-2.5">
                    @forelse($brands as $brand)
                        <div class="vf-brand">
                            <div class="bh">
                                <span class="flex items-center gap-2.5 text-[12.5px] font-extrabold text-slate-900 dark:text-slate-100">
                                    <span class="w-7 h-7 rounded-lg bg-[#04042a] text-amber-300 grid place-items-center shrink-0 dark:bg-amber-400 dark:text-[#04042a]">
                                        <i class="fas fa-car-side text-[11px]"></i>
                                    </span>
                                    {{ $brand->name }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <span class="vf-mono-chip">{{ $brand->models->count() }}</span>
                                    <form method="POST" action="{{ route('admin.vehicle-fitments.brands.destroy', $brand) }}"
                                          data-danger-confirm
                                          data-danger-title="{{ __('Delete Vehicle Brand') }}"
                                          data-danger-description="{{ __('This brand and all models under it will be removed from Vehicle Finder.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="vf-btn danger sm" title="{{ __('Delete :name', ['name' => $brand->name]) }}">
                                            <i class="fas fa-trash text-[10px]"></i>
                                        </button>
                                    </form>
                                </span>
                            </div>
                            @if($brand->models->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5 p-3">
                                    @foreach($brand->models as $model)
                                        <form method="POST" action="{{ route('admin.vehicle-fitments.models.destroy', $model) }}" class="vf-model"
                                              data-danger-confirm
                                              data-danger-title="{{ __('Delete Vehicle Model') }}"
                                              data-danger-description="{{ __('This model will be removed from Vehicle Finder.') }}">
                                            @csrf
                                            @method('DELETE')
                                            <span>{{ $model->name }}</span>
                                            <button type="submit" aria-label="{{ __('Delete :name', ['name' => $model->name]) }}">&times;</button>
                                        </form>
                                    @endforeach
                                </div>
                            @else
                                <p class="px-3 py-2.5 text-[11.5px] text-slate-400 dark:text-slate-500">{{ __('No models yet') }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 dark:border-slate-700 px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No vehicle brands yet.') }} {{ __('Add your first one on the left.') }}
                        </div>
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
                        <p class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Connect one product to a vehicle brand, model, year range, and engine.') }}</p>
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
                data-any-model-label="{{ __('Any model') }}"
                data-no-model-label="{{ __('No models for this brand yet') }}"
                data-any-engine-label="{{ __('Any engine') }}"
                data-any-year-label="{{ __('Any year') }}"
                data-product-search-url="{{ route('admin.vehicle-fitments.products.search') }}"
            >
                @csrf
                <div class="space-y-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
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

                        <div>
                            <label class="vf-lbl">{{ __('Vehicle Brand') }}</label>
                            <select name="vehicle_brand_id" required data-admin-vehicle-brand class="vf-sel">
                                <option value="">{{ __('Select brand') }}</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected(old('vehicle_brand_id') == $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="vf-lbl">{{ __('Vehicle Model') }}</label>
                            <select name="vehicle_model_id" data-admin-vehicle-model class="vf-sel">
                                <option value="">{{ __('Any model') }}</option>
                                @foreach($brands as $brand)
                                    @foreach($brand->models as $model)
                                        <option value="{{ $model->id }}">{{ $brand->name }} / {{ $model->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="vf-lbl">{{ __('Year From') }}</label>
                            <input name="year_from" type="number" min="1900" max="2100" value="{{ old('year_from') }}" placeholder="{{ __('Any') }}" class="vf-inp" data-admin-year-from>
                        </div>
                        <div>
                            <label class="vf-lbl">{{ __('Year To') }}</label>
                            <input name="year_to" type="number" min="1900" max="2100" value="{{ old('year_to') }}" placeholder="{{ __('Any') }}" class="vf-inp" data-admin-year-to>
                        </div>
                        <div>
                            <label class="vf-lbl">{{ __('Engine') }}</label>
                            <input name="engine" maxlength="120" value="{{ old('engine') }}" placeholder="{{ __('e.g. 1.8L, Hybrid, Diesel') }}" class="vf-inp" data-admin-engine>
                        </div>
                        <div>
                            <label class="vf-lbl">{{ __('Notes') }}</label>
                            <input name="notes" maxlength="255" value="{{ old('notes') }}" placeholder="{{ __('Optional fitment notes') }}" class="vf-inp">
                        </div>
                    </div>

                    <button class="vf-btn gold w-full sm:w-auto px-6">
                        <i class="fas fa-link text-[10px]"></i>
                        {{ __('Save Fitment') }}
                    </button>
                </div>

                {{-- Live preview plate --}}
                <aside class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 lg:sticky lg:top-24 lg:self-start bento-shadow">
                    <div class="flex items-center justify-between px-4 py-3 text-white" style="background: linear-gradient(135deg, #04042a, #0a0d3f);">
                        <span class="font-mono text-[10px] font-extrabold uppercase tracking-[0.22em] text-amber-300">{{ __('Fitment Preview') }}</span>
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true"></span>
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
                            <span class="vf-pill good">{{ $fitment->model->name }}</span>
                        @else
                            <span class="vf-pill warn">{{ __('Any model') }}</span>
                        @endif
                    </div>

                    <div class="vf-range">
                        <div class="years">
                            <span>{{ $trackStart }}</span>
                            <span class="mid {{ $isAllYears ? 'full' : '' }}">
                                {{ $yearLabel }} · {{ $fitment->engine ?: __('Any engine') }}
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

        // ── Fitment form: hybrid product search + dependent selects + live preview ──
        document.querySelectorAll('[data-admin-vehicle-fitment]').forEach((form) => {
            const productFilter = form.querySelector('[data-admin-product-filter]');
            const productSelect = form.querySelector('[data-admin-product-select]');
            const brandSelect = form.querySelector('[data-admin-vehicle-brand]');
            const modelSelect = form.querySelector('[data-admin-vehicle-model]');
            const yearFrom = form.querySelector('[data-admin-year-from]');
            const yearTo = form.querySelector('[data-admin-year-to]');
            const engineInput = form.querySelector('[data-admin-engine]');
            const previewProduct = form.querySelector('[data-admin-preview-product]');
            const previewVehicle = form.querySelector('[data-admin-preview-vehicle]');
            const previewYears = form.querySelector('[data-admin-preview-years]');
            const previewEngine = form.querySelector('[data-admin-preview-engine]');

            if (!brandSelect || !modelSelect) {
                return;
            }

            const modelMap = JSON.parse(form.dataset.modelMap || '{}');
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
                modelSelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = models.length > 0 || brandId === '' ? anyModelLabel : noModelLabel;
                modelSelect.appendChild(placeholder);

                models.forEach((model) => {
                    const option = document.createElement('option');
                    option.value = model.id;
                    option.textContent = model.name;
                    modelSelect.appendChild(option);
                });

                modelSelect.disabled = brandId !== '' && models.length === 0;
                updatePreview();
            };

            productFilter?.addEventListener('input', filterProducts);
            productSelect?.addEventListener('change', updatePreview);
            brandSelect.addEventListener('change', setModelOptions);
            modelSelect.addEventListener('change', updatePreview);
            yearFrom?.addEventListener('input', updatePreview);
            yearTo?.addEventListener('input', updatePreview);
            engineInput?.addEventListener('input', updatePreview);
            filterProducts();
            setModelOptions();
            updatePreview();
        });
    </script>
</x-app-layout>
