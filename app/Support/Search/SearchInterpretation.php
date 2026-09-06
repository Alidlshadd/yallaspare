<?php

namespace App\Support\Search;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Support\DbSchema;
use App\Support\SqlSafe;
use App\Support\VehicleFuelType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What the catalogue recognises in what the shopper typed.
 *
 * The search itself already answers "which products match" — this answers the
 * separate question a suggestion panel has to show: *why*. "ssangyong rexton
 * 2024" is a marque the catalogue knows, a car it sells parts for and a model
 * year, and saying so out loud is what turns a list of rows into an answer.
 *
 * Nothing here narrows or re-runs the product search. It reads the vehicle
 * tables only, in two bounded queries, and hands back the names and ids that
 * the rest of the response builds its chips, vehicle rows and links from.
 */
final class SearchInterpretation
{
    /** Enough rows to rank a handful of same-named variants against. */
    private const VARIANT_CANDIDATES = 8;

    /** A displacement is a small number with a decimal point: 1.6, 2.0. */
    private const MAX_DISPLACEMENT = 10.0;

    /**
     * @param  Collection<int, VehicleBrand>  $brands  Best match first.
     * @param  Collection<int, VehicleModel>  $variants  Best match first.
     */
    private function __construct(
        public readonly SearchQuery $search,
        public readonly ?VehicleBrand $brand,
        public readonly Collection $brands,
        public readonly Collection $variants,
        public readonly ?int $year,
        public readonly ?string $engine,
        public readonly ?string $fuel,
    ) {}

    public static function of(SearchQuery $search, ?VehicleContext $context = null): self
    {
        $context ??= new VehicleContext;

        if ($search->isEmpty()) {
            return new self($search, null, collect(), collect(), null, null, null);
        }

        $year = $search->years()[0] ?? null;
        $fuel = self::detectFuel($search);
        $engine = self::detectEngine($search);
        $brands = self::resolveBrands($search);
        $brand = $brands->first();
        $variants = self::resolveVariants($search, $brand, $year);

        $context->addBrands($brands);
        $context->addModels($variants);

        return new self($search, $brand, $brands, $variants, $year, $engine, $fuel);
    }

    /**
     * The one car this query names, or nothing.
     *
     * A variant id is the narrowest filter the shop has, and putting the wrong
     * one behind a link is worse than putting no link there: "camry filter"
     * would quietly become "the parts for the 2018 Camry" and the shopper would
     * never learn that the other generations were dropped on their behalf.
     *
     * So a variant is claimed only when the candidates come down to exactly
     * one — by the year the shopper typed, by the engine or fuel they typed, or
     * because only one car answers to the name at all. Several candidates is an
     * answer too: it means the family, and the family is what the panel offers.
     *
     * Reads the loaded candidates; queries nothing.
     */
    public function variant(): ?VehicleModel
    {
        if ($this->variants->isEmpty()) {
            return null;
        }

        // The year was already applied when the candidates were fetched, so
        // what is left here is the engine and the fuel.
        $narrowed = $this->narrowedByEngine();

        return $narrowed->count() === 1 ? $narrowed->first() : null;
    }

    /**
     * @return Collection<int, VehicleModel>
     */
    private function narrowedByEngine(): Collection
    {
        if ($this->engine === null && $this->fuel === null) {
            return $this->variants;
        }

        $matching = $this->variants->filter(function (VehicleModel $model): bool {
            foreach (self::offeredEngines($model) as $engine) {
                $sizeMatches = $this->engine === null
                    || VehicleFuelType::displacement($engine->engine_size, $engine->fuel_type) === $this->engine;
                $fuelMatches = $this->fuel === null || (string) $engine->fuel_type === $this->fuel;

                if ($sizeMatches && $fuelMatches) {
                    return true;
                }
            }

            return false;
        });

        // An engine nothing on record answers to narrows nothing. Falling back
        // to every candidate keeps the panel honest rather than silently
        // emptying the vehicle group over a detail no car recorded.
        return $matching->isEmpty() ? $this->variants : $matching->values();
    }

