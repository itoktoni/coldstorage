# Purchase Order Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete WMS Purchase Order CRUD (header + lines, status lifecycle, soft inbound link) on top of existing scaffold.

**Architecture:** Two modules mirroring Keluar/KeluarDetail: `Po` + `PoDetail`, ControllerTrait + flat Create/Update/DeleteAction, separate routes/views. Soft link to inbound via free-text `masuk_detail.in_detail_reff = po_code` (no FK).

**Tech Stack:** Laravel, Pest, Buki AutoRoute (`Route::auto`), ControllerTrait, Blade `x-*` components, BaseModel filter/sort.

**Spec:** `docs/superpowers/specs/2026-07-31-purchase-order-design.md`

## Global Constraints

- Views folder for PoDetailController MUST be `pages/podetail/` (ControllerTrait: basename without Controller, lowercase — no hyphen).
- Do NOT touch `masuk_*` / `stock` behavior.
- Do NOT add nested multi-line form, received-qty, supplier master, or line price.
- `pos.blade.php` / livewire pos = Point of Sale — never conflate with PO.
- Match existing WMS style (Keluar blades, empty BasePolicy, Pest Feature/Wms).
- Commit after each task.

---

### Task 1: Migration `po_status` + model constants

**Files:**
- Create: `database/migrations/2026_07_31_000002_add_po_status_to_po_table.php`
- Modify: `app/Models/Po.php`
- Test: `tests/Feature/Wms/PurchaseOrderTest.php` (create in this task)

**Interfaces:**
- Consumes: existing `po` table, `Po` model
- Produces: `Po::STATUS_*` constants; `po_status` column default `Pending`; fillable/filter/sort include `po_status`

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Wms/PurchaseOrderTest.php`:

```php
<?php

use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Product;

it('defaults po_status to Pending', function () {
    $po = Po::create([
        'po_tanggal'    => '2026-07-31',
        'po_code'       => 'PO-TEST-001',
        'po_supplier'   => 'Supplier A',
        'po_keterangan' => null,
    ]);

    expect($po->fresh()->po_status)->toBe(Po::STATUS_PENDING);
});

it('persists po with details and product relation', function () {
    $product = Product::create(['product_nama' => 'PO-Item-1', 'product_harga' => 100]);

    $po = Po::create([
        'po_tanggal'  => '2026-07-31',
        'po_code'     => 'PO-TEST-002',
        'po_supplier' => 'Supplier B',
        'po_status'   => Po::STATUS_ORDERED,
    ]);

    $detail = PoDetail::create([
        'po_detail_id_po'      => $po->po_id,
        'po_detail_id_product' => $product->product_id,
        'po_detail_qty'        => 10,
        'po_detail_code'       => 'POD-0001',
    ]);

    expect($po->details)->toHaveCount(1);
    expect($detail->po)->toBeInstanceOf(Po::class);
    expect($detail->product)->toBeInstanceOf(Product::class);
    expect($detail->product->product_nama)->toBe('PO-Item-1');
});

it('cascades delete from po to detail_po', function () {
    $product = Product::create(['product_nama' => 'PO-Item-2', 'product_harga' => 50]);

    $po = Po::create([
        'po_tanggal'  => '2026-07-31',
        'po_code'     => 'PO-TEST-003',
        'po_supplier' => 'Supplier C',
    ]);

    PoDetail::create([
        'po_detail_id_po'      => $po->po_id,
        'po_detail_id_product' => $product->product_id,
        'po_detail_qty'        => 5,
        'po_detail_code'       => 'POD-0002',
    ]);

    $po->delete();

    expect(PoDetail::query()->where('po_detail_code', 'POD-0002')->exists())->toBeFalse();
});
```

- [ ] **Step 2: Run test — expect fail**

```bash
php artisan test --filter=PurchaseOrderTest
```

Expected: FAIL (missing `po_status` and/or constants).

- [ ] **Step 3: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->string('po_status', 30)->default('Pending')->after('po_supplier');
        });
    }

    public function down(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->dropColumn('po_status');
        });
    }
};
```

- [ ] **Step 4: Update Po model**

Replace `app/Models/Po.php` with:

