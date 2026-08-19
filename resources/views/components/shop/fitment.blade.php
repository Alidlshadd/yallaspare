@props(['fitments'])

@php
    $rows = collect($fitments)->filter(fn ($fitment) => ($fitment['model'] ?? '') !== '')->values();
    $brandName = (string) ($rows->first()['brand'] ?? '');
    $families = $rows
        ->groupBy(fn ($fitment) => (string) ($fitment['family'] ?? $fitment['model']))
        ->map(function ($familyRows, $familyName) {
            return [
                'name' => (string) $familyName,
                'variants' => $familyRows
                    ->groupBy('model')
                    ->map(function ($variantRows, $variantName) {
                        $boundedFrom = $variantRows->pluck('from')->filter(fn ($year) => $year !== null);
                        $boundedTo = $variantRows->pluck('to')->filter(fn ($year) => $year !== null);

                        return [
                            'name' => (string) $variantName,
                            'from' => $variantRows->contains(fn ($row) => $row['from'] === null) ? null : $boundedFrom->min(),
                            'to' => $variantRows->contains(fn ($row) => $row['to'] === null) ? null : $boundedTo->max(),
                            'engines' => $variantRows->pluck('engineRaw')->filter()->unique()->values(),
                            'image' => $variantRows->pluck('image')->filter()->first(),
                        ];
                    })
                    ->sortBy('name')
                    ->values(),
            ];
        })
        ->sortBy('name')
        ->values();
    $variantCount = $families->sum(fn ($family) => $family['variants']->count());
@endphp

<section data-product-compatibility class="rounded-2xl border border-app bg-surface-2 p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[10.5px] font-semibold uppercase tracking-[0.17em] text-accent-ink dark:text-accent">{{ __('Verified fitment') }}</p>
            <h2 class="mt-1.5 text-[17px] font-semibold tracking-[-0.022em] text-app">{{ __('Fits these vehicles') }}</h2>
            <p class="mt-1 font-mono text-[11px] text-muted">{{ $families->count() }} {{ __('families') }} · {{ $variantCount }} {{ __('variants') }}@if($brandName !== '') · {{ $brandName }}@endif</p>
        </div>
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-navy text-accent dark:bg-accent dark:text-navy"><i class="fas fa-car-side text-xs"></i></span>
    </div>

    <div class="mt-4 space-y-3">
        @foreach($families as $family)
            <article class="overflow-hidden rounded-xl border border-app bg-surface-1">
                <div class="flex items-center justify-between border-b border-app px-3.5 py-2.5">
                    <h3 class="text-[12px] font-black uppercase tracking-[.12em] text-app">{{ $family['name'] }}</h3>
                    <span class="font-mono text-[10px] text-muted">{{ $family['variants']->count() }} {{ __('variants') }}</span>
                </div>
                <div class="divide-y divide-slate-200/70 dark:divide-slate-700/70">
                    @foreach($family['variants'] as $variant)
                        <div class="flex items-center gap-3 px-3.5 py-3">
                            @if($variant['image'])
                                <img src="{{ $variant['image'] }}" alt="{{ $variant['name'] }}" class="h-11 w-14 shrink-0 rounded-lg border border-app bg-white object-cover dark:bg-slate-950">
                            @else
                                <span class="grid h-11 w-14 shrink-0 place-items-center rounded-lg border border-dashed border-app text-muted"><i class="fas fa-car-side text-sm"></i></span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h4 class="truncate text-[14px] font-semibold text-app">{{ $variant['name'] }}</h4>
                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-[11px] text-muted">
                                    <span>
                                        @if($variant['from'] !== null && $variant['to'] !== null)
                                            {{ $variant['from'] === $variant['to'] ? $variant['from'] : $variant['from'].'–'.$variant['to'] }}
                                        @elseif($variant['from'] !== null)
                                            {{ $variant['from'].'+' }}
                                        @elseif($variant['to'] !== null)
                                            {{ '≤ '.$variant['to'] }}
                                        @else
                                            {{ __('Any year') }}
                                        @endif
                                    </span>
                                    @if($variant['engines']->isNotEmpty())
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $variant['engines']->join(' · ') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>

    <p class="mt-3 rounded-xl border border-dashed border-app bg-surface-1 px-3.5 py-2.5 text-[12px] text-muted">{{ __('Not sure? Send us your chassis number and we will confirm.') }}</p>
</section>
