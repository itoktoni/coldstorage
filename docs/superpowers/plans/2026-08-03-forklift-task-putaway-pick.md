# Forklift Task (Putaway & Pick) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bangun satu worklist forklift (`forklift_task`) untuk putaway (masuk) dan pick (keluar), scan-only, dengan audit operator + card-locking.

**Architecture:** Table `forklift_task` standalone, granularity per pallet. Putaway task auto-dibuat saat masuk_detail READY; pick task auto-dibuat saat supervisor simpan keluar-prepare (alokasi FEFO). Operator scan pallet (lock) lalu scan lokasi tujuan (done). Satu halaman worklist scan-only dua seksi.

**Tech Stack:** Laravel 13, Livewire, Blade, Pest (SQLite :memory:), MariaDB production.

## Global Constraints

- Field prefix table `forklift_task` = `forklift_*` (mis. `forklift_id`, `forklift_type`).
- Staging area = row `lokasi` dengan `lokasi_category = 'staging'`.
- `masuk_detail` WAJIB punya `in_detail_id_staging` sebelum bisa → READY.
- Scan strict: pallet + rack sama persis. Staging pick boleh beda dari suggest tapi wajib category staging.
- Alokasi keluar = FEFO (`stock_expired_date` ASC, NULL terakhir).
- Scan prefix ikut `config/scan.php` (P=pallet, L=lokasi).
- Worklist putaway + pick = satu halaman, dua seksi.
- Stock pindah pallet utuh; forklift_task TIDAK menyimpan link SO detail.

---

## File Structure

- Create: `database/migrations/xxxx_create_forklift_task_table.php`
- Create: `database/migrations/xxxx_add_in_detail_id_staging_to_masuk_detail.php`
- Create: `app/Models/ForkliftTask.php`
- Create: `app/Http/Controllers/Wms/ForkliftTaskController.php`
- Create: `resources/views/pages/forklift-task/index.blade.php`
- Modify: `app/Models/MasukDetail.php` (fillable + staging relation)
- Modify: `app/Http/Controllers/Wms/PoDetailController.php` (staging input on convert)
- Modify: `resources/views/pages/podetail/convert.blade.php` (staging select)
- Modify: `app/Http/Controllers/Wms/MasukDetailController.php` (READY validasi staging + auto-create putaway task)
- Modify: `app/Livewire/MasukRealisasiScanner.php` (READY validasi staging + auto-create putaway task + staging change)
- Modify: `resources/views/livewire/masuk-realisasi-scanner.blade.php` (staging select)
- Modify: `app/Http/Controllers/Wms/KeluarController.php` (FEFO alokasi + auto-create pick task)
- Modify: `routes/web.php` (forklift-task routes)
- Test: `tests/Feature/Wms/ForkliftTaskTest.php`

---

### Task 1: Migration forklift_task table

**Files:**
- Create: `database/migrations/xxxx_00_00_000000_create_forklift_task_table.php`

**Interfaces:**
- Produces: table `forklift_task` with `forklift_*` prefix columns

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forklift_task', function (Blueprint $table) {
            $table->id('forklift_id');
            $table->enum('forklift_type', ['putaway', 'pick']);
            $table->string('forklift_pallet_code', 50);
            $table->string('forklift_lokasi_asal', 50)->nullable();
            $table->string('forklift_lokasi_tujuan', 50)->nullable();
            $table->string('forklift_lokasi_final', 50)->nullable();
            $table->string('forklift_reff', 100)->nullable();
            $table->enum('forklift_status', ['Pending', 'Progress', 'Done'])->default('Pending');
            $table->string('forklift_operator', 100)->nullable();
            $table->timestamp('forklift_scan_asal_at')->nullable();
            $table->timestamp('forklift_scan_tujuan_at')->nullable();
            $table->timestamps();

            $table->index('forklift_status');
            $table->index('forklift_type');
            $table->index('forklift_pallet_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forklift_task');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: table `forklift_task` created.

- [ ] **Step 3: Commit**

```
git add database/migrations/xxxx_00_00_000000_create_forklift_task_table.php
git commit -m "feat: create forklift_task table"
```

---

### Task 2: Migration in_detail_id_staging to masuk_detail

