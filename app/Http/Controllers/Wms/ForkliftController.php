<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Stock;
use App\Wms\MasukStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForkliftController extends Controller
{
    public function index()
    {
        // Get all masuk_detail with status = ready
        $readyItems = MasukDetail::with(['product', 'realisasi.product', 'realisasi.lokasi'])
            ->where('in_detail_status', MasukStatusEnum::READY)
            ->get();

        // For each ready item, find suitable locations
        $items = $readyItems->map(function ($item) {
            $productCategory = $item->product->product_category;
            $totalQty = $item->realisasi->sum('in_realisasi_qty');

            // Get all lokasi with their current stock
            $allLokasi = Lokasi::with('gudang')->get();

            // Filter lokasi that can accept this product
            $suitableLokasi = $allLokasi->filter(function ($lokasi) use ($productCategory, $totalQty) {
                // Check category compatibility
                if (!$lokasi->canAcceptCategory($productCategory)) {
                    return false;
                }

                // Check capacity
                if (!$lokasi->hasCapacity($totalQty)) {
                    return false;
                }

                return true;
            });

            // Find suggested lokasi (first suitable with least current qty)
            $suggestedLokasi = $suitableLokasi->sortBy(function ($lokasi) {
                return $lokasi->current_qty;
            })->first();

            return [
                'detail' => $item,
                'product' => $item->product,
                'total_qty' => $totalQty,
                'realisasi' => $item->realisasi,
                'suitable_lokasi' => $suitableLokasi,
                'suggested_lokasi_id' => $suggestedLokasi?->lokasi_id,
                'product_category' => $productCategory,
            ];
        });

        return view('pages.forklift.index', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'detail_code' => ['required', 'string', 'exists:masuk_detail,in_detail_code'],
            'lokasi_id' => ['required', 'integer', 'exists:lokasi,lokasi_id'],
        ]);

        $masukDetail = MasukDetail::with(['realisasi', 'product'])->findOrFail($request->detail_code);

        if ($masukDetail->in_detail_status !== MasukStatusEnum::READY) {
            return redirect()->back()->withErrors(['error' => 'Status bukan ready']);
        }

        $lokasi = Lokasi::findOrFail($request->lokasi_id);
        $totalQty = $masukDetail->realisasi->sum('in_realisasi_qty');
        $productCategory = $masukDetail->product->product_category;

        // Validate category
        if (!$lokasi->canAcceptCategory($productCategory)) {
            return redirect()->back()->withErrors([
                'error' => 'Lokasi ini tidak menerima kategori produk "'.$productCategory.'"'
            ]);
        }

        // Validate capacity
        if (!$lokasi->hasCapacity($totalQty)) {
            return redirect()->back()->withErrors([
                'error' => 'Lokasi ini tidak memiliki kapasitas cukup. Sisa: '.($lokasi->lokasi_max_qty - $lokasi->current_qty).', dibutuhkan: '.$totalQty
            ]);
        }

        try {
            DB::transaction(function () use ($masukDetail, $request, $totalQty) {
                // Create stock record
                Stock::create([
                    'stock_id_product'   => $masukDetail->in_detail_id_product,
                    'stock_id_lokasi'    => $request->lokasi_id,
                    'stock_qty'          => $totalQty,
                    'stock_type'         => 'IN',
                    'stock_expired_date' => now()->addDays(30),
                    'stock_reff'         => $masukDetail->in_detail_code,
                ]);

                // Update status to complete
                $masukDetail->update(['in_detail_status' => MasukStatusEnum::COMPLETE]);
            });

            flash()->success('Barang berhasil dipindahkan ke lokasi!');

            return redirect()->route('wms-forklift.index');
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
    }
}
