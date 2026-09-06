<?php

namespace App\Support\Search;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use App\Models\VehicleModelFamily;
use App\Support\DbSchema;
use Illuminate\Support\Collection;

/**
 * Every car this request needs, loaded once.
 *
 * Two parts of the answer ask about vehicles: the interpretation, which is
 * resolved from the typed words before anything else, and the "fits" caption,
 * whose cars are only known once the products come back. Left to themselves
 * each would eager-load its own models, their families, their engines and their
 * marques — six queries over four tables, half of them fetching rows the other
 * half already had.
 *
 * So the ids are collected from both, and then each table is read exactly once.
 * Nothing here is a cache: it lives for one request and knows nothing about the
 * one before it.
 */
final class VehicleContext
{
    /** @var Collection<int, VehicleModel> */
    private Collection $models;

    /** @var Collection<int, VehicleBrand> */
    private Collection $brands;

    /** @var list<int> */
    private array $pendingModelIds = [];

    /** @var list<int> */
    private array $pendingBrandIds = [];

    private bool $hydrated = false;

    public function __construct()
    {
        $this->models = collect();
        $this->brands = collect();
    }

    /**
     * Models already in hand — the interpretation's candidates, fetched by name.
     *
     * @param  Collection<int, VehicleModel>  $models
     */
    public function addModels(Collection $models): void
    {
        foreach ($models as $model) {
            $this->models->put((int) $model->id, $model);
        }
    }

    /**
     * Ids the answer will need but has not fetched: the cars behind the fitment
     * rows of the products being suggested.
     *
     * @param  iterable<mixed>  $ids
     */
    public function needModels(iterable $ids): void
    {
        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id > 0 && ! $this->models->has($id) && ! in_array($id, $this->pendingModelIds, true)) {
                $this->pendingModelIds[] = $id;
            }
        }
    }

    /**
     * @param  iterable<mixed>  $ids
     */
    public function needBrands(iterable $ids): void
    {
        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id > 0 && ! $this->brands->has($id) && ! in_array($id, $this->pendingBrandIds, true)) {
                $this->pendingBrandIds[] = $id;
            }
        }
    }

    /**
     * @param  Collection<int, VehicleBrand>  $brands
     */
    public function addBrands(Collection $brands): void
    {
        foreach ($brands as $brand) {
            $this->brands->put((int) $brand->id, $brand);
        }
    }

    public function model(mixed $id): ?VehicleModel
    {
        $id = (int) $id;

        return $id > 0 ? $this->models->get($id) : null;
    }

    public function brand(mixed $id): ?VehicleBrand
    {
        $id = (int) $id;

        return $id > 0 ? $this->brands->get($id) : null;
    }

    /**
     * Fill in everything asked for, one query per table.
     *
     * Called once, after the products are known and before anything is
     * rendered. Calling it twice is a no-op rather than a second round of
     * queries; that is the whole point of collecting the ids first.
     */
    public function hydrate(): void
    {
        if ($this->hydrated) {
            return;
        }

        $this->hydrated = true;

        $this->loadPendingModels();
        $this->loadBrands();
        $this->loadFamilies();
        $this->loadEngineTypes();
    }

    private function loadPendingModels(): void
    {
        if ($this->pendingModelIds === [] || ! DbSchema::hasTable('vehicle_models')) {
            $this->pendingModelIds = [];

            return;
        }

        VehicleModel::query()
            ->whereKey($this->pendingModelIds)
            ->get([
                'id', 'name', 'name_en', 'name_ar', 'name_ku', 'slug',
                'vehicle_brand_id', 'vehicle_model_family_id',
                'production_start_year', 'production_end_year',
            ])
            ->each(fn (VehicleModel $model) => $this->models->put((int) $model->id, $model));

        $this->pendingModelIds = [];
    }

    private function loadBrands(): void
    {
        // Every marque anything in the answer points at: the ones the query
        // named, and the ones sitting on a fitment row.
        $this->needBrands($this->models->map(fn (VehicleModel $model): mixed => $model->vehicle_brand_id));

        if ($this->pendingBrandIds !== [] && DbSchema::hasTable('vehicle_brands')) {
            VehicleBrand::query()
                ->whereKey($this->pendingBrandIds)
                ->get(['id', 'name', 'slug'])
                ->each(fn (VehicleBrand $brand) => $this->brands->put((int) $brand->id, $brand));
        }

        $this->pendingBrandIds = [];

        foreach ($this->models as $model) {
            $model->setRelation('brand', $this->brands->get((int) $model->vehicle_brand_id));
        }
    }

    private function loadFamilies(): void
    {
        if (! DbSchema::hasTable('vehicle_model_families')) {
            $this->models->each(fn (VehicleModel $model) => $model->setRelation('family', null));

            return;
        }

        $familyIds = $this->models
            ->map(fn (VehicleModel $model): ?int => $model->vehicle_model_family_id !== null ? (int) $model->vehicle_model_family_id : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        /** @var Collection<int, VehicleModelFamily> $families */
        $families = $familyIds === []
            ? collect()
            : VehicleModelFamily::query()
                ->whereKey($familyIds)
                ->get(['id', 'name', 'name_en', 'name_ar', 'name_ku', 'slug', 'vehicle_brand_id'])
                ->keyBy(fn (VehicleModelFamily $family): int => (int) $family->id);

        foreach ($this->models as $model) {
            $model->setRelation('family', $families->get((int) $model->vehicle_model_family_id));
        }
    }

    private function loadEngineTypes(): void
    {
        if (! DbSchema::hasTable('vehicle_model_engine_types')) {
            $this->models->each(fn (VehicleModel $model) => $model->setRelation('engineTypes', collect()));

            return;
        }

        $modelIds = $this->models->keys()->all();

        /** @var Collection<int, Collection<int, VehicleModelEngineType>> $byModel */
        $byModel = $modelIds === []
            ? collect()
            : VehicleModelEngineType::query()
                ->whereIn('vehicle_model_id', $modelIds)
                ->orderBy('name')
                ->get(['id', 'vehicle_model_id', 'name', 'fuel_type', 'engine_size', 'aspiration'])
                ->groupBy(fn (VehicleModelEngineType $engine): int => (int) $engine->vehicle_model_id);

        foreach ($this->models as $model) {
            $model->setRelation('engineTypes', $byModel->get((int) $model->id, collect())->values());
        }
    }
}