**Files:**
- Create: `database/migrations/xxxx_00_00_000001_add_in_detail_id_staging_to_masuk_detail.php`

**Interfaces:**
- Produces: column `in_detail_id_staging` on `masuk_detail`

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masuk_detail', function (Blueprint $table) {
            $table->string('in_detail_id_staging', 50)->nullable()->after('in_detail_id_lokasi');
        });
    }

    public function down(): void
    {
        Schema::table('masuk_detail', function (Blueprint $table) {
            $table->dropColumn('in_detail_id_staging');
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: column added.

- [ ] **Step 3: Commit**

```
git add database/migrations/xxxx_00_00_000001_add_in_detail_id_staging_to_masuk_detail.php
git commit -m "feat: add in_detail_id_staging to masuk_detail"
```

---

### Task 3: ForkliftTask Model

**Files:**
- Create: `app/Models/ForkliftTask.php`

- [ ] **Step 1: Create model**

```php
<?php

namespace App\Models;

class ForkliftTask extends BaseModel
{
    protected $table = 'forklift_task';
    protected $primaryKey = 'forklift_id';

    protected $fillable = [
        'forklift_type', 'forklift_pallet_code', 'forklift_lokasi_asal', 'forklift_lokasi_tujuan',
        'forklift_lokasi_final', 'forklift_reff', 'forklift_status', 'forklift_operator',
        'forklift_scan_asal_at', 'forklift_scan_tujuan_at',
    ];

    protected $casts = [
        'forklift_scan_asal_at'   => 'datetime',
        'forklift_scan_tujuan_at' => 'datetime',
    ];

    const TYPE_PUTAWAY = 'putaway';
    const TYPE_PICK    = 'pick';
    const STATUS_PENDING  = 'Pending';
    const STATUS_PROGRESS = 'Progress';
    const STATUS_DONE     = 'Done';

    public function lokasiAsal()
    {
        return $this->belongsTo(Lokasi::class, 'forklift_lokasi_asal', 'lokasi_code');
    }

    public function lokasiTujuan()
    {
        return $this->belongsTo(Lokasi::class, 'forklift_lokasi_tujuan', 'lokasi_code');
    }
}
```

- [ ] **Step 2: Commit**

```
git add app/Models/ForkliftTask.php
git commit -m "feat: add ForkliftTask model"
```

---

### Task 4: Update MasukDetail model with staging

**Files:**
- Modify: `app/Models/MasukDetail.php`

- [ ] **Step 1: Add fillable and relation**

Add `'in_detail_id_staging'` to `$fillable` array.

Add method:

```php
public function staging()
{
    return $this->belongsTo(Lokasi::class, 'in_detail_id_staging', 'lokasi_code');
}
```

- [ ] **Step 2: Commit**

```
git add app/Models/MasukDetail.php
git commit -m "feat: add staging field + relation to MasukDetail"
```

---

### Task 5: Convert-to-masuk form with staging select

**Files:**
- Modify: `resources/views/pages/podetail/convert.blade.php`
- Modify: `app/Http/Controllers/Wms/PoDetailController.php`

- [ ] **Step 1: Add staging options to convert view data**

In `PoDetailController::getConvertToMasuk()`, after `$lokasiData`, add:

```php
$stagingOptions = Lokasi::where('lokasi_category', 'staging')->pluck('lokasi_nama', 'lokasi_code');
```

Pass to view:

```php
'stagingOptions' => $stagingOptions,
```

- [ ] **Step 2: Add staging select to convert.blade.php**

In each lokasi row, after the qty input, add:

```php
<select name="lokasi_allocations[{{ $lokasi['lokasi_code'] }}][staging_code]"
        class="w-32 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
    <option value="">Staging</option>
    @foreach($stagingOptions as $sc => $sn)
    <option value="{{ $sc }}">{{ $sn }}</option>
    @endforeach
</select>
```

- [ ] **Step 3: Save staging_code on convert**

In `PoDetailController::postConvertToMasuk()`, at MasukDetail::create, add:

```php
'in_detail_id_staging' => $allocation['staging_code'] ?? null,
```

Similarly in `postConvertSingleRow`, for both JSON and non-JSON paths, add `in_detail_id_staging` from request.

- [ ] **Step 4: Commit**

```
git add resources/views/pages/podetail/convert.blade.php app/Http/Controllers/Wms/PoDetailController.php
git commit -m "feat: add staging select to convert-to-masuk"
```

---

### Task 6: READY validasi staging + auto-create putaway task

**Files:**
- Modify: `app/Http/Controllers/Wms/MasukDetailController.php`
- Modify: `app/Livewire/MasukRealisasiScanner.php`
- Modify: `resources/views/livewire/masuk-realisasi-scanner.blade.php`

- [ ] **Step 1: Add staging change to scanner view**

In `resources/views/livewire/masuk-realisasi-scanner.blade.php`, add select for staging:

```html
<div class="flex items-center gap-2">
    <label class="text-sm font-medium">Staging Area:</label>
    <select wire:model.live="stagingCode" class="border border-gray-300 rounded-lg px-3 py-2">
        <option value="">Pilih Staging</option>
        @foreach(\App\Models\Lokasi::where('lokasi_category', 'staging')->get() as $s)
        <option value="{{ $s->lokasi_code }}">{{ $s->lokasi_nama }}</option>
        @endforeach
    </select>
</div>
```

- [ ] **Step 2: Add stagingCode property + validation to scanner**

In `MasukRealisasiScanner`, add:

```php
public $stagingCode = '';
```

In `mount()`, set:

```php
$this->stagingCode = $this->masukDetail->in_detail_id_staging ?? '';
```

In `scan()` method, BEFORE the block that sets status to READY, add:

```php
if (empty($this->stagingCode)) {
    $this->error = 'Pilih staging area terlebih dahulu';
    $this->refreshSummary();
    return;
}
```

When status becomes READY, add auto-create:

```php
ForkliftTask::firstOrCreate(
    ['forklift_type' => 'putaway', 'forklift_pallet_code' => $groupCode],
    [
        'forklift_lokasi_asal'   => $this->stagingCode,
        'forklift_lokasi_tujuan' => $this->masukDetail->in_detail_id_lokasi,
        'forklift_reff'          => $this->masukDetail->in_detail_code,
        'forklift_status'        => 'Pending',
    ]
);
```

Also save staging to masuk_detail when READY:

```php
$this->masukDetail->update(['in_detail_id_staging' => $this->stagingCode]);
```

- [ ] **Step 3: Same in MasukDetailController::postRealisasikan**

Add validation:

```php
$masukDetail = $this->model->findOrFail($id);
// ... existing code ...

// Before computing totalRealisasi:
if (empty($masukDetail->in_detail_id_staging)) {
    return redirect()->back()->withErrors(['error' => 'Pilih staging area terlebih dahulu sebelum set READY']);
}
```

When READY, add auto-create ForkliftTask:

```php
ForkliftTask::firstOrCreate(
    ['forklift_type' => 'putaway', 'forklift_pallet_code' => $groupCode],
    [
        'forklift_lokasi_asal'   => $masukDetail->in_detail_id_staging,
        'forklift_lokasi_tujuan' => $masukDetail->in_detail_id_lokasi,
        'forklift_reff'          => $masukDetail->in_detail_code,
        'forklift_status'        => 'Pending',
    ]
);
```

- [ ] **Step 4: Commit**

```
git add app/Http/Controllers/Wms/MasukDetailController.php app/Livewire/MasukRealisasiScanner.php resources/views/livewire/masuk-realisasi-scanner.blade.php
git commit -m "feat: validate staging before READY + auto-create putaway task"
```

---

### Task 7: ForkliftTaskController (worklist + scan)

**Files:**
- Create: `app/Http/Controllers/Wms/ForkliftTaskController.php`
- Create: `resources/views/pages/forklift-task/index.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create controller**

```php
<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\ForkliftTask;
use App\Models\Lokasi;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForkliftTaskController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tasks = ForkliftTask::where('forklift_status', 'Pending')
            ->orWhere(function ($q) use ($user) {
                $q->where('forklift_status', 'Progress')
                  ->where('forklift_operator', $user->name);
            })
            ->with(['lokasiAsal', 'lokasiTujuan'])
            ->orderBy('forklift_type')
            ->orderBy('forklift_id')
            ->get()
            ->groupBy('forklift_type');

        return view('pages.forklift-task.index', [
            'putawayTasks' => $tasks->get('putaway', collect()),
            'pickTasks'    => $tasks->get('pick', collect()),
        ]);
    }

    public function scan(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $code = trim($request->code);
        $prefix = config('scan.prefix_pallet', 'P');
        $locationPrefix = config('scan.prefix_location', 'L');

        // Detect scan type from prefix
        $isPallet = str_starts_with($code, $prefix);
        $isLocation = str_starts_with($code, $locationPrefix);

        try {
            return DB::transaction(function () use ($code, $isPallet, $isLocation, $prefix, $locationPrefix) {
                $user = Auth::user();

                if ($isPallet) {
                    // Scan pallet → lock task
                    $palletCode = $prefix ? substr($code, strlen($prefix)) : $code;
                    $task = ForkliftTask::where('forklift_pallet_code', $palletCode)
                        ->where('forklift_status', 'Pending')
                        ->lockForUpdate()
                        ->first();

                    if (!$task) {
                        return response()->json(['ok' => false, 'message' => 'Task tidak ditemukan atau sudah dikerjakan operator lain.'], 422);
                    }

                    $task->update([
                        'forklift_status'    => 'Progress',
                        'forklift_operator'  => $user->name,
                        'forklift_scan_asal_at' => now(),
                    ]);

                    return response()->json([
                        'ok' => true,
                        'message' => 'Pallet ' . $palletCode . ' sedang dikerjakan oleh ' . $user->name,
                        'task_type' => $task->forklift_type,
                        'next_scan' => 'location',
                        'task_id'   => $task->forklift_id,
                    ]);
                }

                if ($isLocation) {
                    // Scan location → complete task
                    $locationCode = $locationPrefix ? substr($code, strlen($locationPrefix)) : $code;
                    $lokasi = Lokasi::find($locationCode);
                    if (!$lokasi) {
                        return response()->json(['ok' => false, 'message' => 'Lokasi tidak ditemukan.'], 422);
                    }

                    $task = ForkliftTask::where('forklift_status', 'Progress')
                        ->where('forklift_operator', $user->name)
                        ->lockForUpdate()
                        ->first();

                    if (!$task) {
                        return response()->json(['ok' => false, 'message' => 'Tidak ada task Progress untuk Anda. Scan pallet dulu.'], 422);
                    }

                    if ($task->forklift_type === 'putaway') {
                        // Putaway: scan rack — harus sama persis
                        if ($locationCode !== $task->forklift_lokasi_tujuan) {
                            $expected = $task->lokasiTujuan?->lokasi_nama ?? $task->forklift_lokasi_tujuan;
                            return response()->json(['ok' => false, 'message' => 'Rak tidak sesuai. Harus scan "' . $expected . '".'], 422);
                        }
                        if (!in_array($lokasi->lokasi_category ?? '', ['Rack', 'Rak', 'rack', 'rak', ''])) {
                            // Allow if no category or rack-like
                        }

                        // Update stock: STAGING → IN
                        Stock::where('stock_reff', $task->forklift_pallet_code)
                            ->update([
                                'stock_type'         => Stock::TYPE_IN,
                                'stock_code_lokasi'  => $locationCode,
                                'stock_pallet_code'  => $task->forklift_pallet_code,
                            ]);
                    } else {
                        // Pick: scan staging — boleh beda dari suggest, wajib category staging
                        if (strtolower($lokasi->lokasi_category ?? '') !== 'staging') {
                            return response()->json(['ok' => false, 'message' => 'Lokasi bukan staging area.'], 422);
                        }

                        // Update stock: IN → STAGING
                        Stock::where('stock_reff', $task->forklift_pallet_code)
                            ->update([
                                'stock_type'        => Stock::TYPE_STAGING,
                                'stock_code_lokasi' => $locationCode,
                            ]);
                    }

                    $task->update([
                        'forklift_lokasi_final'   => $locationCode,
                        'forklift_status'         => 'Done',
                        'forklift_scan_tujuan_at' => now(),
                    ]);

                    return response()->json([
                        'ok' => true,
                        'message' => 'Task ' . $task->forklift_type . ' selesai! Pallet ' . $task->forklift_pallet_code . ' di ' . $locationCode,
                    ]);
                }

                return response()->json(['ok' => false, 'message' => 'Kode tidak dikenal. Scan kode pallet (P...) atau lokasi (L...).'], 422);
            });
        } catch (\Throwable $th) {
            return response()->json(['ok' => false, 'message' => $th->getMessage()], 500);
        }
    }
}
```

- [ ] **Step 2: Create worklist view**

Create `resources/views/pages/forklift-task/index.blade.php` with the dark UI pattern, two sections (Putaway then Pick), each showing pallet code, lokasi asal (info), lokasi tujuan (suggest), status, operator. Single big scan input at top.

- [ ] **Step 3: Add routes**

```php
Route::get('/wms/forklift-task', [\App\Http\Controllers\Wms\ForkliftTaskController::class, 'index'])->name('wms-forklift-task.index');
Route::post('/wms/forklift-task/scan', [\App\Http\Controllers\Wms\ForkliftTaskController::class, 'scan'])->name('wms-forklift-task.scan');
```

- [ ] **Step 4: Commit**

```
git add app/Http/Controllers/Wms/ForkliftTaskController.php resources/views/pages/forklift-task/index.blade.php routes/web.php
git commit -m "feat: add ForkliftTaskController + worklist view + routes"
```

---

### Task 8: Update KeluarController — FEFO + auto-create pick task

**Files:**
- Modify: `app/Http/Controllers/Wms/KeluarController.php`

- [ ] **Step 1: Update getPrepare to show FEFO suggestions**

In `getPrepare()`, change `$availableStock` query to sort by expired date ASC (null last):

```php
$availableStock = Stock::where('stock_type', Stock::TYPE_IN)
    ->where('stock_qty', '>', 0)
    ->orderByRaw('CASE WHEN stock_expired_date IS NULL THEN 1 ELSE 0 END, stock_expired_date ASC')
    ...;
```

- [ ] **Step 2: postPrepare auto-create pick tasks**

After the existing transaction that saves stock_assignments (or replaces it), loop through assigned pallets and create forklift_task:

```php
// After saving assignments, group by pallet and create pick tasks
$palletGroups = StockAssignment::where('stock_assignment_id_keluar', $outCode)
    ->with('stock')
    ->get()
    ->groupBy(fn ($a) => $a->stock?->stock_pallet_code ?? 'NOPALLET');

foreach ($palletGroups as $palletCode => $assignments) {
    $firstStock = $assignments->first()->stock;
    $rackAsal = $firstStock?->stock_code_lokasi;
    // Suggest staging — bisa dari config atau default staging
    $stagingSuggest = Lokasi::where('lokasi_category', 'staging')->first()?->lokasi_code;

    $taskData = [
        'forklift_type'         => 'pick',
        'forklift_pallet_code'  => $palletCode,
        'forklift_lokasi_asal'  => $rackAsal,
        'forklift_lokasi_tujuan' => $stagingSuggest,
        'forklift_reff'         => $outCode,
        'forklift_status'       => 'Pending',
    ];

    ForkliftTask::firstOrCreate(
        ['forklift_type' => 'pick', 'forklift_pallet_code' => $palletCode, 'forklift_reff' => $outCode],
        $taskData
    );
}
```

- [ ] **Step 3: Commit**

```
git add app/Http/Controllers/Wms/KeluarController.php
git commit -m "feat: FEFO alokasi + auto-create pick tasks on keluar-prepare"
```

---

### Task 9: Tests

**Files:**
- Create: `tests/Feature/Wms/ForkliftTaskTest.php`

- [ ] **Step 1: Write test**

Create `tests/Feature/Wms/ForkliftTaskTest.php` with tests for:
1. Create putaway task on READY
2. Scan pallet locks task to operator
3. Scan wrong rack rejected
4. Scan correct rack completes putaway (stock → IN)
5. Create pick task on keluar-prepare
6. Scan pallet locks pick task
7. Scan staging completes pick (stock → STAGING)
8. Card-locking: same task cannot be claimed by second operator
9. READY rejected if no staging selected
10. Staging select available at convert and scanner

- [ ] **Step 2: Run tests**

Run: `vendor/bin/pest tests/Feature/Wms/ForkliftTaskTest.php`
Expected: all pass

- [ ] **Step 3: Commit**

```
git add tests/Feature/Wms/ForkliftTaskTest.php
git commit -m "test: add ForkliftTaskTest"
```

---