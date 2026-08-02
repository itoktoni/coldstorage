<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockSalesController extends Controller
{
    public function index()
    {
        $physicalStock = DB::table('stock')
            ->select('stock_id_product', DB::raw('SUM(stock_qty) as total'))
            ->where('stock_type', 'IN')
            ->groupBy('stock_id_product')
            ->pluck('total', 'stock_id_product');

        $reserved = DB::table('detail_so')
            ->select('so_detail_id_product', DB::raw('SUM(so_detail_qty) as total'))
            ->groupBy('so_detail_id_product')
            ->pluck('total', 'so_detail_id_product');

        $products = Product::orderBy('product_nama')->get();

        $rows = $products->map(function ($product) use ($physicalStock, $reserved) {
            $id = $product->product_id;
            $physical = $physicalStock->get($id, 0);
            $reservedQty = $reserved->get($id, 0);
            $available = max(0, $physical - $reservedQty);

            return [
                'product_id'   => $id,
                'product_nama' => $product->product_nama,
                'physical'     => $physical,
                'reserved'     => $reservedQty,
                'available'    => $available,
            ];
        })->filter(fn ($row) => $row['physical'] > 0 || $row['reserved'] > 0)->values();

        return view('pages.stock-sales.index', ['rows' => $rows]);
    }
}
