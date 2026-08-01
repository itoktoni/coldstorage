<?php

namespace App\Livewire;

use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Product;
use App\Wms\MasukStatusEnum;
use Livewire\Component;

class MasukRealisasiScanner extends Component
{
    public $masukDetailId;
    public $masukDetail;
    public $summary = null;
    public $scans;
    public $selectedProductId;
    public $barcodeInput = '';
    public $cameraActive = false;
    public $error = '';
    public $success = '';

    protected $listeners = ['barcodeScanned' => 'scan'];

    public function mount($masukDetailId)
    {
        $this->masukDetailId = $masukDetailId;
        $this->masukDetail = MasukDetail::with('product')->findOrFail($masukDetailId);
        $this->refreshSummary();
    }

    public function scan($barcodeContent)
    {
        $this->error = '';
        $this->success = '';

        // Parse barcode
        $parsed = $this->parseBarcode($barcodeContent);
        if (!$parsed) {
            $this->error = 'Format barcode tidak valid';
            $this->refreshSummary();
            return;
        }

        // Validate product exists
        $product = Product::where('product_code', $parsed['product_code'])->first();
        if (!$product) {
            $this->error = 'Product tidak ditemukan';
            $this->refreshSummary();
            return;
        }

        // Validate product matches masuk detail
        if ($product->product_id != $this->masukDetail->in_detail_id_product) {
            $this->error = 'Product tidak sesuai dengan masuk detail';
            $this->refreshSummary();
            return;
        }

        // Check for duplicate barcode
        $exists = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)
            ->where('in_realisasi_barcode', $barcodeContent)
            ->exists();
        if ($exists) {
            $this->error = 'Barcode ini sudah pernah di-scan';
            $this->refreshSummary();
            return;
        }

        // Create realisasi record
        MasukRealisasi::create([
            'in_realisasi_masuk_code' => $this->masukDetail->in_detail_code,
            'in_realisasi_id_product' => $product->product_id,
            'in_realisasi_qty' => $parsed['qty'],
            'in_realisasi_id_lokasi' => 1, // Staging location
            'in_realisasi_barcode' => $barcodeContent,
        ]);

        // Update status: pending → process on first scan
        if ($this->masukDetail->in_detail_status === MasukStatusEnum::PENDING) {
            $this->masukDetail->update(['in_detail_status' => MasukStatusEnum::PROCESS]);
            $this->masukDetail->refresh();
        }

        // Check if all qty scanned → process → ready
        $totalRealisasi = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)
            ->sum('in_realisasi_qty');
        if ($totalRealisasi >= $this->masukDetail->in_detail_qty && $this->masukDetail->in_detail_status === MasukStatusEnum::PROCESS) {
            $this->masukDetail->update(['in_detail_status' => MasukStatusEnum::READY]);
            $this->masukDetail->refresh();
        }

        $this->success = 'Barcode berhasil di-scan';
        $this->barcodeInput = '';
        $this->refreshSummary();
    }

    public function changeStatus(string $newStatus)
    {
        $enum = MasukStatusEnum::tryFrom($newStatus);
        if (!$enum) {
            $this->error = 'Status tidak valid';
            return;
        }

        $allowed = match($this->masukDetail->in_detail_status) {
            MasukStatusEnum::PENDING => [MasukStatusEnum::PROCESS],
            MasukStatusEnum::PROCESS => [MasukStatusEnum::READY],
            MasukStatusEnum::READY   => [MasukStatusEnum::COMPLETE],
            default => [],
        };

        if (!in_array($enum, $allowed)) {
            $this->error = 'Transisi status tidak diizinkan';
            return;
        }

        $this->masukDetail->update(['in_detail_status' => $enum]);
        $this->masukDetail->refresh();
        $this->success = 'Status diubah ke '.$enum->description();
    }

    public function parseBarcode($content)
    {
        $parts = explode('#', $content);
        if (count($parts) < 4) {
            return null;
        }

        return [
            'product_code' => $parts[0],
            'timestamp' => $parts[1],
            'qty' => (float) $parts[2],
            'expired_date' => $parts[3] === '-' ? null : $parts[3],
        ];
    }

    public function refreshSummary()
    {
        $this->summary = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)
            ->select('in_realisasi_id_product', \DB::raw('SUM(in_realisasi_qty) as total_qty'))
            ->groupBy('in_realisasi_id_product')
            ->with('product')
            ->get();
    }

    public function getDetail($productId)
    {
        $this->selectedProductId = $productId;
        $this->scans = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)
            ->where('in_realisasi_id_product', $productId)
            ->get();
    }

    public function deleteScan($realisasiId)
    {
        MasukRealisasi::findOrFail($realisasiId)->delete();
        $this->refreshSummary();
        if ($this->selectedProductId) {
            $this->getDetail($this->selectedProductId);
        }
    }

    public function closeDetail()
    {
        $this->selectedProductId = null;
        $this->scans = null;
    }

    public function render()
    {
        return view('livewire.masuk-realisasi-scanner');
    }
}
