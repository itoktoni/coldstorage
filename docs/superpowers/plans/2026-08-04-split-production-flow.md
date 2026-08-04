# Split Production Flow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign WMS Split module into a production split workflow with barcode scanning, stock movements, and forklift putaway.

**Architecture:** Livewire component for interactive split form with barcode scanning. Split creates STAGING stock + ForkliftTask, forklift moves to rack (STAGING → IN). Database: modify `split` table, create `split_detail` table.

**Tech Stack:** Laravel, Livewire, MySQL, Tailwind CSS

## Global Constraints

- PHP 8.4, Laravel v13, Livewire v4
- Follow existing code conventions (ControllerTrait, BaseModel, auto-routes)
- Stock types: IN, OUT, RESERVE, STAGING
- ForkliftTask pattern: pallet scan → location scan → stock type update
- Seeder uses `DB::table()->insert()` pattern

---

## File Structure

| File | Action | Purpose |
|------|--------|---------|
| `database/migrations/2026_08_04_000000_modify_split_add_detail.php` | Create | Migration: alter split table + create split_detail |
| `app/Models/SplitDetail.php` | Create | SplitDetail model |
| `app/Livewire/SplitProduce.php` | Create | Livewire component for split form |
| `resources/views/livewire/split-produce.blade.php` | Create | Livewire view |
| `app/Models/Split.php` | Modify | Update fillable, casts, relationships, $filterColumns, $sortColumns |
| `app/Http/Controllers/Wms/SplitController.php` | Modify | Add getProduce(), update share() |
| `resources/views/pages/split/table.blade.php` | Modify | Add Produce button, split_status column |
| `database/seeders/WmsSeeder.php` | Modify | Add split_detail truncation, new products + stock |
| `routes/web.php` | Modify | Add produce route |

---

### Task 1: Migration — Modify split table + Create split_detail

**Files:**
- Create: `database/migrations/2026_08_04_000000_modify_split_add_detail.php`

**Interfaces:**
- Produces: `split` table with new columns, `split_detail` table

