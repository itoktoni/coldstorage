# Stock Assignment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add stock assignment layer so coordinator can cherry-pick specific stock for each SO detail before forklift picks.

**Architecture:** New `stock_assignment` table maps physical stock to SO details. Coordinator assigns via dedicated page. Forklift sees assigned stocks in pick list instead of FIFO suggestions. RESERVE stays virtual unchanged.

**Tech Stack:** Laravel 12, Eloquent, Blade, MySQL/MariaDB, Pest PHP

## Global Constraints

- Use `Schema::table()` for migrations (SQLite test compat)
- Follow existing model patterns: `BaseModel` parent, `$fillable`, `$casts`, relationships
- Routes use separate prefix `wms-so-prepare` (not `wms-so.*`)
- Views use card-based layout (`bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card`)
- No comments in code unless asked
- Tests use Pest PHP, `RefreshDatabase`

---

## File Structure

| Action | File | Responsibility |
|--------|------|---------------|
| Create | `database/migrations/2026_08_03_140000_create_stock_assignment_table.php` | Migration |
| Create | `database/migrations/2026_08_03_140001_add_out_assigned_to_keluar_table.php` | Migration |
| Create | `app/Models/StockAssignment.php` | Model |
| Modify | `app/Models/Keluar.php` | Add `assignments()` relationship |
| Modify | `app/Models/KeluarDetail.php` | Add `assignments()` relationship |
| Modify | `app/Models/Stock.php` | Add `assignments()` relationship |
| Modify | `app/Models/SoDetail.php` | Add `assignments()` relationship |
| Modify | `app/Http/Controllers/Wms/SoController.php` | Add `getAssign()`, `postAssign()` |
| Create | `resources/views/pages/so/assign.blade.php` | Assign stock view |
| Modify | `app/Http/Controllers/Wms/ForkliftController.php` | Update `pick()` to use assignments |
| Modify | `resources/views/pages/forklift/pick.blade.php` | Show assigned stocks |
| Modify | `routes/web.php` | Add assign routes |
| Create | `tests/Feature/Wms/StockAssignmentTest.php` | Tests |

---

### Task 1: Migration — Create stock_assignment table

**Files:**
- Create: `database/migrations/2026_08_03_140000_create_stock_assignment_table.php`