    public function hasVehicle(): bool
    {
        return $this->variants->isNotEmpty();
    }

    /**
     * The name a shopper would recognise: the family the matched variants
     * share, falling back to the variant's own name.
     */
    public function vehicleLabel(?string $locale = null): ?string
    {
        $first = $this->variants->first();

        if (! $first instanceof VehicleModel) {
            return null;
        }

        $family = $first->relationLoaded('family') ? $first->family : null;

        if ($family !== null && $this->variantsShareFamily()) {
            return $family->localizedName($locale);
        }

        return $first->localizedName($locale);
    }

    /**
     * How the shop listing should be asked for "this car's parts".
     *
     * The listing filters by a variant id or a variant *name*, and neither
     * addresses a family: a family holding Rexton, Rexton II, Rexton W and
     * Rexton G4 has no id the listing understands, and `model=Rexton` matches
     * only the one variant spelled exactly that way — three quarters of the
     * family silently dropped.
     *
     * So one candidate is filtered exactly, by id, and several are browsed by
     * the family's name through the search the listing already runs. Both are
     * real parameters; neither narrows further than the query did.
     *
     * @return array<string, mixed>
     */
    public function vehicleFilters(?string $locale = null): array
    {
        if ($this->variants->isEmpty()) {
            return [];
        }

        $brand = $this->brandFilterValue();

        if ($this->variants->count() === 1) {
            return array_filter([
                'brand' => $brand,
                'model' => (int) $this->variants->first()->id,
            ]);
        }

        return array_filter([
            'brand' => $brand,
            'search' => $this->vehicleLabel($locale),
        ]);
    }

    public function familyId(): ?int
    {
        $first = $this->variants->first();

        if (! $first instanceof VehicleModel || ! $this->variantsShareFamily()) {
            return null;
        }

        return $first->vehicle_model_family_id !== null ? (int) $first->vehicle_model_family_id : null;
    }

    /**
     * The vehicle brand name, as the listing's `brand` parameter expects it.
     */
    public function brandFilterValue(): ?string
    {
        if ($this->brand instanceof VehicleBrand) {
            return (string) $this->brand->name;
        }

        $first = $this->variants->first();

        if (! $first instanceof VehicleModel || ! $first->relationLoaded('brand')) {
            return null;
        }

        // Only when every candidate agrees. A name shared across two marques
        // would otherwise pick one of them and hide the other's parts.
        $brandIds = $this->variants->map(fn (VehicleModel $model): int => (int) $model->vehicle_brand_id)->unique();

        return $brandIds->count() === 1 && $first->brand !== null ? (string) $first->brand->name : null;
    }

    /**
     * The ids of every variant the query points at, for picking the fitment
     * row a product should be described by.
     *
     * @return list<int>
     */
    public function variantIds(): array
    {
        return $this->variants->map(fn (VehicleModel $model): int => (int) $model->id)->all();
    }

    /**
     * The typed words the vehicle tables actually answered.
     *
     * Not every word handed to those tables came back with something: "camry
     * filter" asks about both and only "camry" is a car. Knowing which is which
     * is what lets a caption say the query named a part *and* a vehicle instead
     * of guessing that it named one or the other.
     *
     * @return list<string>
     */
    public function vehicleWordsUsed(): array
    {
        $names = [];

        foreach ($this->variants as $model) {
            $names[] = mb_strtolower((string) $model->name);
            $names[] = mb_strtolower((string) $model->name_en);

            $family = $model->relationLoaded('family') ? $model->family : null;

            if ($family !== null) {
                $names[] = mb_strtolower((string) $family->name);
                $names[] = mb_strtolower((string) $family->name_en);
            }
        }

        foreach ($this->brands as $brand) {
            $names[] = mb_strtolower((string) $brand->name);
        }

        $names = array_values(array_filter($names, static fn (string $name): bool => $name !== ''));
        $used = [];

        foreach ($this->search->tokens as $token) {
            if ($token->year !== null) {
                continue;
            }

            foreach ($token->variants as $spelling) {
                $spelling = mb_strtolower($spelling);

                foreach ($names as $name) {
                    // The same containment the vehicle query matched on, so a
                    // word only counts as a car word if a car really answered.
                    if ($spelling !== '' && str_contains($name, $spelling)) {
                        $used[] = $token->text;

                        continue 3;
                    }
                }
            }

            // An engine or a fuel is a vehicle word too, even though no name
            // holds it.
            if (VehicleFuelType::parse($token->text)['fuel_type'] !== null
                || ($this->engine !== null && rtrim($this->engine, '0') === rtrim(str_replace(',', '.', $token->text), '0'))) {
                $used[] = $token->text;
            }
        }

        return array_values(array_unique($used));
    }

