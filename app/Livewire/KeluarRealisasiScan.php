<?php

namespace App\Livewire;

use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Stock;
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
                if (str_starts_with($barcodeContent, $prefix['pallet'] ?? 'P')) {
                    $code = substr($barcodeContent, strlen($prefix['pallet']));
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

                // 4. Process pick per stock
                $pickedItems = [];
                $left = $remaining;
                $expiredDates = [];

                foreach ($stocks as $stock) {
                    if ($left <= 0) {
                        break;
                    }

                    $take = min((float) $stock->stock_qty, $left);
                    $stock->decrement('stock_qty', $take);

                    if ($stock->stock_expired_date) {
                        $expiredDates[] = $stock->stock_expired_date;
                    }

                    KeluarRealisasi::create([
                        'out_realisasi_id_detail' => $detail->out_detail_id,
                        'out_realisasi_id_stock' => $stock->stock_id,
                        'out_realisasi_qty' => $take,
                    ]);

                    $pickedItems[] = [
                        'stock_code' => $stock->stock_code,
                        'qty' => $take,
                    ];

                    $left -= $take;
                }

                $fulfilled = $remaining - $left;

                // 5. Create STAGING stock
                Stock::create([
                    'stock_id_product' => $detail->out_detail_id_product,
                    'stock_code_lokasi' => 'STAGING',
                    'stock_qty' => $fulfilled,
                    'stock_type' => Stock::TYPE_STAGING,
                    'stock_expired_date' => $expiredDates ? min($expiredDates) : null,
                    'stock_reff' => $detail->out_detail_code_keluar,
                ]);

                // 6. Consume RESERVE
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

                // 4. Delete realisasi
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
    }

    public function render()
    {
        return view('livewire.keluar-realisasi-scan');
    }
}