**Interfaces:**
- Produces: `stock_assignment` table with FK to keluar, stock, keluar_detail, detail_so

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
        Schema::create('stock_assignment', function (Blueprint $table) {
            $table->id('stock_assignment_id');
            $table->string('stock_assignment_id_keluar', 255);
            $table->unsignedBigInteger('stock_assignment_id_stock');
            $table->unsignedBigInteger('stock_assignment_id_keluar_detail');
            $table->unsignedBigInteger('stock_assignment_id_so_detail');
            $table->decimal('stock_assignment_qty', 15, 3);
            $table->enum('stock_assignment_status', ['Pending', 'Picked', 'Override'])->default('Pending');
            $table->text('stock_assignment_notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_assignment_id_keluar')
                ->references('out_code')->on('keluar')->onDelete('cascade');
            $table->foreign('stock_assignment_id_stock')
                ->references('stock_id')->on('stock')->onDelete('cascade');
            $table->foreign('stock_assignment_id_keluar_detail')
                ->references('out_detail_id')->on('keluar_detail')->onDelete('cascade');
            $table->foreign('stock_assignment_id_so_detail')
                ->references('so_detail_id')->on('detail_so')->onDelete('cascade');

            $table->index(['stock_assignment_id_keluar']);
            $table->index(['stock_assignment_id_stock']);
            $table->index(['stock_assignment_id_keluar_detail']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_assignment');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Table `stock_assignment` created

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_08_03_140000_create_stock_assignment_table.php
git commit -m "feat: create stock_assignment table migration"
```

---

### Task 2: Migration — Add out_assigned to keluar

**Files:**
- Create: `database/migrations/2026_08_03_140001_add_out_assigned_to_keluar_table.php`

**Interfaces:**
- Produces: `out_assigned` boolean column on `keluar` table

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
        Schema::table('keluar', function (Blueprint $table) {
            $table->boolean('out_assigned')->default(false)->after('out_catatan');
        });
    }

    public function down(): void
    {
        Schema::table('keluar', function (Blueprint $table) {
            $table->dropColumn('out_assigned');
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Column `out_assigned` added to `keluar` table

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_08_03_140001_add_out_assigned_to_keluar_table.php
git commit -m "feat: add out_assigned column to keluar table"
```

---

### Task 3: Model — Create StockAssignment

**Files:**
- Create: `app/Models/StockAssignment.php`

**Interfaces:**
- Produces: `StockAssignment` model with relationships to Keluar, Stock, KeluarDetail, SoDetail

- [ ] **Step 1: Create model file**

```php
<?php

namespace App\Models;

class StockAssignment extends BaseModel
{
    protected $table = 'stock_assignment';
    protected $primaryKey = 'stock_assignment_id';

    protected $fillable = [
        'stock_assignment_id_keluar',
        'stock_assignment_id_stock',
        'stock_assignment_id_keluar_detail',
        'stock_assignment_id_so_detail',
        'stock_assignment_qty',
        'stock_assignment_status',
        'stock_assignment_notes',
    ];

    protected $casts = [
        'stock_assignment_qty' => 'float',
    ];

    public static $filterColumns = [];
    public static $sortColumns   = [];

    public function keluar()
    {
        return $this->belongsTo(Keluar::class, 'stock_assignment_id_keluar', 'out_code');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_assignment_id_stock');
    }

    public function keluarDetail()
    {
        return $this->belongsTo(KeluarDetail::class, 'stock_assignment_id_keluar_detail');
    }

    public function soDetail()
    {
        return $this->belongsTo(SoDetail::class, 'stock_assignment_id_so_detail');
    }
}
```

- [ ] **Step 2: Verify model loads**

Run: `php artisan tinker --execute="echo App\Models\StockAssignment::class;"`
Expected: Class name printed

- [ ] **Step 3: Commit**

```bash
git add app/Models/StockAssignment.php
git commit -m "feat: add StockAssignment model"
```

---

### Task 4: Model — Add relationships to existing models

**Files:**
- Modify: `app/Models/Keluar.php` (add `assignments()` after `details()`)
- Modify: `app/Models/KeluarDetail.php` (add `assignments()` after `realisasi()`)
- Modify: `app/Models/Stock.php` (add `assignments()` after `splits()`)
- Modify: `app/Models/SoDetail.php` (add `assignments()` method)

**Interfaces:**
- Consumes: `StockAssignment` model from Task 3
- Produces: `assignments()` relationship on 4 models

- [ ] **Step 1: Add assignments() to Keluar**

In `app/Models/Keluar.php`, add after the `details()` method (around line 58):

```php
    public function assignments()
    {
        return $this->hasMany(StockAssignment::class, 'stock_assignment_id_keluar', 'out_code');
    }
```

- [ ] **Step 2: Add assignments() to KeluarDetail**

In `app/Models/KeluarDetail.php`, add after the `realisasi()` method (around line 45):

```php
    public function assignments()
    {
        return $this->hasMany(StockAssignment::class, 'stock_assignment_id_keluar_detail');
    }
```

- [ ] **Step 3: Add assignments() to Stock**

In `app/Models/Stock.php`, add after the `splits()` method (around line 103):

```php
    public function assignments()
    {
        return $this->hasMany(StockAssignment::class, 'stock_assignment_id_stock');
    }
```

- [ ] **Step 4: Add assignments() to SoDetail**

In `app/Models/SoDetail.php`, add a new method:

```php
    public function assignments()
    {
        return $this->hasMany(StockAssignment::class, 'stock_assignment_id_so_detail');
    }
```

- [ ] **Step 5: Add out_assigned to Keluar fillable**

In `app/Models/Keluar.php`, add `'out_assigned'` to the `$fillable` array:

```php
    protected $fillable = [
        'out_code', 'out_reff', 'out_tanggal', 'out_status',
        'out_qty', 'out_catatan', 'out_assigned', 'out_created_at', 'out_created_by',
    ];
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/Keluar.php app/Models/KeluarDetail.php app/Models/Stock.php app/Models/SoDetail.php
git commit -m "feat: add assignments() relationships to models"
```

---

### Task 5: Routes — Add assign routes

**Files:**
- Modify: `routes/web.php` (add 2 routes after existing so-prepare routes)

**Interfaces:**
- Consumes: `SoController::getAssign()`, `SoController::postAssign()` (Task 6)
- Produces: routes `wms-so-prepare.assign` and `wms-so-prepare.assignStore`

- [ ] **Step 1: Add routes**

In `routes/web.php`, after the existing so-prepare routes (around line 73), add:

```php
Route::get('/wms/so-prepare/{soId}/assign', [\App\Http\Controllers\Wms\SoController::class, 'getAssign'])
    ->name('wms-so-prepare.assign');
Route::post('/wms/so-prepare/{soId}/assign', [\App\Http\Controllers\Wms\SoController::class, 'postAssign'])
    ->name('wms-so-prepare.assignStore');
```

- [ ] **Step 2: Verify routes registered**

Run: `php artisan route:list --name=wms-so-prepare.assign`
Expected: 2 routes listed

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: add stock assign routes for SO prepare"
```

---

### Task 6: Controller — Add getAssign and postAssign

**Files:**
- Modify: `app/Http/Controllers/Wms/SoController.php` (add 2 methods)

**Interfaces:**
- Consumes: `StockAssignment` model, `Stock`, `Keluar`, `KeluarDetail`, `SoDetail`
- Produces: `getAssign()` returns assign view, `postAssign()` saves assignments

- [ ] **Step 1: Add getAssign method**

Add after `postPrepare()` method (around line 325):

```php
    public function getAssign(Request $request, string $soId)
    {
        $so = So::with(['customer', 'details.product'])->findOrFail($soId);

        $keluarCode = $this->keluarCodeForSo($so);
        if (!$keluarCode) {
            flash()->error('SO ini belum memiliki Keluar.');
            return redirect()->route('wms-so-prepare.index');
        }

        $keluar = Keluar::with(['details.product', 'assignments.stock.lokasi.gudang'])
            ->where('out_code', $keluarCode)
            ->firstOrFail();

        // Available stock: IN type, qty > 0, with product and lokasi
        $availableStock = Stock::where('stock_type', Stock::TYPE_IN)
            ->where('stock_qty', '>', 0)
            ->with('product', 'lokasi.gudang')
            ->get()
            ->groupBy('stock_id_product')
            ->map(function ($stocks) {
                return $stocks->map(function ($s) {
                    // Calculate remaining available: stock_qty - sum of Pending/Picked assignments for this stock
                    $assignedQty = $s->assignments()
                        ->whereIn('stock_assignment_status', ['Pending', 'Picked'])
                        ->sum('stock_assignment_qty');
                    $remaining = max(0, (float) $s->stock_qty - $assignedQty);
                    return [
                        'stock_id'    => $s->stock_id,
                        'stock_code'  => $s->stock_code,
                        'lokasi_code' => $s->stock_code_lokasi,
                        'lokasi_nama' => $s->lokasi?->lokasi_nama ?? '-',
                        'gudang_nama' => $s->lokasi?->gudang?->gudang_nama ?? '-',
                        'stock_qty'   => (float) $s->stock_qty,
                        'remaining'   => $remaining,
                        'expired'     => optional($s->stock_expired_date)->format('Y-m-d'),
                    ];
                });
            });

        // Existing assignments grouped by keluar_detail_id
        $existingAssignments = $keluar->details->mapWithKeys(function ($detail) {
            $assignments = $detail->assignments
                ->map(fn ($a) => [
                    'assignment_id' => $a->stock_assignment_id,
                    'stock_id'      => $a->stock_assignment_id_stock,
                    'qty'           => $a->stock_assignment_qty,
                ]);
            return [$detail->out_detail_id => $assignments];
        });

        return view('pages.so.assign', [
            'so'                    => $so,
            'keluar'                => $keluar,
            'availableStock'        => $availableStock,
            'existingAssignments'   => $existingAssignments,
        ]);
    }
```

- [ ] **Step 2: Add postAssign method**

Add after `getAssign()`:

```php
    public function postAssign(GeneralRequest $request, string $soId)
    {
        $data = $request->validate([
            'assignments'                        => ['required', 'array', 'min:1'],
            'assignments.*.keluar_detail_id'     => ['required', 'integer', 'exists:keluar_detail,out_detail_id'],
            'assignments.*.stock_id'             => ['required', 'integer', 'exists:stock,stock_id'],
            'assignments.*.qty'                  => ['required', 'numeric', 'min:0.001'],
            'assignments.*.so_detail_id'         => ['required', 'integer', 'exists:detail_so,so_detail_id'],
        ]);

        try {
            DB::transaction(function () use ($data, $soId) {
                $so = So::findOrFail($soId);
                $keluarCode = $this->keluarCodeForSo($so);
                if (!$keluarCode) {
                    throw new \Exception('SO ini belum memiliki Keluar.');
                }

                // Delete existing assignments for this keluar
                StockAssignment::where('stock_assignment_id_keluar', $keluarCode)->delete();

                $seq = 1;
                foreach ($data['assignments'] as $row) {
                    // Validate stock is IN type
                    $stock = Stock::where('stock_id', $row['stock_id'])
                        ->where('stock_type', Stock::TYPE_IN)
                        ->first();
                    if (!$stock) {
                        throw new \Exception("Stock ID {$row['stock_id']} tidak tersedia.");
                    }

                    // Validate qty doesn't exceed remaining need
                    $detail = KeluarDetail::findOrFail($row['keluar_detail_id']);
                    $alreadyAssigned = StockAssignment::where('stock_assignment_id_keluar_detail', $row['keluar_detail_id'])
                        ->sum('stock_assignment_qty');
                    $remaining = (float) $detail->out_detail_qty - $alreadyAssigned;
                    if ((float) $row['qty'] > $remaining + 0.001) {
                        throw new \Exception("Qty assign melebihi sisa kebutuhan. Sisa: {$remaining}");
                    }

                    // Validate stock remaining availability
                    $stockAssigned = StockAssignment::where('stock_assignment_id_stock', $row['stock_id'])
                        ->whereIn('stock_assignment_status', ['Pending', 'Picked'])
                        ->sum('stock_assignment_qty');
                    $stockRemaining = (float) $stock->stock_qty - $stockAssigned;
                    if ((float) $row['qty'] > $stockRemaining + 0.001) {
                        throw new \Exception("Stock {$stock->stock_code} tidak cukup. Tersisa: {$stockRemaining}");
                    }

                    StockAssignment::create([
                        'stock_assignment_id_keluar'         => $keluarCode,
                        'stock_assignment_id_stock'          => $row['stock_id'],
                        'stock_assignment_id_keluar_detail'  => $row['keluar_detail_id'],
                        'stock_assignment_id_so_detail'      => $row['so_detail_id'],
                        'stock_assignment_qty'               => $row['qty'],
                        'stock_assignment_status'            => 'Pending',
                    ]);
                    $seq++;
                }

                // Mark keluar as assigned
                Keluar::where('out_code', $keluarCode)->update(['out_assigned' => true]);
            });

            flash()->success('Stock assignment berhasil disimpan.');
            return redirect()->route('wms-so-prepare.assign', ['soId' => $soId]);
        } catch (\Throwable $th) {
            flash()->error($th->getMessage());
            return back()->withInput();
        }
    }
```

- [ ] **Step 3: Add import for StockAssignment**

In `app/Http/Controllers/Wms/SoController.php`, add to imports (top of file):

```php
use App\Models\StockAssignment;
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Wms/SoController.php
git commit -m "feat: add getAssign and postAssign methods to SoController"
```

---

### Task 7: View — Create assign.blade.php

**Files:**
- Create: `resources/views/pages/so/assign.blade.php`

**Interfaces:**
- Consumes: `$so`, `$keluar`, `$availableStock`, `$existingAssignments` from Task 6

- [ ] **Step 1: Create the view file**

```php
<?php /** @var App\Models\So $so */ ?>
<?php /** @var App\Models\Keluar $keluar */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[
        ['url' => route('wms-so-prepare.index'), 'label' => 'Prepare SO'],
        ['url' => route('wms-so-prepare.show', ['soId' => $so->so_id]), 'label' => $so->so_code],
        ['url' => '', 'label' => 'Assign Stock'],
    ]" />

    @if($errors->any())
    <div class="bg-error/10 border border-error rounded-xl p-4 mt-5">
        <ul class="list-disc list-inside text-error font-body-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- SO Info --}}
    <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">assignment</span>
            Assign Stock — {{ $keluar->out_code }}
        </h3>
        <div class="grid grid-cols-12 gap-5">
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">SO</div>
                <div class="font-body-sm font-bold">{{ $so->so_code }}</div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Customer</div>
                <div class="font-body-sm font-bold">{{ $so->customer->customer_nama ?? '-' }}</div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Total Qty</div>
                <div class="font-body-sm font-bold">{{ $keluar->out_qty }}</div>
            </div>
        </div>
    </div>

    {{-- Assign Form --}}
    <form action="{{ route('wms-so-prepare.assignStore', ['soId' => $so->so_id]) }}" method="POST">
        @csrf

        @foreach($keluar->details as $detail)
        @php
            $productId = $detail->out_detail_id_product;
            $stocks = $availableStock->get($productId, collect());
            $assigned = $existingAssignments->get($detail->out_detail_id, collect());
            $totalAssigned = (float) $assigned->sum('qty');
            $remaining = max(0, (float) $detail->out_detail_qty - $totalAssigned);
        @endphp

        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                {{ $detail->product->product_nama ?? '-' }} — Dibutuhkan: {{ $detail->out_detail_qty }} unit
                <span class="ml-auto text-sm {{ $remaining <= 0 ? 'text-success' : 'text-error' }}">
                    Sisa: {{ $remaining }} unit
                </span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant">
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">#</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Barcode</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Rak</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Gudang</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Stok Tersisa</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Expired</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty Ambil</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="assignment-rows" data-detail-id="{{ $detail->out_detail_id }}" data-product-id="{{ $productId }}">
                        @forelse($assigned as $idx => $a)
                        @php
                            $stockInfo = $stocks->firstWhere('stock_id', $a['stock_id']);
                        @endphp
                        <tr class="border-b border-outline-variant/50 assignment-row">
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $idx + 1 }}</td>
                            <td class="py-2 px-3 font-body-sm font-mono text-sm">{{ $stockInfo['stock_code'] ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm">{{ $stockInfo['lokasi_nama'] ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $stockInfo['gudang_nama'] ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm text-right">{{ $stockInfo['remaining'] ?? 0 }}</td>
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant text-right">{{ $stockInfo['expired'] ?? '-' }}</td>
                            <td class="py-2 px-3 text-right">
                                <input type="number" name="assignments[{{ $detail->out_detail_id }}_{{ $a['stock_id'] }}][qty]"
                                       value="{{ $a['qty'] }}" min="0.001" step="0.001"
                                       class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                            </td>
                            <td class="py-2 px-3 text-center">
                                <button type="button" onclick="this.closest('tr').remove()" class="text-error hover:text-error/80">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                            </td>
                        </tr>
                        <input type="hidden" name="assignments[{{ $detail->out_detail_id }}_{{ $a['stock_id'] }}][keluar_detail_id]" value="{{ $detail->out_detail_id }}">
                        <input type="hidden" name="assignments[{{ $detail->out_detail_id }}_{{ $a['stock_id'] }}][stock_id]" value="{{ $a['stock_id'] }}">
                        <input type="hidden" name="assignments[{{ $detail->out_detail_id }}_{{ $a['stock_id'] }}][so_detail_id]" value="{{ $detail->out_detail_id_so_detail }}">
                        @empty
                        <tr class="border-b border-outline-variant/50 assignment-row">
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant">1</td>
                            <td class="py-2 px-3" colspan="5">
                                <select name="assignments[new_{{ $detail->out_detail_id }}_0][stock_id]"
                                    class="w-full h-9 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none stock-select"
                                    data-detail-id="{{ $detail->out_detail_id }}">
                                    <option value="">— Pilih Stock —</option>
                                    @foreach($stocks as $s)
                                        @if($s['remaining'] > 0)
                                        <option value="{{ $s['stock_id'] }}" data-remaining="{{ $s['remaining'] }}">
                                            {{ $s['stock_code'] }} — {{ $s['lokasi_nama'] }} ({{ $s['remaining'] }} unit)
                                        </option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2 px-3 text-right">
                                <input type="number" name="assignments[new_{{ $detail->out_detail_id }}_0][qty]"
                                       value="{{ $remaining }}" min="0.001" step="0.001" max="{{ $remaining }}"
                                       class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                            </td>
                            <td class="py-2 px-3 text-center">
                                <button type="button" onclick="this.closest('tr').remove()" class="text-error hover:text-error/80">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                            </td>
                        </tr>
                        <input type="hidden" name="assignments[new_{{ $detail->out_detail_id }}_0][keluar_detail_id]" value="{{ $detail->out_detail_id }}">
                        <input type="hidden" name="assignments[new_{{ $detail->out_detail_id }}_0][so_detail_id]" value="{{ $detail->out_detail_id_so_detail }}">
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="button" onclick="addRow(this, {{ $detail->out_detail_id }}, {{ $productId }}, {{ $detail->out_detail_id_so_detail }})"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary/80">
                    <span class="material-symbols-outlined text-base">add</span>
                    Tambah Baris
                </button>
            </div>
        </div>
        @endforeach

        <div class="mt-6 mb-12 flex items-center gap-3">
            <a href="{{ route('wms-so-prepare.show', ['soId' => $so->so_id]) }}"
               class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">save</span>
                Simpan Assignments
            </button>
        </div>
    </form>

    <script>
    function addRow(btn, detailId, productId, soDetailId) {
        const tbody = btn.closest('.form-card').querySelector('.assignment-rows');
        const idx = tbody.querySelectorAll('.assignment-row').length;
        const key = 'new_' + detailId + '_' + idx;
        const selectName = 'assignments[' + key + '][stock_id]';
        const qtyName = 'assignments[' + key + '][qty]';

        // Get stock options from existing select or build from data
        const existingSelect = tbody.querySelector('.stock-select');
        const options = existingSelect ? existingSelect.innerHTML : '';

        const row = document.createElement('tr');
        row.className = 'border-b border-outline-variant/50 assignment-row';
        row.innerHTML = `
            <td class="py-2 px-3 font-body-sm text-on-surface-variant">${idx + 1}</td>
            <td class="py-2 px-3" colspan="5">
                <select name="${selectName}" class="w-full h-9 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none stock-select">
                    <option value="">— Pilih Stock —</option>
                    ${options}
                </select>
            </td>
            <td class="py-2 px-3 text-right">
                <input type="number" name="${qtyName}" value="0" min="0.001" step="0.001"
                    class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
            </td>
            <td class="py-2 px-3 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-error hover:text-error/80">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </td>
        `;
        tbody.appendChild(row);

        // Add hidden inputs
        const hiddenKeluar = document.createElement('input');
        hiddenKeluar.type = 'hidden';
        hiddenKeluar.name = 'assignments[' + key + '][keluar_detail_id]';
        hiddenKeluar.value = detailId;
        tbody.appendChild(hiddenKeluar);

        const hiddenSo = document.createElement('input');
        hiddenSo.type = 'hidden';
        hiddenSo.name = 'assignments[' + key + '][so_detail_id]';
        hiddenSo.value = soDetailId;
        tbody.appendChild(hiddenSo);
    }
    </script>
</x-layouts::app>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/pages/so/assign.blade.php
git commit -m "feat: add stock assignment view for SO prepare"
```

---

### Task 8: Controller — Update ForkliftController::pick() to use assignments

**Files:**
- Modify: `app/Http/Controllers/Wms/ForkliftController.php` (update `pick()` method)

**Interfaces:**
- Consumes: `StockAssignment` model
- Produces: `pick()` returns assigned stocks when available, falls back to FIFO

- [ ] **Step 1: Update pick() method**

In `app/Http/Controllers/Wms/ForkliftController.php`, replace the `pick()` method (lines 388-443) with:

```php
    public function pick(string $outCode)
    {
        $keluar = Keluar::with(['details.product', 'assignments.stock.lokasi.gudang'])->findOrFail($outCode);

        $rows = $keluar->details->map(function (KeluarDetail $detail) use ($keluar) {
            $alreadyPicked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $detail->out_detail_id)
                ->sum('out_realisasi_qty');

            $remaining = max(0, (float) $detail->out_detail_qty - $alreadyPicked);

            // Check if assignments exist for this detail
            $assignments = $keluar->assignments
                ->where('stock_assignment_id_keluar_detail', $detail->out_detail_id)
                ->where('stock_assignment_status', '!=', 'Override');

            if ($assignments->isNotEmpty()) {
                // Use assigned stocks
                $assignedStocks = $assignments->map(function (StockAssignment $a) use ($remaining) {
                    $stock = $a->stock;
                    $alreadyAssignedPicked = KeluarRealisasi::where('out_realisasi_id_stock', $a->stock_assignment_id_stock)
                        ->where('out_realisasi_id_detail', $a->stock_assignment_id_keluar_detail)
                        ->sum('out_realisasi_qty');
                    $pickRemaining = max(0, (float) $a->stock_assignment_qty - $alreadyAssignedPicked);
                    return [
                        'stock_id'    => $stock->stock_id,
                        'lokasi_code' => $stock->stock_code_lokasi,
                        'lokasi_nama' => $stock->lokasi?->lokasi_nama ?? '-',
                        'gudang_nama' => $stock->lokasi?->gudang?->gudang_nama ?? '-',
                        'stock_code'  => $stock->stock_code,
                        'stock_qty'   => (float) $stock->stock_qty,
                        'expired'     => optional($stock->stock_expired_date)->format('Y-m-d'),
                        'take_max'    => $pickRemaining,
                        'is_assigned' => true,
                        'assignment_id' => $a->stock_assignment_id,
                    ];
                })->values();
            } else {
                // Fallback to FIFO suggestions
                $assignedStocks = Stock::query()
                    ->where('stock_type', 'IN')
                    ->where('stock_id_product', $detail->out_detail_id_product)
                    ->where('stock_qty', '>', 0)
                    ->orderBy('stock_expired_date')
                    ->orderBy('stock_id')
                    ->with('lokasi.gudang')
                    ->get()
                    ->map(function (Stock $s) use ($remaining) {
                        return [
                            'stock_id'    => $s->stock_id,
                            'lokasi_code' => $s->stock_code_lokasi,
                            'lokasi_nama' => $s->lokasi?->lokasi_nama ?? '-',
                            'gudang_nama' => $s->lokasi?->gudang?->gudang_nama ?? '-',
                            'stock_code'  => $s->stock_code,
                            'stock_qty'   => (float) $s->stock_qty,
                            'expired'     => optional($s->stock_expired_date)->format('Y-m-d'),
                            'take_max'    => min((float) $s->stock_qty, $remaining),
                            'is_assigned' => false,
                            'assignment_id' => null,
                        ];
                    })
                    ->values();
            }

            return [
                'detail'         => $detail,
                'qty_requested'  => (int) $detail->out_detail_qty,
                'qty_picked'     => (float) $alreadyPicked,
                'qty_remaining'  => $remaining,
                'suggested'      => $assignedStocks,
            ];
        });

        $totalQty = (float) $rows->sum('qty_requested');
        $totalPicked = (float) $rows->sum('qty_picked');

        return view('pages.forklift.pick', [
            'keluar' => $keluar,
            'rows'   => $rows->filter(fn ($r) => $r['qty_remaining'] > 0)->values(),
            'summary' => [
                'total_qty'   => $totalQty,
                'total_picked'=> $totalPicked,
                'progress'    => $totalQty > 0 ? min(100, (int) round($totalPicked / $totalQty * 100)) : 0,
                'done_count'  => $rows->filter(fn ($r) => $r['qty_remaining'] <= 0)->count(),
            ],
        ]);
    }
```

- [ ] **Step 2: Add StockAssignment import**

In `app/Http/Controllers/Wms/ForkliftController.php`, add to imports:

```php
use App\Models\StockAssignment;
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Wms/ForkliftController.php
git commit -m "feat: update forklift pick to show assigned stocks"
```

---

### Task 9: View — Update pick.blade.php to show assigned indicator

**Files:**
- Modify: `resources/views/pages/forklift/pick.blade.php`

**Interfaces:**
- Consumes: `is_assigned` and `assignment_id` from suggested stocks array (Task 8)

- [ ] **Step 1: Update the stock suggestion table**

In `resources/views/pages/forklift/pick.blade.php`, in the suggested stocks table, add an "Assigned" indicator column. Update the table header and rows to show:

In the `<thead>` section, after the `#` header, add:
```html
<th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Status</th>
```

In the `<tbody>` rows, after the row number `<td>`, add:
```html
<td class="py-2 px-3 font-body-sm">
    @if($s['is_assigned'] ?? false)
        <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary">
            <span class="material-symbols-outlined text-sm">assignment</span>
            Assigned
        </span>
    @else
        <span class="text-xs text-on-surface-variant">FIFO</span>
    @endif
</td>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/pages/forklift/pick.blade.php
git commit -m "feat: show assigned indicator in forklift pick list"
```

---

### Task 10: Add assign button in prepare-so view

**Files:**
- Modify: `resources/views/pages/so/prepare-so.blade.php` (add assign button)

**Interfaces:**
- Consumes: route `wms-so-prepare.assign`

- [ ] **Step 1: Add assign button**

In `resources/views/pages/so/prepare-so.blade.php`, after the "Kembali ke Daftar Prepare" link (around line 167), add:

```php
        <a href="{{ route('wms-so-prepare.assign', ['soId' => $so->so_id]) }}"
           class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all">
            <span class="material-symbols-outlined text-base">assignment</span>
            Assign Stock
        </a>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/pages/so/prepare-so.blade.php
git commit -m "feat: add assign stock button to prepare-so page"
```

---

### Task 11: Tests — StockAssignmentTest

**Files:**
- Create: `tests/Feature/Wms/StockAssignmentTest.php`

**Interfaces:**
- Consumes: All models and routes from Tasks 1-10

- [ ] **Step 1: Create test file**

```php
<?php

namespace Tests\Feature\Wms;

use App\Models\Customer;
use App\Models\Gudang;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\Stock;
use App\Models\StockAssignment;
use App\Wms\SoStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function createStock(Product $product, Lokasi $lokasi, float $qty): Stock
    {
        return Stock::create([
            'stock_id_product'  => $product->product_id,
            'stock_qty'         => $qty,
            'stock_type'        => Stock::TYPE_IN,
            'stock_code_lokasi' => $lokasi->lokasi_code,
        ]);
    }

    private function createSoWithDetail(Product $product, float $qty): array
    {
        $customer = Customer::create(['customer_nama' => 'Test Customer']);
        $so = So::create([
            'so_id_customer' => $customer->customer_id,
            'so_tanggal'     => now()->toDateString(),
            'so_status'      => SoStatusEnum::PENDING,
        ]);
        $detail = SoDetail::create([
            'so_detail_id_so'       => $so->so_id,
            'so_detail_id_product'  => $product->product_id,
            'so_detail_qty'         => $qty,
        ]);
        return [$so, $detail];
    }

    public function test_coordinator_can_assign_stock_to_so_detail(): void
    {
        $gudang = Gudang::create(['gudang_nama' => 'Gudang A']);
        $lokasi = Lokasi::create(['lokasi_code' => 'B1', 'lokasi_nama' => 'Rak B1', 'lokasi_id_gudang' => $gudang->gudang_id]);
        $product = Product::create(['product_nama' => 'Product A', 'product_code' => 'PA001']);
        $stock = $this->createStock($product, $lokasi, 50);

        [$so, $detail] = $this->createSoWithDetail($product, 20);

        // Create keluar via postPrepare equivalent
        $keluar = Keluar::create([
            'out_tanggal' => now()->toDateString(),
            'out_status'  => 'Pending',
            'out_reff'    => 'Prepare SO',
            'out_qty'     => 20,
        ]);
        KeluarDetail::create([
            'out_detail_code_keluar'  => $keluar->out_code,
            'out_detail_id_product'   => $product->product_id,
            'out_detail_id_so_detail' => $detail->so_detail_id,
            'out_detail_code'         => $keluar->out_code.'-001',
            'out_detail_qty'          => 20,
        ]);

        // Assign stock
        $response = $this->post(route('wms-so-prepare.assignStore', ['soId' => $so->so_id]), [
            'assignments' => [
                [
                    'keluar_detail_id' => $keluar->details->first()->out_detail_id,
                    'stock_id'         => $stock->stock_id,
                    'qty'              => 20,
                    'so_detail_id'     => $detail->so_detail_id,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_assignment', [
            'stock_assignment_id_keluar'         => $keluar->out_code,
            'stock_assignment_id_stock'          => $stock->stock_id,
            'stock_assignment_qty'               => 20,
            'stock_assignment_status'            => 'Pending',
        ]);
    }

    public function test_cannot_assign_more_than_stock_remaining(): void
    {
        $gudang = Gudang::create(['gudang_nama' => 'Gudang A']);
        $lokasi = Lokasi::create(['lokasi_code' => 'B1', 'lokasi_nama' => 'Rak B1', 'lokasi_id_gudang' => $gudang->gudang_id]);
        $product = Product::create(['product_nama' => 'Product A', 'product_code' => 'PA001']);
        $stock = $this->createStock($product, $lokasi, 10);

        [$so, $detail] = $this->createSoWithDetail($product, 20);

        $keluar = Keluar::create([
            'out_tanggal' => now()->toDateString(),
            'out_status'  => 'Pending',
            'out_reff'    => 'Prepare SO',
            'out_qty'     => 20,
        ]);
        $keluarDetail = KeluarDetail::create([
            'out_detail_code_keluar'  => $keluar->out_code,
            'out_detail_id_product'   => $product->product_id,
            'out_detail_id_so_detail' => $detail->so_detail_id,
            'out_detail_code'         => $keluar->out_code.'-001',
            'out_detail_qty'          => 20,
        ]);

        $response = $this->post(route('wms-so-prepare.assignStore', ['soId' => $so->so_id]), [
            'assignments' => [
                [
                    'keluar_detail_id' => $keluarDetail->out_detail_id,
                    'stock_id'         => $stock->stock_id,
                    'qty'              => 15,
                    'so_detail_id'     => $detail->so_detail_id,
                ],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('stock_assignment', 0);
    }

    public function test_stock_can_be_split_across_multiple_sos(): void
    {
        $gudang = Gudang::create(['gudang_nama' => 'Gudang A']);
        $lokasi = Lokasi::create(['lokasi_code' => 'B1', 'lokasi_nama' => 'Rak B1', 'lokasi_id_gudang' => $gudang->gudang_id]);
        $product = Product::create(['product_nama' => 'Product A', 'product_code' => 'PA001']);
        $stock = $this->createStock($product, $lokasi, 30);

        // SO-001 needs 20
        [$so1, $detail1] = $this->createSoWithDetail($product, 20);
        $keluar1 = Keluar::create(['out_tanggal' => now()->toDateString(), 'out_status' => 'Pending', 'out_reff' => 'Prepare SO', 'out_qty' => 20]);
        $kd1 = KeluarDetail::create([
            'out_detail_code_keluar' => $keluar1->out_code, 'out_detail_id_product' => $product->product_id,
            'out_detail_id_so_detail' => $detail1->so_detail_id, 'out_detail_code' => $keluar1->out_code.'-001', 'out_detail_qty' => 20,
        ]);

        // SO-002 needs 15
        [$so2, $detail2] = $this->createSoWithDetail($product, 15);
        $keluar2 = Keluar::create(['out_tanggal' => now()->toDateString(), 'out_status' => 'Pending', 'out_reff' => 'Prepare SO', 'out_qty' => 15]);
        $kd2 = KeluarDetail::create([
            'out_detail_code_keluar' => $keluar2->out_code, 'out_detail_id_product' => $product->product_id,
            'out_detail_id_so_detail' => $detail2->so_detail_id, 'out_detail_code' => $keluar2->out_code.'-001', 'out_detail_qty' => 15,
        ]);

        // Assign 20 to SO-001
        $this->post(route('wms-so-prepare.assignStore', ['soId' => $so1->so_id]), [
            'assignments' => [
                ['keluar_detail_id' => $kd1->out_detail_id, 'stock_id' => $stock->stock_id, 'qty' => 20, 'so_detail_id' => $detail1->so_detail_id],
            ],
        ]);

        // Assign 10 to SO-002 (stock remaining = 30 - 20 = 10)
        $this->post(route('wms-so-prepare.assignStore', ['soId' => $so2->so_id]), [
            'assignments' => [
                ['keluar_detail_id' => $kd2->out_detail_id, 'stock_id' => $stock->stock_id, 'qty' => 10, 'so_detail_id' => $detail2->so_detail_id],
            ],
        ]);

        $this->assertDatabaseHas('stock_assignment', [
            'stock_assignment_id_stock' => $stock->stock_id,
            'stock_assignment_qty' => 20,
        ]);
        $this->assertDatabaseHas('stock_assignment', [
            'stock_assignment_id_stock' => $stock->stock_id,
            'stock_assignment_qty' => 10,
        ]);
    }

    public function test_assign_page_shows_available_stock(): void
    {
        $gudang = Gudang::create(['gudang_nama' => 'Gudang A']);
        $lokasi = Lokasi::create(['lokasi_code' => 'B1', 'lokasi_nama' => 'Rak B1', 'lokasi_id_gudang' => $gudang->gudang_id]);
        $product = Product::create(['product_nama' => 'Product A', 'product_code' => 'PA001']);
        $stock = $this->createStock($product, $lokasi, 50);

        [$so, $detail] = $this->createSoWithDetail($product, 20);

        $keluar = Keluar::create(['out_tanggal' => now()->toDateString(), 'out_status' => 'Pending', 'out_reff' => 'Prepare SO', 'out_qty' => 20]);
        KeluarDetail::create([
            'out_detail_code_keluar' => $keluar->out_code, 'out_detail_id_product' => $product->product_id,
            'out_detail_id_so_detail' => $detail->so_detail_id, 'out_detail_code' => $keluar->out_code.'-001', 'out_detail_qty' => 20,
        ]);

        $response = $this->get(route('wms-so-prepare.assign', ['soId' => $so->so_id]));
        $response->assertOk();
        $response->assertSee('Assign Stock');
        $response->assertSee($stock->stock_code);
    }
}
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter=StockAssignmentTest`
Expected: 4 tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Wms/StockAssignmentTest.php
git commit -m "test: add stock assignment feature tests"
```

---

### Task 12: Run full test suite and lint

**Files:**
- None (verification only)

- [ ] **Step 1: Run all tests**

Run: `php artisan test`
Expected: All tests pass

- [ ] **Step 2: Run lint (if available)**

Run: `./vendor/bin/pint --test`
Expected: No errors

- [ ] **Step 3: Final commit if needed**

```bash
git add -A
git commit -m "chore: stock assignment feature complete"
```