    /**
     * The query with the year token taken out, for "search without the year".
     */
    public function queryWithoutYear(): ?string
    {
        if ($this->year === null) {
            return null;
        }

        $words = array_values(array_filter(
            array_map(
                static fn (Token $token): string => $token->year === null ? $token->text : '',
                $this->search->tokens
            ),
            static fn (string $word): bool => $word !== '',
        ));

        return $words === [] ? null : implode(' ', $words);
    }

    private function variantsShareFamily(): bool
    {
        $familyIds = $this->variants
            ->map(fn (VehicleModel $model): ?int => $model->vehicle_model_family_id !== null
                ? (int) $model->vehicle_model_family_id
                : null)
            ->unique();

        return $familyIds->count() === 1 && $familyIds->first() !== null;
    }

    /**
     * "petrol", "diesel" — read through the same parser the admin form uses, so
     * "gasoline" and "benzin" are understood exactly where they already are.
     */
    private static function detectFuel(SearchQuery $search): ?string
    {
        foreach ($search->tokens as $token) {
            if ($token->year !== null) {
                continue;
            }

            $parsed = VehicleFuelType::parse($token->text);

            if ($parsed['fuel_type'] !== null) {
                return $parsed['fuel_type'];
            }
        }

        return null;
    }

    /**
     * "1.6" — a displacement, not a year and not a part number. The decimal
     * point is what makes it one: a bare "16" is a trim, a quantity or noise.
     */
    private static function detectEngine(SearchQuery $search): ?string
    {
        foreach ($search->tokens as $token) {
            if ($token->year !== null) {
                continue;
            }

            if (preg_match('/^\d{1,2}[.,]\d$/', $token->text) !== 1) {
                continue;
            }

            $size = (float) str_replace(',', '.', $token->text);

            if ($size > 0 && $size < self::MAX_DISPLACEMENT) {
                return VehicleFuelType::displacement($size, VehicleFuelType::PETROL);
            }
        }

        return null;
    }

    /**
     * The marques the query names, the one it names most exactly first.
     *
     * @return Collection<int, VehicleBrand>
     */
    private static function resolveBrands(SearchQuery $search): Collection
    {
        if (! DbSchema::hasTable('vehicle_brands')) {
            return collect();
        }

        $words = self::vehicleWords($search);

        if ($words === []) {
            return collect();
        }

        /** @var Collection<int, VehicleBrand> $brands */
        $brands = VehicleBrand::query()
            ->select(['id', 'name', 'slug'])
            ->where(function (Builder $query) use ($words): void {
                $first = true;

                foreach ($words as $word) {
                    if ($first) {
                        SqlSafe::whereLike($query, 'name', $word);
                        $first = false;
                    } else {
                        SqlSafe::orWhereLike($query, 'name', $word);
                    }
                }
            })
            ->orderBy('name')
            ->limit(5)
            ->get();

        // A word that *is* the marque beats one that merely appears inside it.
        return $brands
            ->sortByDesc(fn (VehicleBrand $brand): int => in_array(mb_strtolower((string) $brand->name), $words, true) ? 1 : 0)
            ->values();
    }

