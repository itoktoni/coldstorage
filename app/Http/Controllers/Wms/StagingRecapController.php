<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\Stock;

class StagingRecapController extends Controller
{
    /**
     * List all lokasi that have STAGING stock.
     */
    public function index()
    {
        $stagingLokasiCodes = Stock::where('stock_type', Stock::TYPE_STAGING)
            ->where('stock_qty', '>', 0)
            ->pluck('stock_code_lokasi')
            ->unique();

        $lokasis = Lokasi::whereIn('lokasi_code', $stagingLokasiCodes)
            ->withCount(['stock as total_stock' => function ($q) {
                $q->where('stock_type', Stock::TYPE_STAGING)->where('stock_qty', '>', 0);
            }])
            ->withSum(['stock as total_qty' => function ($q) {
                $q->where('stock_type', Stock::TYPE_STAGING)->where('stock_qty', '>', 0);
            }], 'stock_qty')
            ->get();

        return view('pages.staging-recap.index', compact('lokasis'));
    }

    /**
     * Show recap page — Livewire component handles all data + actions.
     */
    public function show(string $lokasiCode)
    {
        return view('pages.staging-recap.show', compact('lokasiCode'));
    }
}
