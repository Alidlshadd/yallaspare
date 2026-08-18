@props(['fitments'])

@php
    $rows = collect($fitments)->filter(fn ($f) => ($f['model'] ?? '') !== '')->values();

    $brandName = (string) ($rows->first()['brand'] ?? '');

    // One row per model, engines collected: the same model can appear several
    // times when a part covers more than one engine option.
    $models = $rows
        ->groupBy('model')
        ->map(function ($group, $model) {
            $bounded = $group->filter(fn ($f) => $f['from'] !== null && $f['to'] !== null);

            return [
                'name' => (string) $model,
                // Widest span across this model's rows; null when any row is unbounded,
                // because one open row already covers every year.
                'from' => $group->contains(fn ($f) => $f['from'] === null) ? null : $bounded->min('from'),
                'to' => $group->contains(fn ($f) => $f['to'] === null) ? null : $bounded->max('to'),
                'engines' => $group->pluck('engineRaw')->filter()->unique()->values(),
            ];
        })
        ->sortBy('name')
        ->values();

    // "Configurations" counts the underlying rows, which is what a fitment
    // catalogue means by it — a model with three engines is three of them.
    $configurations = $rows->count();
@endphp

<section data-product-compatibility class="rounded-2xl border border-app bg-surface-2 p-5">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <svg class="h-3.5 w-3.5 shrink-0 text-amber-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M3 7h14M3 13h14" stroke-linecap="round" />
                    <circle cx="7" cy="7" r="1.6" />
                    <circle cx="13" cy="13" r="1.6" />
                </svg>
                <p class="text-[10.5px] font-semibold uppercase tracking-[0.17em] text-muted">{{ __('Verified fitment') }}</p>
            </div>

            <h2 class="mt-1.5 text-[17px] font-semibold tracking-[-0.022em] text-app">{{ __('Fits these vehicles') }}</h2>

            <p class="mt-0.5 font-mono text-[11.5px] tracking-[0.03em] text-muted">
                <span class="text-app">{{ trans_choice(':count model|:count models', $models->count(), ['count' => $models->count()]) }}</span>
                @if ($configurations > $models->count())
                    · <span class="text-app">{{ trans_choice(':count configuration|:count configurations', $configurations, ['count' => $configurations]) }}</span>
                @endif
                @if ($brandName !== '')
                    · {{ $brandName }}
                @endif
            </p>
        </div>
    </div>

    {{-- A fitment chart, not an HTML table dropped on the page: three aligned
         columns, tabular figures so the year ranges line up down the column. --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-app">
        <div class="grid grid-cols-[1fr_88px] gap-3 border-b border-app bg-surface-1 px-3.5 py-2 font-mono text-[9.5px] uppercase tracking-[0.16em] text-muted sm:grid-cols-[1fr_96px_86px]">
            <span>{{ __('Model') }}</span>
            <span class="text-end">{{ __('Years') }}</span>
            <span class="hidden text-end sm:block">{{ __('Engine') }}</span>
        </div>

        @foreach ($models as $model)
            <div class="group relative grid grid-cols-[1fr_88px] items-center gap-3 border-t border-app px-3.5 py-3 transition duration-200 first:border-t-0 hover:bg-surface-1 sm:grid-cols-[1fr_96px_86px]">
                {{-- Index mark: lights on hover, the one moving part in the section. --}}
                <span class="pointer-events-none absolute inset-y-2.5 start-0 w-0.5 rounded bg-transparent transition duration-200 group-hover:bg-amber-400" aria-hidden="true"></span>

                <span class="min-w-0 truncate text-[14.5px] font-medium tracking-[-0.014em] text-app">{{ $model['name'] }}</span>

                <span class="text-end font-mono text-[12.5px] tabular-nums {{ $model['from'] !== null ? 'text-app' : 'text-muted' }}">
                    {{ $model['from'] !== null ? $model['from'].'–'.$model['to'] : __('all years') }}
                </span>

                {{-- Engines share the model's line on desktop; below sm they drop
                     under the name so the column does not squeeze the year. --}}
                <span class="hidden text-end font-mono text-[11.5px] text-muted sm:block">
                    @if ($model['engines']->isEmpty())
                        {{ __('all') }}
                    @elseif ($model['engines']->count() === 1)
                        {{ $model['engines']->first() }}
                    @else
                        {{ trans_choice(':count engine|:count engines', $model['engines']->count(), ['count' => $model['engines']->count()]) }}
                    @endif
                </span>

                @if ($model['engines']->isNotEmpty())
                    <span class="col-span-2 -mt-1 flex flex-wrap gap-1 sm:hidden">
                        @foreach ($model['engines'] as $engine)
                            <span class="rounded border border-app bg-surface-1 px-1.5 py-0.5 font-mono text-[10px] text-muted">{{ $engine }}</span>
                        @endforeach
                    </span>
                @endif
            </div>
        @endforeach

        <p class="border-t border-app bg-surface-1 px-3.5 py-2.5 text-[12.5px] text-muted">
            {{ __('Not sure? Send us your chassis number and we will confirm.') }}
        </p>
    </div>
</section>
