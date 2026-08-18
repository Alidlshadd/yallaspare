@props(['fitments'])

@php
    use App\Support\Garage;

    // Only rows naming a model can be matched against a vehicle.
    $rows = collect($fitments)->filter(fn ($f) => ($f['model'] ?? '') !== '' && ($f['model_id'] ?? null))->values();

    $vehicle = Garage::get();
    $verdict = Garage::verdict($rows);
    $brandName = (string) ($rows->first()['brand'] ?? '');

    $matched = $verdict['row'];
    $others = $matched
        ? $rows->reject(fn ($f) => $f['model_id'] === $matched['model_id'])->values()
        : $rows;

    // Payload for the picker. Years come from the row itself so the customer is
    // never offered a year this part was never listed for.
    $models = $rows->map(fn ($f) => [
        'id' => $f['model_id'],
        'name' => $f['model'],
        'from' => $f['from'],
        'to' => $f['to'],
        'engine' => $f['engineRaw'] ?: null,
    ])->values();

    $labels = [
        'askModel' => __('Which model do you drive?'),
        'askYear' => __('Which year is your :model?'),
        'result' => __('Result'),
        'hintModel' => __('Only models this part is listed for are shown.'),
        'hintYear' => __('This model has a limited fitment range.'),
        'compatible' => __('Compatible with your vehicle'),
        'notCompatible' => __('Not compatible'),
        'allYears' => __('Fits all years and engine options of this model.'),
        'allYearsShort' => __('all years'),
        'listed' => __('Listed for :range.'),
        'listedOnly' => __('This part is listed for :model :range.'),
    ];
@endphp

