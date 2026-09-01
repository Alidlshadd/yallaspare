<?php

use App\Support\VehicleFuelType;

return [

    /*
    |--------------------------------------------------------------------------
    | Fuel types offered to customers
    |--------------------------------------------------------------------------
    |
    | The shop sells petrol parts, so a diesel engine is not something a
    | customer should be asked to pick — but the diesel cars themselves stay on
    | record, because the catalogue is the catalogue whatever is on sale this
    | year. Adding 'diesel' here is all it takes to open it up later.
    |
    | An engine whose fuel type was never filled in is left visible: it may well
    | be petrol, and hiding a car on a guess is worse than showing one engine
    | too many. Give those engines a fuel type in the admin and they fall under
    | this rule like the rest.
    |
    */

    'storefront_fuel_types' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('STOREFRONT_FUEL_TYPES', VehicleFuelType::PETROL))),
        static fn (string $fuel): bool => VehicleFuelType::isValid($fuel),
    )),

];
