# Split Production Flow Design

## Overview

Redesign the WMS Split module from a basic CRUD form into a production split workflow. A source product (e.g., Sirloin 10kg) is split into a target product (e.g., Sirloin Slice) + optional waste product (e.g., Daging Giling). Shrinkage (penyusutan) is auto-calculated.

## User Flow

1. User selects **target product** (the product to create from split)
2. User **scans source barcodes** (multiple scans allowed, all same product)
3. User optionally selects **waste product** (usable byproduct with resale value)
4. User inputs **qty hasil** (target qty) and **qty waste** (waste qty)
5. **Penyusutan** (shrinkage) = total source qty - hasil qty - waste qty (auto-calculated)
6. User clicks **Proses** → stock movements execute

### Validation Rules

- Source barcodes must all belong to the same product
- Source barcodes must have `stock_type = 'IN'` and `stock_qty > 0`
- `qty_hasil + qty_waste <= total_qty_sumber` (penyusutan >= 0)
- Target product must be different from source product
- Waste product is optional; if set, must be different from both source and target

## Database Changes

### Modify `split` table

Drop old columns and add new ones. Keep `split_tanggal`, `split_created_by`, `split_created_at`, `created_at`, `updated_at`:

```php
Schema::table('split', function (Blueprint $table) {
    $table->dropForeign(['split_id_product', 'split_id_stock']);
    $table->dropColumn(['split_id_product', 'split_id_stock', 'split_id_reff', 'split_qty_new', 'split_qty_old', 'split_qty_waste']);

    $table->foreignId('split_id_product_target')->constrained('product', 'product_id')->onDelete('cascade');
    $table->foreignId('split_id_product_waste')->nullable()->constrained('product', 'product_id')->onDelete('set null');
    $table->double('split_qty_hasil')->default(0);
    $table->double('split_qty_waste')->default(0);
    $table->double('split_qty_penyusutan')->default(0);
    $table->string('split_status', 20)->default('Draft'); // Draft, Processed, Cancelled
});
```

### Create `split_detail` table

```php
Schema::create('split_detail', function (Blueprint $table) {
    $table->id('split_detail_id');
    $table->foreignId('split_detail_id_split')->constrained('split', 'split_id')->onDelete('cascade');
    $table->foreignId('split_detail_id_stock')->constrained('stock', 'stock_id')->onDelete('cascade');
    $table->double('split_detail_qty');
    $table->timestamps();
});
```

## Stock Movement Logic

When split is processed (status changes from Draft → Processed):

```
BEGIN TRANSACTION

1. For each split_detail (source barcode):
   - Decrement stock.stock_qty by split_detail_qty
   - If stock.stock_qty reaches 0 → delete the stock row

2. Create new stock row for target product (STAGING):
   - stock_id_product = split_id_product_target
   - stock_qty = split_qty_hasil
   - stock_type = 'STAGING'
   - stock_code_lokasi = staging area (STG-A or source gudang staging)
   - stock_expired_date = same as source expired date
   - stock_reff = 'SPLIT-{split_id}' (for tracking)
   - stock_pallet_code = 'SPLIT-{split_id}' (for forklift scanning)

3. If waste product exists:
   - Create new stock row for waste product (STAGING):
     - stock_id_product = split_id_product_waste
     - stock_qty = split_qty_waste
     - stock_type = 'STAGING'
     - stock_code_lokasi = staging area
     - stock_expired_date = same as source expired date
     - stock_reff = 'SPLIT-{split_id}'
     - stock_pallet_code = 'SPLIT-{split_id}'

4. Create ForkliftTask for each output stock:
   - forklift_type = 'putaway'
   - forklift_pallet_code = 'SPLIT-{split_id}'
   - forklift_lokasi_asal = staging area code (STG-A)
   - forklift_lokasi_tujuan = source lokasi (same gudang as source)
   - forklift_reff = 'SPLIT-{split_id}'
   - forklift_status = 'Pending'

5. Update split status → 'Processed'

COMMIT
```

