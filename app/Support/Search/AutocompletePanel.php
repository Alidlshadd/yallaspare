<?php

namespace App\Support\Search;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Models\VehicleModelFamily;
use App\Support\DbSchema;
use App\Support\SqlSafe;
use App\Support\VehicleFuelType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The suggestion panel, as data.
 *
 * The search already knows how to find things. What it never said out loud was
 * *what it understood* — that "ssangyong rexton 2024" is a marque, a car and a
 * model year — or *why* a given part came back. A shopper who cannot see either
 * has to take a flat list of names on faith, and the one thing a parts shop
 * cannot ask for is faith about whether a part fits.
 *
 * So the response carries four groups, an interpretation, and a reason on every
 * row. None of it re-runs or second-guesses the search: the products come from
 * the same `matchingSearchTerm` scope and the same relevance ordering the shop
 * listing uses, so a suggestion and the page it leads to can never disagree.
 *
 * Every URL is built here from a named route and the listing's own parameters.
 * The panel never assembles a link out of strings, and never sends a filter the
 * listing does not have.
 */
final class AutocompletePanel
{
    public const PRODUCT_LIMIT = 5;

    public const VEHICLE_LIMIT = 4;

    public const BRAND_LIMIT = 3;

    public const CATEGORY_LIMIT = 3;

    private function __construct(
        private readonly SearchQuery $search,
        private readonly SearchInterpretation $interpretation,
        private readonly VehicleContext $vehicles,
        private readonly ?User $viewer,
        private readonly string $currency,
        private readonly int $globalLowStockThreshold,
    ) {}

