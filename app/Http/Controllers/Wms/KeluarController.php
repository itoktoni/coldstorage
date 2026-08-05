<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\ForkliftTask;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\PoDetail;
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
        return $this->model
            ->withCount(['details as detail_count', 'assignments as assignments_count'])
            ->withExists(['details as has_so_detail' => fn ($q) => $q->whereNotNull('out_detail_id_so_detail')])
            ->filter()
            ->sort();
    }

    /**
     * Prepare dari keluar table — rencana pindah pallet ke staging.
     * FEFO: system rekomendasikan pallet expired paling cepat.
     */
    public function getPrepare(string $outCode)
    {
        $keluar = Keluar::with(['details.product', 'details.soDetail.so'])->where('out_code', $outCode)->firstOrFail();

        $lines = $keluar->details->map(function (KeluarDetail $detail) {
            // Teralokasi = qty yang sudah physically scan (bukan pre-allocation)
            // Pakai SoPrepareDetail sebagai source utama (sudah include scan dari SO prepare flow)
            // Tidak perlu jumlahkan dengan KeluarRealisasi karena keduanya dibuat bersamaan saat scan
            $assigned = 0;
            if ($detail->out_detail_id_so_detail) {
                $assigned = (float) SoPrepareDetail::where('so_prepare_detail_id_product', $detail->out_detail_id_product)
                    ->whereHas('prepare', fn ($q) => $q
                        ->where('so_prepare_id_so', $detail->soDetail?->so?->so_id)
                        ->where('so_prepare_id_keluar', $detail->keluar?->out_code)
                    )
                    ->sum('so_prepare_detail_qty');
            }

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

        // Cari existing tasks via pallet codes (forklift_reff sekarang berisi SO/PO codes)
        $existingPalletCodes = $keluar->details->pluck('out_detail_id_product')
            ->pipe(fn ($pids) => Stock::whereIn('stock_id_product', $pids)->pluck('stock_pallet_code')->unique()->values()->all());
        $existingTasks = ForkliftTask::where('forklift_type', 'pick')
            ->whereIn('forklift_pallet_code', $existingPalletCodes)
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
        $keluar = Keluar::with('details.soDetail.so')->where('out_code', $outCode)->firstOrFail();

        $data = $request->validate([
            'pallets' => ['nullable', 'array'],
            'pallets.*' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($outCode, $data, $keluar) {
                // Hapus pick tasks lama untuk pallet-pallet di keluar ini
                $oldPalletCodes = $keluar->details->pluck('out_detail_id_product')
                    ->pipe(fn ($pids) => Stock::whereIn('stock_id_product', $pids)->pluck('stock_pallet_code')->unique()->values()->all());
                ForkliftTask::where('forklift_type', 'pick')
                    ->whereIn('forklift_pallet_code', $oldPalletCodes)
                    ->delete();
                StockAssignment::where('stock_assignment_id_keluar', $outCode)->delete();

                // SO codes dari keluar details yang masih butuh (sisa > 0)
                $soCodes = $keluar->details->filter(function (KeluarDetail $detail) {
                    // Hitung sisa pakai logika yang sama dengan getPrepare()
                    $assigned = 0;
                    if ($detail->out_detail_id_so_detail) {
                        $assigned = (float) SoPrepareDetail::where('so_prepare_detail_id_product', $detail->out_detail_id_product)
                            ->whereHas('prepare', fn ($q) => $q
                                ->where('so_prepare_id_so', $detail->soDetail?->so?->so_id)
                                ->where('so_prepare_id_keluar', $detail->keluar?->out_code)
                            )
                            ->sum('so_prepare_detail_qty');
                    }

                    return max(0, (float) $detail->out_detail_qty - $assigned) > 0;
                })
                    ->pluck('soDetail.so.so_code')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

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

                    // Resolve PO codes dari pallet ini
                    $poCodes = MasukRealisasi::whereIn('in_realisasi_group', $palletStocks->pluck('stock_pallet_code')->unique())
                        ->pluck('in_realisasi_masuk_code')
                        ->pipe(fn ($detailCodes) => MasukDetail::whereIn('in_detail_code', $detailCodes)
                            ->pluck('in_detail_reff')
                            ->pipe(fn ($reffs) => PoDetail::whereIn('po_detail_code', $reffs)
                                ->with('po')
                                ->get()
                                ->pluck('po.po_code')
                                ->filter()
                                ->unique()
                                ->values()
                                ->all()
                            )
                        );

                    $reffParts = array_merge($soCodes, $poCodes);
                    $reffLabel = $reffParts ? implode(', ', $reffParts) : $outCode;

                    ForkliftTask::firstOrCreate(
                        ['forklift_type' => 'pick', 'forklift_pallet_code' => $palletCode, 'forklift_reff' => $reffLabel],
                        [
                            'forklift_type' => 'pick',
                            'forklift_pallet_code' => $palletCode,
                            'forklift_lokasi_asal' => $firstStock->stock_code_lokasi,
                            'forklift_lokasi_tujuan' => $stagingSuggest,
                            'forklift_reff' => $reffLabel,
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

    public function pickList(string $outCode)
    {
        $keluar = Keluar::with(['details.product', 'details.assignments.stock', 'details.soDetail.so'])->where('out_code', $outCode)->firstOrFail();

        $allLines = $keluar->details->map(function ($detail) {
            $assigned = (float) $detail->assignments->sum('stock_assignment_qty');
            $picked = (float) $detail->realisasi()->sum('out_realisasi_qty');
            $assignments = $detail->assignments->map(fn ($a) => [
                'pallet_code' => $a->stock?->stock_pallet_code ?? '-',
                'lokasi' => $a->stock?->stock_code_lokasi ?? '-',
                'qty' => (float) $a->stock_assignment_qty,
            ]);

            return [
                'so_code' => $detail->soDetail?->so?->so_code ?? $detail->out_detail_reff ?? '-',
                'product_nama' => $detail->product->product_nama ?? '-',
                'product_kode' => $detail->product->product_kode ?? '-',
                'qty_needed' => (float) $detail->out_detail_qty,
                'qty_assigned' => $assigned,
                'qty_remaining' => (float) $detail->out_detail_qty - $picked,
                'assignments' => $assignments,
            ];
        });

        $soLines = $allLines->filter(fn ($line) => $line['qty_remaining'] > 0)->values();

        $soGrouped = $soLines->groupBy('so_code')->map(function ($items, $soCode) {
            return [
                'so_code' => $soCode,
                'items' => $items,
                'total_qty' => $items->sum('qty_needed'),
            ];
        })->values();

        $palletGroups = $allLines->filter(fn ($line) => $line['assignments']->isNotEmpty())
            ->groupBy('product_nama')->map(function ($items, $productNama) {
                return [
                    'product_nama' => $productNama,
                    'product_kode' => $items->first()['product_kode'],
                    'assignments' => $items->flatMap(fn ($item) => $item['assignments']),
                ];
            })->values();

        return view('pages.keluar.pick-list', [
            'keluar' => $keluar,
            'soLines' => $soGrouped,
            'palletGroups' => $palletGroups,
        ]);
    }
}
