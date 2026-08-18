<?php

namespace App\Support;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Support\Facades\Session;

/**
 * The customer's saved vehicle.
 *
 * Kept in the session rather than on the user, so a guest gets the same benefit
 * as a signed-in customer and nothing has to be migrated to ship this. Moving it
 * onto the account later only means changing get() and put().
 */
class Garage
{
    public const SESSION_KEY = 'garage.vehicle';

    /**
     * @return array{brand_id:int,brand:string,model_id:int,model:string,year:int|null}|null
     */
    public static function get(): ?array
    {
        $vehicle = Session::get(self::SESSION_KEY);

        if (! is_array($vehicle) || ! isset($vehicle['model_id'], $vehicle['model'])) {
            return null;
        }

        return $vehicle;
    }

    public static function has(): bool
    {
        return self::get() !== null;
    }

    /**
     * Names are resolved from the database rather than trusted from the request,
     * so a tampered form cannot put arbitrary text on the plate.
     */
    public static function put(int $modelId, ?int $year): bool
    {
        $model = VehicleModel::query()->find($modelId);

        if ($model === null) {
            return false;
        }

        $brand = VehicleBrand::query()->find($model->vehicle_brand_id);

        Session::put(self::SESSION_KEY, [
            'brand_id' => (int) $model->vehicle_brand_id,
            'brand' => (string) ($brand?->name ?? ''),
            'model_id' => (int) $model->getKey(),
            'model' => (string) $model->name,
            'year' => $year,
        ]);

        return true;
    }

    public static function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Decide how this product relates to the saved vehicle.
     *
     * @param  iterable<array{model_id:int|null,from:int|null,to:int|null,engine:string|null}>  $fitments
     * @return array{state:'fits'|'misses'|'unknown',row:array|null}
     */
    public static function verdict(iterable $fitments): array
    {
        $vehicle = self::get();

        if ($vehicle === null) {
            return ['state' => 'unknown', 'row' => null];
        }

        $forModel = null;
        foreach ($fitments as $row) {
            if ((int) ($row['model_id'] ?? 0) === $vehicle['model_id']) {
                $forModel = $row;
                break;
            }
        }

        if ($forModel === null) {
            return ['state' => 'misses', 'row' => null];
        }

        // No year bounds means the whole model is covered, so the saved year
        // cannot disqualify it.
        if ($forModel['from'] === null || $forModel['to'] === null) {
            return ['state' => 'fits', 'row' => $forModel];
        }

        // A vehicle saved without a year still matches a ranged row: we have no
        // reason to tell the customer no.
        if ($vehicle['year'] === null) {
            return ['state' => 'fits', 'row' => $forModel];
        }

        $inRange = $vehicle['year'] >= $forModel['from'] && $vehicle['year'] <= $forModel['to'];

        return ['state' => $inRange ? 'fits' : 'misses', 'row' => $forModel];
    }
}
