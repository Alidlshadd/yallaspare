<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Carriers
    |--------------------------------------------------------------------------
    |
    | Carriers the shop hands parcels to. The key is matched against whatever
    | an operator typed in the carrier field, slugged, so "Aramex" and
    | "aramex " both resolve here.
    |
    | 'tracking_url' turns the tracking number into a link the customer can
    | follow; ':number' is replaced with the order's tracking number. A
    | carrier without one — an own driver, a local company with no website —
    | simply shows the number, which is still what support asks for.
    |
    | Adding a carrier here is enough; nothing else needs to change.
    |
    */

    'carriers' => [
        'own-delivery' => [
            'name' => 'Own delivery',
            'tracking_url' => null,
        ],
        'aramex' => [
            'name' => 'Aramex',
            'tracking_url' => 'https://www.aramex.com/us/en/track/results?ShipmentNumber=:number',
        ],
        'dhl' => [
            'name' => 'DHL',
            'tracking_url' => 'https://www.dhl.com/en/express/tracking.html?AWB=:number',
        ],
    ],

];
