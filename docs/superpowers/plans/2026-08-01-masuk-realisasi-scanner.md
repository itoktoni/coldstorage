# Masuk Realisasi Barcode Scanner Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the manual qty input form on Masuk Detail "Realisasikan" with a Livewire-based barcode scanner supporting camera and USB scanner.

**Architecture:** Livewire component handles scan logic, validation, and DB operations. Alpine.js + html5-qrcode for camera scanning. USB scanner via text input. Real-time UI updates without page refresh.

**Tech Stack:** Livewire, Alpine.js, html5-qrcode, Laravel

## Global Constraints

- QR code format: `{product_code}#{timestamp}#{qty}#{expired_date}` separated by `#`
- Scanned product_code must match masuk detail's product
- Save to `masuk_realisasi` table, status stays Pending
- Location = staging (no status change, no stock entry yet)
- Summary grouped by product with total qty
- Detail view shows qty + barcode only
- Delete individual scans from form
- No page refresh (Livewire reactivity)

---

## File Structure

| File | Purpose |
|------|---------|
| `app/Livewire/MasukRealisasiScanner.php` | Livewire component with scan logic |
| `resources/views/livewire/masuk-realisasi-scanner.blade.php` | Scanner UI view |
| `app/Http/Controllers/Wms/MasukDetailController.php` | Update `getRealisasikan` to use Livewire |
| `resources/views/pages/masukdetail/realisasikan.blade.php` | Replace with Livewire component |

---

### Task 1: Create Livewire Component

**Files:**
- Create: `app/Livewire/MasukRealisasiScanner.php`

**Interfaces:**
- Consumes: `MasukDetail` model (via route parameter)
- Produces: `scan()`, `getDetail()`, `deleteScan()`, `closeDetail()` methods

- [ ] **Step 1: Create Livewire component**

```bash
php artisan make:livewire MasukRealisasiScanner
```

- [ ] **Step 2: Implement component logic**

```php
<?php

namespace App\Livewire;

use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Product;
use Livewire\Component;

class MasukRealisasiScanner extends Component
{
    public $masukDetailId;
    public $masukDetail;
    public $summary;
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
            return;
        }

        // Validate product exists
        $product = Product::where('product_code', $parsed['product_code'])->first();
        if (!$product) {
            $this->error = 'Product tidak ditemukan';
            return;
        }

        // Validate product matches masuk detail
        if ($product->product_id != $this->masukDetail->in_detail_id_product) {
            $this->error = 'Product tidak sesuai dengan masuk detail';
            return;
        }

        // Create realisasi record
        MasukRealisasi::create([
            'in_realisasi_masuk_code' => $this->masukDetail->in_detail_code,
            'in_realisasi_id_product' => $product->product_id,
            'in_realisasi_qty' => $parsed['qty'],
            'in_realisasi_id_lokasi' => 1, // Staging location
        ]);

        $this->success = 'Barcode berhasil di-scan';
        $this->barcodeInput = '';
        $this->refreshSummary();
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
```

- [ ] **Step 3: Verify component exists**

Run: `php artisan livewire:list`
Expected: MasukRealisasiScanner component listed

---

### Task 2: Create Scanner View

**Files:**
- Create: `resources/views/livewire/masuk-realisasi-scanner.blade.php`

**Interfaces:**
- Consumes: Livewire component properties and methods
- Produces: Scanner UI with camera and USB support

- [ ] **Step 1: Create view file**

