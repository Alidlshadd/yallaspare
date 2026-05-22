        <section class="relative overflow-hidden rounded-[2rem] border border-cyan-200/40 bg-[radial-gradient(circle_at_15%_20%,rgba(56,189,248,0.22),transparent_28%),radial-gradient(circle_at_85%_15%,rgba(34,211,238,0.20),transparent_24%),radial-gradient(circle_at_70%_78%,rgba(14,165,233,0.22),transparent_26%),linear-gradient(135deg,#020617_0%,#082f49_48%,#164e63_100%)] p-6 text-white shadow-[0_30px_70px_rgba(15,23,42,0.42)] sm:p-8">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-100/70 to-transparent"></div>
            <div class="pointer-events-none absolute -right-16 top-10 h-56 w-56 rounded-full border border-white/10 bg-cyan-200/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-12 bottom-0 h-48 w-48 rounded-full bg-sky-300/10 blur-3xl"></div>

            <div class="relative grid gap-6 xl:grid-cols-[1.45fr_0.9fr]">
                <div class="space-y-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full border border-cyan-200/30 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-50/90 backdrop-blur">{{ __('Promotion Command Center') }}</span>
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $campaignStateClass }}">{{ $campaignStateLabel }}</span>
                    </div>

                    <div class="max-w-3xl">
                        <h1 class="text-3xl font-bold tracking-[-0.03em] sm:text-5xl">{{ __('Coupon Analytics & Management') }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-cyan-50/78 sm:text-base">
                            {{ __('Manage campaigns from one panel, identify the strongest channel, and control discount flows with more clarity, speed, and confidence.') }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <article class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-100/75">{{ __('Active Campaigns') }}</p>
                            <p class="mt-3 text-3xl font-bold text-white">{{ number_format($activeCoupons) }}</p>
                            <p class="mt-1 text-xs text-cyan-50/70">{{ __('Live coupons currently driving orders') }}</p>
                        </article>
                        <article class="rounded-2xl border border-white/15 bg-slate-950/20 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-100/75">{{ __('Top Channel') }}</p>
                            <p class="mt-3 text-2xl font-bold text-white">{{ $primaryPlatformLabel }}</p>
                            <p class="mt-1 text-xs text-cyan-50/70">{{ $primaryPlatformValue }}% of coupon usage share</p>
                        </article>
                        <article class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-100/75">{{ __('Revenue Impact') }}</p>
                            <p class="mt-3 text-2xl font-bold text-white">{{ number_format($revenueImpact, $currencyDecimals) }} {{ $currencyLabel }}</p>
                            <p class="mt-1 text-xs text-cyan-50/70">{{ __('Estimated GMV touched by discounts') }}</p>
                        </article>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                        <article class="rounded-3xl border border-white/15 bg-slate-950/20 p-5 backdrop-blur-md">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100/75">{{ __('7-Day Pulse') }}</p>
                                    <h2 class="mt-1 text-lg font-semibold text-white">{{ __('Redemption momentum') }}</h2>
                                </div>
                                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-cyan-50/80">{{ number_format($totalRedemptions) }} redemptions</span>
                            </div>

                            <div class="mt-5 flex h-32 items-end gap-2">
                                @foreach ($heroTrendPoints as $index => $point)
                                    <div class="flex flex-1 flex-col items-center gap-2">
                                        <div class="flex h-24 w-full items-end">
                                            <div class="w-full rounded-t-2xl bg-gradient-to-t from-cyan-400 via-sky-300 to-white/90 shadow-[0_8px_24px_rgba(34,211,238,0.35)]" style="height: {{ max(14, (int) round(($point / max($heroTrendMax, 1)) * 100)) }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-cyan-50/55">D{{ $index + 1 }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </article>

                        <article class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-md">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100/75">{{ __('Current Focus') }}</p>
                            <div class="mt-4 space-y-4">
                                <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-cyan-100/75">{{ __('Best Performing Segment') }}</p>
                                    <p class="mt-2 text-xl font-bold text-white">{{ $primaryUsageLabel }}</p>
                                    <p class="mt-1 text-xs text-cyan-50/70">{{ $primaryUsageValue }}% distribution weight</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-cyan-100/75">{{ __('Avg Discount') }}</p>
                                        <p class="mt-2 text-2xl font-bold text-white">{{ $avgDiscount }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-cyan-100/75">{{ __('Schedule') }}</p>
                                        <p class="mt-2 text-sm font-semibold text-white">{{ $campaignWindow }}</p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div class="grid gap-4">
                    <article class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-md">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100/75">{{ __('Quick Actions') }}</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">{{ __('Launch and monitor campaigns faster') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-cyan-50/74">{{ __('Create a new coupon, jump into the management table, and review the active window from one surface. This header now acts as a decision layer, not just a title block.') }}</p>

                        <div class="mt-5 grid gap-3">
                            <a href="{{ route('admin.discounts.coupons.create') }}" id="hero-create-coupon-btn" class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-cyan-950/25 transition hover:bg-cyan-50">
                                {{ __('Create Coupon') }}
                            </a>
                            <a href="#coupon-management" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-slate-950/20 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                                {{ __('Jump to Table') }}
                            </a>
                        </div>
                    </article>

                    <article class="rounded-3xl border border-white/15 bg-slate-950/20 p-5 backdrop-blur-md">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100/75">{{ __('Campaign Snapshot') }}</p>
                                <h3 class="mt-2 text-lg font-semibold text-white">{{ __('Operational Status') }}</h3>
                            </div>
                            <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-cyan-50/80">{{ count($couponRows) }} tracked</span>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span class="text-sm text-cyan-50/72">{{ __('Campaign State') }}</span>
                                <span class="text-sm font-semibold text-white">{{ $campaignStateLabel }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span class="text-sm text-cyan-50/72">{{ __('Usage Leader') }}</span>
                                <span class="text-sm font-semibold text-white">{{ $primaryPlatformLabel }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span class="text-sm text-cyan-50/72">{{ __('Window') }}</span>
                                <span class="text-right text-sm font-semibold text-white">{{ $campaignWindow }}</span>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-3xl border border-cyan-200/20 bg-gradient-to-br from-white/12 to-cyan-300/10 p-5 backdrop-blur-md">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100/75">{{ __('Why It Works Better') }}</p>
                        <ul class="mt-4 space-y-3 text-sm text-cyan-50/78">
                            <li class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3">{{ __('Clear hierarchy: the headline, KPIs, and actions now sit inside a single decision surface.') }}</li>
                            <li class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3">{{ __('The hero now carries data: the area that felt empty now presents a performance-focused summary.') }}</li>
                            <li class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3">{{ __('On mobile it stacks into one column; on desktop it keeps a balanced control-panel layout.') }}</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>
