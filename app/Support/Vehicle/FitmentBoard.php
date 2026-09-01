<?php

namespace App\Support\Vehicle;

use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Support\VehicleFuelType;
use App\Support\VehicleLocalization;
use Illuminate\Support\Collection;

/**
 * What a part fits, read off the records an administrator actually filled in.
 *
 * The product page used to read only the fitment row's own columns, and those
 * columns are narrowing overrides — an operator leaves them blank when the part
 * fits the whole variant, which is what the admin form's placeholder invites.
 * Blank meant the page printed "Any year" over a variant whose build years were
 * recorded all along, and printed the engine's stored display text rather than
 * the structured columns beside it.
 *
 * So each fitment is resolved against its variant here: the fitment narrows,
 * the variant supplies what the fitment left open, and nothing is invented for
 * what neither of them knows.
 *
 * One fitment is one configuration. Rows are never merged — a Rexton G4 built
 * 2018-2021 and one built 2022-2026 are different cars taking different parts,
 * and folding them into "2018-2026" would claim a fit that was never recorded.
 */
class FitmentBoard
{
    /**
     * @param  Collection<int, array<string, mixed>>  $families
     */
    private function __construct(
        public readonly Collection $families,
        public readonly int $configurationCount,
    ) {}

    public static function empty(): self
    {
        return new self(collect(), 0);
    }

    /**
     * Build the board from a product whose fitments are already loaded.
     *
     * Nothing is queried here. If the relation was not eager loaded the board
     * comes back empty rather than issuing a query per row from inside a view.
     */
    public static function forProduct(Product $product, ?string $locale = null): self
    {
        if (! $product->relationLoaded('vehicleFitments')) {
            return self::empty();
        }

        $rows = $product->vehicleFitments
            ->filter(fn (ProductVehicleFitment $fitment): bool => $fitment->model !== null)
            ->map(fn (ProductVehicleFitment $fitment): array => self::row($fitment, $locale))
            ->values();

        if ($rows->isEmpty()) {
            return self::empty();
        }

        // One car is one card. An engine is an option on a car, not another
        // car, so two fitments that differ only by engine belong together —
        // while two year ranges are two different vehicles and never do.
        $configurations = $rows
            ->groupBy('card_key')
            ->map(fn (Collection $group): array => self::configuration($group))
            ->values();

        $families = $configurations
            ->groupBy('family')
            ->map(fn (Collection $cards, string $family): array => [
                'name' => $family,
                'configurations' => $cards
                    ->sortBy([
                        fn (array $row) => $row['variant'],
                        // Newest first within a variant: the car most likely to
                        // be on the road today is the one read first.
                        fn (array $row) => -($row['year_from'] ?? $row['year_to'] ?? 0),
                    ])
                    ->values(),
            ])
            ->sortBy('name')
            ->values();

        return new self($families, $configurations->count());
    }

    public function isEmpty(): bool
    {
        return $this->families->isEmpty();
    }

    public function familyCount(): int
    {
        return $this->families->count();
    }

    /**
     * Fold the fitments for one car into the card a customer reads.
     *
     * Every engine the product was recorded against is listed; the card is
     * confirmed when the car is pinned down by years and at least one of those
     * engines is known.
     *
     * @param  Collection<int, array<string, mixed>>  $group
     * @return array<string, mixed>
     */
    private static function configuration(Collection $group): array
    {
        $first = $group->first();

        $engines = $group
            ->pluck('engine')
            ->filter(fn (array $engine): bool => $engine['known'])
            ->unique(fn (array $engine): string => $engine['label'])
            ->sortBy(fn (array $engine): string => $engine['label'])
            ->values();

        return [
            'family' => $first['family'],
            'variant' => $first['variant'],
            'year_from' => $first['year_from'],
            'year_to' => $first['year_to'],
            'years' => $first['years'],
            'has_years' => $first['has_years'],
            'engines' => $engines,
            // A card only claims a confirmed fit when it says enough for a
            // customer to recognise their own car in it. Years and an engine
            // are what separate one Rexton from the next; without them the
            // record is a lead, not an answer, and saying "confirmed" over it
            // would be a promise the data cannot keep.
            'complete' => $first['has_years'] && $engines->isNotEmpty(),
            'notes' => $group->pluck('notes')->filter()->unique()->values()->implode(' · '),
        ];
    }

