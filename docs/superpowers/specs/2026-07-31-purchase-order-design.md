# Purchase Order (PO) Design

**Date:** 2026-07-31  
**Status:** Approved (Approach C — force-continue after user chose C)  
**Scope:** WMS procurement master — header + lines, status lifecycle, soft link to inbound

---

## 1. Goal

CRUD Purchase Order so warehouse can record supplier orders before goods arrive. Soft-link to inbound via free-text `masuk_detail.in_detail_reff = po_code`. No auto stock movement.

**Success:**
- User creates PO header + line items
- Status moves Pending → Ordered → Partial → Closed (manual)
- Delete PO cascades details
- Menu reachable under Procurement
- Soft ref to inbound works without schema change on `masuk_detail`

---

## 2. Approach (C)

| Option | Summary | Why not |
|--------|---------|---------|
| A | Header+lines only, no status | Too thin for procurement ops |
| B | Nested one-page form | Breaks Keluar pattern; more custom code |
| **C** | Header+lines + status + soft inbound link | **Chosen** — matches codebase, YAGNI hard link |

---

## 3. Architecture

```
po ──hasMany──► detail_po
 │                ├── po_detail_id_product → product
 │                └── po_detail_qty
 │
 │ soft link (text, no FK)
 ▼
masuk_detail.in_detail_reff = po.po_code   (manual by user)
     │
     ▼
masuk_realisasi → stock   (unchanged existing flow)
```

Two modules, same pattern as Keluar / KeluarDetail:

| Module | Model | Table | Controller | Route name | Views |
|--------|-------|-------|------------|------------|-------|
| PO header | `Po` | `po` | `Wms\PoController` | `wms-po` | `pages/po/{table,form}` |
| PO lines | `PoDetail` | `detail_po` | `Wms\PoDetailController` | `wms-po-detail` | `pages/podetail/{table,form}` |

ControllerTrait resolves views via class basename:
- `PoController` → `pages.po.*`
- `PoDetailController` → `pages.podetail.*`

---

## 4. Data model

### 4.1 Existing (keep)

**`po`** (migration `2026_07_31_000001_create_po_tables.php`):
- `po_id` PK
- `po_tanggal` date
- `po_code` string(50) unique
- `po_supplier` string(200)
- `po_keterangan` text nullable
- timestamps

**`detail_po`**:
- `po_detail_id` PK
- `po_detail_id_po` FK → `po.po_id` cascadeOnDelete
- `po_detail_id_product` FK → `product.product_id`
- `po_detail_qty` integer
- `po_detail_code` string(50) unique
- timestamps

### 4.2 Delta (new migration)

```
po_status  string(30)  default 'Pending'  after po_supplier
```

Status constants on `Po`:
- `STATUS_PENDING = 'Pending'`
- `STATUS_ORDERED = 'Ordered'`
- `STATUS_PARTIAL = 'Partial'`
- `STATUS_CLOSED  = 'Closed'`

No enum DB type — string matches `Keluar.out_status` style.

### 4.3 Out of schema (v1)

- No FK `masuk_detail` → `po`
- No received-qty column on `detail_po`
- No supplier master table
- No price/amount columns
- No PO→inbound auto job

---

## 5. Models

### Po (extend existing)
- fillable: + `po_status`
- filterColumns: + `po_status`
- sortColumns: + `po_status`
- constants STATUS_*
- `details()` hasMany already exists

### PoDetail (keep)
- fillable, relations already correct
- filterColumns add `po_detail_id_po` for filter-by-header
- share `poOptions` = Po::pluck('po_code', 'po_id') on detail controller (optional; or free number input like keluar string FK — **use FK select** because schema is FK not string)

### Controllers
- **PoController** — exists; keep `getData()` with `details.product`; share needs no productOptions on header (move productOptions only to detail). Header form: tanggal, code, supplier, status, keterangan.
- **PoDetailController** — new, mirror `KeluarDetailController`:
  - share: `productOptions`, `poOptions` (Po::pluck po_code, po_id)
  - getData: with `po`, `product`

### Policy
- `PoPolicy` exists (empty BasePolicy) — keep
- `PoDetailPolicy` — new empty BasePolicy if framework requires; else rely on default

---

## 6. Routes & menu

**routes/web.php** (after inbound or new Procurement block):
```php
Route::auto('/wms/po', 'Wms\PoController', ['name' => 'wms-po']);
Route::auto('/wms/po-detail', 'Wms\PoDetailController', ['name' => 'wms-po-detail']);
```

