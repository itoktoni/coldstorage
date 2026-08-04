<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\ForkliftTask;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\Lokasi;
use App\Models\Stock;
use App\Models\StockAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeluarController extends Controller
{
    use ControllerTrait;

    public function __construct(Keluar $model)
    {
        $this->model = $model::getModel();
    }

    protected function getData()
    {
        return $this->model->with(['details.realisasi'])->filter()->sort();
    }

    /**
     * Prepare dari keluar table — tampilkan detail keluar + alokasi stock.
     */
    public function getPrepare(string $outCode)
    {
        $keluar = Keluar::with(['details.product', 'details.soDetail.so'])->where('out_code', $outCode)->firstOrFail();

        // Build detail lines: qty needed vs assigned
        $lines = $keluar->details->map(function (KeluarDetail $detail) {
            $assigned = (float) StockAssignment::where('stock_assignment_id_keluar_detail', $detail->out_detail_id)
                ->sum('stock_assignment_qty');

            return [
                'detail'        => $detail,
                'product'       => $detail->product,
                'so_code'       => $detail->soDetail?->so?->so_code ?? '-',
                'qty_needed'    => (float) $detail->out_detail_qty,
                'qty_assigned'  => $assigned,
                'qty_remaining' => max(0, (float) $detail->out_detail_qty - $assigned),
            ];
        });

        // Available stock grouped by product (type=IN, qty>0)
        $availableStock = Stock::where('stock_type', Stock::TYPE_IN)
            ->where('stock_qty', '>', 0)
            ->orderByRaw('CASE WHEN stock_expired_date IS NULL THEN 1 ELSE 0 END, stock_expired_date ASC')
            ->with('lokasi.gudang')
            ->get()
            ->groupBy('stock_id_product')
            ->map(function ($stocks) {
                return $stocks->map(function ($s) {
                    $assignedQty = StockAssignment::where('stock_assignment_id_stock', $s->stock_id)
                        ->whereIn('stock_assignment_status', ['Pending', 'Picked'])
                        ->sum('stock_assignment_qty');
                    $remaining = max(0, (float) $s->stock_qty - $assignedQty);
                    return [
                        'stock_id'    => $s->stock_id,
                        'stock_code'  => $s->stock_code,
                        'lokasi_code' => $s->stock_code_lokasi,
                        'lokasi_nama' => $s->lokasi?->lokasi_nama ?? '-',
                        'gudang_nama' => $s->lokasi?->gudang?->gudang_nama ?? '-',
                        'stock_qty'   => (float) $s->stock_qty,
                        'remaining'   => $remaining,
                        'expired'     => optional($s->stock_expired_date)->format('Y-m-d'),
                    ];
                });
            });

        // Existing assignments grouped by keluar_detail_id
        $existingAssignments = StockAssignment::where('stock_assignment_id_keluar', $outCode)
            ->with('stock')
            ->get()
            ->groupBy('stock_assignment_id_keluar_detail');

        // Auto-allocate FEFO: suggest qty per (detail_id, stock_id) pair
        $suggestions = collect();
        foreach ($lines as $line) {
            $need = $line['qty_remaining'];
            $productId = $line['product']->product_id ?? null;
            if ($need <= 0 || !$productId) continue;

            $stocks = $availableStock->get($productId, collect());
            foreach ($stocks as $s) {
                if ($need <= 0) break;
                $take = min($s['remaining'], $need);
                $key = $line['detail']->out_detail_id . '_' . $s['stock_id'];
                $suggestions->put($key, $take);
                $need -= $take;
            }
        }

        return view('pages.keluar.prepare', [
            'keluar'              => $keluar,
            'lines'               => $lines,
            'availableStock'      => $availableStock,
            'existingAssignments' => $existingAssignments,
            'suggestions'         => $suggestions,
        ]);
    }

    /**
     * Simpan alokasi stock untuk keluar detail.
     */
    public function postPrepare(Request $request, string $outCode)
    {
        $keluar = Keluar::where('out_code', $outCode)->firstOrFail();

        $data = $request->validate([
            'assign'     => ['nullable', 'array'],
            'assign.*'   => ['nullable', 'array'],
        ]);

        try {
            DB::transaction(function () use ($outCode, $data) {
                // Hapus assignment lama
                StockAssignment::where('stock_assignment_id_keluar', $outCode)->delete();

                if (!empty($data['assign'])) {
                    foreach ($data['assign'] as $detailId => $stocks) {
                        foreach ($stocks as $key => $row) {
                            if (!is_array($row)) {
                                continue;
                            }
                            $stockId = $row['stock_id'] ?? $key;
                            $keluarDetailId = $row['keluar_detail_id'] ?? $detailId;
                            $qty = (float) ($row['qty'] ?? 0);

                            if ($qty <= 0) {
                                continue;
                            }

                            $keluarDetail = KeluarDetail::findOrFail($keluarDetailId);
                            $stock = Stock::where('stock_id', $stockId)->where('stock_type', Stock::TYPE_IN)->first();
                            if (!$stock) {
                                throw new \RuntimeException("Stock ID {$stockId} tidak tersedia.");
                            }

                            StockAssignment::create([
                                'stock_assignment_id_keluar'        => $outCode,
                                'stock_assignment_id_stock'         => $stockId,
                                'stock_assignment_id_keluar_detail' => $keluarDetailId,
                                'stock_assignment_id_so_detail'     => $keluarDetail->out_detail_id_so_detail,
                                'stock_assignment_qty'              => $qty,
                                'stock_assignment_status'           => 'Pending',
                            ]);
                        }
                    }
                }
            });

            flash()->success('Alokasi stock berhasil disimpan.');

            // Auto-create pick tasks per pallet
            $palletGroups = StockAssignment::where('stock_assignment_id_keluar', $outCode)
                ->with('stock')
                ->get()
                ->groupBy(fn ($a) => $a->stock?->stock_pallet_code ?? 'NOPALLET');

            foreach ($palletGroups as $palletCode => $assignments) {
                $firstStock = $assignments->first()->stock;
                $rackAsal = $firstStock?->stock_code_lokasi;
                $stagingSuggest = Lokasi::where('lokasi_category', 'staging')->first()?->lokasi_code;

                ForkliftTask::firstOrCreate(
                    ['forklift_type' => 'pick', 'forklift_pallet_code' => $palletCode, 'forklift_reff' => $outCode],
                    [
                        'forklift_type'          => 'pick',
                        'forklift_pallet_code'   => $palletCode,
                        'forklift_lokasi_asal'   => $rackAsal,
                        'forklift_lokasi_tujuan'  => $stagingSuggest,
                        'forklift_reff'          => $outCode,
                        'forklift_status'        => 'Pending',
                    ]
                );
            }

            return redirect()->route('wms-keluar-prepare.show', ['outCode' => $outCode]);
        } catch (\Throwable $th) {
            flash()->error($th->getMessage());
            return back()->withInput();
        }
    }
}