After forklift completes putaway:
- Stock type changes from STAGING → IN
- Stock lokasi updated to the actual rack location

## Files to Create/Modify

### New Files
- `database/migrations/2026_08_04_000000_modify_split_add_detail.php` — migration
- `app/Models/SplitDetail.php` — model
- `app/Livewire/SplitProduce.php` — Livewire component
- `resources/views/livewire/split-produce.blade.php` — view

### Modified Files
- `app/Models/Split.php` — update fillable, casts, relationships (`productTarget`, `productWaste`, `details`), `$filterColumns`, `$sortColumns`
- `app/Http/Controllers/Wms/SplitController.php` — add `getProduce()` method returning Livewire view, update `share()` with product options
- `resources/views/pages/split/table.blade.php` — add "Produce" action button (soft green), add `split_status` column
- `resources/views/pages/split/form.blade.php` — keep for edit/detail view only
- `database/seeders/WmsSeeder.php` — add `split_detail` to truncation list, add split products + stock

### Seeder Products (new)

| Code | Nama | Harga | Category |
|------|------|-------|----------|
| PROD-23 | Sirloin Slice (kg) | 210000 | daging |
| PROD-24 | Daging Giling (kg) | 95000 | daging |
| PROD-25 | Has Dalam Slice (kg) | 220000 | daging |
| PROD-26 | Tetelan Sapi (kg) | 75000 | daging |

### Seeder Stock (new)

Source stock for split testing:

| Code | Product | Qty | Lokasi | Type |
|------|---------|-----|--------|------|
| STK-20260804-0001 | Sirloin 10kg (PROD-1 existing) | 50 | LOC-01 | IN |
| STK-20260804-0002 | Has Dalam (PROD-2 existing) | 30 | LOC-01 | IN |

## UI Layout (Livewire SplitProduce)

```
┌─────────────────────────────────────────┐
│  Split Production                       │
├─────────────────────────────────────────┤
│  Produk Target: [Select dropdown]       │
│  Waste Product: [Select dropdown]       │
├─────────────────────────────────────────┤
│  Scan Barcode Sumber:                   │
│  [____________] [Scan]                  │
│                                         │
│  Scanned Barcodes:                      │
│  ┌──────────┬────────┬──────┬────┐      │
│  │ Barcode  │ Product│ Qty  │    │      │
│  ├──────────┼────────┼──────┼────┤      │
│  │ STK-001  │Sirloin │ 10kg │ 🗑 │      │
│  │ STK-002  │Sirloin │ 10kg │ 🗑 │      │
│  └──────────┴────────┴──────┴────┘      │
│  Total Sumber: 20kg                     │
├─────────────────────────────────────────┤
│  Qty Hasil:  [____] kg                  │
│  Qty Waste:  [____] kg                  │
│  Penyusutan: 3kg (auto)                 │
├─────────────────────────────────────────┤
│  [Proses Split]                         │
└─────────────────────────────────────────┘
```

After processing → ForkliftTask auto-created → Forklift moves STAGING → IN at rack.

## Table Page Changes

- Add "Produce" action button (soft green) that navigates to `/wms/split/produce` route
- Keep existing table showing split history
- Add `split_status` column to table
- Show `split_qty_penyusutan` in table
- `$sortColumns`: `['split_tanggal', 'split_status', 'split_id']`
- `$filterColumns`: `['split_id_product_target', 'split_id_product_waste', 'split_status', 'split_tanggal']`

## Route

Add manual route for produce page (outside auto-route):
```php
Route::get('/wms/split/produce', [SplitController::class, 'getProduce'])->name('wms-split.produce');
```

## Livewire Component: SplitProduce

- `mount()`: initialize empty state, load product options
- `scanBarcode($code)`: validate stock, add to scanned list
- `removeScan($index)`: remove from scanned list
- `process()`: execute stock movements in DB transaction
- Properties: `$targetProductId`, `$wasteProductId`, `$scannedBarcodes`, `$qtyHasil`, `$qtyWaste`
- Computed: `$totalSumber`, `$penyusutan`, `$isValid`
