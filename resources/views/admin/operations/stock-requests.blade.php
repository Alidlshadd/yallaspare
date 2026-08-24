<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800">{{ __('Product Requests') }}</h2>
            <p class="text-sm text-slate-500">{{ __('See who is waiting for unavailable products and send real restock notifications.') }}</p>
        </div>
    </x-slot>

    @php
        $srConfig = [
            'labels' => [
                'listName' => __('Order List'),
                'addedFlash' => __('Added to purchase list'),
                'qtyBumped' => __('Already in list — quantity +1'),
                'alreadyAdded' => __('In purchase list — click to add +1'),
                'addToList' => __('Add to purchase list'),
                'waiting' => __('Waiting'),
                'notified' => __('Notified'),
                'stockWord' => __('Stock'),
                'pendingWord' => __('pending'),
                'fromStockRequests' => __('from stock requests'),
            ],
        ];

        $priorityChip = fn (int $pending) => match (true) {
            $pending >= 3 => ['label' => __('High demand'), 'class' => 'bg-rose-50 text-rose-600'],
            $pending === 2 => ['label' => __('Medium'), 'class' => 'bg-amber-50 text-amber-700'],
            $pending === 1 => ['label' => __('Low'), 'class' => 'bg-slate-100 text-slate-500'],
            default => ['label' => __('Handled'), 'class' => 'bg-emerald-50 text-emerald-600'],
        };

        $stockChip = fn (int $stock) => match (true) {
            $stock <= 0 => ['label' => __('Out of stock'), 'class' => 'bg-rose-50 text-rose-600'],
            $stock <= $lowStockThreshold => ['label' => __('Low stock'), 'class' => 'bg-amber-50 text-amber-700'],
            default => ['label' => __('In stock'), 'class' => 'bg-slate-100 text-slate-500'],
        };
    @endphp

    <style>
        /* Stock Requests board (sr-) */
        .sr-hero {
            background: linear-gradient(135deg, #04041f, #12124a);
            position: relative; overflow: hidden;
        }
        .sr-hero::after {
            content: ""; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
        }
        .sr-hero > * { position: relative; z-index: 1; }

        .sr-num { font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }

        .sr-icon-btn {
            width: 30px; height: 30px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px; border: 1px solid var(--border); color: var(--text-muted); background: var(--surface);
            transition: transform .12s ease, background .15s ease, color .15s ease;
        }
        .sr-icon-btn:hover { transform: scale(1.06); color: #04041f; border-color: var(--border); }
        .dark .sr-icon-btn { background: var(--surface); border-color: var(--border); color: var(--text-muted); }
        .dark .sr-icon-btn:hover { color: var(--text); }

        .sr-add {
            background: #ff8a3d; border-color: #ff8a3d; color: #422006;
            box-shadow: 0 3px 8px -3px rgba(180, 83, 9, 0.5);
        }
        .sr-add:hover { color: #422006; border-color: #e65c00; }
        .dark .sr-add { background: #ff8a3d; border-color: #ff8a3d; color: #422006; }
        .dark .sr-add:hover { color: #422006; }
        .sr-add .sr-ic-added { display: none; }
        .sr-add.sr-added { background: #059669; border-color: #059669; color: #fff; box-shadow: none; }
        .sr-add.sr-added:hover { color: #fff; }
        .dark .sr-add.sr-added { background: #10b981; border-color: #10b981; color: var(--text); }
        .sr-add.sr-added .sr-ic-add { display: none; }
        .sr-add.sr-added .sr-ic-added { display: inline; }

        .sr-chip-waiting { background: rgb(var(--accent-rgb) / 0.16); color: var(--brand-orange-ink); }
        .dark .sr-chip-waiting { color: #ff8a3d; }
        .sr-chip-notified { background: rgba(5,150,105,0.12); color: #059669; }
        .dark .sr-chip-notified { color: #34d399; }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="stockRequestsBoard" data-config="{{ json_encode($srConfig) }}">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ session('error') }}</div>
            @endif

            <section class="sr-hero flex flex-col gap-3 rounded-2xl px-5 py-4 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-accent">{{ __('Automatic restock notifications') }}</p>
                    <p class="mt-1 text-sm text-white/75">{{ __('When stock changes from zero to available, waiting customers are notified automatically.') }}</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-400/15 px-3 py-1.5 text-xs font-bold text-emerald-300 ring-1 ring-inset ring-emerald-300/25">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>{{ __('Active') }}
                </span>
            </section>

            {{-- ============ summary cards ============ --}}
            <section class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-muted dark:text-slate-500">{{ __('Pending') }}</p>
                        <i class="fas fa-hourglass-half text-accent" aria-hidden="true"></i>
                    </div>
                    <p class="sr-num mt-2 text-2xl font-bold text-accent dark:text-accent">{{ number_format($summary['pending']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-muted dark:text-slate-500">{{ __('Notified') }}</p>
                        <i class="fas fa-bell text-emerald-500" aria-hidden="true"></i>
                    </div>
                    <p class="sr-num mt-2 text-2xl font-bold text-emerald-600">{{ number_format($summary['notified']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-muted dark:text-slate-500">{{ __('Products') }}</p>
                        <i class="fas fa-box-open text-info" aria-hidden="true"></i>
                    </div>
                    <p class="sr-num mt-2 text-2xl font-bold text-info dark:text-info">{{ number_format($summary['products']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-muted dark:text-slate-500">{{ __('High Demand') }}</p>
                        <i class="fas fa-fire text-rose-500" aria-hidden="true"></i>
                    </div>
                    <p class="sr-num mt-2 text-2xl font-bold text-rose-600">{{ number_format($summary['high_demand']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-muted dark:text-slate-500">{{ __('Out of Stock') }}</p>
                        <i class="fas fa-circle-exclamation text-rose-500" aria-hidden="true"></i>
                    </div>
                    <p class="sr-num mt-2 text-2xl font-bold text-rose-600">{{ number_format($summary['out_of_stock_requests']) }}</p>
                </article>
                <article class="sr-hero rounded-2xl border border-transparent p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-white/55">{{ __('In Purchase List') }}</p>
                        <i class="fas fa-cart-shopping text-accent" aria-hidden="true"></i>
                    </div>
                    <p class="sr-num mt-2 text-2xl font-bold text-accent" x-text="inListCountLabel">0</p>
                    <p class="text-[10px] text-white/45">{{ __('on this page') }}</p>
                </article>
            </section>

            {{-- ============ filters ============ --}}
            <form method="GET" action="{{ route('admin.stock-requests.index') }}" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search product, SKU, brand') }}" class="min-w-0 flex-[2_1_220px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <select aria-label="{{ __('Status') }}" name="status" class="min-w-0 flex-[1_1_140px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach(['pending' => __('Pending'), 'notified' => __('Notified'), 'all' => __('All')] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.stock-requests.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800">{{ __('Reset') }}</a>
                    <button class="rounded-xl bg-navy-deep px-4 py-2 text-sm font-semibold text-white hover:bg-navy-raised">{{ __('Filter') }}</button>
                </div>
            </form>

            @unless($hasTable)
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700">
                    <i class="fas fa-plug-circle-xmark mb-3 block text-2xl text-slate-300" aria-hidden="true"></i>
                    <p class="text-sm font-bold text-slate-600">{{ __('Back-in-stock subscriptions table is not available yet.') }}</p>
                    <p class="mt-1 text-xs text-muted">{{ __('Run the pending database migrations to enable stock request tracking.') }}</p>
                </div>
            @else
                {{-- ============ main request table ============ --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3">
                        <i class="fas fa-list-check text-muted" aria-hidden="true"></i>
                        <h3 class="text-sm font-bold text-slate-800">{{ __('Requested products') }}</h3>
                        <span class="ms-auto text-xs font-semibold text-muted">{{ __(':from–:to of :total', ['from' => $products->firstItem() ?? 0, 'to' => $products->lastItem() ?? 0, 'total' => $products->total()]) }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('Product') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Stock') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Requests') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Waiting') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Latest') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Priority') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($products as $product)
                                    @php
                                        $pending = (int) $product->pending_count;
                                        $stock = (int) $product->stock_quantity;
                                        $priority = $priorityChip($pending);
                                        $stockBadge = $stockChip($stock);
                                        $rowSubs = ($subscribersByProduct[$product->id] ?? collect())->map(fn ($sub) => [
                                            'id' => $sub->id,
                                            'name' => $sub->user?->name ?? __('Guest'),
                                            'email' => $sub->user?->email ?: ($sub->user?->phone ?? ''),
                                            'date' => $sub->created_at?->diffForHumans() ?? '',
                                            'status' => $sub->notified_at ? 'notified' : 'waiting',
                                        ])->values();
                                        $rowProduct = [
                                            'id' => $product->id,
                                            'name' => $product->name,
                                            'sku' => $product->sku ?? '',
                                            'stock' => $stock,
                                            'pending' => $pending,
                                            'qty' => max(1, $pending),
                                            'cost' => (float) ($product->dealer_price ?? $product->price),
                                            'notifyUrl' => route('admin.stock-requests.notify', $product),
                                        ];
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40" data-product="{{ json_encode($rowProduct) }}" data-subs="{{ json_encode($rowSubs) }}">
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-slate-900">{{ $product->name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $product->sku ?? __('N/A') }} @if($product->brand) · {{ $product->brand }} @endif</div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="sr-num font-semibold {{ $stock <= 0 ? 'text-rose-600' : 'text-slate-700 dark:text-slate-200' }}">{{ number_format($stock) }}</span>
                                            <span class="mt-1 block"><span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $stockBadge['class'] }}">{{ $stockBadge['label'] }}</span></span>
                                        </td>
                                        <td class="sr-num px-4 py-3 text-right text-slate-600">{{ number_format((int) $product->request_count) }}</td>
                                        <td class="sr-num px-4 py-3 text-right text-lg font-bold {{ $pending > 0 ? 'text-accent dark:text-accent' : 'text-slate-300 dark:text-slate-600' }}">{{ number_format($pending) }}</td>
                                        <td class="px-4 py-3 text-right text-xs text-slate-500">{{ optional($product->latest_requested_at ? \Carbon\Carbon::parse($product->latest_requested_at) : null)->diffForHumans() ?? __('N/A') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $priority['class'] }}">{{ $priority['label'] }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" class="sr-icon-btn" @click="openDrawer" title="{{ __('View requests') }}" aria-label="{{ __('View requests') }}">
                                                    <i class="fas fa-users" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="sr-icon-btn sr-add" data-sr-add data-id="{{ $product->id }}" @click="addFromRow" aria-label="{{ __('Add to purchase list') }}">
                                                    <i class="fas fa-cart-plus sr-ic-add" aria-hidden="true"></i>
                                                    <i class="fas fa-check sr-ic-added" aria-hidden="true"></i>
                                                </button>
                                                <a href="{{ route('admin.products.edit', $product) }}" class="sr-icon-btn" title="{{ __('View product') }}" aria-label="{{ __('View product') }}">
                                                    <i class="fas fa-up-right-from-square" aria-hidden="true"></i>
                                                </a>
                                                @if($pending > 0 && $stock > 0)
                                                    <form method="POST" action="{{ route('admin.stock-requests.notify', $product) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="rounded-lg bg-navy-deep px-2.5 py-1.5 text-sm font-bold text-white hover:bg-navy-raised">{{ __('Send Notifications') }}</button>
                                                    </form>
                                                @elseif($pending > 0)
                                                    <span class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-[11px] font-bold text-amber-700">{{ __('Awaiting stock') }}</span>
                                                @else
                                                    <span class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-[11px] font-bold text-emerald-600">{{ __('Done') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-12 text-center">
                                            <i class="fas fa-inbox mb-3 block text-2xl text-slate-300" aria-hidden="true"></i>
                                            <p class="text-sm font-bold text-slate-600">{{ __('No stock requests matched the filters.') }}</p>
                                            <p class="mt-1 text-xs text-muted">{{ __('Requests will appear here when customers ask for unavailable products.') }}</p>
                                            <div class="mt-4 flex justify-center gap-2">
                                                <a href="{{ route('admin.products.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800">{{ __('Go to Products') }}</a>
                                                <a href="{{ route('admin.purchase-planning.index') }}" class="font-display rounded-xl bg-navy-deep px-4 py-2 text-sm font-bold text-white hover:bg-navy-raised">{{ __('Purchase Planning') }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 px-4 py-3">{{ $products->links() }}</div>
                </section>

                {{-- ============ recent requests ============ --}}
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3">
                        <i class="fas fa-clock-rotate-left text-muted" aria-hidden="true"></i>
                        <h3 class="text-sm font-bold text-slate-800">{{ __('Recent Requests') }}</h3>
                        <span class="ms-auto text-xs font-semibold text-muted">{{ __('latest :count', ['count' => $requests->count()]) }}</span>
                    </div>
                    <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-3">
                        @forelse($requests as $requestRow)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-bold text-slate-900">{{ $requestRow->product?->name ?? __('Deleted product') }}</div>
                                        <div class="mt-0.5 truncate text-xs text-slate-500">{{ $requestRow->user?->name ?? __('Guest') }} · {{ $requestRow->user?->email ?: $requestRow->user?->phone }}</div>
                                    </div>
                                    <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $requestRow->notified_at ? 'sr-chip-notified' : 'sr-chip-waiting' }}">
                                        {{ $requestRow->notified_at ? __('Notified') : __('Waiting') }}
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-[11px] text-muted">{{ $requestRow->created_at?->diffForHumans() }}</span>
                                    @if($requestRow->product)
                                        <a href="{{ route('admin.products.edit', $requestRow->product) }}" class="text-[11px] font-bold text-info hover:underline dark:text-info">{{ __('View product') }}</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="md:col-span-2 xl:col-span-3 py-8 text-center">
                                <i class="fas fa-inbox mb-2 block text-xl text-slate-300" aria-hidden="true"></i>
                                <p class="text-sm font-bold text-slate-600">{{ __('No stock requests yet.') }}</p>
                                <p class="mt-1 text-xs text-muted">{{ __('Requests will appear here when customers ask for unavailable products.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endunless

            {{-- ============ waiting customers drawer ============ --}}
            <div class="fixed inset-0 z-50 bg-navy-deep/55" x-show="drawerOpen" x-cloak @click.self="closeDrawer" role="dialog" aria-modal="true">
                <aside class="absolute end-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl">
                    <div class="sr-hero flex items-start justify-between gap-3 px-5 py-4 text-white">
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-accent">{{ __('Waiting customers') }}</p>
                            <h3 class="mt-0.5 truncate text-[15px] font-bold" x-text="drawerName"></h3>
                            <p class="text-[11px] text-white/60" x-text="drawerMeta"></p>
                        </div>
                        <button type="button" class="shrink-0 px-1 text-lg" @click="closeDrawer" aria-label="{{ __('Close') }}">✕</button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div class="px-5 py-10 text-center" x-show="drawerEmpty">
                            <i class="fas fa-user-clock mb-2 block text-xl text-slate-300" aria-hidden="true"></i>
                            <p class="text-sm font-bold text-slate-600">{{ __('No customer requests for this product.') }}</p>
                        </div>
                        <template x-for="sub in drawerSubs" :key="sub.id">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900" x-text="sub.name"></p>
                                    <p class="truncate text-xs text-slate-500" x-text="sub.email"></p>
                                    <p class="text-[11px] text-muted" x-text="sub.date"></p>
                                </div>
                                <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="subChipClass(sub)" x-text="subStatusLabel(sub)"></span>
                            </div>
                        </template>
                    </div>

                    <p class="px-5 pt-2 text-xs font-bold text-emerald-600" x-show="hasFlash" x-cloak x-text="flashMessage"></p>
                    <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 px-5 py-3.5">
                        <form method="POST" :action="drawerNotifyUrl" x-show="drawerCanNotify">
                            @csrf
                            @method('PATCH')
                            <button class="rounded-lg bg-navy-deep px-3.5 py-2 text-sm font-bold text-white hover:bg-navy-raised">{{ __('Send Notifications') }}</button>
                        </form>
                        <span class="rounded-lg bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700" x-show="drawerHasPending && !drawerCanNotify">{{ __('Awaiting stock') }}</span>
                        <button type="button" class="rounded-lg bg-accent px-3.5 py-2 text-sm font-bold text-[#422006] hover:bg-accent" @click="addFromDrawer">
                            <i class="fas fa-cart-plus me-1" aria-hidden="true"></i>{{ __('Add to Purchase List') }}
                        </button>
                        <button type="button" class="ms-auto rounded-lg border border-slate-200 px-3.5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800" @click="closeDrawer">{{ __('Close') }}</button>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