```blade
<?php /** @var App\Models\MasukDetail $model */ ?>

<div>
    <x-breadcrumb :items="[['url' => '/wms/masuk-detail/getTable', 'label' => 'Masuk Detail'], ['url' => '', 'label' => 'Realisasikan']]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Header Info --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                Realisasikan {{ $masukDetail->in_detail_code }}
            </h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                    <input type="text" value="{{ $masukDetail->product->product_nama ?? '-' }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qty Direncanakan</label>
                    <input type="text" value="{{ $masukDetail->in_detail_qty }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <input type="text" value="{{ $masukDetail->in_detail_status }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
            </div>
        </div>

        {{-- Scanner Section --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code_scanner</span>
                Scanner
            </h3>

            {{-- Flash Messages --}}
            @if($error)
            <div class="bg-error/10 border border-error rounded-xl p-4 mb-4">
                <p class="text-error font-body-sm font-semibold">{{ $error }}</p>
            </div>
            @endif
            @if($success)
            <div class="bg-success/10 border border-success rounded-xl p-4 mb-4">
                <p class="text-success font-body-sm font-semibold">{{ $success }}</p>
            </div>
            @endif

            <div class="grid grid-cols-12 gap-4">
                {{-- USB Scanner Input --}}
                <div class="col-span-8">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Barcode Input (USB Scanner)</label>
                    <input type="text" 
                           wire:model.live="barcodeInput" 
                           wire:keydown.enter="scan($event.target.value)"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="Scan barcode di sini..."
                           autofocus />
                </div>

                {{-- Camera Scanner Button --}}
                <div class="col-span-4 flex items-end">
                    <button type="button" 
                            x-on:click="$dispatch('open-camera-scanner')"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        <span class="material-symbols-outlined text-lg mr-1">photo_camera</span>
                        Scan Camera
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary Table --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">summarize</span>
                Summary Realisasi
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-on-surface-variant bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Total Qty</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3 font-medium">{{ $item->product->product_nama ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $item->total_qty }}</td>
                            <td class="px-4 py-3">
                                <button wire:click="getDetail({{ $item->in_realisasi_id_product }})" 
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors text-sm">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                    Detail
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-center text-on-surface-variant">Belum ada realisasi</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail Modal --}}
        @if($selectedProductId)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeDetail">
            <div class="bg-surface-container-lowest rounded-xl p-6 max-w-lg w-full mx-4" x-on:click.stop>
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant">
                    Detail Realisasi
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-on-surface-variant bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Barcode</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scans as $scan)
                            <tr class="border-b">
                                <td class="px-4 py-3">{{ $scan->in_realisasi_qty }}</td>
                                <td class="px-4 py-3 text-xs">{{ $scan->in_realisasi_code }}</td>
                                <td class="px-4 py-3">
                                    <button wire:click="deleteScan({{ $scan->in_realisasi_id }})" 
                                            wire:confirm="Hapus scan ini?"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors text-sm">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-center text-on-surface-variant">Tidak ada data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <button wire:click="closeDetail" 
                            class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Camera Scanner Modal --}}
        <div x-data="{ show: false }" 
             x-on:open-camera-scanner.window="show = true"
             x-on:close-camera-scanner.window="show = false"
             x-show="show"
             x-cloak
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-surface-container-lowest rounded-xl p-6 max-w-lg w-full mx-4" x-on:click.stop>
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant">
                    Scan QR Code
                </h3>
                <div id="camera-scanner" class="w-full h-64 bg-gray-200 rounded-lg mb-4"></div>
                <div class="flex justify-end">
                    <button x-on:click="show = false; $dispatch('close-camera-scanner')" 
                            class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Camera Scanner Script --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            let html5QrcodeScanner = null;

            Livewire.on('open-camera-scanner', () => {
                const scannerDiv = document.getElementById('camera-scanner');
                if (!scannerDiv) return;

                html5QrcodeScanner = new Html5QrcodeScanner("camera-scanner", {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                });

                html5QrcodeScanner.render((decodedText) => {
                    @this.scan(decodedText);
                    html5QrcodeScanner.clear();
                    Livewire.dispatch('close-camera-scanner');
                }, (error) => {
                    // Ignore scan errors
                });
            });

            Livewire.on('close-camera-scanner', () => {
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                }
            });
        });
    </script>
</div>
```

- [ ] **Step 2: Verify view renders**

Run: `php artisan view:cache`
Expected: No errors

---

### Task 3: Update Controller

**Files:**
- Modify: `app/Http/Controllers/Wms/MasukDetailController.php:38-45`

**Interfaces:**
- Consumes: `MasukDetail` model
- Produces: Updated `getRealisasikan` method

- [ ] **Step 1: Update getRealisasikan method**

```php
public function getRealisasikan(Request $request, string $id)
{
    return $this->views('pages.masukdetail.realisasikan', [
        'masukDetailId' => $id,
    ]);
}
```

- [ ] **Step 2: Verify controller updated**

Run: `php artisan route:list --path=masuk-detail`
Expected: Route exists

---

### Task 4: Update View to Use Livewire

**Files:**
- Modify: `resources/views/pages/masukdetail/realisasikan.blade.php`

**Interfaces:**
- Consumes: Livewire component
- Produces: Updated view

- [ ] **Step 1: Replace view content**

```blade
<x-layouts::app>
    <livewire:masuk-realisasi-scanner :masukDetailId="$masukDetailId" />
</x-layouts::app>
```

- [ ] **Step 2: Verify view works**

Run: `php artisan view:clear && php artisan view:cache`
Expected: No errors

---

### Task 5: Test Scanner Flow

**Files:**
- Test: Manual testing

**Interfaces:**
- Consumes: All previous tasks
- Produces: Working scanner

- [ ] **Step 1: Test USB scanner flow**

1. Navigate to masuk detail realisasikan page
2. Connect USB barcode scanner
3. Scan a barcode
4. Verify: barcode appears in summary table
5. Verify: no page refresh

- [ ] **Step 2: Test camera scanner flow**

1. Navigate to masuk detail realisasikan page on mobile
2. Click "Scan Camera" button
3. Scan QR code with camera
4. Verify: barcode appears in summary table
5. Verify: no page refresh

- [ ] **Step 3: Test validation**

1. Scan barcode with wrong product
2. Verify: error message "Product tidak sesuai dengan masuk detail"
3. Scan invalid barcode format
4. Verify: error message "Format barcode tidak valid"

- [ ] **Step 4: Test detail view**

1. Click "Detail" button on summary row
2. Verify: modal shows individual scans
3. Click delete button on a scan
4. Verify: scan removed, summary updated

---

### Task 6: Commit Changes

**Files:**
- All modified files

**Interfaces:**
- Consumes: All previous tasks
- Produces: Committed changes

- [ ] **Step 1: Stage changes**

```bash
git add app/Livewire/MasukRealisasiScanner.php
git add resources/views/livewire/masuk-realisasi-scanner.blade.php
git add app/Http/Controllers/Wms/MasukDetailController.php
git add resources/views/pages/masukdetail/realisasikan.blade.php
```

- [ ] **Step 2: Commit**

```bash
git commit -m "feat: add barcode scanner for masuk realisasi"
```