    /**
     * One fitment row, resolved against the variant it points at.
     *
     * @return array<string, mixed>
     */
    private static function row(ProductVehicleFitment $fitment, ?string $locale): array
    {
        /** @var VehicleModel $model */
        $model = $fitment->model;

        // The fitment narrows; the variant supplies what it left open. Blank on
        // both is genuinely unknown and says so rather than guessing.
        $from = self::year($fitment->year_from) ?? self::year($model->production_start_year);
        $to = self::year($fitment->year_to) ?? self::year($model->production_end_year);

        $engine = self::engine($fitment, $model, $locale);

        return [
            // What makes one card: the car and the years it covers. The engine
            // is deliberately absent — it is what the card lists inside.
            'card_key' => implode('|', [(int) $model->id, $from ?? '', $to ?? '']),
            'family' => (string) ($model->family?->localizedName($locale) ?: $model->localizedName($locale)),
            'variant' => $model->localizedName($locale),
            'year_from' => $from,
            'year_to' => $to,
            'years' => self::yearLabel($from, $to),
            'has_years' => $from !== null || $to !== null,
            'engine' => $engine,
            'notes' => trim((string) $fitment->notes),
        ];
    }

    /**
     * How this engine should be written, from the structured columns wherever
     * they exist.
     *
     * The fitment stores the engine as the display text it was chosen by, which
     * is enough to find the row that holds the real parts — fuel, displacement,
     * aspiration. Matching on those parts rather than on the text means a stored
     * label that predates a formatting change still resolves.
     *
     * @return array{label: string, displacement: string, aspiration: string, fuel: string, known: bool}
     */
    private static function engine(ProductVehicleFitment $fitment, VehicleModel $model, ?string $locale): array
    {
        $stated = trim((string) $fitment->engine);

        if ($stated === '') {
            return self::unknownEngine();
        }

        $type = self::matchEngineType($stated, $model);

        if ($type === null) {
            // No structured row to lean on. The text is shown as recorded, with
            // its words translated — never re-read for a displacement it may
            // not mean.
            return [
                'label' => VehicleLocalization::engine($stated, $locale),
                'displacement' => '',
                'aspiration' => '',
                'fuel' => '',
                'known' => true,
            ];
        }

        // The same rule the finder applies: a customer is not shown an engine
        // the shop has no parts for. The record keeps it either way, and one
        // config line brings it back.
        if (! $type->isOfferedInStorefront()) {
            return self::unknownEngine();
        }

        $displacement = VehicleFuelType::displacement($type->engine_size, $type->fuel_type);

        return [
            'label' => $type->localizedName($locale),
            'displacement' => $displacement,
            'aspiration' => VehicleLocalization::aspiration($type->aspiration, $locale),
            'fuel' => $type->localizedFuelLabel($locale),
            'known' => true,
        ];
    }

    /** @return array{label: string, displacement: string, aspiration: string, fuel: string, known: bool} */
    private static function unknownEngine(): array
    {
        return ['label' => '', 'displacement' => '', 'aspiration' => '', 'fuel' => '', 'known' => false];
    }

    /**
     * Find the variant's engine row that the fitment's text refers to.
     *
     * Exact text first, because that is what the admin form stored. Failing
     * that, the text is read for its parts and compared to the columns, so
     * "2 Turbo Petrol" still finds the 2.0 turbo petrol it was written for.
     */
    private static function matchEngineType(string $stated, VehicleModel $model): ?VehicleModelEngineType
    {
        if (! $model->relationLoaded('engineTypes')) {
            return null;
        }

        $exact = $model->engineTypes->first(
            fn (VehicleModelEngineType $type): bool => mb_strtolower(trim((string) $type->name)) === mb_strtolower($stated)
        );

        if ($exact !== null) {
            return $exact;
        }

        $wanted = VehicleFuelType::parse($stated);

        if ($wanted['fuel_type'] === null && $wanted['engine_size'] === null) {
            return null;
        }

        return $model->engineTypes->first(function (VehicleModelEngineType $type) use ($wanted): bool {
            $size = $type->engine_size === null ? null : (float) $type->engine_size;

            return $type->fuel_type === $wanted['fuel_type']
                && $size === $wanted['engine_size']
                && (($type->aspiration ?: null) === ($wanted['aspiration'] ?: null));
        });
    }

    private static function year(mixed $value): ?int
    {
        $year = (int) $value;

        return $year > 0 ? $year : null;
    }

    /**
     * The years a customer reads. A recorded year is never written as "any".
     */
    private static function yearLabel(?int $from, ?int $to): string
    {
        return match (true) {
            $from !== null && $to !== null && $from === $to => (string) $from,
            $from !== null && $to !== null => $from.'–'.$to,
            $from !== null => __(':year and newer', ['year' => $from]),
            $to !== null => __(':year and older', ['year' => $to]),
            default => __('Model years not recorded'),
        };
    }
}
