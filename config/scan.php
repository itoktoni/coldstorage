<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scan Prefix Configuration
    |--------------------------------------------------------------------------
    |
    | Prefix codes for forklift scan detection:
    | - P = Pallet (group of barcodes)
    | - L = Location (rack/area/gudang)
    | - B = Barcode (single stock item)
    |
    | Codes without matching prefix default to barcode mode.
    |
    */

    'prefix' => [
        'pallet'   => env('SCAN_PREFIX_PALLET', 'P'),
        'location' => env('SCAN_PREFIX_LOCATION', 'L'),
        'barcode'  => env('SCAN_PREFIX_BARCODE', 'B'),
    ],

];
