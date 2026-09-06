<?php

namespace App\Support\Search;

use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Support\VehicleFuelType;

/**
 * The one line that says which car a suggested part is for.
 *
 * A part can be recorded against a dozen cars, and printing the first row the
 * database happened to return is worse than printing nothing: it tells a
 * shopper searching for a 2024 Rexton that the part fits a 2015 Tivoli. So the
 * row shown is the row the *query* points at — the variant that was named, then
 * the car and year, then the car — and where none of those apply the part
 * simply says how many vehicles it covers.
 *
 * The fitment rows come eager-loaded with the products; the cars behind them
 * come from the VehicleContext, which loaded every car this request needs in
 * one pass. Nothing here queries.
 */
final class FitmentSummary
{
    private function __construct(
        public readonly ?string $label,
        public readonly ?int $vehicleCount,
    ) {}

    public static function none(): self
    {
        return new self(null, null);
    }

    /**
     * @return array{label: string|null, vehicle_count: int|null}|null
     */
    public function toArray(): ?array
    {
        if ($this->label === null && $this->vehicleCount === null) {
            return null;
        }

        return [
            'label' => $this->label,
            'vehicle_count' => $this->vehicleCount,
        ];
    }

    public static function for(
        Product $product,
        SearchInterpretation $interpretation,
        VehicleContext $vehicles,
        ?string $locale = null,
    ): self {
        if (! $product->relationLoaded('vehicleFitments')) {
            return self::none();
        }

        /** @var list<ProductVehicleFitment> $fitments */
        $fitments = $product->vehicleFitments->all();

        if ($fitments === []) {
            return self::none();
        }

        // A car the query names but whose years exclude the year asked for is
        // not an answer, whatever else it matches.
        $eligible = $interpretation->year === null
            ? $fitments
            : array_values(array_filter(
                $fitments,
                static fn (ProductVehicleFitment $fitment): bool => self::coversYear($fitment, $interpretation->year, $vehicles)
            ));

        $vehicleCount = self::distinctVehicleCount($fitments);
        $chosen = self::choose($eligible, $fitments, $interpretation, $vehicles);

        if ($chosen === null) {
            return new self(null, $vehicleCount);
        }

        return new self(self::describe($chosen, $interpretation, $vehicles, $locale), $vehicleCount);
    }

    /**
     * Does this row answer for the given year?
     *
     * A row that records either of its own bounds answers from those alone: a
     * part listed for 2022-2023 must not claim 2024 merely because the car was
     * still being built then. Only a row that recorded neither falls back to
     * the variant's production span.
     */
    public static function coversYear(ProductVehicleFitment $fitment, int $year, VehicleContext $vehicles): bool
    {
        $from = $fitment->year_from !== null ? (int) $fitment->year_from : null;
        $to = $fitment->year_to !== null ? (int) $fitment->year_to : null;

        if ($from !== null || $to !== null) {
            return ($from === null || $from <= $year)
                && ($to === null || $to >= $year);
        }

        $model = $vehicles->model($fitment->vehicle_model_id);

        if (! $model instanceof VehicleModel) {
            return true;
        }

        $start = $model->production_start_year ? (int) $model->production_start_year : null;
        $end = $model->production_end_year ? (int) $model->production_end_year : null;

        return ($start === null || $start <= $year)
            && ($end === null || $end >= $year);
    }

    /**
     * @param  list<ProductVehicleFitment>  $eligible
     * @param  list<ProductVehicleFitment>  $all
     */
    private static function choose(
        array $eligible,
        array $all,
        SearchInterpretation $interpretation,
        VehicleContext $vehicles,
    ): ?ProductVehicleFitment {
        if ($eligible === []) {
            return null;
        }

        $variantIds = $interpretation->variantIds();
        $fuel = $interpretation->fuel;
        $variant = $interpretation->variant();

        // 1. The exact car the query resolved to, when it resolved to one.
        if ($variant !== null) {
            $exact = self::firstFitting(
                $eligible,
                static fn (ProductVehicleFitment $fitment): bool => (int) $fitment->vehicle_model_id === (int) $variant->id,
                $fuel,
                $vehicles,
            );

            if ($exact !== null) {
                return $exact;
            }
        }

        // 2-3. Any car the query named. The year has already been applied to
        // $eligible, so a hit here is a car *and* a year the shopper can trust.
        if ($variantIds !== []) {
            $named = self::firstFitting(
                $eligible,
                static fn (ProductVehicleFitment $fitment): bool => in_array((int) $fitment->vehicle_model_id, $variantIds, true),
                $fuel,
                $vehicles,
            );

            if ($named !== null) {
                return $named;
            }
        }

        // 4. Nothing was named, but the part fits exactly one car, so there is
        // nothing to pick wrongly.
        if (count($all) === 1 && count($eligible) === 1) {
            return self::fuelIsCompatible($eligible[0], $fuel, $vehicles) ? $eligible[0] : null;
        }

        // 5. Several general fitments and no reason to prefer any of them. The
        // count still gets shown; a specific car does not.
        return null;
    }