**config/menu.php** — new section:
```php
[
    'label' => 'Procurement',
    'items' => [
        ['route' => 'wms-po.getTable', 'icon' => 'shopping_cart', 'label' => 'Purchase Order'],
        ['route' => 'wms-po-detail.getTable', 'icon' => 'list_alt', 'label' => 'PO Detail'],
    ],
],
```
Place before Inbound.

**permision.php** — no change (empty restrict = allow).

---

## 7. UI (views)

Copy Keluar / KeluarDetail blades.

### pages/po/form.blade.php
Fields: `po_code`, `po_tanggal` (date), `po_supplier`, `po_status` (select or input), `po_keterangan` (textarea).

### pages/po/table.blade.php
Standard table from `$model::$sortColumns` (code, tanggal, supplier, status).

### pages/podetail/form.blade.php
Fields: `po_detail_id_po` (select poOptions), `po_detail_id_product` (select productOptions), `po_detail_code`, `po_detail_qty` (number).

### pages/podetail/table.blade.php
Standard table; sortColumns: code, qty (+ optionally show product name via relation later — v1 use raw columns only).

---

## 8. Validation & business rules

Via existing CreateAction/UpdateAction + GeneralRequest fillable filter:
- Only fillable fields accepted
- DB unique on `po_code`, `po_detail_code`
- FK integrity on product + po
- `po_detail_qty` must be positive integer — **app-level** if CreateAction has no rules: document as recommended model boot or leave DB free for v1. Prefer: if GeneralRequest / actions support rules, add `min:1`; else document manual check later.

**Status rules (v1 soft):**
- Default Pending on create
- Closed: still editable (no hard lock) — YAGNI lock until inbound link tracks qty
- No auto status from receiving

**Delete:**
- Delete PO → cascade detail_po (DB)
- No block if `in_detail_reff` mentions code (soft link, orphan text OK)

**Inbound soft link (ops, not code):**
- When creating `masuk_detail`, set `in_detail_reff` = `po_code`
- No UI picker required v1

---

## 9. Testing

`tests/Feature/Wms/PurchaseOrderTest.php` (Pest), mirror OutboundTest:

1. **persists po with details + product relation**
2. **cascade delete po removes detail_po**
3. **default status Pending** (after migration)
4. **unique po_code** throws / fails on duplicate (optional if RefreshDatabase)

Use existing factories or direct `::create` like OutboundTest.

---

## 10. Implementation inventory

### Exists (reuse)
- [x] Migration create po + detail_po
- [x] Model Po, PoDetail
- [x] PoController (thin)
- [x] PoPolicy

### Build
- [ ] Migration add `po_status`
- [ ] Po model: status constants, fillable/filter/sort
- [ ] PoDetailController
- [ ] PoDetailPolicy (if needed)
- [ ] Routes x2
- [ ] Views: po form+table, podetail form+table
- [ ] Menu Procurement
- [ ] PoController: drop productOptions from share (header unused); status field only
- [ ] Feature test PurchaseOrderTest
- [ ] Run migrate + pest filter

### Explicitly skip
- Nested multi-line form
- Auto masuk_detail from PO
- Received qty / Partial auto calc
- Supplier master
- Line price
- Permission matrix entries
- Seeder (optional later)

---

## 11. Error handling

| Case | Behavior |
|------|----------|
| Duplicate po_code | DB unique → CreateAction failure flash |
| Delete PO with lines | Cascade OK |
| Invalid product FK | DB / validation fail |
| Closed PO edit | Allowed v1 |

---

## 12. Risks & notes

- **ControllerTrait module name:** `PoDetailController` → `podetail` (no hyphen). Views folder must be `pages/podetail/` not `pages/po-detail/`.
- **Keluar detail uses string FK** (`out_detail_code_keluar`); PO detail uses **numeric FK** (`po_detail_id_po`) — form uses select of po_id, not free text.
- **pos.blade.php** is Point of Sale — never conflate with PO routes.
- PO not in `requirements.md`; lives as procurement layer above inbound (prd-aligned soft reff).

---

## 13. Acceptance checklist

- [ ] Migrate adds po_status default Pending
- [ ] `/wms/po` table/create/update/delete works
- [ ] `/wms/po-detail` CRUD linked to PO + product
- [ ] Menu shows Purchase Order + PO Detail
- [ ] Delete PO removes details
- [ ] Pest PurchaseOrderTest green
- [ ] No change to masuk_* / stock behavior
