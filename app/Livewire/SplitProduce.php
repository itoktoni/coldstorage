<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Split;
use App\Models\SplitTarget;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SplitProduce extends Component
{
    public $splitId = null;

    public $split = null;

    // Source
    public $sourceProductId = '';

    public $sourceProductName = '';

    // Waste
    public $wasteProductId = '';

    public $wasteProductName = '';

    // Targets
    public $targets = [];
    // Each target: ['product_id' => '', 'qty' => '', 'jumlah' => '']

    // Scanner
    public $barcodeInput = '';

    public $scannedBarcodes = [];

    public $totalSumber = 0;

    // Generate form
    public $generateProductId = '';

    public $generateQty = '';

    public $generateJumlah = '';

    // Summary
    public $totalHasil = 0;

    public $qtyWaste = 0;

    public $penyusutan = 0;

    public $expiredDate = '';

    // Status
    public $error = '';

    public $success = '';

    public $products = [];

    public function mount()
    {
        $this->products = Product::pluck('product_nama', 'product_id')->toArray();
    }

    // ── Source ──

    public function updatedSourceProductId()
    {
        if ($this->sourceProductId) {
            $this->sourceProductName = $this->products[$this->sourceProductId] ?? '';
        }
    }

    public function saveSource()
    {
        $this->error = '';
        $this->success = '';

        if (! $this->sourceProductId) {
            $this->error = 'Pilih produk asal terlebih dahulu.';

            return;
        }

        if ($this->splitId) {
            $this->split->update([
                'split_id_product_source' => $this->sourceProductId,
            ]);
        } else {
            $this->split = Split::create([
                'split_id_product_source' => $this->sourceProductId,
                'split_status' => 'Draft',
                'split_tanggal' => now()->toDateString(),
                'split_created_by' => auth()->id(),
                'split_created_at' => now(),
            ]);
            $this->splitId = $this->split->split_id;
        }

        $this->success = 'Produk asal tersimpan.';
    }

    // ── Waste ──

    public function updatedWasteProductId()
    {
        if ($this->wasteProductId) {
            $this->wasteProductName = $this->products[$this->wasteProductId] ?? '';
            $this->split->update(['split_id_product_waste' => $this->wasteProductId]);
        } else {
            $this->wasteProductName = '';
            $this->split->update(['split_id_product_waste' => null]);
        }
        $this->recalcSummary();
    }

    // ── Targets ──

    public function addTarget()
    {
        $this->targets[] = ['product_id' => '', 'qty' => '', 'jumlah' => '1'];
    }

    public function removeTarget($index)
    {
        unset($this->targets[$index]);
        $this->targets = array_values($this->targets);
        $this->saveTargets();
    }

    public function updatedTargets()
    {
        $this->saveTargets();
    }

    public function generateTargets()
    {
        if (! $this->generateProductId || ! $this->generateQty || ! $this->generateJumlah) {
            $this->error = 'Isi produk, qty, dan jumlah untuk generate.';

            return;
        }

        $qty = (float) $this->generateQty;
        $jumlah = (int) $this->generateJumlah;

        if ($qty <= 0 || $jumlah <= 0) {
            $this->error = 'Qty dan jumlah harus lebih dari 0.';

            return;
        }

        for ($i = 0; $i < $jumlah; $i++) {
            $this->targets[] = [
                'product_id' => $this->generateProductId,
                'qty' => $qty,
                'jumlah' => '1',
            ];
        }

        $this->generateProductId = '';
        $this->generateQty = '';
        $this->generateJumlah = '';

        $this->saveTargets();
    }

    public function saveTargets()
    {
        if (! $this->splitId) {
            return;
        }

        // Delete existing targets
        SplitTarget::where('split_target_id_split', $this->splitId)->delete();

        // Create new targets
        $urutan = 1;
        foreach ($this->targets as $target) {
            if (empty($target['product_id']) || empty($target['qty'])) {
                continue;
            }

            SplitTarget::create([
                'split_target_id_split' => $this->splitId,
                'split_target_id_product' => $target['product_id'],
                'split_target_qty' => $target['qty'],
                'split_target_jumlah' => $target['jumlah'] ?? 1,
                'split_target_urutan' => $urutan++,
            ]);
        }

        $this->recalcSummary();
    }

    // ── Scanner ──

    public function scanBarcode($barcode = null)
    {
        $this->error = '';
        $this->success = '';

        $barcode = $barcode ?? $this->barcodeInput;
        if (empty($barcode)) {
            return;
        }

        $barcode = trim($barcode);

        if (! $this->sourceProductId) {
            $this->error = 'Pilih dan simpan produk asal terlebih dahulu.';

            return;
        }

        $stock = Stock::with('product')->where('stock_code', $barcode)->first();

        if (! $stock) {
            $this->error = "Barcode '{$barcode}' tidak ditemukan di sistem.";

            return;
        }

        if ($stock->product->product_id != $this->sourceProductId) {
            $this->error = "Barcode '{$barcode}' adalah produk '{$stock->product->product_nama}', bukan produk asal yang dipilih ({$this->sourceProductName}).";

            return;
        }

        // Check duplicate
        foreach ($this->scannedBarcodes as $scan) {
            if ($scan['stock_id'] == $stock->stock_id) {
                $this->error = "Barcode '{$barcode}' sudah discan sebelumnya.";

                return;
            }
        }

        $this->scannedBarcodes[] = [
            'stock_id' => $stock->stock_id,
            'stock_code' => $stock->stock_code,
            'product_nama' => $stock->product->product_nama,
            'stock_qty' => $stock->stock_qty,
            'stock_expired_date' => $stock->stock_expired_date,
        ];

        $this->barcodeInput = '';
        $this->totalSumber = collect($this->scannedBarcodes)->sum('stock_qty');
    }

    public function removeScan($index)
    {
        unset($this->scannedBarcodes[$index]);
        $this->scannedBarcodes = array_values($this->scannedBarcodes);
        $this->totalSumber = collect($this->scannedBarcodes)->sum('stock_qty');
    }

    // ── Summary ──

    public function recalcSummary()
    {
        // Total hasil from targets
        $this->totalHasil = 0;
        foreach ($this->targets as $target) {
            $qty = (float) ($target['qty'] ?? 0);
            $jumlah = (int) ($target['jumlah'] ?? 1);
            $this->totalHasil += $qty * $jumlah;
        }

        // Waste
        $this->qtyWaste = 0;
    }

    // ── Process ──

    public function process()
    {
        $this->error = '';
        $this->success = '';

        if (! $this->splitId) {
            $this->error = 'Simpan konfigurasi split terlebih dahulu.';

            return;
        }

        if (count($this->scannedBarcodes) == 0) {
            $this->error = 'Scan minimal 1 barcode sumber.';

            return;
        }

        if (count($this->targets) == 0) {
            $this->error = 'Tambah minimal 1 target hasil split.';

            return;
        }

        $this->recalcSummary();

        if ($this->penyusutan < 0) {
            $this->error = 'Total hasil split melebihi total sumber. Periksa qty dan jumlah target.';

            return;
        }

        try {
            DB::transaction(function () {
                // 1. Process each scanned source stock
                foreach ($this->scannedBarcodes as $scan) {
                    $sourceStock = Stock::find($scan['stock_id']);
                    if (! $sourceStock) {
                        throw new \Exception("Stock {$scan['stock_code']} tidak ditemukan.");
                    }

                    // 2. Create new stock for each target
                    foreach ($this->targets as $target) {
                        if (empty($target['product_id']) || empty($target['qty'])) {
                            continue;
                        }

                        $qty = (float) $target['qty'];
                        $jumlah = (int) ($target['jumlah'] ?? 1);
                        $expiredDate = $this->expiredDate ?: $sourceStock->stock_expired_date;
                        $product = Product::find($target['product_id']);
                        $productCode = $product->product_code ?? 'PROD';

                        for ($i = 0; $i < $jumlah; $i++) {
                            // Generate barcode: {product_code}#{timestamp}{random}#{qty}#{expired_date}
                            $timestamp = now()->format('YmdHis');
                            $random = strtoupper(uniqid());
                            $barcode = implode('#', [
                                $productCode,
                                $timestamp.$random,
                                (string) $qty,
                                $expiredDate ? date('Ymd', strtotime($expiredDate)) : '-',
                            ]);

                            Stock::create([
                                'stock_code' => $barcode,
                                'stock_id_product' => $target['product_id'],
                                'stock_qty' => $qty,
                                'stock_type' => Stock::TYPE_IN,
                                'stock_expired_date' => $expiredDate,
                                'stock_reff' => $this->split->split_id,
                            ]);
                        }
                    }

                    // 3. Deduct source stock to 0
                    $sourceStock->update(['stock_qty' => 0]);
                }

                // 4. Update split status
                $this->split->update([
                    'split_status' => 'Active',
                ]);
            });

            $this->success = 'Split berhasil diproses! Stock sumber = 0, stock baru sudah dibuat.';
            $this->scannedBarcodes = [];
            $this->totalSumber = 0;
        } catch (\Exception $e) {
            $this->error = 'Gagal memproses split: '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.split-produce');
    }
}
