<?php

namespace App\Livewire;

use App\Models\ForkliftTask;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Po;
use App\Models\Product;
use App\Models\Stock;
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

    public $existingStockBarcodes = [];

    public $stagingCode = '';

    protected $listeners = ['barcodeScanned' => 'scan'];

    public function mount($masukDetailId)
    {
        $this->masukDetailId = $masukDetailId;
        $this->masukDetail = MasukDetail::with('product')->findOrFail($masukDetailId);
        $this->stagingCode = $this->masukDetail->in_detail_id_staging ?? '';
        $this->refreshSummary();
    }

    public function scan($barcodeContent)
    {
        $this->error = '';
        $this->success = '';

        if (empty($this->stagingCode)) {
            $this->error = 'Pilih staging area terlebih dahulu';
            $this->refreshSummary();

            return;
        }

        // Parse barcode
        $parsed = $this->parseBarcode($barcodeContent);
        if (! $parsed) {
            $this->error = 'Format barcode tidak valid';
            $this->refreshSummary();

            return;
        }

        // Validate product exists
        $product = Product::where('product_code', $parsed['product_code'])->first();
        if (! $product) {
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

        // Check for duplicate barcode in this detail
        $exists = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)
            ->where('in_realisasi_barcode', $barcodeContent)
            ->exists();
        if ($exists) {
            $this->error = 'Barcode ini sudah pernah di-scan';
            $this->refreshSummary();

            return;
        }

        // Check if barcode already exists in stock (from previous realisasi)
        $stockExists = Stock::where('stock_code', $barcodeContent)->exists();
        if ($stockExists) {
            $this->error = 'Barcode ini sudah terdaftar di stock. Tidak bisa di-scan lagi.';
            $this->refreshSummary();

            return;
        }

        // Create realisasi record
        MasukRealisasi::create([
            'in_realisasi_masuk_code' => $this->masukDetail->in_detail_code,
            'in_realisasi_id_product' => $product->product_id,
            'in_realisasi_qty' => $parsed['qty'],
            'in_realisasi_code_lokasi' => $this->stagingLokasiCode(),
            'in_realisasi_barcode' => $barcodeContent,
        ]);

        // Update status: pending → process on first scan
        if ($this->masukDetail->in_detail_status === MasukStatusEnum::PENDING) {
            $this->masukDetail->update(['in_detail_status' => MasukStatusEnum::PROCESS]);
            $this->masukDetail->refresh();
            $this->updatePoStatus();
        }

        // Check if all qty scanned → process → ready
        $totalRealisasi = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)
            ->sum('in_realisasi_qty');

        // Check if any scanned barcode already exists in stock (from OTHER sessions)
        $groupCode = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)->value('in_realisasi_group');
        $existingBarcodes = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)
            ->whereNotNull('in_realisasi_barcode')
            ->pluck('in_realisasi_barcode')
            ->filter(fn ($bc) => Stock::where('stock_code', $bc)
                ->where(function ($q) use ($groupCode) {
                    $q->whereNull('stock_reff')
                        ->orWhere('stock_reff', '!=', $groupCode);
                })
                ->exists());

        if ($existingBarcodes->isNotEmpty() && $totalRealisasi >= $this->masukDetail->in_detail_qty && $this->masukDetail->in_detail_status === MasukStatusEnum::PROCESS) {
            $count = $existingBarcodes->count();
            $total = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)->count();
            $this->error = "Tidak bisa READY: {$count} dari {$total} barcode sudah terdaftar di stock. Hapus barcode duplikat terlebih dahulu.";
            $this->refreshSummary();

            return;
        }

        if ($totalRealisasi >= $this->masukDetail->in_detail_qty && $this->masukDetail->in_detail_status === MasukStatusEnum::PROCESS) {
            $this->masukDetail->update(['in_detail_status' => MasukStatusEnum::READY]);
            $this->masukDetail->refresh();
            $this->generateGroupForDetail($this->masukDetail->in_detail_code);
            $this->insertStockForDetail($this->masukDetail->in_detail_code);
            // Auto-create putaway task
            $group = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)->value('in_realisasi_group');
            ForkliftTask::firstOrCreate(
                ['forklift_type' => 'putaway', 'forklift_pallet_code' => $group],
                [
                    'forklift_lokasi_asal' => $this->stagingCode,
                    'forklift_lokasi_tujuan' => $this->masukDetail->in_detail_id_lokasi,
                    'forklift_reff' => $this->masukDetail->in_detail_code,
                    'forklift_status' => 'Pending',
                ]
            );
            $this->masukDetail->update(['in_detail_id_staging' => $this->stagingCode]);
            $this->updatePoStatus();
            $this->dispatch('show-toast', message: 'Semua qty sudah terpenuhi! Status → READY', type: 'success');
        }

        $this->success = 'Barcode berhasil di-scan';
        $this->dispatch('show-toast', message: 'Barcode berhasil di-scan', type: 'success');
        $this->barcodeInput = '';
        $this->refreshSummary();
    }

    public function changeStatus(string $newStatus)
    {
        $enum = MasukStatusEnum::tryFrom($newStatus);
        if (! $enum) {
            $this->error = 'Status tidak valid';

            return;
        }

        $allowed = match ($this->masukDetail->in_detail_status) {
            MasukStatusEnum::PENDING => [MasukStatusEnum::PROCESS],
            MasukStatusEnum::PROCESS => [MasukStatusEnum::READY],
            MasukStatusEnum::READY => [MasukStatusEnum::COMPLETE],
            default => [],
        };

        if (! in_array($enum, $allowed)) {
            $this->error = 'Transisi status tidak diizinkan';

            return;
        }

        $this->masukDetail->update(['in_detail_status' => $enum]);
        $this->masukDetail->refresh();
        $this->updatePoStatus();

        if ($enum === MasukStatusEnum::READY) {
            $this->generateGroupForDetail($this->masukDetail->in_detail_code);
            $this->insertStockForDetail($this->masukDetail->in_detail_code);
            $group = MasukRealisasi::where('in_realisasi_masuk_code', $this->masukDetail->in_detail_code)->value('in_realisasi_group');
            ForkliftTask::firstOrCreate(
                ['forklift_type' => 'putaway', 'forklift_pallet_code' => $group],
                [
                    'forklift_lokasi_asal' => $this->stagingCode,
                    'forklift_lokasi_tujuan' => $this->masukDetail->in_detail_id_lokasi,
                    'forklift_reff' => $this->masukDetail->in_detail_code,
                    'forklift_status' => 'Pending',
                ]
            );
            $this->masukDetail->update(['in_detail_id_staging' => $this->stagingCode]);
        }

        $this->success = 'Status diubah ke '.$enum->description();
        $this->dispatch('show-toast', message: 'Status → '.$enum->description(), type: 'success');
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

        // Only flag barcodes that were in stock BEFORE this detail's realisasi created new stock.
        // Exclude stock rows whose stock_reff matches this detail's group (just created by us).
        $detailCode = $this->masukDetail->in_detail_code;
        $groupCode = MasukRealisasi::where('in_realisasi_masuk_code', $detailCode)->value('in_realisasi_group');

        $this->existingStockBarcodes = MasukRealisasi::where('in_realisasi_masuk_code', $detailCode)
            ->whereNotNull('in_realisasi_barcode')
            ->pluck('in_realisasi_barcode')
            ->filter(fn ($bc) => Stock::where('stock_code', $bc)
                ->where(function ($q) use ($groupCode) {
                    $q->whereNull('stock_reff')
                        ->orWhere('stock_reff', '!=', $groupCode);
                })
                ->exists())
            ->values()
            ->all();
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
        $realisasi = MasukRealisasi::findOrFail($realisasiId);

        // Hapus stock yang terkait (stock_code = barcode)
        if ($realisasi->in_realisasi_barcode) {
            Stock::where('stock_code', $realisasi->in_realisasi_barcode)->delete();
        }

        $realisasi->delete();
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

    protected function stagingLokasiCode(): string
    {
        return $this->masukDetail->in_detail_id_lokasi ?? '';
    }

    protected function generateGroupForDetail(string $detailCode): void
    {
        $existing = MasukRealisasi::where('in_realisasi_masuk_code', $detailCode)->whereNotNull('in_realisasi_group')->value('in_realisasi_group');
        $group = $existing ?: MasukRealisasi::generateGroupCode();

        MasukRealisasi::where('in_realisasi_masuk_code', $detailCode)->update(['in_realisasi_group' => $group]);
    }

    protected function insertStockForDetail(string $detailCode): void
    {
        $group = MasukRealisasi::where('in_realisasi_masuk_code', $detailCode)->value('in_realisasi_group');
        if (! $group || Stock::where('stock_reff', $group)->exists()) {
            return;
        }

        MasukRealisasi::where('in_realisasi_masuk_code', $detailCode)->get()->each(function (MasukRealisasi $realisasi) use ($group) {
            if (Stock::where('stock_code', $realisasi->in_realisasi_barcode)->exists()) {
                return;
            }

            Stock::create([
                'stock_code' => $realisasi->in_realisasi_barcode,
                'stock_id_product' => $realisasi->in_realisasi_id_product,
                'stock_code_lokasi' => null,
                'stock_qty' => $realisasi->in_realisasi_qty,
                'stock_type' => Stock::TYPE_STAGING,
                'stock_expired_date' => $this->expiredDateFromBarcode($realisasi->in_realisasi_barcode),
                'stock_reff' => $group,
                'stock_pallet_code' => $group,
            ]);
        });
    }

    protected function expiredDateFromBarcode(?string $barcode): ?string
    {
        $parsed = $barcode ? $this->parseBarcode($barcode) : null;
        $expired = $parsed['expired_date'] ?? null;

        return empty($expired) ? null : $expired;
    }

    public function regeneratePallet()
    {
        $detailCode = $this->masukDetail->in_detail_code;
        $oldGroup = MasukRealisasi::where('in_realisasi_masuk_code', $detailCode)->value('in_realisasi_group');

        if (! $oldGroup) {
            $this->error = 'Belum ada pallet code untuk di-generate ulang.';

            return;
        }

        $newGroup = MasukRealisasi::generateGroupCode();

        MasukRealisasi::where('in_realisasi_masuk_code', $detailCode)->update(['in_realisasi_group' => $newGroup]);
        Stock::where('stock_reff', $oldGroup)->update(['stock_reff' => $newGroup, 'stock_pallet_code' => $newGroup]);
        ForkliftTask::where('forklift_pallet_code', $oldGroup)->update(['forklift_pallet_code' => $newGroup]);

        $this->success = 'Pallet code berhasil di-generate ulang: '.$newGroup;
        $this->dispatch('show-toast', message: 'Pallet code di-generate ulang: '.$newGroup, type: 'success');
        $this->refreshSummary();
    }

    protected function updatePoStatus(): void
    {
        $poDetail = $this->masukDetail->poDetail;
        if (! $poDetail) {
            return;
        }

        $po = $poDetail->po;
        if (! $po) {
            return;
        }

        // Get all masuk_details linked to this PO (via po_detail_code)
        $poDetailCodes = $po->details()->pluck('po_detail_code')->all();
        $masukDetails = MasukDetail::whereIn('in_detail_reff', $poDetailCodes)->get();

        if ($masukDetails->isEmpty()) {
            return;
        }

        $allComplete = $masukDetails->every(fn ($md) => $md->in_detail_status->value === 'complete');
        $allReady = $masukDetails->every(fn ($md) => in_array($md->in_detail_status->value, ['ready', 'complete']));
        $anyProcess = $masukDetails->contains(fn ($md) => $md->in_detail_status->value === 'process');

        $newStatus = match (true) {
            $allComplete => Po::STATUS_DONE,
            $allReady => Po::STATUS_READY,
            $anyProcess => Po::STATUS_PROCESS,
            default => Po::STATUS_PENDING,
        };

        if ($po->po_status !== $newStatus) {
            $po->update(['po_status' => $newStatus]);
        }
    }

    public function render()
    {
        return view('livewire.masuk-realisasi-scanner');
    }
}