    /**
     * @return Collection<int, VehicleModel>
     */
    private static function resolveVariants(SearchQuery $search, ?VehicleBrand $brand, ?int $year): Collection
    {
        if (! DbSchema::hasTable('vehicle_models')) {
            return collect();
        }

        $words = self::vehicleWords($search);

        if ($words === []) {
            return collect();
        }

        // No eager loads: the marque, the family and the engines are filled in
        // by the VehicleContext, together with the cars the fitment rows point
        // at, so each of those tables is read once for the whole request.
        /** @var Collection<int, VehicleModel> $variants */
        $variants = VehicleModel::query()
            ->select([
                'id', 'name', 'name_en', 'name_ar', 'name_ku', 'slug',
                'vehicle_brand_id', 'vehicle_model_family_id',
                'production_start_year', 'production_end_year',
            ])
            ->where(fn (Builder $query) => self::matchVariantNames($query, $words))
            // A marque named in the same query is a constraint, not a hint:
            // "kgm rexton" must not offer a Rexton from another maker.
            ->when(
                $brand instanceof VehicleBrand,
                fn (Builder $query) => $query->where('vehicle_brand_id', $brand->id)
            )
            // The generation the shopper actually named. A car built 2013-2017
            // is not an answer to "rexton 2024", and offering it as one is how
            // a suggestion panel sends someone to the wrong parts.
            ->when($year !== null, function (Builder $query) use ($year): void {
                $query->where(function (Builder $years) use ($year): void {
                    $years->whereNull('production_start_year')->orWhere('production_start_year', '<=', $year);
                })->where(function (Builder $years) use ($year): void {
                    $years->whereNull('production_end_year')->orWhere('production_end_year', '>=', $year);
                });
            })
            ->orderByDesc('production_start_year')
            ->orderBy('name')
            ->limit(self::VARIANT_CANDIDATES)
            ->get();

        if ($variants->isEmpty()) {
            return $variants;
        }

        // How much of the query each variant actually accounts for. Ranking in
        // PHP over at most eight loaded rows costs nothing and keeps the SQL a
        // plain filter rather than a scoring expression.
        return $variants
            ->sortByDesc(fn (VehicleModel $model): int => self::wordsAnswered($model, $words))
            ->values();
    }

    /**
     * @param  list<string>  $words
     */
    private static function matchVariantNames(Builder $query, array $words): void
    {
        $first = true;

        foreach ($words as $word) {
            foreach (['name', 'name_en', 'name_ar', 'name_ku'] as $column) {
                if ($column !== 'name' && ! DbSchema::hasColumn('vehicle_models', $column)) {
                    continue;
                }

                if ($first) {
                    SqlSafe::whereLike($query, $column, $word);
                    $first = false;
                } else {
                    SqlSafe::orWhereLike($query, $column, $word);
                }
            }
        }

        if (! DbSchema::hasTable('vehicle_model_families')) {
            return;
        }

        // "Rexton" may be the family rather than the variant, and the variants
        // under it are often named for their generation instead.
        $query->orWhereHas('family', function (Builder $family) use ($words): void {
            $family->where(function (Builder $names) use ($words): void {
                $first = true;

                foreach ($words as $word) {
                    foreach (['name', 'name_en', 'name_ar', 'name_ku'] as $column) {
                        if ($column !== 'name' && ! DbSchema::hasColumn('vehicle_model_families', $column)) {
                            continue;
                        }

                        if ($first) {
                            SqlSafe::whereLike($names, $column, $word);
                            $first = false;
                        } else {
                            SqlSafe::orWhereLike($names, $column, $word);
                        }
                    }
                }
            });
        });
    }

