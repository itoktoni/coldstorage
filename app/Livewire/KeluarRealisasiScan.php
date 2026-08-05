<?php

namespace App\Livewire;

use App\Models\ForkliftTask;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Lokasi;
use App\Models\SoDetail;
use App\Models\SoPrepare;
use App\Models\SoPrepareDetail;
use App\Models\Stock;
use App\Models\StockAssignment;
use App\Wms\SoStatusEnum;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class KeluarRealisasiScan extends Component
{
    public $detailId;

    public $detail;

    public $keluar;

    public $realisasiList;

    public $qtyNeeded;

    public $qtyPicked;

    public $qtyRemaining;

    public $barcodeInput = '';

    public $errorMsg = '';

    public $successMsg = '';

    public $pallets;

    public function mount($detailId)
    {
        $this->detailId = $detailId;
        $this->refreshData();
    }

    public function scan($barcodeContent)
    {
        $this->errorMsg = '';
        $this->successMsg = '';

        $detail = KeluarDetail::with(['product', 'keluar', 'soDetail.so'])->findOrFail($this->detailId);

        try {
            DB::transaction(function () use ($detail, $barcodeContent) {
                $prefix = config('scan.prefix', ['pallet' => 'P', 'location' => 'L', 'barcode' => 'B']);

                // 1. Detect mode and find stocks (dari STAGING)
                // Pallet codes = PAL-xxx, barcodes = PROD-xxx#... (starts with P but NOT PAL)
                if (str_starts_with($barcodeContent, 'PAL-') || str_starts_with($barcodeContent, ($prefix['pallet'] ?? 'P').'AL-')) {
                    $code = $barcodeContent; // Use full code for pallet lookup
                    $stocks = Stock::where('stock_pallet_code', $code)
                        ->where('stock_type', Stock::TYPE_STAGING)
                        ->where('stock_qty', '>', 0)
                        ->orderBy('stock_expired_date')
                        ->lockForUpdate()
                        ->get();
                    $mode = 'pallet';
                } elseif (str_starts_with($barcodeContent, $prefix['location'] ?? 'L')) {
                    $code = substr($barcodeContent, strlen($prefix['location']));
                    $stocks = Stock::where('stock_code_lokasi', $code)
                        ->where('stock_id_product', $detail->out_detail_id_product)
                        ->where('stock_type', Stock::TYPE_STAGING)
                        ->where('stock_qty', '>', 0)
                        ->orderBy('stock_expired_date')
                        ->lockForUpdate()
                        ->get();
                    $mode = 'location';
                } else {
                    $code = str_starts_with($barcodeContent, $prefix['barcode'] ?? 'B')
                        ? substr($barcodeContent, strlen($prefix['barcode']))
                        : $barcodeContent;
                    $stocks = Stock::where('stock_code', $code)
                        ->where('stock_type', Stock::TYPE_STAGING)
                        ->where('stock_qty', '>', 0)
                        ->lockForUpdate()
                        ->get();
                    $mode = 'barcode';
                }

                if ($stocks->isEmpty()) {
                    throw new \RuntimeException('Barcode tidak dikenali atau stock tidak tersedia.');
                }

                // 2. Check product match (for pallet/location mode)
                if ($mode !== 'barcode') {
                    $wrongProduct = $stocks->first(fn ($s) => $s->stock_id_product != $detail->out_detail_id_product);
                    if ($wrongProduct) {
                        throw new \RuntimeException('Barcode ini untuk produk lain.');
                    }
                } else {
                    // Barcode mode: filter to matching product only
                    $stocks = $stocks->filter(fn ($s) => $s->stock_id_product == $detail->out_detail_id_product);
                    if ($stocks->isEmpty()) {
                        throw new \RuntimeException('Stock ini bukan untuk produk yang dibutuhkan.');
                    }
                }

                // 3. Check remaining
                $alreadyPicked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $detail->out_detail_id)
                    ->sum('out_realisasi_qty');
                $remaining = (float) $detail->out_detail_qty - $alreadyPicked;

                if ($remaining <= 0) {
                    throw new \RuntimeException('Item ini sudah terpenuhi.');
                }

                // 4. Validate: stock harus dialokasikan untuk keluar ini
                $outCode = $detail->out_detail_code_keluar;
                $assignedStockIds = StockAssignment::where('stock_assignment_id_keluar', $outCode)
                    ->pluck('stock_assignment_id_stock')
                    ->all();

                $unallocated = $stocks->filter(fn ($s) => ! in_array($s->stock_id, $assignedStockIds));
                if ($unallocated->isNotEmpty()) {
                    $codes = $unallocated->pluck('stock_code')->implode(', ');
                    throw new \RuntimeException("Stock {$codes} belum dialokasikan untuk keluar ini. Scan hanya boleh dari pallet yang sudah di-prepare.");
                }

                // 5. Process pick per stock
                $pickedItems = [];
                $left = $remaining;
                $expiredDates = [];

                // Ensure SoPrepare exists for this SO (needed by cetakDelivery / cetakInvoice / ship)
                $so = $detail->soDetail?->so;
                $prepare = null;
                if ($so) {
                    $prepare = SoPrepare::firstOrCreate(
                        ['so_prepare_id_so' => $so->so_id],
                        ['so_prepare_id_keluar' => $detail->out_detail_code_keluar]
                    );
                }

                foreach ($stocks as $stock) {
                    if ($left <= 0) {
                        break;
                    }

                    $take = min((float) $stock->stock_qty, $left);
                    $stock->decrement('stock_qty', $take);

                    if ((float) $stock->fresh()->stock_qty <= 0) {
                        $stock->delete();
                    }

                    if ($stock->stock_expired_date) {
                        $expiredDates[] = $stock->stock_expired_date;
                    }

                    $realisasi = KeluarRealisasi::create([
                        'out_realisasi_id_detail' => $detail->out_detail_id,
                        'out_realisasi_id_stock' => $stock->stock_id,
                        'out_realisasi_qty' => $take,
                    ]);

                    // Create so_prepare_detail so cetakDelivery / cetakInvoice / ship have real qty
                    if ($prepare) {
                        SoPrepareDetail::create([
                            'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
                            'so_prepare_detail_id_realisasi' => $realisasi->out_realisasi_id,
                            'so_prepare_detail_id_product' => $stock->stock_id_product,
                            'so_prepare_detail_id_stock' => $stock->stock_id,
                            'so_prepare_detail_qty' => $take,
                        ]);
                    }

                    $pickedItems[] = [
                        'stock_code' => $stock->stock_code,
                        'qty' => $take,
                    ];

                    $left -= $take;
                }

                $fulfilled = $remaining - $left;

                // 5. Consume RESERVE
                $soCode = $detail->soDetail?->so?->so_code ?? '';
                Stock::consumeReserve($soCode, $detail->out_detail_id_product, $fulfilled);

                // 7. Recompute keluar status
                $keluar = Keluar::where('out_code', $detail->out_detail_code_keluar)->first();
                if ($keluar) {
                    $allDetails = KeluarDetail::where('out_detail_code_keluar', $keluar->out_code)->get();
                    $totalPicked = 0;
                    foreach ($allDetails as $d) {
                        $totalPicked += (float) KeluarRealisasi::where('out_realisasi_id_detail', $d->out_detail_id)
                            ->sum('out_realisasi_qty');
                    }
                    $totalQty = (float) $allDetails->sum('out_detail_qty');

                    if ($totalPicked + 1e-9 >= $totalQty) {
                        $keluar->update(['out_status' => Keluar::STATUS_DONE]);
                        $this->cleanupZeroStock($keluar);
                    } elseif ($totalPicked > 0) {
                        $keluar->update(['out_status' => 'In Progress']);
                    }

                    // Selalu cek apakah semua produk SO sudah terpenuhi → SO Confirmed
                    $this->checkSoConfirmation($detail);
                }

                return $fulfilled;
            });

            $this->successMsg = 'Scan berhasil. Stock dialokasikan ke keluar.';
            $this->barcodeInput = '';
            $this->refreshData();
        } catch (\Throwable $th) {
            $this->errorMsg = $th->getMessage();
        }
    }

    public function removeRealisasi($realisasiId)
    {
        $this->errorMsg = '';
        $this->successMsg = '';

        try {
            DB::transaction(function () use ($realisasiId) {
                $realisasi = KeluarRealisasi::findOrFail($realisasiId);
                $detail = KeluarDetail::findOrFail($realisasi->out_realisasi_id_detail);

                // 1. Restore IN stock
                Stock::where('stock_id', $realisasi->out_realisasi_id_stock)
                    ->increment('stock_qty', $realisasi->out_realisasi_qty);

                // 2. Remove STAGING stock
                Stock::where('stock_type', Stock::TYPE_STAGING)
                    ->where('stock_reff', $detail->out_detail_code_keluar)
                    ->where('stock_id_product', $detail->out_detail_id_product)
                    ->orderByDesc('stock_id')
                    ->limit(1)
                    ->decrement('stock_qty', $realisasi->out_realisasi_qty);

                // 3. Restore RESERVE
                $soCode = $detail->soDetail?->so?->so_code ?? '';
                if ($soCode) {
                    $reserve = Stock::where('stock_type', Stock::TYPE_RESERVE)
                        ->where('stock_reff', $soCode)
                        ->where('stock_id_product', $detail->out_detail_id_product)
                        ->where('stock_qty', '>', 0)
                        ->first();
                    if ($reserve) {
                        $reserve->increment('stock_qty', $realisasi->out_realisasi_qty);
                    }
                }

                // 4. Delete realisasi + linked so_prepare_detail
                SoPrepareDetail::where('so_prepare_detail_id_realisasi', $realisasi->out_realisasi_id)->delete();
                $realisasi->delete();

                // 5. Recompute keluar status
                $keluar = Keluar::where('out_code', $detail->out_detail_code_keluar)->first();
                if ($keluar) {
                    $allDetails = KeluarDetail::where('out_detail_code_keluar', $keluar->out_code)->get();
                    $totalPicked = 0;
                    foreach ($allDetails as $d) {
                        $totalPicked += (float) KeluarRealisasi::where('out_realisasi_id_detail', $d->out_detail_id)
                            ->sum('out_realisasi_qty');
                    }
                    $totalQty = (float) $allDetails->sum('out_detail_qty');

                    if ($totalPicked <= 0) {
                        $keluar->update(['out_status' => Keluar::STATUS_PENDING]);
                    } elseif ($totalPicked + 1e-9 < $totalQty) {
                        $keluar->update(['out_status' => 'In Progress']);
                    }

                    // Jika SO sudah Confirmed, revert ke Prepare
                    $so = $detail->soDetail?->so;
                    if ($so && $so->so_status === SoStatusEnum::CONFIRMED) {
                        $so->update(['so_status' => SoStatusEnum::PREPARE]);
                        $prepare = SoPrepare::where('so_prepare_id_so', $so->so_id)->first();
                        if ($prepare) {
                            $prepare->update(['so_prepare_status' => SoPrepare::STATUS_PENDING]);
                        }
                    }
                }
            });

            $this->successMsg = 'Realisasi berhasil dihapus.';
            $this->refreshData();
        } catch (\Throwable $th) {
            $this->errorMsg = 'Gagal menghapus: '.$th->getMessage();
        }
    }

    private function cleanupZeroStock(Keluar $keluar): void
    {
        // Delete STAGING stock with qty 0 for this keluar
        Stock::where('stock_type', Stock::TYPE_STAGING)
            ->where('stock_reff', $keluar->out_code)
            ->where('stock_qty', '<=', 0)
            ->delete();

        // Delete RESERVE stock with qty 0 for related SOs
        $soCodes = $keluar->details()
            ->with('soDetail.so')
            ->get()
            ->pluck('soDetail.so.so_code')
            ->filter()
            ->values();

        if ($soCodes->isNotEmpty()) {
            Stock::where('stock_type', Stock::TYPE_RESERVE)
                ->whereIn('stock_reff', $soCodes)
                ->where('stock_qty', '<=', 0)
                ->delete();
        }
    }

    /**
     * Cek apakah semua keluar untuk SO ini sudah Done → SO Confirmed.
     */
    private function checkSoConfirmation(KeluarDetail $detail): void
    {
        $so = $detail->soDetail?->so;
        if (! $so || $so->so_status === SoStatusEnum::CONFIRMED) {
            return;
        }

        // Cek semua SO detail untuk SO ini — apakah semua qty sudah terpenuhi?
        $soDetails = SoDetail::where('so_detail_id_so', $so->so_id)->get();

        $allFulfilled = $soDetails->isNotEmpty() && $soDetails->every(function ($sd) {
            $totalNeeded = (float) $sd->so_detail_qty;
            $totalPicked = (float) KeluarDetail::where('out_detail_id_so_detail', $sd->so_detail_id)
                ->get()
                ->sum(fn ($kd) => KeluarRealisasi::where('out_realisasi_id_detail', $kd->out_detail_id)->sum('out_realisasi_qty'));

            return $totalPicked + 1e-9 >= $totalNeeded;
        });

        if ($allFulfilled) {
            $so->update(['so_status' => SoStatusEnum::CONFIRMED]);

            $prepare = SoPrepare::where('so_prepare_id_so', $so->so_id)->first();
            if ($prepare && $prepare->so_prepare_status !== SoPrepare::STATUS_DONE) {
                $prepare->update(['so_prepare_status' => SoPrepare::STATUS_DONE]);
            }
        }
    }

    private function refreshData(): void
    {
        $this->detail = KeluarDetail::with(['product', 'keluar', 'soDetail.so', 'realisasi.stock'])
            ->findOrFail($this->detailId);

        $this->keluar = $this->detail->keluar;

        $this->qtyNeeded = (float) $this->detail->out_detail_qty;
        $this->qtyPicked = (float) $this->detail->realisasi->sum('out_realisasi_qty');
        $this->qtyRemaining = max(0, $this->qtyNeeded - $this->qtyPicked);

        $this->realisasiList = $this->detail->realisasi()
            ->with('stock')
            ->orderByDesc('out_realisasi_id')
            ->get();

        // Load pallet assignments for this specific keluar detail
        $outCode = $this->detail->out_detail_code_keluar;
        $palletAssignments = StockAssignment::where('stock_assignment_id_keluar', $outCode)
            ->where('stock_assignment_id_keluar_detail', $this->detailId)
            ->with('stock')
            ->get();

        // Cari ForkliftTask via pallet codes (forklift_reff sekarang berisi SO/PO codes)
        $palletCodes = $palletAssignments->pluck('stock.stock_pallet_code')->filter()->unique()->values()->all();
        $tasks = ForkliftTask::where('forklift_type', 'pick')
            ->whereIn('forklift_pallet_code', $palletCodes)
            ->get()
            ->keyBy('forklift_pallet_code');

        $this->pallets = $palletAssignments
            ->groupBy(fn ($a) => $a->stock?->stock_pallet_code ?? 'NOPALLET')
            ->map(function ($assignments, $palletCode) use ($tasks) {
                $firstStock = $assignments->first()->stock;
                $rawTotal = (float) $assignments->sum('stock_assignment_qty');
                $totalQty = min($rawTotal, $this->qtyNeeded);
                $task = $tasks->get($palletCode);
                $pickedQty = (float) $assignments->sum(function ($a) {
                    return KeluarRealisasi::where('out_realisasi_id_detail', $a->stock_assignment_id_keluar_detail)
                        ->where('out_realisasi_id_stock', $a->stock_assignment_id_stock)
                        ->sum('out_realisasi_qty');
                });

                // Lokasi aktual dari task (jika Done) atau stock table
                if ($task && $task->forklift_status === 'Done' && $task->forklift_lokasi_final) {
                    $actualLocation = $task->forklift_lokasi_final;
                } else {
                    $actualLocation = Stock::where('stock_pallet_code', $palletCode)
                        ->where('stock_qty', '>', 0)
                        ->value('stock_code_lokasi');
                }

                return [
                    'pallet_code' => $palletCode,
                    'lokasi' => $actualLocation ?? ($firstStock?->stock_code_lokasi ?? '-'),
                    'is_staging' => $actualLocation && Lokasi::find($actualLocation)?->lokasi_category === 'staging',
                    'total_qty' => $totalQty,
                    'picked_qty' => $pickedQty,
                    'task_status' => $task?->forklift_status ?? '-',
                ];
            })
            ->values();
    }

    private function getStagingLokasiCode(): string
    {
        $lokasi = Lokasi::where('lokasi_category', 'staging')->first();

        return $lokasi?->lokasi_code ?? 'STAGING';
    }

    public function render()
    {
        return view('livewire.keluar-realisasi-scan');
    }
}
