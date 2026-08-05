<?php

namespace App\Livewire;

use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\So;
use App\Models\SoPrepare;
use App\Models\SoPrepareDetail;
use App\Models\Stock;
use App\Wms\SoStatusEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SoPrepareScan extends Component
{
    public $soId;

    public $so;

    public $prepare;

    public $stockRows;

    public $lines;

    public $allocations;

    public $barcodeInput = '';

    public $assignQtys = [];

    public $errorMsg = '';

    public $successMsg = '';

    public function mount($soId)
    {
        $this->soId = $soId;
        $this->refreshData();
    }

    public function scan($barcodeContent)
    {
        $this->errorMsg = '';
        $this->successMsg = '';

        $so = So::with('details')->findOrFail($this->soId);

        if ($so->so_status !== SoStatusEnum::PREPARE) {
            $this->errorMsg = 'SO ini tidak berstatus Prepare.';

            return;
        }

        $prepare = SoPrepare::firstOrCreate(
            ['so_prepare_id_so' => $so->so_id],
            ['so_prepare_id_keluar' => $this->keluarCodeForSo($so)]
        );

        try {
            DB::transaction(function () use ($so, $prepare, $barcodeContent) {
                // 1. Find stock by barcode
                $stock = Stock::where('stock_code', $barcodeContent)->first();

                if (! $stock) {
                    throw new \RuntimeException('Stock tidak ditemukan.');
                }

                // 2. Check stock type - must be IN or staging
                if (! in_array($stock->stock_type, [Stock::TYPE_IN, Stock::TYPE_STAGING], true)) {
                    throw new \RuntimeException('Barcode ini tidak di prepare untuk SO.');
                }

                if ((float) $stock->stock_qty <= 0) {
                    throw new \RuntimeException('Stock ini sudah habis.');
                }

                // 2. Validate product matches SO
                $line = $so->details->firstWhere('so_detail_id_product', $stock->stock_id_product);
                if (! $line) {
                    throw new \RuntimeException('Product tidak ada di SO ini.');
                }

                // 3. Calculate allocation qty
                $assignedForLine = (float) $prepare->details()
                    ->where('so_prepare_detail_id_product', $stock->stock_id_product)
                    ->sum('so_prepare_detail_qty');
                $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;

                if ($lineRemaining <= 0) {
                    throw new \RuntimeException('Kebutuhan SO untuk product ini sudah terpenuhi.');
                }

                $assignedForStock = (float) $prepare->details()
                    ->where('so_prepare_detail_id_stock', $stock->stock_id)
                    ->sum('so_prepare_detail_qty');
                $stockRemaining = (float) $stock->stock_qty - $assignedForStock;

                if ($stockRemaining <= 0) {
                    throw new \RuntimeException('Stock ini sudah habis dialokasikan.');
                }

                $qty = min($lineRemaining, $stockRemaining);

                // 4. Create keluar_realisasi (only when a matching KeluarDetail exists)
                $keluarDetail = KeluarDetail::where('out_detail_code_keluar', $this->keluarCodeForSo($so))
                    ->where('out_detail_id_product', $stock->stock_id_product)
                    ->first();

                $realisasiId = null;
                if ($keluarDetail) {
                    $realisasi = KeluarRealisasi::create([
                        'out_realisasi_id_detail' => $keluarDetail->out_detail_id,
                        'out_realisasi_id_stock' => $stock->stock_id,
                        'out_realisasi_qty' => $qty,
                    ]);
                    $realisasiId = $realisasi->out_realisasi_id;
                }

                // 5. Create so_prepare_detail
                SoPrepareDetail::create([
                    'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
                    'so_prepare_detail_id_realisasi' => $realisasiId,
                    'so_prepare_detail_id_product' => $stock->stock_id_product,
                    'so_prepare_detail_id_stock' => $stock->stock_id,
                    'so_prepare_detail_qty' => $qty,
                ]);

                // 6. Decrement source stock
                Stock::where('stock_id', $stock->stock_id)->decrement('stock_qty', $qty);

                // 6b. Create STAGING stock for this allocation
                $keluarCode = $this->keluarCodeForSo($so);
                Stock::create([
                    'stock_id_product' => $stock->stock_id_product,
                    'stock_code_lokasi' => 'STAGING',
                    'stock_qty' => $qty,
                    'stock_type' => Stock::TYPE_STAGING,
                    'stock_expired_date' => $stock->stock_expired_date,
                    'stock_reff' => $keluarCode ?? $so->so_code,
                ]);

                // 6c. Consume RESERVE for this SO
                Stock::consumeReserve($so->so_code, $stock->stock_id_product, $qty);

                // 7. Check fulfillment
                $this->checkFulfillment($so, $prepare);
            });

            $this->successMsg = "Scan berhasil. Stock {$barcodeContent} dialokasikan.";
            $this->barcodeInput = '';
            $this->refreshData();
        } catch (\Throwable $th) {
            $this->errorMsg = $th->getMessage();
        }
    }

    public function assignStock($stockId, $qty)
    {
        $this->errorMsg = '';
        $this->successMsg = '';

        $qty = (float) $qty;
        if ($qty <= 0) {
            $this->errorMsg = 'Qty harus lebih dari 0.';

            return;
        }

        $so = So::with('details')->findOrFail($this->soId);

        if ($so->so_status !== SoStatusEnum::PREPARE) {
            $this->errorMsg = 'SO ini tidak berstatus Prepare.';

            return;
        }

        $prepare = SoPrepare::firstOrCreate(
            ['so_prepare_id_so' => $so->so_id],
            ['so_prepare_id_keluar' => $this->keluarCodeForSo($so)]
        );

        try {
            DB::transaction(function () use ($so, $prepare, $stockId, $qty) {
                $stock = Stock::where('stock_id', $stockId)
                    ->whereIn('stock_type', [Stock::TYPE_IN, Stock::TYPE_STAGING])
                    ->first();

                if (! $stock) {
                    throw new \RuntimeException('Stock tidak ditemukan di IN/staging.');
                }

                $line = $so->details->firstWhere('so_detail_id_product', $stock->stock_id_product);
                if (! $line) {
                    throw new \RuntimeException('Product tidak ada di SO ini.');
                }

                $assignedForLine = (float) $prepare->details()
                    ->where('so_prepare_detail_id_product', $stock->stock_id_product)
                    ->sum('so_prepare_detail_qty');
                $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;

                if ($qty > $lineRemaining + 0.001) {
                    throw new \RuntimeException('Qty melebihi sisa kebutuhan SO. Sisa: '.$lineRemaining);
                }

                $assignedForStock = (float) $prepare->details()
                    ->where('so_prepare_detail_id_stock', $stockId)
                    ->sum('so_prepare_detail_qty');
                $stockRemaining = (float) $stock->stock_qty - $assignedForStock;

                if ($qty > $stockRemaining + 0.001) {
                    throw new \RuntimeException('Qty melebihi sisa stock staging. Sisa: '.$stockRemaining);
                }

                $keluarDetail = KeluarDetail::where('out_detail_code_keluar', $this->keluarCodeForSo($so))
                    ->where('out_detail_id_product', $stock->stock_id_product)
                    ->first();

                $realisasiId = null;
                if ($keluarDetail) {
                    $realisasi = KeluarRealisasi::create([
                        'out_realisasi_id_detail' => $keluarDetail->out_detail_id,
                        'out_realisasi_id_stock' => $stock->stock_id,
                        'out_realisasi_qty' => $qty,
                    ]);
                    $realisasiId = $realisasi->out_realisasi_id;
                }

                SoPrepareDetail::create([
                    'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
                    'so_prepare_detail_id_realisasi' => $realisasiId,
                    'so_prepare_detail_id_product' => $stock->stock_id_product,
                    'so_prepare_detail_id_stock' => $stock->stock_id,
                    'so_prepare_detail_qty' => $qty,
                ]);

                Stock::where('stock_id', $stock->stock_id)->decrement('stock_qty', $qty);

                // Create STAGING stock for this allocation
                $keluarCode = $this->keluarCodeForSo($so);
                Stock::create([
                    'stock_id_product' => $stock->stock_id_product,
                    'stock_code_lokasi' => 'STAGING',
                    'stock_qty' => $qty,
                    'stock_type' => Stock::TYPE_STAGING,
                    'stock_expired_date' => $stock->stock_expired_date,
                    'stock_reff' => $keluarCode ?? $so->so_code,
                ]);

                // Consume RESERVE for this SO
                Stock::consumeReserve($so->so_code, $stock->stock_id_product, $qty);

                $this->checkFulfillment($so, $prepare);
            });

            $this->successMsg = 'Alokasi berhasil.';
            $this->refreshData();
        } catch (\Throwable $th) {
            $this->errorMsg = $th->getMessage();
        }
    }

    public function removeAllocation($detailId)
    {
        $this->errorMsg = '';
        $this->successMsg = '';

        try {
            DB::transaction(function () use ($detailId) {
                $detail = SoPrepareDetail::findOrFail($detailId);

                // 1. Restore source stock (IN)
                Stock::where('stock_id', $detail->so_prepare_detail_id_stock)
                    ->increment('stock_qty', $detail->so_prepare_detail_qty);

                // 1b. Remove STAGING stock created for this allocation
                $so = $this->so;
                $keluarCode = $this->keluarCodeForSo($so);
                Stock::where('stock_type', Stock::TYPE_STAGING)
                    ->where('stock_id_product', $detail->so_prepare_detail_id_product)
                    ->where('stock_reff', $keluarCode ?? $so->so_code)
                    ->where('stock_qty', '>=', $detail->so_prepare_detail_qty)
                    ->limit(1)
                    ->decrement('stock_qty', $detail->so_prepare_detail_qty);

                // 1c. Restore RESERVE for this SO
                if ($so) {
                    $reserve = Stock::where('stock_type', Stock::TYPE_RESERVE)
                        ->where('stock_reff', $so->so_code)
                        ->where('stock_id_product', $detail->so_prepare_detail_id_product)
                        ->first();
                    if ($reserve) {
                        $reserve->increment('stock_qty', $detail->so_prepare_detail_qty);
                    }
                }

                // 2. Delete keluar_realisasi (only if one was created)
                if ($detail->so_prepare_detail_id_realisasi) {
                    KeluarRealisasi::where('out_realisasi_id', $detail->so_prepare_detail_id_realisasi)->delete();
                }

                // 3. Delete so_prepare_detail
                $detail->delete();

                // 4. Revert prepare status if was Done
                $prepare = SoPrepare::firstOrCreate(
                    ['so_prepare_id_so' => $this->soId],
                    ['so_prepare_id_keluar' => $this->keluarCodeForSo($this->so)]
                );
                if ($prepare->so_prepare_status === SoPrepare::STATUS_DONE) {
                    $prepare->update(['so_prepare_status' => SoPrepare::STATUS_PENDING]);

                    // Also revert SO status from Confirmed → Prepare
                    if ($this->so->so_status === SoStatusEnum::CONFIRMED) {
                        $this->so->update(['so_status' => SoStatusEnum::PREPARE]);
                    }
                }
            });

            $this->successMsg = 'Alokasi berhasil dihapus.';
            $this->refreshData();
        } catch (\Throwable $th) {
            $this->errorMsg = 'Gagal menghapus: '.$th->getMessage();
        }
    }

    private function refreshData(): void
    {
        $this->so = So::with(['customer', 'details.product'])->findOrFail($this->soId);

        $this->prepare = SoPrepare::firstOrCreate(
            ['so_prepare_id_so' => $this->so->so_id],
            ['so_prepare_id_keluar' => $this->keluarCodeForSo($this->so)]
        );

        $this->stockRows = $this->stagedStockForSo();
        $this->lines = $this->prepareLineStatus();
        $this->allocations = $this->prepare
            ? $this->prepare->details()
                ->with('product')
                ->orderBy('so_prepare_detail_id', 'desc')
                ->get()
            : collect();

        // Initialize assignQtys for each stock row
        foreach ($this->stockRows as $sr) {
            if (! isset($this->assignQtys[$sr['stock_id']])) {
                $this->assignQtys[$sr['stock_id']] = rtrim(rtrim(number_format(min($sr['qty_remaining'], $sr['so_need_remaining']), 3, '.', ''), '0'), '.');
            }
        }
    }

    private function stagedStockForSo(): Collection
    {
        $productIds = $this->so->details->pluck('so_detail_id_product');

        $stocks = Stock::whereIn('stock_type', [Stock::TYPE_IN, Stock::TYPE_STAGING])
            ->where('stock_qty', '>', 0)
            ->whereIn('stock_id_product', $productIds)
            ->with('lokasi.gudang')
            ->get();

        $assignedByStock = $this->prepare
            ? $this->prepare->details()
                ->selectRaw('so_prepare_detail_id_stock, SUM(so_prepare_detail_qty) as total')
                ->groupBy('so_prepare_detail_id_stock')
                ->pluck('total', 'so_prepare_detail_id_stock')
            : collect();

        // Remaining SO need per product (qty_needed − allocated)
        $lineNeeds = [];
        foreach ($this->so->details as $detail) {
            $pid = $detail->so_detail_id_product;
            $lineNeeds[$pid] = (float) $detail->so_detail_qty - (float) $this->prepare->details()
                ->where('so_prepare_detail_id_product', $pid)
                ->sum('so_prepare_detail_qty');
        }

        return $stocks->map(function ($stock) use ($assignedByStock, $lineNeeds) {
            $assigned = (float) ($assignedByStock->get($stock->stock_id) ?? 0);
            $soNeed = max(0, $lineNeeds[$stock->stock_id_product] ?? 0);

            return [
                'stock_id' => $stock->stock_id,
                'stock_code' => $stock->stock_code,
                'product' => $stock->product,
                'lokasi_nama' => $stock->lokasi?->lokasi_nama ?? '-',
                'gudang_nama' => $stock->lokasi?->gudang?->gudang_nama ?? '-',
                'stock_qty' => (float) $stock->stock_qty,
                'qty_assigned' => $assigned,
                'qty_remaining' => max(0, (float) $stock->stock_qty - $assigned),
                'so_need_remaining' => $soNeed,
            ];
        });
    }

    private function prepareLineStatus(): array
    {
        return $this->so->details->map(function ($d) {
            // Hitung dari so_prepare_detail
            $assigned = (float) $this->prepare->details()
                ->where('so_prepare_detail_id_product', $d->so_detail_id_product)
                ->sum('so_prepare_detail_qty');

            // Hitung dari keluar-realisasi (stock sudah dipotong via warehouse scan)
            $keluarPicked = KeluarRealisasi::whereHas('detail', fn ($q) => $q->where('out_detail_id_so_detail', $d->so_detail_id))
                ->sum('out_realisasi_qty');

            $totalAssigned = $assigned + (float) $keluarPicked;

            return [
                'detail' => $d,
                'qty_needed' => (float) $d->so_detail_qty,
                'qty_assigned' => $totalAssigned,
                'qty_remaining' => max(0, (float) $d->so_detail_qty - $totalAssigned),
            ];
        })->all();
    }

    private function keluarCodeForSo(So $so): ?string
    {
        return KeluarDetail::whereHas('soDetail', fn ($q) => $q->where('so_detail_id_so', $so->so_id))
            ->value('out_detail_code_keluar');
    }

    private function checkFulfillment(So $so, SoPrepare $prepare): void
    {
        $fulfilled = true;
        foreach ($so->details as $d) {
            $assigned = (float) $prepare->details()
                ->where('so_prepare_detail_id_product', $d->so_detail_id_product)
                ->sum('so_prepare_detail_qty');

            $keluarPicked = KeluarRealisasi::whereHas('detail', fn ($q) => $q->where('out_detail_id_so_detail', $d->so_detail_id))
                ->sum('out_realisasi_qty');

            if ($assigned + (float) $keluarPicked + 1e-9 < (float) $d->so_detail_qty) {
                $fulfilled = false;
                break;
            }
        }

        if ($fulfilled) {
            $prepare->update(['so_prepare_status' => SoPrepare::STATUS_DONE]);
            $so->update(['so_status' => SoStatusEnum::CONFIRMED]);
        }
    }

    public function render()
    {
        return view('livewire.so-prepare-scan');
    }

    /**
     * Force-complete prepare even if partial. Marks prepare Done → SO Confirmed.
     * Invoice will be created from whatever real qty was allocated.
     */
    public function completePrepare()
    {
        $this->errorMsg = '';
        $this->successMsg = '';

        $so = So::with('details')->findOrFail($this->soId);
        $prepare = SoPrepare::where('so_prepare_id_so', $so->so_id)->first();

        if (! $prepare) {
            $this->errorMsg = 'Tidak ada data prepare untuk SO ini.';

            return;
        }

        $hasAllocation = $prepare->details()->count() > 0;
        if (! $hasAllocation) {
            $this->errorMsg = 'Belum ada alokasi barang. Scan atau alokasikan minimal 1 item.';

            return;
        }

        $prepare->update(['so_prepare_status' => SoPrepare::STATUS_DONE]);
        $so->update(['so_status' => SoStatusEnum::CONFIRMED]);

        $this->successMsg = 'Prepare diselesaikan. SO sudah Confirmed dan siap dikirim.';
        $this->refreshData();
    }
}
