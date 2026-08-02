<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockFlowController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('product_nama')->get();

        $staging = $this->getMasukQty('pending');
        $prepare = $this->getMasukQty('process');
        $in = DB::table('masuk_detail')
            ->select('in_detail_id_product', DB::raw('SUM(in_detail_qty) as total'))
            ->whereIn('in_detail_status', ['ready', 'complete'])
            ->groupBy('in_detail_id_product')
            ->pluck('total', 'in_detail_id_product');

        $reserved = $this->getReservedQty();
        $road = $this->getKeluarQty('In Progress');
        $done = $this->getKeluarQty('Done');

        $physicalStock = $this->getPhysicalStock();

        $rows = $products->map(function ($product) use ($staging, $prepare, $in, $reserved, $road, $done, $physicalStock) {
            $id = $product->product_id;
            $stagingQty = $staging->get($id, 0);
            $prepareQty = $prepare->get($id, 0);
            $inQty = $in->get($id, 0);
            $reservedQty = $reserved->get($id, 0);
            $roadQty = $road->get($id, 0);
            $doneQty = $done->get($id, 0);
            $physical = $physicalStock->get($id, 0);

            return [
                'product_id'   => $id,
                'product_nama' => $product->product_nama,
                'staging'      => $stagingQty,
                'prepare'      => $prepareQty,
                'in'           => $inQty,
                'reserved'     => $reservedQty,
                'road'         => $roadQty,
                'out'          => $doneQty,
                'physical'     => $physical,
            ];
        })->filter(fn ($row) =>
            $row['staging'] > 0 || $row['prepare'] > 0 || $row['in'] > 0 ||
            $row['reserved'] > 0 || $row['road'] > 0 || $row['out'] > 0 || $row['physical'] > 0
        )->values();

        return view('pages.stock-flow.index', ['rows' => $rows]);
    }

    private function getMasukQty(string $status)
    {
        return DB::table('masuk_detail')
            ->select('in_detail_id_product', DB::raw('SUM(in_detail_qty) as total'))
            ->where('in_detail_status', $status)
            ->groupBy('in_detail_id_product')
            ->pluck('total', 'in_detail_id_product');
    }

    private function getReservedQty()
    {
        return DB::table('detail_so')
            ->select('so_detail_id_product', DB::raw('SUM(so_detail_qty) as total'))
            ->groupBy('so_detail_id_product')
            ->pluck('total', 'so_detail_id_product');
    }

    private function getKeluarQty(string $status)
    {
        return DB::table('keluar_detail')
            ->join('keluar', 'keluar_detail.out_detail_code_keluar', '=', 'keluar.out_code')
            ->select('out_detail_id_product', DB::raw('SUM(out_detail_qty) as total'))
            ->where('keluar.out_status', $status)
            ->groupBy('out_detail_id_product')
            ->pluck('total', 'out_detail_id_product');
    }

    private function getPhysicalStock()
    {
        return DB::table('stock')
            ->select('stock_id_product', DB::raw('SUM(stock_qty) as total'))
            ->where('stock_type', 'IN')
            ->groupBy('stock_id_product')
            ->pluck('total', 'stock_id_product');
    }
}