```php
<?php

namespace App\Models;

class Po extends BaseModel
{
    protected $table = 'po';
    protected $primaryKey = 'po_id';
    public $timestamps = true;

    const STATUS_PENDING = 'Pending';
    const STATUS_ORDERED = 'Ordered';
    const STATUS_PARTIAL = 'Partial';
    const STATUS_CLOSED  = 'Closed';

    public static $filterColumns = ['po_code', 'po_supplier', 'po_tanggal', 'po_status'];
    public static $sortColumns   = ['po_code', 'po_tanggal', 'po_supplier', 'po_status'];

    protected $fillable = [
        'po_tanggal',
        'po_code',
        'po_supplier',
        'po_status',
        'po_keterangan',
    ];

    protected $casts = [
        'po_tanggal' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(PoDetail::class, 'po_detail_id_po', 'po_id');
    }
}
```

- [ ] **Step 5: Migrate + run tests**

```bash
php artisan migrate
php artisan test --filter=PurchaseOrderTest
```

Expected: PASS (all 3).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_31_000002_add_po_status_to_po_table.php app/Models/Po.php tests/Feature/Wms/PurchaseOrderTest.php
git commit -m "feat(wms): add po_status and PurchaseOrder tests"
```

---

### Task 2: PoDetail model filter + controllers + policy

**Files:**
- Modify: `app/Models/PoDetail.php`
- Modify: `app/Http/Controllers/Wms/PoController.php`
- Create: `app/Http/Controllers/Wms/PoDetailController.php`
- Create: `app/Policies/PoDetailPolicy.php`

**Interfaces:**
- Consumes: `Po`, `PoDetail`, `Product`, ControllerTrait
- Produces: PoDetailController with `productOptions` + `poOptions`; Po header share without productOptions

- [ ] **Step 1: PoDetail filterColumns**

In `app/Models/PoDetail.php`, set:

```php
public static $filterColumns = ['po_detail_code', 'po_detail_id_product', 'po_detail_id_po'];
public static $sortColumns   = ['po_detail_code', 'po_detail_qty'];
```

(fillable + relations already correct — leave them.)

- [ ] **Step 2: Slim PoController**

```php
<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Po;

class PoController extends Controller
{
    use ControllerTrait;

    public function __construct(Po $model)
    {
        $this->model = $model::getModel();
    }

    protected function getData()
    {
        return $this->model->with('details.product')->filter()->sort();
    }
}
```

- [ ] **Step 3: PoDetailController**

```php
<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Product;

class PoDetailController extends Controller
{
    use ControllerTrait;

    public function __construct(PoDetail $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model'          => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'poOptions'      => Po::pluck('po_code', 'po_id'),
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with(['po', 'product'])->filter()->sort();
    }
}
```

- [ ] **Step 4: PoDetailPolicy**

```php
<?php

namespace App\Policies;

class PoDetailPolicy extends BasePolicy {}
```

- [ ] **Step 5: Smoke — class load**

```bash
php artisan tinker --execute="echo class_exists(App\Http\Controllers\Wms\PoDetailController::class) ? 'ok' : 'fail';"
```

Expected: `ok`

- [ ] **Step 6: Commit**

```bash
git add app/Models/PoDetail.php app/Http/Controllers/Wms/PoController.php app/Http/Controllers/Wms/PoDetailController.php app/Policies/PoDetailPolicy.php
git commit -m "feat(wms): PoDetailController and slim PoController"
```

---

### Task 3: Routes + menu

**Files:**
- Modify: `routes/web.php`
- Modify: `config/menu.php`

**Interfaces:**
- Consumes: PoController, PoDetailController
- Produces: route names `wms-po.*`, `wms-po-detail.*`; menu section Procurement

- [ ] **Step 1: Routes**

In `routes/web.php`, inside the auth group, after inbound block (before outbound or after stock), add:

```php
    // WMS Procurement
    Route::auto('/wms/po', 'Wms\PoController', ['name' => 'wms-po']);
    Route::auto('/wms/po-detail', 'Wms\PoDetailController', ['name' => 'wms-po-detail']);
