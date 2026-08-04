<?php

namespace App\Livewire;

use App\Models\ForkliftTask;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\Split;
use App\Models\SplitDetail;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SplitProduce extends Component
{
    public $sourceProductId = '';

    public $targetProductId = '';

    public $wasteProductId = '';

    public $splitStatus = 'Draft';

    public $scannedBarcodes = [];

    public $barcodeInput = '';

    public $qtyHasil = 0;

    public $qtyWaste = 0;

    public $error = '';

    public $success = '';

    protected $listeners = ['barcodeScanned' => 'scanBarcode'];

    public function scanBarcode($code)
    {
        $this->error = '';
        $code = trim($code);

        if (empty($code)) {
            $this->error = 'Barcode tidak boleh kosong';

            return;
        }

        if (collect($this->scannedBarcodes)->contains('stock_code', $code)) {
            $this->error = 'Barcode sudah di-scan';

            return;
        }

        $stock = Stock::where('stock_code', $code)
            ->where('stock_type', Stock::TYPE_IN)
            ->where('stock_qty', '>', 0)
            ->first();

        if (! $stock) {
            $this->error = 'Stock tidak ditemukan atau tidak tersedia';

            return;
        }

        if (! empty($this->sourceProductId) && $this->sourceProductId != $stock->stock_id_product) {
            $this->error = 'Product barcode tidak sesuai dengan product asal yang dipilih';

            return;
        }

        if (! empty($this->targetProductId) && $this->targetProductId == $stock->stock_id_product) {
            $this->error = 'Product sumber tidak boleh sama dengan product target';

            return;
        }

        if (! empty($this->scannedBarcodes)) {
            $firstProduct = $this->scannedBarcodes[0]['stock_id_product'];
            if ($stock->stock_id_product !== $firstProduct) {
                $this->error = 'Product barcode harus sama dengan product sebelumnya';

                return;
            }
        }

        $this->scannedBarcodes[] = [
            'stock_id' => $stock->stock_id,
            'stock_code' => $stock->stock_code,
            'stock_id_product' => $stock->stock_id_product,
            'product_nama' => $stock->product->product_nama ?? '-',
            'stock_qty' => (float) $stock->stock_qty,
            'stock_expired_date' => $stock->stock_expired_date,
            'stock_code_lokasi' => $stock->stock_code_lokasi,
        ];

        $this->barcodeInput = '';
        $this->dispatch('show-toast', message: 'Barcode berhasil di-scan', type: 'success');
    }

    public function removeScan($index)
    {
        unset($this->scannedBarcodes[$index]);
        $this->scannedBarcodes = array_values($this->scannedBarcodes);
    }

    public function saveConfig()
    {
        $this->error = '';
        $this->success = '';

        if (empty($this->sourceProductId)) {
            $this->error = 'Pilih produk asal terlebih dahulu';

            return;
        }

        if (empty($this->targetProductId)) {
            $this->error = 'Pilih produk target terlebih dahulu';

            return;
        }

        if ($this->sourceProductId == $this->targetProductId) {
            $this->error = 'Produk asal dan target tidak boleh sama';

            return;
        }

        if (! empty($this->wasteProductId) && $this->wasteProductId == $this->sourceProductId) {
            $this->error = 'Produk waste tidak boleh sama dengan produk asal';

            return;
        }

        if (! empty($this->wasteProductId) && $this->wasteProductId == $this->targetProductId) {
            $this->error = 'Produk waste tidak boleh sama dengan produk target';

            return;
        }

        try {
            $split = Split::create([
                'split_id_product_source' => $this->sourceProductId,
                'split_id_product_target' => $this->targetProductId,
                'split_id_product_waste' => $this->wasteProductId ?: null,
                'split_qty_hasil' => 0,
                'split_qty_waste' => 0,
                'split_qty_penyusutan' => 0,
                'split_status' => $this->splitStatus,
                'split_tanggal' => now()->toDateString(),
            ]);

            $this->success = 'Konfigurasi split berhasil disimpan! (ID: '.$split->split_id.')';
            $this->dispatch('show-toast', message: $this->success, type: 'success');
        } catch (\Throwable $th) {
            $this->error = 'Gagal menyimpan: '.$th->getMessage();
        }
    }

    public function updatedTargetProductId()
    {
        $this->validateTargetProduct();
    }

    private function validateTargetProduct(): void
    {
        if (empty($this->targetProductId) || empty($this->scannedBarcodes)) {
            return;
        }

        $sourceProductId = $this->scannedBarcodes[0]['stock_id_product'];
        if ($this->targetProductId == $sourceProductId) {
            $this->error = 'Product target tidak boleh sama dengan product sumber';
            $this->targetProductId = '';
        }
    }

    public function getTotalSumberProperty(): float
    {
        return collect($this->scannedBarcodes)->sum('stock_qty');
    }

    public function getSourceProductName(): ?string
    {
        if (! empty($this->scannedBarcodes)) {
            return $this->scannedBarcodes[0]['product_nama'] ?? null;
        }

        if (! empty($this->sourceProductId)) {
            return Product::find($this->sourceProductId)?->product_nama;
        }

        return null;
    }

    public function getPenyusutanProperty(): float
    {
        $total = $this->totalSumber;
        $hasil = (float) $this->qtyHasil;
        $waste = (float) $this->qtyWaste;

        return max(0, $total - $hasil - $waste);
    }

    public function getIsValidProperty(): bool
    {
        if (empty($this->sourceProductId)) {
            return false;
        }
        if (empty($this->targetProductId)) {
            return false;
        }
        if (empty($this->scannedBarcodes)) {
            return false;
        }
        if ((float) $this->qtyHasil <= 0) {
            return false;
        }
        if ($this->penyusutan < 0) {
            return false;
        }

        return true;
    }

    public function process(): void
    {
        $this->error = '';
        $this->success = '';

        if (! $this->isValid) {
            $this->error = 'Lengkapi semua data terlebih dahulu';

            return;
        }

        try {
            DB::transaction(function () {
                $splitCode = 'SPLIT-'.uniqid();

                // 1. Create split record
                $split = Split::create([
                    'split_id_product_source' => $this->sourceProductId,
                    'split_id_product_target' => $this->targetProductId,
                    'split_id_product_waste' => $this->wasteProductId ?: null,
                    'split_qty_hasil' => $this->qtyHasil,
                    'split_qty_waste' => $this->qtyWaste,
                    'split_qty_penyusutan' => $this->penyusutan,
                    'split_status' => 'Processed',
                    'split_tanggal' => now()->toDateString(),
                ]);

                // 2. Create split_detail + decrement source stock
                foreach ($this->scannedBarcodes as $scan) {
                    SplitDetail::create([
                        'split_detail_id_split' => $split->split_id,
                        'split_detail_id_stock' => $scan['stock_id'],
                        'split_detail_qty' => $scan['stock_qty'],
                    ]);

                    Stock::where('stock_id', $scan['stock_id'])
                        ->decrement('stock_qty', $scan['stock_qty']);
                }

                // 3. Get staging area from first source
                $firstScan = $this->scannedBarcodes[0];
                $sourceLokasi = $firstScan['stock_code_lokasi'];
                $stagingCode = $this->getStagingForLokasi($sourceLokasi);

                // 4. Create STAGING stock for target product
                $targetExpired = $firstScan['stock_expired_date'];
                Stock::create([
                    'stock_id_product' => $this->targetProductId,
                    'stock_qty' => $this->qtyHasil,
                    'stock_type' => Stock::TYPE_STAGING,
                    'stock_code_lokasi' => $stagingCode,
                    'stock_expired_date' => $targetExpired,
                    'stock_reff' => $splitCode,
                    'stock_pallet_code' => $splitCode,
                ]);

                // 5. Create STAGING stock for waste product (if exists)
                if (! empty($this->wasteProductId) && $this->qtyWaste > 0) {
                    Stock::create([
                        'stock_id_product' => $this->wasteProductId,
                        'stock_qty' => $this->qtyWaste,
                        'stock_type' => Stock::TYPE_STAGING,
                        'stock_code_lokasi' => $stagingCode,
                        'stock_expired_date' => $targetExpired,
                        'stock_reff' => $splitCode,
                        'stock_pallet_code' => $splitCode,
                    ]);
                }

                // 6. Create ForkliftTask for putaway
                ForkliftTask::create([
                    'forklift_type' => ForkliftTask::TYPE_PUTAWAY,
                    'forklift_pallet_code' => $splitCode,
                    'forklift_lokasi_asal' => $stagingCode,
                    'forklift_lokasi_tujuan' => $sourceLokasi,
                    'forklift_reff' => $splitCode,
                    'forklift_status' => ForkliftTask::STATUS_PENDING,
                ]);
            });

            $this->success = 'Split berhasil diproses! Forklift task sudah dibuat.';
            $this->dispatch('show-toast', message: $this->success, type: 'success');

            $this->reset(['sourceProductId', 'targetProductId', 'wasteProductId', 'splitStatus', 'scannedBarcodes', 'barcodeInput', 'qtyHasil', 'qtyWaste']);
        } catch (\Throwable $th) {
            $this->error = 'Gagal memproses split: '.$th->getMessage();
        }
    }

    private function getStagingForLokasi(?string $lokasiCode): string
    {
        $lokasi = Lokasi::where('lokasi_code', $lokasiCode)->first();
        if ($lokasi && $lokasi->lokasi_category !== 'staging') {
            $staging = Lokasi::where('lokasi_code_gudang', $lokasi->lokasi_code_gudang)
                ->where('lokasi_category', 'staging')
                ->first();
            if ($staging) {
                return $staging->lokasi_code;
            }
        }

        return 'STG-A';
    }

    public function render()
    {
        $products = Product::pluck('product_nama', 'product_id');

        return view('livewire.split-produce', [
            'products' => $products,
            'totalSumber' => $this->totalSumber,
            'penyusutan' => $this->penyusutan,
            'isValid' => $this->isValid,
            'sourceProductName' => $this->getSourceProductName(),
        ]);
    }
}
