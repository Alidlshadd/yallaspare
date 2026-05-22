        <form method="POST" action="{{ route('admin.discounts.update') }}" id="coupon-dashboard-form" class="space-y-6">
            @csrf
            @method('PUT')

            <input type="hidden" name="discounts_enabled" value="{{ $discountsEnabled }}">
            <input type="hidden" name="discount_label" value="{{ $discountLabel }}">
            <input type="hidden" name="discount_type" value="{{ $discountType }}">
            <input type="hidden" name="discount_value" value="{{ $discountValue }}">
            <input type="hidden" name="discount_starts_at" value="{{ $discountStartsAt }}">
            <input type="hidden" name="discount_ends_at" value="{{ $discountEndsAt }}">
            <input type="hidden" name="discount_scope" value="{{ $discountScope }}">
            @foreach ($selectedProducts as $pid)
                <input type="hidden" name="discount_product_ids[]" value="{{ (int) $pid }}">
            @endforeach
            @foreach ($selectedCategories as $cid)
                <input type="hidden" name="discount_category_ids[]" value="{{ (int) $cid }}">
            @endforeach
            @foreach ($selectedBrands as $brand)
                <input type="hidden" name="discount_brands[]" value="{{ (string) $brand }}">
            @endforeach

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="group relative overflow-hidden rounded-3xl border border-cyan-200/60 bg-[linear-gradient(145deg,rgba(236,254,255,0.96),rgba(207,250,254,0.82))] p-5 shadow-[0_18px_40px_rgba(8,145,178,0.12)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(8,145,178,0.18)] dark:border-cyan-900/50 dark:bg-[linear-gradient(145deg,rgba(8,47,73,0.95),rgba(15,23,42,0.96))]">
                    <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-cyan-300/30 blur-2xl"></div>
                    <div class="relative">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-700 dark:text-cyan-300">{{ __('Active Coupons') }}</p>
                        <p class="mt-3 text-3xl font-bold text-slate-950 dark:text-white">{{ number_format($activeCoupons) }}</p>
                        <div class="mt-4 flex items-center justify-between text-xs">
                            <span class="rounded-full border border-cyan-200 bg-white/80 px-2.5 py-1 font-semibold text-cyan-700 dark:border-cyan-800 dark:bg-slate-900/60 dark:text-cyan-300">{{ __('Live now') }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Campaign inventory') }}</span>
                        </div>
                    </div>
                </article>
                <article class="group relative overflow-hidden rounded-3xl border border-sky-200/60 bg-[linear-gradient(145deg,rgba(239,246,255,0.98),rgba(224,242,254,0.9))] p-5 shadow-[0_18px_40px_rgba(14,165,233,0.12)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(14,165,233,0.18)] dark:border-sky-900/50 dark:bg-[linear-gradient(145deg,rgba(8,47,73,0.95),rgba(15,23,42,0.96))]">
                    <div class="absolute -left-6 bottom-0 h-24 w-24 rounded-full bg-sky-300/25 blur-2xl"></div>
                    <div class="relative">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">{{ __('Total Redemptions') }}</p>
                        <p class="mt-3 text-3xl font-bold text-slate-950 dark:text-white" data-kpi="redemptions">{{ number_format($totalRedemptions) }}</p>
                        <div class="mt-4 flex items-center justify-between text-xs">
                            <span class="rounded-full border border-sky-200 bg-white/80 px-2.5 py-1 font-semibold text-sky-700 dark:border-sky-800 dark:bg-slate-900/60 dark:text-sky-300">{{ __('All channels') }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ $primaryPlatformLabel }} leads</span>
                        </div>
                    </div>
                </article>
                <article class="group relative overflow-hidden rounded-3xl border border-indigo-200/60 bg-[linear-gradient(145deg,rgba(238,242,255,0.98),rgba(224,231,255,0.9))] p-5 shadow-[0_18px_40px_rgba(99,102,241,0.12)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(99,102,241,0.18)] dark:border-indigo-900/50 dark:bg-[linear-gradient(145deg,rgba(30,27,75,0.95),rgba(15,23,42,0.96))]">
                    <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-indigo-300/20 blur-2xl"></div>
                    <div class="relative">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-700 dark:text-indigo-300">{{ __('Revenue Impact') }}</p>
                        <p class="mt-3 text-3xl font-bold text-slate-950 dark:text-white" data-kpi="impact">{{ number_format($revenueImpact, $currencyDecimals) }} {{ $currencyLabel }}</p>
                        <div class="mt-4 flex items-center justify-between text-xs">
                            <span class="rounded-full border border-indigo-200 bg-white/80 px-2.5 py-1 font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-slate-900/60 dark:text-indigo-300">{{ __('GMV touch') }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Discount influence') }}</span>
                        </div>
                    </div>
                </article>
                <article class="group relative overflow-hidden rounded-3xl border border-amber-200/70 bg-[linear-gradient(145deg,rgba(255,251,235,0.98),rgba(254,243,199,0.92))] p-5 shadow-[0_18px_40px_rgba(245,158,11,0.14)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(245,158,11,0.18)] dark:border-amber-900/50 dark:bg-[linear-gradient(145deg,rgba(120,53,15,0.92),rgba(15,23,42,0.96))]">
                    <div class="absolute -right-4 bottom-0 h-24 w-24 rounded-full bg-amber-300/25 blur-2xl"></div>
                    <div class="relative">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">{{ __('Average Discount') }}</p>
                        <p class="mt-3 text-3xl font-bold text-slate-950 dark:text-white" data-kpi="avg">{{ $avgDiscount }}</p>
                        <div class="mt-4 flex items-center justify-between text-xs">
                            <span class="rounded-full border border-amber-200 bg-white/80 px-2.5 py-1 font-semibold text-amber-700 dark:border-amber-800 dark:bg-slate-900/60 dark:text-amber-300">{{ __('Baseline') }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ $primaryUsageLabel }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-[1.5fr_0.75fr_0.75fr]">
                <article class="relative overflow-hidden rounded-[2rem] border border-cyan-200/60 bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.18),transparent_22%),linear-gradient(160deg,rgba(248,250,252,0.98),rgba(224,242,254,0.92))] p-6 shadow-[0_20px_48px_rgba(14,165,233,0.12)] dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.12),transparent_22%),linear-gradient(160deg,rgba(15,23,42,0.98),rgba(8,47,73,0.92))] xl:col-span-1">
                    <div class="pointer-events-none absolute right-0 top-0 h-32 w-32 rounded-full bg-cyan-300/20 blur-3xl"></div>
                    <div class="relative">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-700 dark:text-cyan-300">{{ __('Analytics Canvas') }}</p>
                                <h2 class="mt-1 text-xl font-semibold text-slate-950 dark:text-slate-100">{{ __('Redemption Trends') }}</h2>
                            </div>
                            <span class="rounded-full border border-cyan-200 bg-white/75 px-3 py-1 text-[11px] font-semibold text-cyan-700 shadow-sm dark:border-cyan-800 dark:bg-slate-900/60 dark:text-cyan-300">{{ __('Last 30 Days') }}</span>
                        </div>

                        <div class="mt-5 grid gap-4 lg:grid-cols-[1fr_220px]">
                            <div class="rounded-3xl border border-white/70 bg-white/65 p-4 shadow-inner shadow-cyan-100/50 backdrop-blur dark:border-slate-800 dark:bg-slate-950/70 dark:shadow-none">
                                <svg id="trend-chart" viewBox="0 0 560 170" class="h-56 w-full">
                                    <defs>
                                        <linearGradient id="trendGradient" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.32"></stop>
                                            <stop offset="100%" stop-color="#06b6d4" stop-opacity="0"></stop>
                                        </linearGradient>
                                    </defs>
                                    <path d="" fill="url(#trendGradient)" id="trend-area"></path>
                                    <path d="" fill="none" stroke="#0891b2" stroke-width="3" id="trend-line"></path>
                                </svg>
                            </div>

                            <div class="grid gap-3">
                                <div class="rounded-2xl border border-cyan-200/70 bg-white/80 p-4 dark:border-cyan-900/40 dark:bg-slate-950/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-cyan-700 dark:text-cyan-300">{{ __('Top Platform') }}</p>
                                    <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $primaryPlatformLabel }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $primaryPlatformValue }}% share</p>
                                </div>
                                <div class="rounded-2xl border border-emerald-200/70 bg-white/80 p-4 dark:border-emerald-900/40 dark:bg-slate-950/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ __('Active vs Tracked') }}</p>
                                    <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $activeRowCount }} / {{ count($couponRows) }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Operational campaigns') }}</p>
                                </div>
                                <div class="rounded-2xl border border-amber-200/70 bg-white/80 p-4 dark:border-amber-900/40 dark:bg-slate-950/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">{{ __('Schedule Window') }}</p>
                                    <p class="mt-2 text-sm font-bold text-slate-950 dark:text-white">{{ $campaignWindow }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] p-5 shadow-[0_18px_38px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Distribution') }}</p>
                            <h2 class="mt-1 text-base font-semibold text-slate-950 dark:text-slate-100">{{ __('Coupon Usage') }}</h2>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $primaryUsageLabel }}</span>
                    </div>
                    <div class="mt-5 space-y-3" id="usage-distribution"></div>
                </article>

                <article class="rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] p-5 shadow-[0_18px_38px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Channels') }}</p>
                            <h2 class="mt-1 text-base font-semibold text-slate-950 dark:text-slate-100">{{ __('Platform Distributioni') }}</h2>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $primaryPlatformLabel }}</span>
                    </div>
                    <div class="mt-5 space-y-3" id="platform-distribution"></div>
                </article>
            </section>

            <section id="coupon-management" class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.16),transparent_30%),linear-gradient(135deg,rgba(8,47,73,0.98),rgba(15,23,42,0.98))] px-5 py-6 text-white sm:px-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="max-w-2xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-100/80">{{ __('Operations Table') }}</p>
                            <h2 class="mt-2 text-2xl font-semibold">{{ __('Coupon Management Table') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-cyan-50/75">{{ __('Search, filter, and manage coupon campaigns from a denser control surface that matches the analytics header above.') }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs font-semibold uppercase tracking-[0.12em] sm:grid-cols-4">
                            <span class="rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-center text-cyan-50/85">Active {{ $activeRowCount }}</span>
                            <span class="rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-center text-cyan-50/85">Scheduled {{ $scheduledRowCount }}</span>
                            <span class="rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-center text-cyan-50/85">Expired {{ $expiredRowCount }}</span>
                            <span class="rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-center text-cyan-50/85">Paused {{ $pausedRowCount }}</span>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-5 sm:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <input id="coupon-search" type="text" placeholder="{{ __('Search code, platform...') }}" class="w-56 rounded-2xl border border-slate-200 bg-white/95 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                            <select id="coupon-filter" class="rounded-2xl border border-slate-200 bg-white/95 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                                <option value="all">{{ __('All Status') }}</option>
                                <option value="active">{{ __('Active') }}</option>
                                <option value="scheduled">{{ __('Scheduled') }}</option>
                                <option value="expired">{{ __('Expired') }}</option>
                                <option value="paused">{{ __('Paused') }}</option>
                            </select>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="export-coupons-btn" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Export') }}</button>
                            <a href="{{ route('admin.discounts.coupons.create') }}" id="create-coupon-btn" class="rounded-2xl bg-gradient-to-r from-slate-950 to-cyan-800 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_12px_24px_rgba(8,145,178,0.22)] transition hover:from-slate-900 hover:to-cyan-700">{{ __('Create Coupon') }}</a>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto rounded-[1.5rem] border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                            <thead class="bg-slate-50/90 dark:bg-slate-800/80">
                                <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">
                                    <th class="px-5 py-4">{{ __('Coupon Code') }}</th>
                                    <th class="px-5 py-4">{{ __('Discount') }}</th>
                                    <th class="px-5 py-4">{{ __('Usage') }}</th>
                                    <th class="px-5 py-4">{{ __('Expiry') }}</th>
                                    <th class="px-5 py-4">{{ __('Status') }}</th>
                                    <th class="px-5 py-4">{{ __('Platforms') }}</th>
                                    <th class="px-5 py-4 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="coupon-table-body" class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="coupon-builder" class="relative hidden overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_24px_56px_rgba(15,23,42,0.10)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.18),transparent_28%),linear-gradient(135deg,rgba(8,47,73,0.98),rgba(15,23,42,0.98))] px-5 py-6 text-white sm:px-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="max-w-2xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-100/80">{{ __('Campaign Studio') }}</p>
                            <h2 class="mt-2 text-2xl font-semibold">{{ __('Create Coupon') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-cyan-50/75">{{ __('Configure code, discount logic, limits, and schedule from a denser builder that aligns with the analytics surface above.') }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <label class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur">
                                <input type="checkbox" name="coupon_enabled" value="1" class="h-4 w-4 rounded border-white/20 bg-transparent text-cyan-400 focus:ring-cyan-300 dark:border-slate-600 dark:bg-slate-900" @checked($couponEnabled)>
                                {{ __('Campaign Active') }}
                            </label>
                            <button type="button" id="close-coupon-builder-btn" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white transition hover:bg-white/15">{{ __('Close') }}</button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 px-5 py-6 sm:px-6 xl:grid-cols-[0.82fr_1.18fr]">
                    <aside class="space-y-4">
                        <article class="overflow-hidden rounded-[1.75rem] border border-cyan-200/70 bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.18),transparent_28%),linear-gradient(160deg,rgba(248,250,252,0.98),rgba(224,242,254,0.92))] p-5 shadow-[0_18px_38px_rgba(8,145,178,0.12)] dark:border-cyan-900/40 dark:bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.12),transparent_24%),linear-gradient(160deg,rgba(15,23,42,0.96),rgba(8,47,73,0.92))]">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-700 dark:text-cyan-300">{{ __('Live Preview') }}</p>
                            <div class="mt-4 rounded-[1.5rem] border border-white/60 bg-white/70 p-5 backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Coupon Code') }}</p>
                                        <p class="mt-2 text-2xl font-bold uppercase tracking-[0.08em] text-slate-950 dark:text-white">{{ $couponCode ?: 'Awaiting code' }}</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $couponEnabled ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $campaignStateLabel }}
                                    </span>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Discount') }}</p>
                                        <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">
                                            {{ $couponType === 'percent' ? rtrim(rtrim(number_format($couponValue, 2), '0'), '.') . '%' : number_format($couponValue, $currencyDecimals) . ' ' . $currencyLabel }}
                                        </p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Usage Limit') }}</p>
                                        <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ number_format($couponUsageLimit) }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-slate-200/80 bg-white/80 p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Window') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ $campaignWindow }}</p>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-slate-200/80 bg-white p-5 shadow-[0_18px_36px_rgba(15,23,42,0.07)] dark:border-slate-800 dark:bg-slate-950/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Builder Flow') }}</p>
                            <div class="mt-4 space-y-3">
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/90 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/80">
                                    <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('1. Identity') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Name the coupon and confirm its activation state.') }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/90 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/80">
                                    <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('2. Discount Logic') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Choose a percentage or fixed amount and define the value.') }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/90 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/80">
                                    <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('3. Guardrails') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Set the usage cap and validity window before publishing.') }}</p>
                                </div>
                            </div>
                        </article>
                    </aside>

                    <div id="coupon-rules-panel" class="grid grid-cols-1 gap-4 lg:grid-cols-2" data-coupon-panel>
                        <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Identity') }}</p>
                                    <h3 class="mt-1 text-base font-semibold text-slate-950 dark:text-slate-100">{{ __('Basic Info') }}</h3>
                                </div>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ __('Kod + Status') }}</span>
                            </div>
                            <div class="mt-4 space-y-4">
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Coupon Code') }}</span>
                                    <div class="flex gap-2">
                                        <input type="text" name="coupon_code" id="coupon_code" value="{{ old('coupon_code', $couponCode) }}" placeholder="{{ __('SAVE10') }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm uppercase text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40" data-coupon-control>
                                        <button type="button" id="coupon-generate-btn" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800" data-coupon-control>{{ __('Generate') }}</button>
                                    </div>
                                    @error('coupon_code')
                                        <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                                    @enderror
                                </label>

                                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Coupon Status') }}</p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Status, anahtar ve takvime gore otomatik guncellenir.') }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold" data-coupon-status-badge>{{ __('Disabled') }}</span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Value Design') }}</p>
                                    <h3 class="mt-1 text-base font-semibold text-slate-950 dark:text-slate-100">{{ __('Discount Rules') }}</h3>
                                </div>
                                <span class="rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-[11px] font-semibold text-cyan-700 dark:border-cyan-900/40 dark:bg-cyan-950/30 dark:text-cyan-300">{{ __('Flexible') }}</span>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Type') }}</span>
                                    <select name="coupon_type" id="coupon_type" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40" data-coupon-control>
                                        <option value="percent" @selected($couponType === 'percent')>{{ __('Percent (%)') }}</option>
                                        <option value="fixed" @selected($couponType === 'fixed')>{{ __('Fixed Amount') }}</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Value') }}</span>
                                    <input type="number" step="0.01" min="0" name="coupon_value" id="coupon_value" value="{{ old('coupon_value', (string) data_get($settings, 'coupon_value', '0')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40" data-coupon-control>
                                    <span id="coupon-value-help" class="mt-1.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Percent type supports max 100.') }}</span>
                                    @error('coupon_value')
                                        <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                                    @enderror
                                </label>
                            </div>
                            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/90 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Guidance') }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Percent mode works better for broad campaigns. Fixed amount is stronger when you need predictable monetary impact.') }}</p>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Guardrails') }}</p>
                                    <h3 class="mt-1 text-base font-semibold text-slate-950 dark:text-slate-100">{{ __('Limits') }}</h3>
                                </div>
                                <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">{{ __('Protection') }}</span>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Minimum Order') }}</span>
                                    <input type="number" step="0.01" min="0" name="coupon_min_order" id="coupon_min_order" value="{{ old('coupon_min_order', (string) data_get($settings, 'coupon_min_order', '0')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40" data-coupon-control>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Usage Limit') }}</span>
                                    <input type="number" min="0" name="coupon_usage_limit" id="coupon_usage_limit" value="{{ old('coupon_usage_limit', (string) data_get($settings, 'coupon_usage_limit', '0')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40" data-coupon-control>
                                </label>
                            </div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-300">{{ __('Set a minimum basket value to protect low-margin orders.') }}</div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-300">{{ __('Set a usage limit to prevent excessive redemptions and budget drift.') }}</div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))] lg:col-span-2">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Timing') }}</p>
                                    <h3 class="mt-1 text-base font-semibold text-slate-950 dark:text-slate-100">{{ __('Schedule') }}</h3>
                                </div>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $campaignWindow }}</span>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-[1fr_1fr_180px]">
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Starts At') }}</span>
                                    <input type="date" name="coupon_starts_at" id="coupon_starts_at" value="{{ $couponStartsAt }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40" data-coupon-control>
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Expiry') }}</span>
                                    <input type="date" name="coupon_ends_at" id="coupon_ends_at" value="{{ $couponEndsAt }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40" data-coupon-control>
                                    @error('coupon_ends_at')
                                        <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                                    @enderror
                                </label>
                                <div class="flex items-end">
                                    <button type="button" id="coupon-clear-dates" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800" data-coupon-control>{{ __('Clear Dates') }}</button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div class="border-t border-slate-200/80 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/60 sm:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Publish') }}</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Save updates to publish coupon configuration changes to admin settings.') }}</p>
                        </div>
                        <button type="submit" class="inline-flex items-center rounded-2xl bg-gradient-to-r from-slate-950 to-cyan-800 px-6 py-3 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(15,23,42,0.28)] transition hover:from-slate-900 hover:to-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300">
                            {{ __('Save Coupon Configuration') }}
                        </button>
                    </div>
                </div>
            </section>

            <div id="edit-coupon-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-md sm:items-center">
                <div class="my-4 flex max-h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.99),rgba(248,250,252,0.98))] shadow-[0_36px_90px_rgba(15,23,42,0.35)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.99),rgba(15,23,42,0.95))] sm:my-6">
                    <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.18),transparent_28%),linear-gradient(135deg,rgba(8,47,73,0.98),rgba(15,23,42,0.98))] px-5 py-6 text-white sm:px-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="max-w-2xl">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-100/80">{{ __('Coupon Editor') }}</p>
                                <h3 class="mt-2 text-xl font-semibold sm:text-2xl">{{ __('Edit Coupon Details') }}</h3>
                                <p class="mt-2 text-sm leading-6 text-cyan-50/75">{{ __('Update coupon identity, targeting, and operational state from the same premium control surface used across the page.') }}</p>
                            </div>
                            <button type="button" id="edit-modal-close" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white transition hover:bg-white/15">{{ __('Close') }}</button>
                        </div>
                    </div>

                    <div class="grid gap-6 overflow-y-auto px-5 py-6 sm:px-6 lg:grid-cols-[0.72fr_1.28fr]">
                        <aside class="space-y-4">
                            <article class="overflow-hidden rounded-[1.75rem] border border-cyan-200/70 bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.16),transparent_26%),linear-gradient(160deg,rgba(248,250,252,0.98),rgba(224,242,254,0.92))] p-5 shadow-[0_18px_38px_rgba(8,145,178,0.12)] dark:border-cyan-900/40 dark:bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.10),transparent_22%),linear-gradient(160deg,rgba(15,23,42,0.96),rgba(8,47,73,0.92))]">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-700 dark:text-cyan-300">{{ __('Modal Snapshot') }}</p>
                                <div class="mt-4 rounded-[1.5rem] border border-white/60 bg-white/75 p-5 backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Editing Code') }}</p>
                                    <p class="mt-2 text-2xl font-bold uppercase tracking-[0.08em] text-slate-950 dark:text-white" id="edit-code-preview">{{ __('Awaiting selection') }}</p>

                                    <div class="mt-5 grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Status') }}</p>
                                            <p class="mt-2 text-sm font-bold text-slate-950 dark:text-white" id="edit-status-preview">{{ __('Draft') }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Expiry') }}</p>
                                            <p class="mt-2 text-sm font-bold text-slate-950 dark:text-white" id="edit-expiry-preview">{{ __('Open') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article class="rounded-[1.75rem] border border-slate-200/80 bg-white p-5 shadow-[0_18px_36px_rgba(15,23,42,0.07)] dark:border-slate-800 dark:bg-slate-950/70">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Usage Progress') }}</p>
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/90 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                                    <div class="mb-2 flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                                        <span class="font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Current Usage') }}</span>
                                        <span id="edit-usage-label">0 / 0</span>
                                    </div>
                                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                        <div id="edit-usage-bar" class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-sky-400" style="width: 0%;"></div>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-3 text-xs leading-5 text-slate-600 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-300">
                                    {{ __('Adjust code, expiry, and platform targeting without leaving the analytics dashboard.') }}
                                </div>
                            </article>
                        </aside>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Identity') }}</p>
                                <label class="mt-4 block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Coupon Code') }}</span>
                                    <input id="edit-code" type="text" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm uppercase text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                                </label>
                            </article>

                            <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Discount') }}</p>
                                <label class="mt-4 block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Discount Value') }}</span>
                                    <input id="edit-discount" type="text" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                                </label>
                            </article>

                            <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Timing') }}</p>
                                <label class="mt-4 block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Expiry') }}</span>
                                    <input id="edit-expiry" type="date" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                                </label>
                            </article>

                            <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Status') }}</p>
                                <label class="mt-4 inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <input id="edit-status-toggle" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900">
                                    {{ __('Active') }}
                                </label>
                            </article>

                            <article class="rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)] md:col-span-2 dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Targeting') }}</p>
                                        <h4 class="mt-1 text-base font-semibold text-slate-950 dark:text-slate-100">{{ __('Platforms') }}</h4>
                                    </div>
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ __('Multi Channel') }}</span>
                                </div>
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                        <input type="checkbox" id="edit-platform-web" class="h-4 w-4 rounded border-slate-300 text-cyan-600 dark:border-slate-600 dark:bg-slate-900">
                                        {{ __('Web') }}
                                    </label>
                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                        <input type="checkbox" id="edit-platform-mobile" class="h-4 w-4 rounded border-slate-300 text-cyan-600 dark:border-slate-600 dark:bg-slate-900">
                                        {{ __('Mobile') }}
                                    </label>
                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                        <input type="checkbox" id="edit-platform-dealer" class="h-4 w-4 rounded border-slate-300 text-cyan-600 dark:border-slate-600 dark:bg-slate-900">
                                        {{ __('Dealer Portal') }}
                                    </label>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="border-t border-slate-200/80 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/60 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Apply the edits to update the current coupon row in the dashboard preview.') }}</p>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" id="edit-modal-cancel" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Cancel') }}</button>
                                <button type="button" id="edit-modal-save" class="rounded-2xl bg-gradient-to-r from-slate-950 to-cyan-800 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_12px_24px_rgba(8,145,178,0.22)] transition hover:from-slate-900 hover:to-cyan-700">{{ __('Save Changes') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>