```

- [ ] **Step 2: Menu**

In `config/menu.php`, insert before Inbound section:

```php
        [
            'label' => 'Procurement',
            'items' => [
                ['route' => 'wms-po.getTable', 'icon' => 'shopping_cart', 'label' => 'Purchase Order'],
                ['route' => 'wms-po-detail.getTable', 'icon' => 'list_alt', 'label' => 'PO Detail'],
            ],
        ],
```

- [ ] **Step 3: Verify routes**

```bash
php artisan route:list --name=wms-po
```

Expected: rows for `wms-po.*` and `wms-po-detail.*` (getTable, getCreate, postCreate, etc.).

- [ ] **Step 4: Commit**

```bash
git add routes/web.php config/menu.php
git commit -m "feat(wms): PO routes and Procurement menu"
```

---

### Task 4: Views

**Files:**
- Create: `resources/views/pages/po/form.blade.php`
- Create: `resources/views/pages/po/table.blade.php`
- Create: `resources/views/pages/podetail/form.blade.php`
- Create: `resources/views/pages/podetail/table.blade.php`

**Interfaces:**
- Consumes: ControllerTrait template paths `pages.po.*`, `pages.podetail.*`
- Produces: form/table UI matching Keluar pattern

- [ ] **Step 1: pages/po/form.blade.php**

```blade
<?php /** @var App\Models\Po $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-input col="6" name="po_code" />
                <x-input col="6" name="po_tanggal" type="date" />
                <x-input col="6" name="po_supplier" />
                <x-input col="6" name="po_status" />
                <x-input col="12" name="po_keterangan" type="textarea" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
```

- [ ] **Step 2: pages/po/table.blade.php**

Copy `resources/views/pages/keluar/table.blade.php` entirely; change only the model docblock:

```blade
<?php /** @var App\Models\Po $model */ ?>
```

(rest identical — uses `$model::$sortColumns` dynamically.)

- [ ] **Step 3: pages/podetail/form.blade.php**

```blade
<?php /** @var App\Models\PoDetail $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-select col="6" name="po_detail_id_po" :options="$poOptions" />
                <x-select col="6" name="po_detail_id_product" :options="$productOptions" />
                <x-input col="6" name="po_detail_code" />
                <x-input col="6" name="po_detail_qty" type="number" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
```

- [ ] **Step 4: pages/podetail/table.blade.php**

Copy `resources/views/pages/keluardetail/table.blade.php`; docblock:

```blade
<?php /** @var App\Models\PoDetail $model */ ?>
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/pages/po resources/views/pages/podetail
git commit -m "feat(wms): PO and PO Detail views"
```

---

### Task 5: Final verification

**Files:** none new — verify only

- [ ] **Step 1: Re-run PO tests**

```bash
php artisan test --filter=PurchaseOrderTest
```

Expected: 3 passed.

- [ ] **Step 2: Route + view resolve**

```bash
php artisan route:list --name=wms-po
php artisan view:cache
```

Expected: routes listed; view cache succeeds (or at least no missing `pages.po` / `pages.podetail` errors).

- [ ] **Step 3: Manual acceptance (if app running)**

- Open `/wms/po` → create PO with status Pending
- Open `/wms/po-detail` → add line with product + qty
- Delete PO → detail gone
- Menu: Procurement → Purchase Order / PO Detail

- [ ] **Step 4: Final commit if any fix leftovers**

Only if Step 1–2 required fixes.

---

## Spec coverage checklist

| Spec section | Task |
|--------------|------|
| §4.2 po_status migration | Task 1 |
| §5 Po constants/fillable | Task 1 |
| §5 PoDetailController + options | Task 2 |
| §5 PoDetailPolicy | Task 2 |
| §6 Routes + menu | Task 3 |
| §7 Views | Task 4 |
| §9 Tests | Task 1 |
| Soft link inbound | Ops only — no code (spec §8) |
| Out of scope items | Skipped intentionally |

## Plan self-review

1. **Spec coverage:** All in-scope build items mapped to tasks 1–5.
2. **Placeholders:** None — full code in steps.
3. **Types:** `po_detail_id_po` is numeric FK (select), not string like Keluar — form uses `$poOptions` consistently.
4. **View path:** `podetail` not `po-detail` — documented in Global Constraints + Task 4.
