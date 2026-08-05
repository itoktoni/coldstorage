<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\ForkliftTask;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Lokasi;
use App\Models\SoPrepareDetail;
use App\Models\Stock;
use App\Models\StockAssignment;
use Carbon\Carbon;
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
     * Prepare dari keluar table — rencana pindah pallet ke staging.
     * FEFO: system rekomendasikan pallet expired paling cepat.
     */
    public function getPrepare(string $outCode)
    {
        $keluar = Keluar::with(['details.product', 'details.soDetail.so'])->where('out_code', $outCode)->firstOrFail();

        $lines = $keluar->details->map(function (KeluarDetail $detail) {
            // StockAssignment (dari keluar-prepare pallet selection)
            $assignedFromAssignment = (float) StockAssignment::where('stock_assignment_id_keluar_detail', $detail->out_detail_id)
                ->sum('stock_assignment_qty');

            // KeluarRealisasi (dari scan di halaman realisasi)
            $assignedFromRealisasi = (float) KeluarRealisasi::where('out_realisasi_id_detail', $detail->out_detail_id)
                ->sum('out_realisasi_qty');

            // SoPrepareDetail (dari SO prepare scan)
            $assignedFromSoPrepare = 0;
            if ($detail->out_detail_id_so_detail) {
                $assignedFromSoPrepare = (float) SoPrepareDetail::where('so_prepare_detail_id_product', $detail->out_detail_id_product)
                    ->whereHas('prepare', fn ($q) => $q->where('so_prepare_id_so', $detail->soDetail?->so?->so_id))
                    ->sum('so_prepare_detail_qty');
            }

            $assigned = max($assignedFromAssignment, $assignedFromRealisasi, $assignedFromSoPrepare);

            return [
                'detail' => $detail,
                'product' => $detail->product,
                'so_code' => $detail->soDetail?->so?->so_code ?? '-',
                'qty_needed' => (float) $detail->out_detail_qty,
                'qty_assigned' => $assigned,
                'qty_remaining' => max(0, (float) $detail->out_detail_qty - $assigned),
            ];
        });

        // Group available stock by pallet_code — pilih pallet, bukan barcode
        $pallets = Stock::where('stock_type', Stock::TYPE_IN)
            ->where('stock_qty', '>', 0)
            ->with('product', 'lokasi.gudang')
            ->orderByRaw('CASE WHEN stock_expired_date IS NULL THEN 1 ELSE 0 END, stock_expired_date ASC')
            ->get()
            ->groupBy('stock_pallet_code')
            ->map(function ($palletStocks) {
                $first = $palletStocks->first();
                $earliestExpiry = $palletStocks->pluck('stock_expired_date')->filter()->min();

                return [
                    'pallet_code' => $first->stock_pallet_code ?? 'NOPALLET',
                    'product_id' => $first->product->product_id,
                    'product_nama' => $first->product->product_nama ?? '-',
                    'total_qty' => (float) $palletStocks->sum('stock_qty'),
                    'barcode_count' => $palletStocks->count(),
                    'expired' => $earliestExpiry ? Carbon::parse($earliestExpiry)->format('Y-m-d') : null,
                    'lokasi_nama' => $first->lokasi?->lokasi_nama ?? '-',
                    'gudang_nama' => $first->lokasi?->gudang?->gudang_nama ?? '-',
                    'barcodes' => $palletStocks->pluck('stock_code')->all(),
                ];
            })
            ->values()
            ->sortBy([
                ['expired', 'asc'],
                ['total_qty', 'asc'],
            ])
            ->values();

        $recommended = $this->fefoRecommendation($lines, $pallets);

        $existingTasks = ForkliftTask::where('forklift_reff', $outCode)
            ->where('forklift_type', 'pick')
            ->get()
            ->map(function ($task) {
                // Jika task Done, pakai lokasi_final (lokasi aktual setelah forklift memindah)
                if ($task->forklift_status === 'Done' && $task->forklift_lokasi_final) {
                    $actualLocation = $task->forklift_lokasi_final;
                } else {
                    // Sebelum done, ambil dari stock table
                    $actualLocation = Stock::where('stock_pallet_code', $task->forklift_pallet_code)
                        ->where('stock_qty', '>', 0)
                        ->value('stock_code_lokasi') ?? $task->forklift_lokasi_asal;
                }

                return [
                    'task' => $task,
                    'pallet_code' => $task->forklift_pallet_code,
                    'dari' => $actualLocation,
                    'status' => $task->forklift_status,
                    'is_staging' => Lokasi::find($actualLocation)?->lokasi_category === 'staging',
                ];
            });

        return view('pages.keluar.prepare', [
            'keluar' => $keluar,
            'lines' => $lines,
            'pallets' => $pallets,
            'recommended' => $recommended,
            'existingTasks' => $existingTasks,
        ]);
    }

    private function fefoRecommendation($lines, $pallets): array
    {
        $needs = $lines->pluck('qty_remaining', 'detail.out_detail_id_product')->filter(fn ($v) => $v > 0)->all();
        if (empty($needs)) {
            return [];
        }

        $recommended = [];
        $palletsByProduct = $pallets->groupBy('product_id');

        foreach ($needs as $productId => $qtyNeeded) {
            $remaining = $qtyNeeded;
            foreach ($palletsByProduct->get($productId, collect()) as $pallet) {
                if ($remaining <= 0) {
                    break;
                }
                $recommended[] = $pallet['pallet_code'];
                $remaining -= $pallet['total_qty'];
            }
        }

        return $recommended;
    }

    /**
     * Simpan rencana pemindahan pallet ke staging.
     * Buat ForkliftTask per pallet yang dipilih.
     */
    public function postPrepare(Request $request, string $outCode)
    {
        $keluar = Keluar::with('details')->where('out_code', $outCode)->firstOrFail();

        $data = $request->validate([
            'pallets' => ['nullable', 'array'],
            'pallets.*' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($outCode, $data, $keluar) {
                ForkliftTask::where('forklift_reff', $outCode)->where('forklift_type', 'pick')->delete();
                StockAssignment::where('stock_assignment_id_keluar', $outCode)->delete();

                $selectedPallets = $data['pallets'] ?? [];
                $stagingSuggest = Lokasi::where('lokasi_category', 'staging')->first()?->lokasi_code;

                foreach ($selectedPallets as $palletCode) {
                    $palletStocks = Stock::where('stock_pallet_code', $palletCode)
                        ->where('stock_type', Stock::TYPE_IN)
                        ->where('stock_qty', '>', 0)
                        ->get();

                    if ($palletStocks->isEmpty()) {
                        continue;
                    }

                    $firstStock = $palletStocks->first();

                    ForkliftTask::firstOrCreate(
                        ['forklift_type' => 'pick', 'forklift_pallet_code' => $palletCode, 'forklift_reff' => $outCode],
                        [
                            'forklift_type' => 'pick',
                            'forklift_pallet_code' => $palletCode,
                            'forklift_lokasi_asal' => $firstStock->stock_code_lokasi,
                            'forklift_lokasi_tujuan' => $stagingSuggest,
                            'forklift_reff' => $outCode,
                            'forklift_status' => 'Pending',
                        ]
                    );

                    foreach ($palletStocks as $stock) {
                        $detail = $keluar->details->first(fn ($d) => $d->out_detail_id_product === $stock->stock_id_product);
                        if (! $detail) {
                            continue;
                        }

                        StockAssignment::create([
                            'stock_assignment_id_keluar' => $outCode,
                            'stock_assignment_id_stock' => $stock->stock_id,
                            'stock_assignment_id_keluar_detail' => $detail->out_detail_id,
                            'stock_assignment_id_so_detail' => $detail->out_detail_id_so_detail,
                            'stock_assignment_qty' => (float) $stock->stock_qty,
                            'stock_assignment_status' => 'Pending',
                        ]);
                    }
                }
            });

            $count = count($data['pallets'] ?? []);
            flash()->success("Rencana pemindahan pallet berhasil. {$count} pallet akan dipindah ke staging.");

            return redirect()->route('wms-keluar-prepare.show', ['outCode' => $outCode]);
        } catch (\Throwable $th) {
            flash()->error($th->getMessage());

            return back()->withInput();
        }
    }

    /**
     * Halaman realisasi pick per keluar detail (Livewire scan).
     */
    public function realisasiScan(string $detailId)
    {
        $detail = KeluarDetail::with(['product', 'keluar', 'soDetail.so'])->findOrFail($detailId);

        return view('pages.keluar.realisasi-scan', [
            'detailId' => $detail->out_detail_id,
        ]);
    }
}
