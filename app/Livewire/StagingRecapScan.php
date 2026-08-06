<?php

namespace App\Livewire;

use App\Models\ForkliftTask;
use App\Models\Lokasi;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class StagingRecapScan extends Component
{
    public $lokasiCode;

    public $lokasi;

    public $items = [];

    public $rackLokasis = [];

    public $palletCode = '';

    public $barcodeInput = '';

    public $errorMsg = '';

    public $successMsg = '';

    public function mount($lokasiCode)
    {
        $this->lokasiCode = $lokasiCode;
        $this->lokasi = Lokasi::where('lokasi_code', $lokasiCode)->firstOrFail();
        $this->palletCode = $this->generatePalletCode();
        $this->loadRacks();
        $this->loadItems();
    }

    public function scan($barcode)
    {
        $this->errorMsg = '';
        $this->successMsg = '';

        $barcode = trim($barcode);

        $stock = Stock::where('stock_code', $barcode)
            ->where('stock_type', Stock::TYPE_STAGING)
            ->where('stock_qty', '>', 0)
            ->where(function ($q) {
                $q->where('stock_code_lokasi', $this->lokasiCode)
                    ->orWhere('stock_code_lokasi', 'STAGING');
            })
            ->with('product')
            ->first();

        if (! $stock) {
            $this->errorMsg = 'Stock tidak ditemukan di lokasi staging ini.';

            return;
        }

        // Check if already in items — merge qty
        $existingIndex = null;
        foreach ($this->items as $i => $item) {
            if ($item['stock_id'] === $stock->stock_id) {
                $existingIndex = $i;
                break;
            }
        }

        if ($existingIndex !== null) {
            $this->successMsg = 'Stock sudah ada di daftar rekap.';

            return;
        }

        // Add new item
        $suggestedRack = $this->suggestRack($stock->product);

        $this->items[] = [
            'stock_id' => $stock->stock_id,
            'stock_code' => $stock->stock_code,
            'product_nama' => $stock->product->product_nama ?? '-',
            'product_category' => $stock->product->product_category ?? null,
            'stock_qty' => (float) $stock->stock_qty,
            'stock_expired' => $stock->stock_expired_date ? Carbon::parse($stock->stock_expired_date)->format('d M Y') : null,
            'stock_pallet_code' => $stock->stock_pallet_code,
            'rack_code' => $suggestedRack?->lokasi_code ?? '',
            'removed' => false,
        ];

        $this->successMsg = "Ditambahkan: {$stock->product->product_nama} ({$stock->stock_code})";
        $this->barcodeInput = '';
    }

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['removed'] = true;
        }
    }

    public function regeneratePallet()
    {
        $this->palletCode = $this->generatePalletCode();
    }

    public function confirmRecap()
    {
        $this->errorMsg = '';
        $this->successMsg = '';

        $activeItems = array_filter($this->items, fn ($i) => ! $i['removed']);
        $activeItems = array_values($activeItems);

        if (empty($activeItems)) {
            $this->errorMsg = 'Tidak ada item untuk dikonfirmasi.';

            return;
        }

        // Validate all have rack_code
        foreach ($activeItems as $item) {
            if (empty($item['rack_code'])) {
                $this->errorMsg = "Pilih rack tujuan untuk: {$item['product_nama']}";

                return;
            }
        }

        DB::transaction(function () use ($activeItems) {
            foreach ($activeItems as $item) {
                $stock = Stock::where('stock_id', $item['stock_id'])
                    ->where('stock_type', Stock::TYPE_STAGING)
                    ->first();

                if (! $stock) {
                    throw new \RuntimeException("Stock tidak ditemukan: {$item['stock_code']}");
                }

                $stock->update([
                    'stock_type' => Stock::TYPE_IN,
                    'stock_code_lokasi' => $item['rack_code'],
                    'stock_pallet_code' => $this->palletCode,
                ]);
            }

            // Auto-create forklift putaway task
            $firstDest = $activeItems[0]['rack_code'];
            ForkliftTask::create([
                'forklift_type' => ForkliftTask::TYPE_PUTAWAY,
                'forklift_pallet_code' => $this->palletCode,
                'forklift_lokasi_asal' => $this->lokasiCode,
                'forklift_lokasi_tujuan' => $firstDest,
                'forklift_reff' => 'staging-recap',
                'forklift_status' => ForkliftTask::STATUS_PENDING,
            ]);
        });

        flash()->success('Staging recap diproses. Forklift task dibuat.');

        return redirect()->route('wms-staging-recap.index');
    }

    private function loadItems(): void
    {
        $stocks = Stock::where('stock_type', Stock::TYPE_STAGING)
            ->where('stock_qty', '>', 0)
            ->where(function ($q) {
                $q->where('stock_code_lokasi', $this->lokasiCode)
                    ->orWhere('stock_code_lokasi', 'STAGING');
            })
            ->with('product')
            ->get();

        $this->items = $stocks->map(function ($stock) {
            $suggestedRack = $this->suggestRack($stock->product);

            return [
                'stock_id' => $stock->stock_id,
                'stock_code' => $stock->stock_code,
                'product_nama' => $stock->product->product_nama ?? '-',
                'product_category' => $stock->product->product_category ?? null,
                'stock_qty' => (float) $stock->stock_qty,
                'stock_expired' => $stock->stock_expired_date ? Carbon::parse($stock->stock_expired_date)->format('d M Y') : null,
                'stock_pallet_code' => $stock->stock_pallet_code,
                'rack_code' => $suggestedRack?->lokasi_code ?? '',
                'removed' => false,
            ];
        })->toArray();
    }

    private function loadRacks(): void
    {
        $this->rackLokasis = Lokasi::whereNull('lokasi_category')
            ->where('lokasi_code', 'not like', 'STG-%')
            ->get()
            ->map(fn ($l) => [
                'code' => $l->lokasi_code,
                'nama' => $l->lokasi_nama.' ('.($l->gudang?->gudang_nama ?? '').')',
            ])
            ->toArray();
    }

    private function suggestRack($product): ?Lokasi
    {
        if (! $product) {
            return null;
        }

        return Lokasi::whereNull('lokasi_category')
            ->where('lokasi_code', 'not like', 'STG-%')
            ->get()
            ->filter(fn ($l) => $l->hasCapacity(0))
            ->sortBy(fn ($l) => $l->current_qty)
            ->first();
    }

    private function generatePalletCode(): string
    {
        do {
            $code = 'PLT-'.now()->format('Ymd').'-'.unic_number(4);
        } while (ForkliftTask::where('forklift_pallet_code', $code)->exists());

        return $code;
    }

    public function render()
    {
        return view('livewire.staging-recap-scan');
    }
}