    /**
     * @param  list<ProductVehicleFitment>  $fitments
     * @param  callable(ProductVehicleFitment): bool  $matches
     */
    private static function firstFitting(
        array $fitments,
        callable $matches,
        ?string $fuel,
        VehicleContext $vehicles,
    ): ?ProductVehicleFitment {
        $candidates = array_values(array_filter($fitments, $matches));

        if ($candidates === []) {
            return null;
        }

        if ($fuel === null) {
            return $candidates[0];
        }

        // A Diesel row is not an answer to a Petrol search, and captioning one
        // as if it were is worse than showing no caption at all.
        foreach ($candidates as $candidate) {
            if (self::fuelIsCompatible($candidate, $fuel, $vehicles)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Whether this row can be spoken of as the requested fuel.
     *
     * A row that names no engine and sits on a car with no engines recorded
     * says nothing about fuel either way, and silence is compatible.
     */
    private static function fuelIsCompatible(
        ProductVehicleFitment $fitment,
        ?string $fuel,
        VehicleContext $vehicles,
    ): bool {
        if ($fuel === null) {
            return true;
        }

        $engine = trim((string) $fitment->engine);

        if ($engine !== '') {
            $parsed = VehicleFuelType::parse($engine)['fuel_type'];

            return $parsed === null || $parsed === $fuel;
        }

        $model = $vehicles->model($fitment->vehicle_model_id);

        if (! $model instanceof VehicleModel || ! $model->relationLoaded('engineTypes')) {
            return true;
        }

        $recorded = $model->engineTypes
            ->map(static fn (VehicleModelEngineType $type): string => (string) $type->fuel_type)
            ->filter(static fn (string $type): bool => $type !== '');

        return $recorded->isEmpty() || $recorded->contains($fuel);
    }

    /**
     * "Rexton · 2022–2026 · 2.0 Petrol"
     */
    private static function describe(
        ProductVehicleFitment $fitment,
        SearchInterpretation $interpretation,
        VehicleContext $vehicles,
        ?string $locale,
    ): string {
        $model = $vehicles->model($fitment->vehicle_model_id);
        $parts = [];

        if ($model instanceof VehicleModel) {
            $parts[] = $model->localizedName($locale);
        } else {
            $brand = $vehicles->brand($fitment->vehicle_brand_id);

            if ($brand !== null) {
                $parts[] = (string) $brand->name;
            }
        }

        $years = self::years($fitment, $model);
        if ($years !== null) {
            $parts[] = $years;
        }

        $engine = self::engine($fitment, $model, $interpretation, $locale);
        if ($engine !== null) {
            $parts[] = $engine;
        }

        return implode(' · ', array_filter($parts, static fn (string $part): bool => trim($part) !== ''));
    }

    /**
     * The row's own years when it recorded any, otherwise the car's.
     */
    private static function years(ProductVehicleFitment $fitment, ?VehicleModel $model): ?string
    {
        $from = $fitment->year_from !== null ? (int) $fitment->year_from : null;
        $to = $fitment->year_to !== null ? (int) $fitment->year_to : null;

        if ($from !== null || $to !== null) {
            return match (true) {
                $from !== null && $to !== null && $from === $to => (string) $from,
                $from !== null && $to !== null => $from.'–'.$to,
                $from !== null => $from.'–',
                default => '–'.$to,
            };
        }

        return $model?->productionYears();
    }

    /**
     * The engine, but only when it can be stated honestly.
     */
    private static function engine(
        ProductVehicleFitment $fitment,
        ?VehicleModel $model,
        SearchInterpretation $interpretation,
        ?string $locale,
    ): ?string {
        $recorded = trim((string) $fitment->engine);
        $fuel = $interpretation->fuel;

        if ($recorded !== '') {
            $parsed = VehicleFuelType::parse($recorded)['fuel_type'];

            // Free text naming the wrong fuel is dropped rather than shown; the
            // car and its years still say something true.
            if ($fuel !== null && $parsed !== null && $parsed !== $fuel) {
                return null;
            }

            return $recorded;
        }

        if (! $model instanceof VehicleModel) {
            return null;
        }

        $engine = SearchInterpretation::engineMatchingFuel($model, $fuel);

        return $engine === null ? null : $engine->localizedName($locale);
    }

    /**
     * @param  list<ProductVehicleFitment>  $fitments
     */
    private static function distinctVehicleCount(array $fitments): ?int
    {
        $ids = [];

        foreach ($fitments as $fitment) {
            $id = $fitment->vehicle_model_id !== null ? (int) $fitment->vehicle_model_id : null;

            if ($id !== null && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids === [] ? null : count($ids);
    }
}