    /**
     * @param  list<string>  $words
     */
    private static function wordsAnswered(VehicleModel $model, array $words): int
    {
        // The variant's own names only. The family is not loaded yet at this
        // point, and it would not separate the candidates anyway: every one of
        // them is here *because* it or its family answered to the word.
        $haystack = mb_strtolower(implode(' ', array_filter([
            (string) $model->name,
            (string) $model->name_en,
        ])));

        $answered = 0;

        foreach ($words as $word) {
            if ($word !== '' && str_contains($haystack, $word)) {
                $answered++;
            }
        }

        return $answered;
    }

    /**
     * The words worth asking the vehicle tables about: every spelling of every
     * token that is not a year and not a part number.
     *
     * @return list<string>
     */
    private static function vehicleWords(SearchQuery $search): array
    {
        $words = [];

        foreach ($search->tokens as $token) {
            if ($token->year !== null || $token->looksLikePartNumber()) {
                continue;
            }

            foreach ($token->variants as $variant) {
                $variant = mb_strtolower(trim($variant));

                if (mb_strlen($variant) >= 2 && ! in_array($variant, $words, true)) {
                    $words[] = $variant;
                }
            }
        }

        return $words;
    }

    /**
     * How many distinct live products a set of variants is recorded as fitting.
     *
     * One grouped query for the whole panel rather than a count per row: the
     * suggestion list is a hot path, and a count per suggestion is exactly the
     * N+1 this endpoint cannot afford.
     *
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    public static function productCountsForVariants(array $variantIds): array
    {
        if ($variantIds === [] || ! DbSchema::hasTable('product_vehicle_fitments')) {
            return [];
        }

        return DB::table('product_vehicle_fitments')
            ->join('products', 'products.id', '=', 'product_vehicle_fitments.product_id')
            ->whereIn('product_vehicle_fitments.vehicle_model_id', $variantIds)
            ->where('products.is_active', true)
            ->groupBy('product_vehicle_fitments.vehicle_model_id')
            ->selectRaw('product_vehicle_fitments.vehicle_model_id as variant_id, COUNT(DISTINCT products.id) as total')
            ->pluck('total', 'variant_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * The same count, per vehicle marque.
     *
     * @param  list<int>  $brandIds
     * @return array<int, int>
     */
    public static function productCountsForBrands(array $brandIds): array
    {
        if ($brandIds === [] || ! DbSchema::hasTable('product_vehicle_fitments')) {
            return [];
        }

        return DB::table('product_vehicle_fitments')
            ->join('products', 'products.id', '=', 'product_vehicle_fitments.product_id')
            ->whereIn('product_vehicle_fitments.vehicle_brand_id', $brandIds)
            ->where('products.is_active', true)
            ->groupBy('product_vehicle_fitments.vehicle_brand_id')
            ->selectRaw('product_vehicle_fitments.vehicle_brand_id as brand_id, COUNT(DISTINCT products.id) as total')
            ->pluck('total', 'brand_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * The engines a customer is offered for this car.
     *
     * The same rule the vehicle finder and the fitment board apply: an engine
     * the shop stocks no parts for is not put in front of a shopper. The record
     * keeps it either way — one config line brings it back.
     *
     * @return Collection<int, VehicleModelEngineType>
     */
    public static function offeredEngines(VehicleModel $model): Collection
    {
        if (! $model->relationLoaded('engineTypes')) {
            return collect();
        }

        return $model->engineTypes
            ->filter(static fn (VehicleModelEngineType $engine): bool => $engine->isOfferedInStorefront())
            ->values();
    }

    /**
     * The engine on a variant that answers the fuel the shopper asked for.
     */
    public static function engineMatchingFuel(VehicleModel $model, ?string $fuel): ?VehicleModelEngineType
    {
        $engines = self::offeredEngines($model);

        if ($fuel !== null) {
            return $engines->first(
                static fn (VehicleModelEngineType $engine): bool => (string) $engine->fuel_type === $fuel
            );
        }

        // No fuel asked for: only speak for the car when there is nothing to
        // choose between.
        return $engines->count() === 1 ? $engines->first() : null;
    }
}
