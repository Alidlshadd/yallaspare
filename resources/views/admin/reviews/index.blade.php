<x-app-layout>
    <x-slot name="header">{{ __('Customer Reviews') }}</x-slot>

    @php
        $totalReviews = (int) ($stats['total'] ?? 0);
        $averageRating = (float) ($stats['average'] ?? 0);
        $averageRounded = (int) round($averageRating);
        $fiveStar = (int) ($stats['five_star'] ?? 0);
        $lowRating = (int) ($stats['low_rating'] ?? 0);
        $maxRatingCount = max(1, (int) $ratingCounts->max());

        $flaggedCount = (int) ($stats['flagged'] ?? 0);
        $ratingQueryValue = $lowOnly ? 'low' : ((int) $rating > 0 ? (int) $rating : null);

        $ratingUrl = fn (int|string $value) => route('admin.reviews.index', array_filter([
            'search' => $search,
            'rating' => ($value === 'low' || (is_int($value) && $value > 0)) ? $value : null,
            'flagged' => $flaggedOnly ? 1 : null,
        ], fn ($param) => $param !== null && $param !== ''));

        $flaggedUrl = fn (bool $enabled) => route('admin.reviews.index', array_filter([
            'search' => $search,
            'rating' => $ratingQueryValue,
            'flagged' => $enabled ? 1 : null,
        ], fn ($param) => $param !== null && $param !== ''));

        $averageFillPct = round(min(5, max(0, $averageRating)) / 5 * 100, 1);
    @endphp

    <style>
        [hidden] { display: none !important; }
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
        .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }

        /* Chips — same dialect as Products/Categories/Vehicle Finder */
        .ychip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 11.5px; font-weight: 700; line-height: 1;
            border: 1px solid #e2e8f0; background: #fff; color: var(--text-secondary);
            text-decoration: none;
            transition: all .15s ease;
        }
        .ychip:hover { background: var(--surface-sunk); border-color: var(--border); color: #04041f; }
        .ychip .star { color: #e65c00; font-size: 12px; line-height: 1; }
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
        .ychip.on .star { color: #ffb27a; }
        .ychip.on .cnt { background: rgba(252,211,77,0.18); color: #ffb27a; }
        .dark .ychip { background: var(--surface-sunk); border-color: var(--border); color: var(--text-secondary); }
        .dark .ychip .cnt { background: rgba(255,255,255,0.06); color: var(--text-secondary); }
        .dark .ychip:hover { background: #334155; color: #fff; }
        .dark .ychip.on { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
        .dark .ychip.on .star { color: #04041f; }
        .dark .ychip.on .cnt { background: rgba(4,4,42,0.18); color: #04041f; }

        /* Buttons */
        .rv-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            height: 38px; padding: 0 16px; border-radius: 10px; border: 1px solid #e2e8f0;
            background: #fff; color: var(--text-secondary); font-size: 12px; font-weight: 700; cursor: pointer;
            text-decoration: none; transition: all .15s ease;
        }
        .rv-btn:hover { transform: translateY(-1px); }
        .rv-btn.primary { background: #04041f; color: #ffb27a; border-color: #04041f; }
        .rv-btn.primary:hover { background: #070740; }
        .rv-btn.danger { background: #fef2f2; color: #b91c1c; border-color: #fca5a5; }
        .rv-btn.danger:hover { background: #fee2e2; }
        .rv-btn.sm { height: 30px; padding: 0 11px; font-size: 11px; border-radius: 8px; }
        .dark .rv-btn { background: var(--surface-sunk); border-color: var(--border); color: var(--text-secondary); }
        .dark .rv-btn:hover { background: #334155; }
        .dark .rv-btn.primary { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
        .dark .rv-btn.primary:hover { background: #e65c00; }
        .dark .rv-btn.danger { background: rgba(239,68,68,0.10); color: #fca5a5; border-color: rgba(239,68,68,0.30); }

        /* Inputs */
        .rv-inp {
            width: 100%; height: 38px; padding: 0 12px; font-size: 13px;
            border: 1px solid #e2e8f0; border-radius: 10px;
            background: var(--surface-sunk); color: var(--text);
        }
        .rv-inp:focus {
            outline: none; border-color: #ff8a3d; background: #fff;
            box-shadow: 0 0 0 3px rgb(255 138 61 / 0.25);
        }
        .dark .rv-inp { background: var(--surface-sunk); border-color: var(--border); color: var(--text); }
        .dark .rv-inp:focus { background: var(--surface); }

        /* Rating distribution rows */
        .rv-dist {
            display: grid; grid-template-columns: 44px minmax(0,1fr) 52px;
            align-items: center; gap: 10px;
            padding: 6px 8px; border-radius: 10px; text-decoration: none;
            transition: background .15s ease;
        }
        .rv-dist:hover { background: var(--surface-sunk); }
        .dark .rv-dist:hover { background: var(--surface-sunk); }
        .rv-dist.on { background: #fef3c7; }
        .dark .rv-dist.on { background: rgb(255 138 61 / 0.12); }
        .rv-dist .lab {
            font-family: ui-monospace, monospace; font-size: 11px; font-weight: 700; color: var(--text-muted);
            display: flex; align-items: center; gap: 3px;
        }
        .rv-dist .lab .star { color: #e65c00; }
        .dark .rv-dist .lab { color: var(--text-muted); }
        .rv-dist .track { position: relative; height: 8px; border-radius: 999px; background: var(--surface-sunk); border: 1px solid #e3e9f1; overflow: hidden; }
        .dark .rv-dist .track { background: var(--surface-sunk); border-color: var(--border); }
        .rv-dist .fill { position: absolute; inset-block: 0; inset-inline-start: 0; border-radius: 999px; background: linear-gradient(90deg, #ff8a3d, #e65c00); }
        .rv-dist .fill.low { background: linear-gradient(90deg, #fb7185, #f43f5e); }
        .rv-dist .cnt { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-align: end; font-variant-numeric: tabular-nums; }
        .dark .rv-dist .cnt { color: var(--text-secondary); }

        /* Review cards */
        .rv-card {
            position: relative; overflow: hidden;
            background: #fff; border: 1px solid #e3e9f1; border-radius: 18px;
            padding: 16px;
            box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06);
            transition: all .2s ease;
            display: flex; flex-direction: column; gap: 10px;
        }
        .rv-card:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(7,7,64,0.10); border-color: #ffb27a; }
        .dark .rv-card { background: var(--surface); border-color: var(--border); }
        .dark .rv-card:hover { border-color: #ff8a3d; }
        .rv-card.low::before {
            content: ""; position: absolute; top: 0; bottom: 0; inset-inline-start: 0; width: 3px;
            background: linear-gradient(180deg, #fb7185, #f43f5e);
        }
        .rv-stars { font-size: 14px; letter-spacing: 2px; line-height: 1; }
        .rv-stars .f { color: #e65c00; }
        .rv-stars .e { color: #cbd5e1; }
        .dark .rv-stars .e { color: #475569; }

        /* Fractional average stars: amber overlay clipped to the exact average */
        .rv-avg-stars { position: relative; display: inline-block; font-size: 16px; letter-spacing: 2px; line-height: 1; }
        .rv-avg-stars .base { color: rgba(255,255,255,0.25); }
        .rv-avg-stars .over {
            position: absolute; top: 0; inset-inline-start: 0;
            overflow: hidden; white-space: nowrap; color: #ff8a3d;
        }

        /* Expandable comments */
        .rv-comment.clamp { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
        .rv-more {
            background: none; border: none; padding: 0; cursor: pointer;
            font-size: 11.5px; font-weight: 700; color: #b45309;
        }
        .rv-more:hover { text-decoration: underline; }
        .dark .rv-more { color: #ff8a3d; }

        /* Card entrance */
        .rv-card { animation: rv-rise .35s ease both; animation-delay: calc(var(--i, 0) * 35ms); }
        @keyframes rv-rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .rv-card { animation: none; } }
        .rv-thumb {
            width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid #e3e9f1; display: grid; place-items: center; color: var(--text-muted);
            overflow: hidden;
        }
        .rv-thumb img { width: 100%; height: 100%; object-fit: contain; }
        .dark .rv-thumb { background: linear-gradient(135deg, #1e293b, #0f172a); border-color: var(--border); }
        .rv-avatar {
            width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
            background: #04041f; color: #ffb27a;
            display: grid; place-items: center;
            font-size: 12px; font-weight: 700;
        }
        .dark .rv-avatar { background: #ff8a3d; color: #04041f; }
        .rv-mono {
            font-family: ui-monospace, monospace; font-size: 10.5px; font-weight: 600; color: var(--text-muted);
            background: var(--surface-sunk); border: 1px solid #e3e9f1; padding: 2px 7px; border-radius: 7px;
        }
        .dark .rv-mono { background: var(--surface-sunk); border-color: var(--border); color: var(--text-muted); }

        /* Pagination — same dialect as Products/Categories */
        .y-pagination nav { display: flex; }
        .y-pagination ul,
        .y-pagination .pagination { display: flex; flex-wrap: wrap; gap: 4px; list-style: none; margin: 0; padding: 0; }
        .y-pagination a,
        .y-pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 34px; padding: 0 10px;
            border-radius: 9px; background: #fff;
            border: 1px solid #e2e8f0; color: var(--text-secondary);
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
        .dark .y-pagination a:hover { background: var(--surface-sunk); color: #fff; border-color: #475569; }
        .dark .y-pagination .active span,
        .dark .y-pagination span[aria-current="page"] { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-4">

        {{-- ─────────────── Flash ─────────────── --}}
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

        {{-- ═════════════ Rating board ═════════════ --}}
        <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)] items-stretch">

            {{-- Average rating --}}
            <div class="relative overflow-hidden rounded-2xl p-5 text-white flex flex-col gap-3"
                 style="background: linear-gradient(135deg, #04041f 0%, #070740 50%, #070740 100%);">
                <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
                <div class="absolute top-0 bottom-0 start-0 w-[3px]" style="background: linear-gradient(180deg, #ff8a3d 0%, #e65c00 100%);"></div>
                <div class="absolute -top-16 -end-16 h-52 w-52 rounded-full bg-accent/10 blur-[60px] pointer-events-none"></div>

                <div class="relative font-mono text-[10px] font-bold uppercase tracking-[0.28em] text-accent">{{ __('Catalog · Feedback') }}</div>
                <h1 class="relative text-2xl font-bold leading-tight -mt-1">{{ __('Customer Reviews') }}</h1>

                <div class="relative flex items-end gap-3 mt-1">
                    <span class="text-[44px] font-bold leading-none">{{ number_format($averageRating, 1) }}</span>
                    <div class="pb-1.5">
                        <span class="rv-avg-stars" role="img" aria-label="{{ __('Average rating: :avg out of 5', ['avg' => number_format($averageRating, 1)]) }}">
                            <span class="base" aria-hidden="true">★★★★★</span>
                            <span class="over" aria-hidden="true" style="width: {{ $averageFillPct }}%;">★★★★★</span>
                        </span>
                        <p class="text-[11.5px] text-white/60 mt-1.5">
                            {{ trans_choice(':count review|:count reviews', $totalReviews, ['count' => number_format($totalReviews)]) }}
                            @if($search !== '')
                                · {{ __('filtered') }}
                            @endif
                        </p>
                    </div>
                </div>

                <p class="relative text-[11.5px] text-white/60 leading-snug">
                    {{ __(':n five-star reviews', ['n' => number_format($fiveStar)]) }}
                </p>

                @if($lowRating > 0)
                    <a href="{{ $ratingUrl($lowOnly ? 0 : 'low') }}"
                       class="relative inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl text-sm font-bold transition mt-auto border
                              {{ $lowOnly
                                    ? 'bg-rose-400/90 text-navy-deep border-rose-300 hover:bg-rose-300'
                                    : 'bg-rose-500/15 text-rose-200 border-rose-400/30 hover:bg-rose-500/25' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {{ $lowOnly
                            ? __('Showing low ratings — clear')
                            : __('Review :n low ratings', ['n' => number_format($lowRating)]) }}
                    </a>
                @else
                    <p class="relative inline-flex items-center gap-2 mt-auto text-[11.5px] font-bold text-emerald-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('No low ratings — all good') }}
                    </p>
                @endif
            </div>

            {{-- Rating distribution --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 bento-shadow flex flex-col justify-center gap-1">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Rating distribution') }}</div>
                    @if((int) $rating > 0 || $lowOnly)
                        <a href="{{ $ratingUrl(0) }}" class="rv-btn sm">{{ __('Clear rating filter') }}</a>
                    @endif
                </div>
                @for($value = 5; $value >= 1; $value--)
                    @php
                        $count = (int) ($ratingCounts->get($value, 0) ?? 0);
                        $isActiveRow = (int) $rating === $value || ($lowOnly && $value <= 2);
                    @endphp
                    <a href="{{ $ratingUrl((int) $rating === $value ? 0 : $value) }}"
                       class="rv-dist {{ $isActiveRow ? 'on' : '' }}"
                       title="{{ __('Filter by :n-star reviews', ['n' => $value]) }}">
                        <span class="lab">{{ $value }} <span class="star">★</span></span>
                        <span class="track">
                            <span class="fill {{ $value <= 2 ? 'low' : '' }}" style="width: {{ $count > 0 ? max(2, round($count / $maxRatingCount * 100)) : 0 }}%;"></span>
                        </span>
                        <span class="cnt">{{ number_format($count) }}</span>
                    </a>
                @endfor
            </div>
        </div>

        {{-- ═════════════ Search band ═════════════ --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl px-5 py-4 bento-shadow flex flex-wrap items-center gap-4">
            <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-1 min-w-[220px] max-w-md gap-2">
                @if($ratingQueryValue !== null)
                    <input type="hidden" name="rating" value="{{ $ratingQueryValue }}">
                @endif
                @if($flaggedOnly)
                    <input type="hidden" name="flagged" value="1">
                @endif
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-muted">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                    </span>
                    <input name="search" value="{{ $search }}"
                           placeholder="{{ __('Search product, SKU, customer, title, or comment...') }}"
                           class="rv-inp !ps-10">
                </div>
                <button type="submit" class="rv-btn primary shrink-0">{{ __('Search') }}</button>
                @if($search !== '' || (int) $rating > 0 || $lowOnly || $flaggedOnly)
                    <a href="{{ route('admin.reviews.index') }}" class="rv-btn shrink-0">{{ __('Clear') }}</a>
                @endif
            </form>
            <div class="flex flex-wrap gap-1.5">
                <a href="{{ $ratingUrl(0) }}" class="ychip {{ (int) $rating === 0 && ! $lowOnly ? 'on' : '' }}">
                    {{ __('All') }} <span class="cnt">{{ number_format($totalReviews) }}</span>
                </a>
                @for($value = 5; $value >= 1; $value--)
                    <a href="{{ $ratingUrl($value) }}" class="ychip {{ (int) $rating === $value ? 'on' : '' }}">
                        <span class="star">★</span> {{ $value }}
                        <span class="cnt">{{ number_format((int) ($ratingCounts->get($value, 0) ?? 0)) }}</span>
                    </a>
                @endfor
                @if($flaggedCount > 0 || $flaggedOnly)
                    <a href="{{ $flaggedUrl(! $flaggedOnly) }}"
                       class="ychip {{ $flaggedOnly ? 'on' : '' }}"
                       title="{{ __('Reviews where profanity was auto-masked') }}">
                        <i class="fas fa-eye-slash text-[10px]"></i> {{ __('Censored') }}
                        <span class="cnt">{{ number_format($flaggedCount) }}</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- ═════════════ Review cards ═════════════ --}}
        @if($reviews->count() === 0)
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl py-14 px-4 text-center bento-shadow">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center text-muted dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m-9 4V7a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H9l-5 4z"/></svg>
                </div>
                <div class="text-base font-bold text-slate-900 dark:text-white">{{ __('No reviews found.') }}</div>
                <div class="text-[13px] text-slate-500 dark:text-slate-400 mt-1.5">{{ __('Try changing the search or rating filter.') }}</div>
                @if($search !== '' || (int) $rating > 0 || $lowOnly)
                    <a href="{{ route('admin.reviews.index') }}" class="rv-btn primary mt-4 inline-flex">{{ __('Reset filters') }}</a>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($reviews as $review)
                    @php
                        $isLow = (int) $review->rating <= 2;
                        $isLongComment = $review->comment && mb_strlen((string) $review->comment) > 220;
                    @endphp
                    <div class="rv-card {{ $isLow ? 'low' : '' }}" style="--i: {{ $loop->index }};">
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2">
                                <span class="rv-stars" aria-label="{{ __(':n out of 5', ['n' => (int) $review->rating]) }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= (int) $review->rating ? 'f' : 'e' }}">★</span>
                                    @endfor
                                </span>
                                @if($review->is_flagged)
                                    <span class="inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300"
                                          title="{{ __('Profanity was auto-masked in this review') }}">
                                        <i class="fas fa-eye-slash text-[9px]"></i> {{ __('Censored') }}
                                    </span>
                                @endif
                            </span>
                            <span class="font-mono text-[10.5px] text-muted dark:text-slate-500">
                                {{ optional($review->reviewed_at ?? $review->created_at)->format('d M Y') ?: '-' }}
                            </span>
                        </div>

                        <div>
                            <p class="text-[13.5px] font-bold text-slate-900 dark:text-slate-100">{{ $review->title ?: __('Customer review') }}</p>
                            @if($review->comment)
                                <p class="rv-comment {{ $isLongComment ? 'clamp' : '' }} mt-1.5 text-[13px] leading-relaxed text-slate-600 dark:text-slate-300"
                                   @if($isLongComment) data-rv-comment @endif>{{ $review->comment }}</p>
                                @if($isLongComment)
                                    <button type="button" class="rv-more mt-1" data-rv-more
                                            data-more="{{ __('Show more') }}" data-less="{{ __('Show less') }}">{{ __('Show more') }}</button>
                                @endif
                            @endif
                        </div>

                        <div class="mt-auto pt-3 border-t border-dashed border-slate-200 dark:border-slate-700 flex flex-col gap-2.5">
                            @if($review->product)
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="rv-thumb">
                                        @if($review->product->image)
                                            <img src="{{ asset('storage/' . ltrim((string) $review->product->image, '/')) }}" alt="{{ $review->product->name }}" loading="lazy">
                                        @else
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16 9 11l4 4 3-3 4 4M4 19h16"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('admin.products.edit', $review->product) }}"
                                           class="block truncate text-[12.5px] font-bold text-slate-900 hover:text-accent dark:text-slate-100 dark:hover:text-accent transition">
                                            {{ $review->product->name }}
                                        </a>
                                        <span class="rv-mono">{{ $review->product->sku ?: __('No SKU') }}</span>
                                    </div>
                                </div>
                            @else
                                <p class="text-[12px] text-muted dark:text-slate-500">{{ __('Product unavailable') }}</p>
                            @endif

                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="rv-avatar">{{ mb_strtoupper(mb_substr($review->user?->name ?? '?', 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        @if($review->user && Route::has('admin.users.show'))
                                            <a href="{{ route('admin.users.show', $review->user) }}"
                                               class="block truncate text-[12px] font-bold text-slate-900 hover:text-accent dark:text-slate-100 dark:hover:text-accent transition">
                                                {{ $review->user->name }}
                                            </a>
                                        @else
                                            <p class="truncate text-[12px] font-bold text-slate-900 dark:text-slate-100">{{ $review->user?->name ?? __('Customer') }}</p>
                                        @endif
                                        <p class="truncate text-[10.5px] text-slate-500 dark:text-slate-400">{{ $review->user?->email ?? '-' }}</p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}"
                                      data-danger-confirm
                                      data-danger-title="{{ __('Delete Review') }}"
                                      data-danger-description="{{ __('This review will be permanently removed from the product page.') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rv-btn danger sm" title="{{ __('Delete') }}">
                                        <i class="fas fa-trash text-[10px]"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ═════════════ Pagination ═════════════ --}}
        @if($reviews->hasPages())
            <div class="flex flex-wrap justify-between items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl px-5 py-3.5 bento-shadow">
                <span class="text-[12px] text-slate-500 dark:text-slate-400">
                    {{ __('Showing :from–:to of :total reviews', [
                        'from'  => $reviews->firstItem() ?? 0,
                        'to'    => $reviews->lastItem() ?? 0,
                        'total' => $reviews->total(),
                    ]) }}
                </span>
                <div class="y-pagination">{{ $reviews->links() }}</div>
            </div>
        @endif

    </div>
    </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        document.querySelectorAll('[data-rv-more]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const comment = btn.parentElement.querySelector('[data-rv-comment]');
                if (!comment) return;
                const expanded = comment.classList.toggle('clamp') === false;
                btn.textContent = expanded ? (btn.dataset.less || 'Show less') : (btn.dataset.more || 'Show more');
            });
        });
    </script>
</x-app-layout>