    /**
     * An answered panel for a query worth answering.
     */
    public static function for(string $term, ?User $viewer = null): self
    {
        $search = SearchQuery::parse($term);
        $vehicles = new VehicleContext;

        return new self(
            $search,
            SearchInterpretation::of($search, $vehicles),
            $vehicles,
            $viewer,
            (string) Setting::getValue('currency_code', 'IQD'),
            (int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)),
        );
    }

    /**
     * The structured half of the response.
     *
     * `groups` always carries all four keys and an empty group is an empty
     * array, never a missing one: the panel decides what to hide, and it can
     * only do that from a shape it can rely on.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Category>  $categories
     * @return array<string, mixed>
     */
    public function toArray(Collection $products, Collection $categories, ?int $totalProducts): array
    {
        $shown = $products->take(self::PRODUCT_LIMIT);

        // Every car this answer will name — the ones the query resolved to and
        // the ones behind the fitment rows — read from their tables once,
        // before a single row is rendered.
        $this->vehicles->needModels(
            $shown->flatMap(fn (Product $product): array => $product->relationLoaded('vehicleFitments')
                ? $product->vehicleFitments->pluck('vehicle_model_id')->all()
                : [])
        );
        $this->vehicles->needBrands(
            $shown->flatMap(fn (Product $product): array => $product->relationLoaded('vehicleFitments')
                ? $product->vehicleFitments->pluck('vehicle_brand_id')->all()
                : [])
        );
        $this->vehicles->hydrate();

        $rows = $this->productRows($shown, $categories);

        return [
            'interpreted' => $this->interpreted(),
            'correction' => $this->correction($rows),
            'groups' => [
                'products' => $rows,
                'vehicles' => $this->vehicleRows(),
                'brands' => $this->brandRows(),
                'categories' => $this->categoryRows($categories),
            ],
            'meta' => $this->meta($rows, $totalProducts),
        ];
    }

    /**
     * The same shape with nothing in it, for a query too short to answer.
     *
     * A panel that renders from a response whose keys come and go has to guard
     * every read; giving it one shape means it never has to.
     *
     * @return array<string, mixed>
     */
    public function emptyStructure(): array
    {
        return [
            'interpreted' => [
                'brand' => null,
                'vehicle' => null,
                'variant' => null,
                'year' => null,
                'engine' => null,
                'fuel' => null,
            ],
            'correction' => null,
            'groups' => [
                'products' => [],
                'vehicles' => [],
                'brands' => [],
                'categories' => [],
            ],
            'meta' => [
                'total_products' => 0,
                'total_products_label' => null,
                'view_all_url' => route('shop.index'),
                'has_exact_matches' => false,
                'without_year_query' => null,
                'without_year_url' => null,
            ],
        ];
    }

    /**
     * The products, ordered exactly as the shop listing orders them.
     *
     * One extra eager load over what the old endpoint fetched — the fitment
     * rows — because a suggestion that cannot say what a part fits is the thing
     * this endpoint exists to stop being. One query for the whole page, and the
     * cars those rows point at are fetched with everything else the answer
     * needs rather than behind this relation.
     *
     * @return Builder<Product>
     */
    public function productQuery(): Builder
    {
        $relations = ['images'];

        if (DbSchema::hasTable('product_vehicle_fitments')) {
            // Ids only: which car each row points at is looked up once, for
            // every product at once, rather than per relation per product.
            $relations['vehicleFitments'] = function ($query): void {
                $query
                    ->select(['id', 'product_id', 'vehicle_brand_id', 'vehicle_model_id', 'year_from', 'year_to', 'engine'])
                    ->orderBy('year_from')
                    ->orderBy('id');
            };
        }

        return Product::query()
            ->with($relations)
            ->where('is_active', true)
            // The same definition the shop listing uses, so a suggestion list
            // can never disagree with the results page it leads to.
            ->matchingSearchTerm($this->search->normalized)
            ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END')
            // The same ladder the results page uses, so a suggestion and the
            // page it leads to cannot disagree about what the best answer is.
            ->orderBySearchRelevance($this->search->normalized)
            ->latest('id');
    }

    /**
     * The categories, counted once for the legacy field and once for the group.
     *
     * Two aggregates on one query rather than two queries: the legacy count has
     * always meant "every product filed here" and changing it would quietly
     * move a number other clients read, while the panel wants the count a
     * shopper will actually land on.
     *
     * @return Builder<Category>
     */
    public function categoryQuery(): Builder
    {
        return Category::query()
            ->where(function (Builder $query): void {
                $first = true;

                foreach ($this->search->tokens as $token) {
                    foreach ($token->variants as $variant) {
                        foreach (['name_en', 'name_ar', 'name_ku'] as $column) {
                            if ($first) {
                                SqlSafe::whereLike($query, $column, $variant);
                                $first = false;
                            } else {
                                SqlSafe::orWhereLike($query, $column, $variant);
                            }
                        }
                    }
                }
            })
            ->withCount([
                'products',
                'products as active_product_count' => fn (Builder $query) => $query->where('is_active', true),
            ])
            ->orderByDesc('products_count');
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Category>  $categories
     * @return list<array<string, mixed>>
     */
    private function productRows(Collection $products, Collection $categories): array
    {
        $locale = app()->getLocale();

        // The categories the query itself named. A part filed under one of them
        // has had that word answered by its shelf rather than by its name,
        // which is what lets "camry brakes" read as a vehicle *and* a part.
        $categoryIds = $categories->map(fn (Category $category): int => (int) $category->id)->all();

        return $products
            ->map(function (Product $product) use ($locale, $categoryIds): array {
                $reason = MatchReason::for($product, $this->search, $this->interpretation, $categoryIds);
                $fits = FitmentSummary::for($product, $this->interpretation, $this->vehicles, $locale);

                return [
                    'id' => (int) $product->id,
                    'name' => $product->localizedName($locale),
                    'sku' => (string) $product->sku,
                    'oem' => $this->identifier($product->oem_number),
                    'part_number' => $this->identifier($product->part_number),
                    'price_formatted' => $this->money($product->priceFor($this->viewer)),
                    'stock' => $this->stock($product),
                    // One resolver, shared with the admin product picker: cover
                    // image, else the first uploaded one, else the legacy
                    // column, else null so the panel draws its own placeholder
                    // rather than a broken image.
                    'image' => $product->primaryImageUrl(400),
                    'url' => route('shop.show', $product),
                    'match' => $reason->toArray(),
                    'fits' => $fits->toArray(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vehicleRows(): array
    {
        $variants = $this->interpretation->variants->take(self::VEHICLE_LIMIT);

        if ($variants->isEmpty()) {
            return [];
        }

        $counts = SearchInterpretation::productCountsForVariants(
            $variants->map(fn (VehicleModel $model): int => (int) $model->id)->all()
        );

        $locale = app()->getLocale();

        return $variants
            ->map(function (VehicleModel $model) use ($counts, $locale): array {
                $brand = $model->relationLoaded('brand') ? $model->brand : null;
                $family = $model->relationLoaded('family') ? $model->family : null;

                return [
                    'id' => $family !== null ? (int) $family->id : (int) $model->id,
                    'variant_id' => (int) $model->id,
                    // The variant's own name, never the family's. A family
                    // holds Rexton, Rexton II, Rexton W and Rexton G4, and
                    // printing the family over all four would offer a shopper
                    // the same word four times with four different links.
                    'name' => $model->localizedName($locale),
                    // Two variants may still share a name — that is the whole
                    // point of the variant table — so the years and the engines
                    // are what tell a shopper which car a row means.
                    'detail' => $this->vehicleDetail($model, $family, $locale),
                    'url' => $this->shopUrl([
                        'brand' => $brand?->name,
                        'model' => (int) $model->id,
                    ]),
                    'product_count' => $counts[(int) $model->id] ?? 0,
                    'product_count_label' => $this->countLabel($counts[(int) $model->id] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function vehicleDetail(VehicleModel $model, ?VehicleModelFamily $family, ?string $locale): string
    {
        $parts = [];

        $years = $model->productionYears();
        if ($years !== null) {
            $parts[] = $years;
        }

        $engines = SearchInterpretation::offeredEngines($model)
            ->map(static fn (VehicleModelEngineType $engine): string => $engine->localizedName($locale))
            ->filter(static fn (string $label): bool => trim($label) !== '')
            ->unique()
            ->take(3)
            ->all();

        if ($engines !== []) {
            $parts[] = implode(' / ', $engines);
        }

        // Nothing recorded to tell this car apart by. The family it belongs to
        // is at least true and at least placing — better than a bare name over
        // a link a shopper cannot predict.
        if ($parts === [] && $family !== null) {
            $familyName = $family->localizedName($locale);

            if ($familyName !== '' && $familyName !== $model->localizedName($locale)) {
                $parts[] = $familyName;
            }
        }

        return implode(' · ', $parts);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function brandRows(): array
    {
        $brands = $this->interpretation->brands->take(self::BRAND_LIMIT);

        if ($brands->isEmpty()) {
            return [];
        }

        $counts = SearchInterpretation::productCountsForBrands(
            $brands->map(fn (VehicleBrand $brand): int => (int) $brand->id)->all()
        );

        return $brands
            ->map(fn (VehicleBrand $brand): array => [
                'id' => (int) $brand->id,
                'name' => (string) $brand->name,
                'url' => $this->shopUrl(['brand' => $brand->name]),
                'product_count' => $counts[(int) $brand->id] ?? 0,
                'product_count_label' => $this->countLabel($counts[(int) $brand->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return list<array<string, mixed>>
     */
    private function categoryRows(Collection $categories): array
    {
        return $categories
            ->take(self::CATEGORY_LIMIT)
            ->map(function (Category $category): array {
                // An aggregate alias, not a column: read it the way a dynamic
                // attribute has to be read, and let a caller that skipped the
                // count get a null the panel knows to hide.
                $count = $category->getAttribute('active_product_count');

                return [
                    'id' => (int) $category->id,
                    'name' => $category->localizedName(),
                    'url' => $this->shopUrl(['category' => $category->slug ?: (string) $category->id]),
                    'product_count' => $count === null ? null : (int) $count,
                    'product_count_label' => $count === null ? null : $this->countLabel((int) $count),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function interpreted(): array
    {
        $locale = app()->getLocale();
        $brand = $this->interpretation->brand;
        $variant = $this->interpretation->variant();
        $firstVariant = $this->interpretation->variants->first();
        $vehicleLabel = $this->interpretation->vehicleLabel($locale);

        return [
            'brand' => $brand === null ? null : [
                'id' => (int) $brand->id,
                'label' => (string) $brand->name,
                'url' => $this->shopUrl(['brand' => $brand->name]),
            ],
            'vehicle' => $firstVariant === null ? null : [
                'id' => $this->interpretation->familyId() ?? (int) $firstVariant->id,
                'label' => $vehicleLabel,
                // Exact when the query came down to one car, family-wide when
                // it did not. Never one variant standing in for four.
                'url' => $this->shopUrl($this->interpretation->vehicleFilters($locale)),
            ],
            // Only when the query really settled on one car. A variant id is
            // the narrowest filter the shop has, and the panel does not get to
            // apply it on a guess. Where it is set, it is also deliberately
            // *not* a chip: a "Variant: Camry" beside "Vehicle: Camry" is the
            // same word twice, and the vehicle group already lists the cars.
            'variant' => $variant === null ? null : [
                'id' => (int) $variant->id,
                'label' => $variant->listLabel($locale),
                'url' => $this->shopUrl([
                    'brand' => $this->interpretation->brandFilterValue(),
                    'model' => (int) $variant->id,
                ]),
            ],
            'year' => $this->interpretation->year,
            'engine' => $this->interpretation->engine,
            'fuel' => $this->interpretation->fuel === null
                ? null
                : VehicleFuelType::label($this->interpretation->fuel, $locale),
        ];
    }

    /**
     * "Did you mean Rexton?", and only when the catalogue really said so.
     *
     * The suggestion itself comes from the existing dictionary pass, unchanged.
     * What is added here is a refusal to offer one for a token that looks like
     * a part number: a digit off in an OEM number is a different part, not a
     * typo, and guessing at one would send a shopper to the wrong thing.
     *
     * @return array{from: string, to: string, suggested_query: string}|null
     */
    private function correction(array $rows): ?array
    {
        // "Did you mean Filters?" over five filters the shopper can already see
        // is noise, and worse, it reads as though those five were the wrong
        // answer. A correction answers "nothing found", so it is only looked
        // for when nothing was found — which also keeps the dictionary out of
        // the hot path on every request that succeeds.
        if ($rows !== []) {
            return null;
        }

        $suggestion = SearchSuggestions::forQuery($this->search);

        if ($suggestion === null) {
            return null;
        }

        foreach ($this->search->tokens as $token) {
            if ($token->text === $suggestion['word'] && $token->looksLikePartNumber()) {
                return null;
            }
        }

        return [
            'from' => $suggestion['word'],
            'to' => $suggestion['suggestion'],
            'suggested_query' => $suggestion['query'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function meta(array $rows, ?int $totalProducts): array
    {
        $withoutYear = $this->interpretation->queryWithoutYear();

        return [
            // Exact when the page could count it for free, and null when
            // counting would have meant running the whole search a second time.
            // The panel hides the figure rather than printing a guess.
            'total_products' => $totalProducts,
            'total_products_label' => $totalProducts === null ? null : $this->countLabel($totalProducts),
            // The whole query, and nothing beside it. Adding the interpreted
            // filters on top would narrow the page below what the panel just
            // counted, and a "view all 42" that shows 11 is a lie.
            'view_all_url' => $this->shopUrl(['search' => $this->search->normalized]),
            'has_exact_matches' => $this->hasExactMatches($rows),
            // Offered only when there is a year to drop.
            'without_year_query' => $withoutYear,
            'without_year_url' => $withoutYear === null ? null : $this->shopUrl(['search' => $withoutYear]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function hasExactMatches(array $rows): bool
    {
        foreach ($rows as $row) {
            $type = $row['match']['type'] ?? null;

            if (in_array($type, [MatchReason::SKU_EXACT, MatchReason::OEM_EXACT, MatchReason::PART_NUMBER_EXACT], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A shop link carrying only the parameters that have a value.
     *
     * An empty parameter is not a filter, it is a query string the listing has
     * to ignore and a URL a shopper cannot read.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function shopUrl(array $parameters): string
    {
        $clean = [];

        foreach ($parameters as $key => $value) {
            if ($value === null) {
                continue;
            }

            $value = is_string($value) ? trim($value) : $value;

            if ($value === '' || $value === 0) {
                continue;
            }

            $clean[$key] = $value;
        }

        return route('shop.index', $clean);
    }

    private function identifier(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function money(float $price): string
    {
        return trim(number_format($price, 2).' '.$this->currency);
    }

    /**
     * "1 result", "12 results" — and the five other forms Arabic needs.
     *
     * Counted here rather than in the bundle: pluralisation is a language rule,
     * not a template, and Arabic has six forms where English has two. The panel
     * printed "1 results" until this moved to the side that has trans_choice.
     */
    private function countLabel(int $count): string
    {
        return trans_choice(':count result|:count results', $count, ['count' => $count]);
    }

    /**
     * What the shopper can do about this row today — and nothing more.
     *
     * The state, never the number. The storefront's listing surfaces publish a
     * yes-or-no; the only place a figure is quoted is the product page, and
     * only inside the scarcity band ("Only 3 left in stock"), which is a
     * deliberate nudge rather than a licence to publish the inventory. A
     * suggestion row has no use for the count, so it does not carry one: an
     * exact stock level is a competitor's homework, and a field nobody reads is
     * the easiest kind to leak.
     *
     * The threshold that separates low from in-stock is likewise never sent —
     * publishing it alongside the state would let the level be inferred anyway.
     * It is read from the same place the low-stock report and the alerts read
     * it: the product's own value, then the shop-wide setting.
     *
     * Nothing here decides whether a product may be seen; the query did that.
     *
     * @return array{state: string, label: string}
     */
    private function stock(Product $product): array
    {
        $quantity = (int) $product->stock_quantity;
        $threshold = $product->low_stock_threshold !== null
            ? (int) $product->low_stock_threshold
            : $this->globalLowStockThreshold;

        [$state, $label] = match (true) {
            $quantity <= 0 => ['out_of_stock', __('Out of stock')],
            $quantity <= $threshold => ['low_stock', __('Low stock')],
            default => ['in_stock', __('In stock')],
        };

        return [
            'state' => $state,
            'label' => $label,
        ];
    }
}
