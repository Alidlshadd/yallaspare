        <section class="relative overflow-hidden rounded-[2rem] border border-emerald-200/40 bg-[radial-gradient(circle_at_14%_20%,rgba(74,222,128,0.18),transparent_26%),radial-gradient(circle_at_84%_14%,rgba(16,185,129,0.18),transparent_24%),radial-gradient(circle_at_76%_80%,rgba(45,212,191,0.14),transparent_28%),linear-gradient(135deg,#020617_0%,#052e2b_42%,#14532d_100%)] p-6 text-white shadow-[0_30px_70px_rgba(15,23,42,0.42)] sm:p-8">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-100/70 to-transparent"></div>
            <div class="pointer-events-none absolute -right-16 top-10 h-56 w-56 rounded-full border border-white/10 bg-emerald-200/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-12 bottom-0 h-48 w-48 rounded-full bg-cyan-300/10 blur-3xl"></div>

            <div class="relative grid gap-6 xl:grid-cols-[1.35fr_0.9fr]">
                <div class="space-y-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full border border-emerald-200/30 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-50/90 backdrop-blur">{{ __('Discount Builder') }}</span>
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $activationClass }}">{{ $activationLabel }}</span>
                    </div>

                    <div class="max-w-3xl">
                        <h1 class="text-3xl font-bold tracking-[-0.03em] sm:text-5xl">{{ $builderTitle }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-50/78 sm:text-base">
                            {{ __('Create a discount in a simpler flow: set the value, choose when it runs, and decide where it applies.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.discounts.edit') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                            {{ __('Coupon Management') }}
                        </a>
                        <a href="#discount-rule-form" class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-50">
                            {{ $builderTitle }}
                        </a>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <article class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Rule Status') }}</p>
                            <p class="mt-3 text-3xl font-bold text-white">{{ $discountsEnabled ? 'Live' : 'Draft' }}</p>
                            <p class="mt-1 text-xs text-emerald-50/70">{{ __('Activation controlled from this page') }}</p>
                        </article>
                        <article class="rounded-2xl border border-white/15 bg-slate-950/20 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Rule Type') }}</p>
                            <p class="mt-3 text-2xl font-bold text-white">{{ ucfirst($discountType) }}</p>
                            <p class="mt-1 text-xs text-emerald-50/70">{{ $discountValue }}{{ $discountType === 'percent' ? '%' : '' }} configured</p>
                        </article>
                        <article class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Target Scope') }}</p>
                            <p class="mt-3 text-2xl font-bold text-white">{{ $scopeLabel }}</p>
                            <p class="mt-1 text-xs text-emerald-50/70">{{ $selectedCount }} selected targets</p>
                        </article>
                    </div>

                    <article class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-md">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Quick Start') }}</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('1. Basic Info') }}</p>
                                <p class="mt-2 text-sm font-semibold text-white">{{ __('Set label, type, and discount value.') }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('2. Schedule') }}</p>
                                <p class="mt-2 text-sm font-semibold text-white">{{ __('Choose when the rule starts and ends.') }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('3. Scope') }}</p>
                                <p class="mt-2 text-sm font-semibold text-white">{{ __('Apply it storewide or to a targeted group.') }}</p>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="grid gap-4">
                    <article class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-md">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Current Setup') }}</p>
                        <div class="mt-4 space-y-4">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('Discount Label') }}</p>
                                <p class="mt-2 text-xl font-bold text-white">{{ $discountLabel !== '' ? $discountLabel : 'No label set' }}</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('Starts') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-white">{{ $discountStartsAt !== '' ? $discountStartsAt : 'Immediate' }}</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('Ends') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-white">{{ $discountEndsAt !== '' ? $discountEndsAt : 'Open-ended' }}</p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
