<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Forklift Task Types
    |--------------------------------------------------------------------------
    |
    | Setiap transaksi forklift punya tipe dan warna card yang bisa diubah di sini.
    |
    |   putaway     : staging -> rack (barang dari area masuk dipindah ke rak)
    |   pick        : rack -> staging (barang diambil dari rak untuk Sales Order)
    |   relocation  : rack -> rack (barang dipindah antar rak)
    |
    | 'color' dipakai untuk border kiri card.
    */

    'types' => [
        'putaway' => [
            'label' => 'Staging → Rack',
            'icon'  => 'warehouse',
            'color' => '#00288e',
        ],
        'pick' => [
            'label' => 'Rack → Staging',
            'icon'  => 'inventory_2',
            'color' => '#f59e0b',
        ],
        'relocation' => [
            'label' => 'Rack → Rack',
            'icon'  => 'swap_horiz',
            'color' => '#7c3aed',
        ],
    ],
];
