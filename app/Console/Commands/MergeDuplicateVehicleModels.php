<?php

namespace App\Console\Commands;

use App\Models\ProductVehicleFitment;
use App\Models\VehicleModel;
use App\Models\VehicleModelEngineType;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Find vehicles recorded twice, and — only when told to — fold them into one.
 *
 * A variant used to be created on every trip through the add-variant form, so
 * an operator adding a second engine to a car ended up with a second copy of
 * that car. The storefront then offered both, identical down to the years, with
 * nothing on screen to tell them apart.
 *
 * What counts as the same car is deliberately narrow: same brand, same family,
 * same name once spacing and case are set aside, and the same two production
 * years — both of them recorded. A missing year is never guessed at, and two
 * different year ranges are never folded together, because those are different
 * cars taking different parts.
 *
 * Reports and changes nothing unless --apply is passed.
 */
class MergeDuplicateVehicleModels extends Command
{
    protected $signature = 'vehicles:merge-duplicates
        {--apply : Actually merge. Without this the command only reports.}';

    protected $description = 'Report vehicle variants recorded more than once, and optionally merge them';

    public function handle(): int
    {
        $groups = $this->duplicateGroups();

        if ($groups->isEmpty()) {
            $this->info('No duplicate vehicle variants found.');

            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');

        $this->line('');
        $this->line($apply ? 'Merging duplicate variants:' : 'Dry run — nothing will be changed. Pass --apply to merge.');
        $this->line('');

        $merged = 0;

        foreach ($groups as $group) {
            $canonical = $group->first();
            $duplicates = $group->slice(1);

            $this->describe($canonical, $duplicates);

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($canonical, $duplicates, &$merged): void {
                foreach ($duplicates as $duplicate) {
                    $this->merge($canonical, $duplicate);
                    $merged++;
                }
            });
        }

        $this->line('');

        if ($apply) {
            $this->info("Merged {$merged} duplicate variant(s).");

            return self::SUCCESS;
        }

        $this->warn($groups->count().' group(s) would be merged. Re-run with --apply.');

        return self::SUCCESS;
    }

    /**
     * Variants that record the same car, grouped, canonical first.
     *
     * The oldest row wins: it is the one whose slug is most likely to be in a
     * link somebody has already followed or saved.
     *
     * @return Collection<int, EloquentCollection<int, VehicleModel>>
     */
    private function duplicateGroups(): Collection
    {
        return VehicleModel::query()
            ->with(['engineTypes', 'brand:id,name', 'family:id,name'])
            ->withCount('fitments')
            ->whereNotNull('production_start_year')
            ->whereNotNull('production_end_year')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (VehicleModel $model) => $model->identityKey())
            ->filter(fn (EloquentCollection $group) => $group->count() > 1)
            ->values();
    }

    /**
     * @param  EloquentCollection<int, VehicleModel>  $duplicates
     */
    private function describe(VehicleModel $canonical, Collection $duplicates): void
    {
        $years = $canonical->production_start_year.'–'.$canonical->production_end_year;
        $this->line("  <options=bold>{$canonical->brand?->name} / {$canonical->family?->name} / {$canonical->name}</> {$years}");
        $this->line("    keep    #{$canonical->id}  engines: ".$this->engineList($canonical)."  fitments: {$canonical->fitments_count}");

        foreach ($duplicates as $duplicate) {
            $this->line("    merge   #{$duplicate->id}  engines: ".$this->engineList($duplicate)."  fitments: {$duplicate->fitments_count}");
        }

        $this->line('');
    }

    private function engineList(VehicleModel $model): string
    {
        $names = $model->engineTypes->pluck('name')->filter()->values();

        return $names->isEmpty() ? '—' : $names->implode(', ');
    }

    /**
     * Move everything the duplicate carries onto the canonical row, then remove
     * it. Nothing is deleted before its references have somewhere else to be.
     */
    private function merge(VehicleModel $canonical, VehicleModel $duplicate): void
    {
        $this->moveEngineTypes($canonical, $duplicate);
        $this->moveFitments($canonical, $duplicate);

        // Only now, with nothing pointing at it.
        $duplicate->refresh()->loadCount('fitments');

        if ($duplicate->fitments_count > 0) {
            throw new \RuntimeException("Variant #{$duplicate->id} still has fitments after the move; refusing to delete it.");
        }

        $duplicate->engineTypes()->delete();
        $duplicate->delete();
    }

    /**
     * Engines the canonical row does not already have.
     *
     * Compared on what the engine is rather than on its display text, so a
     * label written before a formatting change is not taken for a new engine.
     */
    private function moveEngineTypes(VehicleModel $canonical, VehicleModel $duplicate): void
    {
        $canonical->load('engineTypes');
        $known = $canonical->engineTypes->map(fn ($engine) => $this->engineSignature($engine))->all();

        foreach ($duplicate->engineTypes()->get() as $engine) {
            $signature = $this->engineSignature($engine);

            if (in_array($signature, $known, true)) {
                continue;
            }

            // The unique key is (model, name), so a name already spoken for on
            // the canonical row by a different engine has to be left alone.
            $nameTaken = VehicleModelEngineType::query()
                ->where('vehicle_model_id', $canonical->id)
                ->where('name', $engine->name)
                ->exists();

            if ($nameTaken) {
                continue;
            }

            $engine->forceFill(['vehicle_model_id' => $canonical->id])->save();
            $known[] = $signature;
        }
    }

    private function engineSignature(VehicleModelEngineType $engine): string
    {
        return implode('|', [
            (string) $engine->fuel_type,
            $engine->engine_size === null ? '' : number_format((float) $engine->engine_size, 1, '.', ''),
            strtolower(trim((string) $engine->aspiration)),
        ]);
    }

    /**
     * Repoint every fitment, dropping only rows the canonical variant already
     * says word for word. A product never loses a car it fits.
     */
    private function moveFitments(VehicleModel $canonical, VehicleModel $duplicate): void
    {
        // Both sides are signed against the canonical id, so a row that already
        // says the same thing is recognised as saying it.
        $existing = ProductVehicleFitment::query()
            ->where('vehicle_model_id', $canonical->id)
            ->get(['id', 'product_id', 'vehicle_model_id', 'year_from', 'year_to', 'engine'])
            ->map(fn (ProductVehicleFitment $fitment) => $this->fitmentSignature($fitment, $canonical->id))
            ->all();

        foreach ($duplicate->fitments()->get() as $fitment) {
            $signature = $this->fitmentSignature($fitment, $canonical->id);

            if (in_array($signature, $existing, true)) {
                // The same product, car, years and engine already recorded.
                $fitment->delete();

                continue;
            }

            $fitment->forceFill(['vehicle_model_id' => $canonical->id])->save();
            $existing[] = $signature;
        }
    }

    private function fitmentSignature(ProductVehicleFitment $fitment, ?int $modelId = null): string
    {
        return implode('|', [
            (int) $fitment->product_id,
            $modelId ?? (int) $fitment->vehicle_model_id,
            (int) $fitment->year_from,
            (int) $fitment->year_to,
            mb_strtolower(trim((string) $fitment->engine)),
        ]);
    }
}