<section data-product-compatibility class="rounded-2xl border border-app bg-surface-2 p-5">
    <div class="flex items-center gap-2">
        <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path d="M3 8.5 10 4l7 4.5V16H3z" stroke-linejoin="round" />
            <path d="M8 16v-4h4v4" stroke-linejoin="round" />
        </svg>
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">
            {{ $vehicle ? __('My Garage') : __('Check fitment') }}
        </p>
    </div>

    @if ($rows->isEmpty())
        <p class="mt-3 rounded-xl border border-dashed border-app bg-surface-1 px-3 py-2 text-sm text-muted">
            {{ __('Compatibility details are available on request.') }}
        </p>

    @elseif ($vehicle)
        {{-- A vehicle is saved: open with the answer. --}}
        {{-- Stacked, not two columns: this section sits in the product page's
             narrow right column, and a lg: breakpoint reads the viewport rather
             than that column, so side-by-side collapsed into one word per line. --}}
        <div class="mt-4 flex flex-col gap-4">
            <div class="overflow-hidden rounded-xl border border-app shadow-lg shadow-slate-900/10 dark:shadow-black/40">
                <div class="flex items-center justify-between border-b border-app bg-[linear-gradient(90deg,#0b1330,#16215c)] px-3.5 py-2">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-white/55">{{ __('My Garage') }}</span>
                    <span class="font-mono text-[10px] tracking-[0.12em] text-amber-400">IQ</span>
                </div>
                <div class="bg-surface-1 px-4 py-4">
                    <p class="font-mono text-[10.5px] uppercase tracking-[0.16em] text-muted">{{ $vehicle['brand'] }}</p>
                    <p class="mt-1 text-2xl font-semibold tracking-[-0.03em] text-app">{{ $vehicle['model'] }}</p>

                    @if ($vehicle['year'])
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            <span class="rounded-md border border-app px-2.5 py-1 font-mono text-[11px] text-muted">{{ $vehicle['year'] }}</span>
                        </div>
                    @endif

                    <div class="mt-3.5 flex items-center justify-between gap-3 border-t border-dashed border-app pt-3">
                        <span class="inline-flex items-center gap-1.5 text-[11.5px] text-muted">
                            <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="m3 8.5 3 3 7-7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ __('Saved') }}
                        </span>
                        <form method="POST" action="{{ route('garage.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[12.5px] text-amber-600 underline underline-offset-4 transition hover:text-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:text-amber-400">
                                {{ __('Change vehicle') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3.5">
                @php $fits = $verdict['state'] === 'fits'; @endphp
                <div class="flex items-center gap-3.5 rounded-xl border px-5 py-4 {{ $fits ? 'border-emerald-500/35 bg-emerald-500/10' : 'border-rose-500/35 bg-rose-500/10' }}">
                    <svg class="h-6 w-6 shrink-0 {{ $fits ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                        @if ($fits)
                            <path d="m4.5 12.5 4.5 4.5L19.5 6.5" stroke-linecap="round" stroke-linejoin="round" />
                        @else
                            <path d="m6.5 6.5 11 11M17.5 6.5l-11 11" stroke-linecap="round" />
                        @endif
                    </svg>
                    <div class="min-w-0">
                        <p class="text-[17px] font-semibold tracking-[-0.02em] text-app">
                            {{ $fits
                                ? __('Exact fit for your :model', ['model' => $vehicle['model']])
                                : __('Not compatible with your :model', ['model' => $vehicle['model']]) }}
                        </p>
                        <p class="mt-0.5 text-[13px] text-muted">
                            @if ($fits && $matched && $matched['from'] !== null)
                                {{ $vehicle['year'] ?: $matched['from'] }} — {{ __('matches the listed range of :range', ['range' => $matched['from'].'–'.$matched['to']]) }}
                            @elseif ($fits)
                                {{ __('All model years and engine options.') }}
                            @elseif ($matched)
                                {{ __('This part is listed for :model :range.', ['model' => $vehicle['model'], 'range' => $matched['from'].'–'.$matched['to']]) }}
                            @else
                                {{ __('Check the models below or change your vehicle.') }}
                            @endif
                        </p>
                    </div>
                </div>

                @if ($others->isNotEmpty())
                    <div class="rounded-xl border border-app bg-surface-1 px-5 py-4">
                        <p class="text-[12.5px] text-muted">
                            {{ $matched
                                ? __('Also fits :count other models', ['count' => $others->count()])
                                : __('This part fits :count models.', ['count' => $others->count()]) }}
                        </p>
                        <div class="mt-2.5 flex flex-col">
                            @foreach ($others as $other)
                                <div class="flex items-baseline justify-between gap-4 border-t border-app py-2.5 first:border-t-0 first:pt-0">
                                    <span class="text-[13.5px] font-medium text-app">{{ $other['model'] }}</span>
                                    <span class="whitespace-nowrap font-mono text-[11.5px] text-muted">
                                        {{ $other['from'] !== null ? $other['from'].'–'.$other['to'] : __('all years') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

    @else
        {{-- No vehicle yet: ask, answer, and save it so every later page opens
             with the plate above instead of this. --}}
        <form
            method="POST"
            action="{{ route('garage.store') }}"
            x-data="fitmentPicker"
            data-models='@json($models)'
            data-labels='@json($labels)'
        >
            @csrf
            <input type="hidden" name="vehicle_model_id" :value="pickedId">
            <input type="hidden" name="year" :value="yearValue">

            <div class="mt-4 flex items-center">
                @foreach ([__('Model'), __('Year'), __('Result')] as $i => $stepName)
                    @if ($i > 0)
                        <span class="mx-3 h-px min-w-[16px] flex-1 bg-slate-300/60 dark:bg-slate-700"></span>
                    @endif
                    <span class="flex shrink-0 items-center gap-2.5">
                        <span class="grid h-6 w-6 place-items-center rounded-full border font-mono text-[11px] transition duration-300" :class="stepClass({{ $i }})">{{ $i + 1 }}</span>
                        <span class="whitespace-nowrap text-[12.5px] transition duration-300" :class="stepNameClass({{ $i }})">{{ $stepName }}</span>
                    </span>
                @endforeach
            </div>

            <div class="mt-5">
                {{-- Server-rendered defaults, then Alpine takes over. Without them the
                     whole section is empty until JS runs, and to a crawler. --}}
                <p class="text-[17px] font-semibold tracking-[-0.02em] text-app" x-text="prompt">{{ $labels['askModel'] }}</p>
                <p class="mt-0.5 text-[13px] text-muted" x-text="hint">{{ $labels['hintModel'] }}</p>

                {{-- Looped in Blade rather than x-for so the choices exist in the HTML;
                     the handler takes the id as a plain number, which the CSP build of
                     Alpine can parse. --}}
                <div class="mt-4 grid gap-2.5 sm:grid-cols-2" x-show="onModels" x-cloak>
                    @foreach ($models as $model)
                        <button
                            type="button"
                            @click="pickModelById({{ $model['id'] }})"
                            class="rounded-xl border bg-surface-1 px-4 py-3.5 text-start transition duration-200 hover:shadow-lg hover:shadow-slate-900/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:hover:shadow-black/40"
                            :class="modelClassById({{ $model['id'] }})"
                        >
                            <span class="block text-[14.5px] font-semibold tracking-[-0.012em] text-app">{{ $model['name'] }}</span>
                            <span class="mt-0.5 block font-mono text-[11px] text-muted">
                                {{ $model['from'] !== null ? $model['from'].'–'.$model['to'] : __('all years') }}
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-5" x-show="onYears" x-cloak>
                    <template x-for="y in years" :key="y">
                        <button
                            type="button"
                            @click="pickYear(y)"
                            class="rounded-lg border border-app bg-surface-1 px-2 py-2.5 font-mono text-[13px] text-app transition duration-200 hover:-translate-y-0.5 hover:border-amber-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400"
                            x-text="y"
                        ></button>
                    </template>
                </div>
            </div>

            {{-- Actions sit on their own row: in this narrow column they otherwise
                 squeeze the verdict into one word per line. --}}
            <div class="mt-5 rounded-xl border px-4 py-3.5" :class="statusClass" x-show="onResult" x-cloak>
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" :class="statusIconClass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true" x-show="isFit" x-cloak>
                        <path d="m4.5 12.5 4.5 4.5L19.5 6.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <svg class="mt-0.5 h-5 w-5 shrink-0" :class="statusIconClass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true" x-show="isMiss" x-cloak>
                        <path d="m6.5 6.5 11 11M17.5 6.5l-11 11" stroke-linecap="round" />
                    </svg>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-app" x-text="statusText"></p>
                        <p class="mt-0.5 text-[12.5px] text-muted" x-text="statusSub"></p>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-end gap-4">
                    <button type="button" @click="reset" class="text-[12.5px] text-muted underline underline-offset-4 transition hover:text-app focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                        {{ __('Start over') }}
                    </button>
                    <button type="submit" class="rounded-lg bg-primary px-3.5 py-2 text-[12.5px] font-semibold text-white transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                        {{ __('Save to My Garage') }}
                    </button>
                </div>
            </div>
        </form>
    @endif
</section>