- [ ] **Step 1: Create migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify split table
        Schema::table('split', function (Blueprint $table) {
            $table->dropForeign(['split_id_product', 'split_id_stock']);
            $table->dropColumn(['split_id_product', 'split_id_stock', 'split_id_reff', 'split_qty_new', 'split_qty_old', 'split_qty_waste']);

            $table->foreignId('split_id_product_target')->constrained('product', 'product_id')->onDelete('cascade');
            $table->foreignId('split_id_product_waste')->nullable()->constrained('product', 'product_id')->onDelete('set null');
            $table->double('split_qty_hasil')->default(0);
            $table->double('split_qty_waste')->default(0);
            $table->double('split_qty_penyusutan')->default(0);
            $table->string('split_status', 20)->default('Draft');
        });

        // Create split_detail table
        Schema::create('split_detail', function (Blueprint $table) {
            $table->id('split_detail_id');
            $table->foreignId('split_detail_id_split')->constrained('split', 'split_id')->onDelete('cascade');
            $table->foreignId('split_detail_id_stock')->constrained('stock', 'stock_id')->onDelete('cascade');
            $table->double('split_detail_qty')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_detail');

        Schema::table('split', function (Blueprint $table) {
            $table->dropForeign(['split_id_product_target', 'split_id_product_waste']);
            $table->dropColumn(['split_id_product_target', 'split_id_product_waste', 'split_qty_hasil', 'split_qty_waste', 'split_qty_penyusutan', 'split_status']);

            $table->foreignId('split_id_product')->constrained('product', 'product_id')->onDelete('cascade');
            $table->foreignId('split_id_stock')->constrained('stock', 'stock_id')->onDelete('cascade');
            $table->integer('split_id_reff')->nullable();
            $table->double('split_qty_new');
            $table->double('split_qty_old');
            $table->double('split_qty_waste');
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_08_04_000000_modify_split_add_detail.php
git commit -m "feat: migrate split table schema and create split_detail"
```

---

### Task 2: SplitDetail Model

**Files:**
- Create: `app/Models/SplitDetail.php`

**Interfaces:**
- Produces: `SplitDetail` model with `split()`, `stock()` relationships

- [ ] **Step 1: Create SplitDetail model**

```php
<?php

namespace App\Models;

class SplitDetail extends BaseModel
{
    protected $table = 'split_detail';
    protected $primaryKey = 'split_detail_id';
    public $timestamps = true;

    protected $fillable = [
        'split_detail_id_split',
        'split_detail_id_stock',
        'split_detail_qty',
    ];

    protected $casts = [
        'split_detail_qty' => 'double',
    ];

    public function split()
    {
        return $this->belongsTo(Split::class, 'split_detail_id_split', 'split_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'split_detail_id_stock', 'stock_id');
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/SplitDetail.php
git commit -m "feat: add SplitDetail model"
```

---

### Task 3: Update Split Model

**Files:**
- Modify: `app/Models/Split.php`

**Interfaces:**
- Consumes: `SplitDetail` model from Task 2
- Produces: Updated Split with `productTarget()`, `productWaste()`, `details()` relationships

- [ ] **Step 1: Update Split model**

```php
<?php

namespace App\Models;

class Split extends BaseModel
{
    protected $table = 'split';
    protected $primaryKey = 'split_id';
    public $timestamps = true;

    public static $filterColumns = ['split_id_product_target', 'split_id_product_waste', 'split_status', 'split_tanggal'];
    public static $sortColumns   = ['split_tanggal', 'split_status', 'split_id'];

    protected $fillable = [
        'split_id_product_target',
        'split_id_product_waste',
        'split_qty_hasil',
        'split_qty_waste',
        'split_qty_penyusutan',
        'split_status',
        'split_tanggal',
        'split_created_by',
        'split_created_at',
    ];

    protected $casts = [
        'split_qty_hasil'      => 'double',
        'split_qty_waste'      => 'double',
        'split_qty_penyusutan' => 'double',
        'split_tanggal'        => 'date',
        'split_created_at'     => 'datetime',
    ];

    public function productTarget()
    {
        return $this->belongsTo(Product::class, 'split_id_product_target', 'product_id');
    }

    public function productWaste()
    {
        return $this->belongsTo(Product::class, 'split_id_product_waste', 'product_id');
    }

    public function details()
    {
        return $this->hasMany(SplitDetail::class, 'split_detail_id_split', 'split_id');
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/Split.php
git commit -m "feat: update Split model with new schema and relationships"
```

---

### Task 4: Livewire SplitProduce Component

**Files:**
- Create: `app/Livewire/SplitProduce.php`
- Create: `resources/views/livewire/split-produce.blade.php`

**Interfaces:**
- Consumes: `Split`, `SplitDetail`, `Stock`, `Product`, `ForkliftTask` models
- Produces: Livewire component with `scanBarcode()`, `removeScan()`, `process()` methods

- [ ] **Step 1: Create Livewire component**

```php
<?php

namespace App\Livewire;

use App\Models\ForkliftTask;
use App\Models\Product;
use App\Models\Split;
use App\Models\SplitDetail;
use App\Models\Stock;
use Livewire\Component;

class SplitProduce extends Component
{
    public $targetProductId = '';
    public $wasteProductId = '';
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

        // Check if already scanned
        if (collect($this->scannedBarcodes)->contains('stock_code', $code)) {
            $this->error = 'Barcode sudah di-scan';
            return;
        }

        // Find stock
        $stock = Stock::where('stock_code', $code)
            ->where('stock_type', Stock::TYPE_IN)
            ->where('stock_qty', '>', 0)
            ->first();

        if (!$stock) {
            $this->error = 'Stock tidak ditemukan atau tidak tersedia';
            return;
        }

        // If first scan, set the source product
        if (empty($this->scannedBarcodes)) {
            // All scanned barcodes must be same product
        } else {
            // Check product matches first scanned barcode
            $firstProduct = $this->scannedBarcodes[0]['stock_id_product'];
            if ($stock->stock_id_product !== $firstProduct) {
                $this->error = 'Product barcode harus sama dengan product sebelumnya';
                return;
            }
        }

        $this->scannedBarcodes[] = [
            'stock_id'       => $stock->stock_id,
            'stock_code'     => $stock->stock_code,
            'stock_id_product' => $stock->stock_id_product,
            'product_nama'   => $stock->product->product_nama ?? '-',
            'stock_qty'      => (float) $stock->stock_qty,
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

    public function updatedTargetProductId()
    {
        $this->validateTargetProduct();
    }

    private function validateTargetProduct()
    {
        if (empty($this->targetProductId)) {
            return;
        }

        if (empty($this->scannedBarcodes)) {
            return;
        }

        $sourceProductId = $this->scannedBarcodes[0]['stock_id_product'];
        if ($this->targetProductId == $sourceProductId) {
            $this->error = 'Product target tidak boleh sama dengan product sumber';
            $this->targetProductId = '';
        }
    }

    public function getTotalSumberProperty()
    {
        return collect($this->scannedBarcodes)->sum('stock_qty');
    }

    public function getPenyusutanProperty()
    {
        $total = $this->totalSumber;
        $hasil = (float) $this->qtyHasil;
        $waste = (float) $this->qtyWaste;
        return max(0, $total - $hasil - $waste);
    }

    public function getIsValidProperty()
    {
        if (empty($this->targetProductId)) return false;
        if (empty($this->scannedBarcodes)) return false;
        if ((float) $this->qtyHasil <= 0) return false;
        if ($this->penyusutan < 0) return false;
        return true;
    }

    public function process()
    {
        $this->error = '';
        $this->success = '';

        if (!$this->isValid) {
            $this->error = 'Lengkapi semua data terlebih dahulu';
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                $totalSumber = $this->totalSumber;
                $splitCode = 'SPLIT-' . uniqid();

                // 1. Create split record
                $split = Split::create([
                    'split_id_product_target' => $this->targetProductId,
                    'split_id_product_waste'  => $this->wasteProductId ?: null,
                    'split_qty_hasil'         => $this->qtyHasil,
                    'split_qty_waste'         => $this->qtyWaste,
                    'split_qty_penyusutan'    => $this->penyusutan,
                    'split_status'            => 'Processed',
                    'split_tanggal'           => now()->toDateString(),
                ]);

                // 2. Create split_detail for each source barcode
                foreach ($this->scannedBarcodes as $scan) {
                    SplitDetail::create([
                        'split_detail_id_split' => $split->split_id,
                        'split_detail_id_stock' => $scan['stock_id'],
                        'split_detail_qty'      => $scan['stock_qty'],
                    ]);

                    // 3. Decrement source stock
                    Stock::where('stock_id', $scan['stock_id'])
                        ->decrement('stock_qty', $scan['stock_qty']);
                }

                // 4. Get staging area from first source
                $firstScan = $this->scannedBarcodes[0];
                $sourceLokasi = $firstScan['stock_code_lokasi'];
                $stagingCode = $this->getStagingForLokasi($sourceLokasi);

                // 5. Create STAGING stock for target product
                $targetExpired = $firstScan['stock_expired_date'];
                Stock::create([
                    'stock_id_product'   => $this->targetProductId,
                    'stock_qty'          => $this->qtyHasil,
                    'stock_type'         => Stock::TYPE_STAGING,
                    'stock_code_lokasi'  => $stagingCode,
                    'stock_expired_date' => $targetExpired,
                    'stock_reff'         => $splitCode,
                    'stock_pallet_code'  => $splitCode,
                ]);

                // 6. Create STAGING stock for waste product (if exists)
                if (!empty($this->wasteProductId) && $this->qtyWaste > 0) {
                    Stock::create([
                        'stock_id_product'   => $this->wasteProductId,
                        'stock_qty'          => $this->qtyWaste,
                        'stock_type'         => Stock::TYPE_STAGING,
                        'stock_code_lokasi'  => $stagingCode,
                        'stock_expired_date' => $targetExpired,
                        'stock_reff'         => $splitCode,
                        'stock_pallet_code'  => $splitCode,
                    ]);
                }

                // 7. Create ForkliftTask for putaway
                ForkliftTask::create([
                    'forklift_type'         => ForkliftTask::TYPE_PUTAWAY,
                    'forklift_pallet_code'  => $splitCode,
                    'forklift_lokasi_asal'  => $stagingCode,
                    'forklift_lokasi_tujuan' => $sourceLokasi,
                    'forklift_reff'         => $splitCode,
                    'forklift_status'       => ForkliftTask::STATUS_PENDING,
                ]);
            });

            $this->success = 'Split berhasil diproses! Forklift task sudah dibuat.';
            $this->dispatch('show-toast', message: $this->success, type: 'success');

            // Reset form
            $this->reset(['targetProductId', 'wasteProductId', 'scannedBarcodes', 'barcodeInput', 'qtyHasil', 'qtyWaste']);
        } catch (\Throwable $th) {
            $this->error = 'Gagal memproses split: ' . $th->getMessage();
        }
    }

    private function getStagingForLokasi(?string $lokasiCode): string
    {
        // Get gudang from source lokasi, find staging area in same gudang
        $lokasi = \App\Models\Lokasi::where('lokasi_code', $lokasiCode)->first();
        if ($lokasi && $lokasi->lokasi_category !== 'staging') {
            $staging = \App\Models\Lokasi::where('lokasi_code_gudang', $lokasi->lokasi_code_gudang)
                ->where('lokasi_category', 'staging')
                ->first();
            if ($staging) {
                return $staging->lokasi_code;
            }
        }
        return 'STG-A'; // fallback
    }

    public function render()
    {
        $products = Product::pluck('product_nama', 'product_id');

        return view('livewire.split-produce', [
            'products' => $products,
        ]);
    }
}
```

- [ ] **Step 2: Create Livewire view**

```blade
<div>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '/wms/split', 'label' => 'Split'], ['url' => '', 'label' => 'Produce']]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Error / Success --}}
        @if ($error)
            <div class="alert alert-error mb-4">{{ $error }}</div>
        @endif
        @if ($success)
            <div class="alert alert-success mb-4">{{ $success }}</div>
        @endif

        <div class="grid grid-cols-12 gap-5">
            {{-- Left: Form --}}
            <div class="col-span-12 lg:col-span-8">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
                    <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">call_split</span>
                        Split Production
                    </h3>

                    {{-- Target Product --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-on-surface mb-1">Produk Target (Hasil Split)</label>
                        <select wire:model.live="targetProductId" class="select select-bordered w-full">
                            <option value="">-- Pilih Produk Target --</option>
                            @foreach ($products as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Waste Product --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-on-surface mb-1">Waste Product (Optional)</label>
                        <select wire:model.live="wasteProductId" class="select select-bordered w-full">
                            <option value="">-- Tidak Ada Waste --</option>
                            @foreach ($products as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Scan Barcode --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-on-surface mb-1">Scan Barcode Sumber</label>
                        <div class="flex gap-2">
                            <input type="text" wire:model="barcodeInput" wire:keydown.enter="scanBarcode($event.target.value)" class="input input-bordered flex-1" placeholder="Scan atau ketik barcode..." />
                            <button wire:click="scanBarcode('{{ $barcodeInput }}')" class="btn btn-primary">Scan</button>
                        </div>
                    </div>

                    {{-- Scanned Barcodes Table --}}
                    @if (count($scannedBarcodes) > 0)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-on-surface mb-1">Barcode Sumber</label>
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Barcode</th>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Expired</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($scannedBarcodes as $index => $scan)
                                            <tr>
                                                <td>{{ $scan['stock_code'] }}</td>
                                                <td>{{ $scan['product_nama'] }}</td>
                                                <td>{{ number_format($scan['stock_qty'], 2) }}</td>
                                                <td>{{ $scan['stock_expired_date'] ?? '-' }}</td>
                                                <td>
                                                    <button wire:click="removeScan({{ $index }})" class="btn btn-ghost btn-xs text-error">
                                                        <span class="material-symbols-outlined text-sm">delete</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Qty Inputs --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-on-surface mb-1">Qty Hasil (kg)</label>
                            <input type="number" wire:model.live="qtyHasil" class="input input-bordered w-full" step="0.01" min="0" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-on-surface mb-1">Qty Waste (kg)</label>
                            <input type="number" wire:model.live="qtyWaste" class="input input-bordered w-full" step="0.01" min="0" />
                        </div>
                    </div>

                    {{-- Process Button --}}
                    <button wire:click="process" wire:loading.attr="disabled" class="btn btn-primary w-full" @if (!$isValid) disabled @endif>
                        <span wire:loading.remove>Proses Split</span>
                        <span wire:loading class="loading loading-spinner"></span>
                    </button>
                </div>
            </div>

            {{-- Right: Summary --}}
            <div class="col-span-12 lg:col-span-4">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
                    <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">info</span>
                        Ringkasan
                    </h3>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-on-surface/70">Total Sumber</span>
                            <span class="font-medium">{{ number_format($totalSumber, 2) }} kg</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-on-surface/70">Qty Hasil</span>
                            <span class="font-medium">{{ number_format($qtyHasil, 2) }} kg</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-on-surface/70">Qty Waste</span>
                            <span class="font-medium">{{ number_format($qtyWaste, 2) }} kg</span>
                        </div>
                        <div class="border-t border-outline-variant pt-3 flex justify-between">
                            <span class="text-on-surface/70">Penyusutan</span>
                            <span class="font-medium text-warning">{{ number_format($penyusutan, 2) }} kg</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Commit**

```bash
git add app/Livewire/SplitProduce.php resources/views/livewire/split-produce.blade.php
git commit -m "feat: add SplitProduce Livewire component with barcode scanning"
```

---

### Task 5: Update SplitController + Route

**Files:**
- Modify: `app/Http/Controllers/Wms/SplitController.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `SplitProduce` Livewire component from Task 4
- Produces: `getProduce()` method, produce route

- [ ] **Step 1: Update SplitController**

```php
<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Split;
use App\Models\Stock;

class SplitController extends Controller
{
    use ControllerTrait;

    public function __construct(Split $model)
    {
        $this->model = $model::getModel();
    }

    public function getProduce()
    {
        return view('livewire.split-produce');
    }

    protected function share($data = [])
    {
        return array_merge([
            'model' => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'stockOptions' => Stock::pluck('stock_code', 'stock_id'),
        ], $data);
    }
}
```

- [ ] **Step 2: Add produce route**

In `routes/web.php`, add inside the WMS split route group (around line 117):

```php
Route::get('/wms/split/produce', [\App\Http\Controllers\Wms\SplitController::class, 'getProduce'])->name('wms-split.produce');
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Wms/SplitController.php routes/web.php
git commit -m "feat: add split produce route and controller method"
```

---

### Task 6: Update Table View

**Files:**
- Modify: `resources/views/pages/split/table.blade.php`

**Interfaces:**
- Consumes: Updated Split model with `split_status`, `productTarget`, `productWaste`

- [ ] **Step 1: Update table view**

Add `split_status` column and Produce button. Replace the table body loop:

```blade
<?php /** @var App\Models\Split $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => ucfirst(module())]]" />
    <div class="content mt-4 lg:mt-0">
        <x-filter :per-page="25" :fields="$fields">
            <x-slot:advanced>
                @foreach ($fields as $key => $advance)
                <x-filter-item :label="$advance" :name="$key"/>
                @endforeach
                <x-button variant="primary" class="btn-block" onclick="applyAdvanced()">Apply</x-button>
                <x-button variant="soft" class="btn-block" onclick="resetAdvanced()">Reset</x-button>
            </x-slot:advanced>
        </x-filter>

        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Target Product</th>
                <th>Waste Product</th>
                <th>Qty Hasil</th>
                <th>Qty Waste</th>
                <th>Penyusutan</th>
            </x-slot:head>
            <x-slot:body>
                @forelse ($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <td class="flex gap-1">
                        <a href="{{ route('wms-split.produce') }}" class="bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1 rounded-lg text-sm">
                            <span class="material-symbols-outlined text-sm align-middle">play_arrow</span> Produce
                        </a>
                        <x-table-action :model="$model" :id="$table->field_primary" />
                    </td>
                    <td>{{ $table->split_tanggal?->format('d M Y') ?? '-' }}</td>
                    <td>
                        @if ($table->split_status === 'Processed')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">Processed</span>
                        @elseif ($table->split_status === 'Draft')
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs">Draft</span>
                        @else
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs">{{ $table->split_status }}</span>
                        @endif
                    </td>
                    <td>{{ $table->productTarget->product_nama ?? '-' }}</td>
                    <td>{{ $table->productWaste->product_nama ?? '-' }}</td>
                    <td>{{ number_format($table->split_qty_hasil, 2) }}</td>
                    <td>{{ number_format($table->split_qty_waste, 2) }}</td>
                    <td>{{ number_format($table->split_qty_penyusutan, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No data available.</td>
                </tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <x-table-mobile-list>
                    @forelse ($data as $table)
                    <x-table-mobile-item :id="$table->field_primary">
                        <x-table-mobile-header title="{{ $table->productTarget->product_nama ?? 'Split' }}" />
                        <x-table-mobile-text :label="'Tanggal'" :text="$table->split_tanggal?->format('d M Y') ?? '-'" />
                        <x-table-mobile-text :label="'Status'" :text="$table->split_status" />
                        <x-table-mobile-text :label="'Qty Hasil'" :text="number_format($table->split_qty_hasil, 2)" />
                        <x-table-mobile-text :label="'Penyusutan'" :text="number_format($table->split_qty_penyusutan, 2)" />
                        <x-table-mobile-footer :label="$table->field_primary">
                            <x-table-action :model="$model" :id="$table->field_primary" />
                        </x-table-mobile-footer>
                    </x-table-mobile-item>
                    @empty
                    <x-table-mobile-item>
                        <div class="text-center p-4">No data available.</div>
                    </x-table-mobile-item>
                    @endforelse
                </x-table-mobile-list>
            </x-slot:mobile>
        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']"/>
    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/pages/split/table.blade.php
git commit -m "feat: update split table view with status, products, and produce button"
```

---

### Task 7: Update Seeder

**Files:**
- Modify: `database/seeders/WmsSeeder.php`

**Interfaces:**
- Consumes: New split schema, SplitDetail table
- Produces: New products (PROD-23 to PROD-26), source stock for split testing

- [ ] **Step 1: Update WmsSeeder**

Add `split_detail` to truncation list and add new products + stock:

In the truncation array (line 18), add `'split_detail'` before `'split'`:

```php
foreach ([
    'split_detail', 'split', 'keluar_realisasi', ...
```

After the existing product insert (around line 79), add new products:

```php
// Split products
['product_code' => 'PROD-23', 'product_nama' => 'Sirloin Slice (kg)', 'product_harga' => 210000, 'created_at' => $now, 'updated_at' => $now],
['product_code' => 'PROD-24', 'product_nama' => 'Daging Giling (kg)', 'product_harga' => 95000, 'created_at' => $now, 'updated_at' => $now],
['product_code' => 'PROD-25', 'product_nama' => 'Has Dalam Slice (kg)', 'product_harga' => 220000, 'created_at' => $now, 'updated_at' => $now],
['product_code' => 'PROD-26', 'product_nama' => 'Tetelan Sapi (kg)', 'product_harga' => 75000, 'created_at' => $now, 'updated_at' => $now],
```

After the existing stock insert (around line 143), add source stock for split testing:

```php
// Source stock for split testing
['stock_code' => 'STK-20260804-0001', 'stock_id_product' => 1, 'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 50, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
['stock_code' => 'STK-20260804-0002', 'stock_id_product' => 2, 'stock_code_lokasi' => 'LOC-01', 'stock_qty' => 30, 'stock_expired_date' => '2026-10-15', 'stock_type' => 'IN', 'created_at' => $now, 'updated_at' => $now],
```

- [ ] **Step 2: Run seeder**

Run: `php artisan db:seed --class=WmsSeeder`
Expected: Seeder runs successfully with new products and stock

- [ ] **Step 3: Commit**

```bash
git add database/seeders/WmsSeeder.php
git commit -m "feat: update seeder with split products and source stock"
```

---

### Task 8: Run Pint + Final Verification

**Files:**
- All modified PHP files

**Interfaces:**
- N/A

- [ ] **Step 1: Run Pint formatter**

Run: `vendor/bin/pint --dirty --format agent`
Expected: All PHP files formatted correctly

- [ ] **Step 2: Run migration fresh + seed**

Run: `php artisan migrate:fresh --seed`
Expected: Database migrated and seeded successfully

- [ ] **Step 3: Verify route**

Run: `php artisan route:list --name=split`
Expected: Split routes including produce route listed

- [ ] **Step 4: Commit any formatting fixes**

```bash
git add -A
git commit -m "style: format code with pint"
```
