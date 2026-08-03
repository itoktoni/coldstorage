# Forklift Pick: Scan-Only UI & Pallet Support

## Context

Forklift operators work in -20°C freezer. Touchscreen/button interaction is not feasible — they can only look at the screen and use a barcode scanner. The current pick flow requires multiple form interactions which doesn't work in this environment.

Additionally, warehouse operations use pallets as the primary pick unit. A pallet may contain multiple barcodes. The system needs to support pallet-based picking while also allowing direct barcode or location-based picks.

## Goals

1. **Scan-only UI** — operator only scans codes, no button pressing
2. **Auto-detect scan type** — prefix-based detection (P=pallet, L=location, B=barcode)
3. **Pallet support** — optional pallet code grouping barcodes
4. **FIFO by expiry** — always pick oldest expiry first
5. **Realisasi per barcode** — even when picking by pallet/location, records are per barcode

## Scan Prefix Config

Config in `.env`:

```
SCAN_PREFIX_PALLET=P
SCAN_PREFIX_LOCATION=L
SCAN_PREFIX_BARCODE=B
```

Detection logic:
- Code starts with `P` → **Pallet mode**: find all IN stock with matching `stock_pallet_code`
- Code starts with `L` → **Location mode**: find all IN stock at that location for the current product
- Code starts with `B` → **Barcode mode**: find 1 stock with matching `stock_code`
- Code has no matching prefix → **Fallback to barcode mode**

## Data Model Changes

### stock table

Add column `stock_pallet_code` (nullable string):

```sql
ALTER TABLE stock ADD COLUMN stock_pallet_code VARCHAR(100) NULL;
```

- `stock_pallet_code` = `in_realisasi_group` (pallet grouping code)
- `stock_reff` = `in_realisasi_masuk_code` (batch/inbound reference)
- Both fields serve different purposes and are kept separate

### Stock model

Add `stock_pallet_code` to `$fillable`.

## Inbound Flow Change

When forklift relocates pallet to rack (in `ForkliftController::store`):

```php
Stock::create([
    // ... existing fields
    'stock_reff'        => $data['group_code'],  // keep as-is (already uses group_code)
    'stock_pallet_code' => $data['group_code'],  // NEW: pallet grouping code
]);
```

## Pick Flow (Scan-Only)

### Route

```
GET  /wms/forklift/pick/{outCode}     → show pick page (scan UI)
POST /wms/forklift/pick/{outCode}     → process scan (JSON response)
```

### UI Layout

```
┌─────────────────────────────────────────────┐
│  🏗️ PICK 3/7  ████████░░░░░░  42%          │
├─────────────────────────────────────────────┤
│                                             │
│  Produk: Ice Cream Walls                   │
│  Butuh: 50 kg  |  Terpick: 20 kg          │
│  Sisa: 30 kg                               │
│                                             │
│  ┌─────────────────────────────────────┐    │
│  │                                     │    │
│  │   SCAN: Pallet / Location / Barcode │    │
│  │                                     │    │
│  │   > _                               │    │
│  │                                     │    │
│  └─────────────────────────────────────┘    │
│                                             │
│  Rak suggested: A-03 (FIFO)               │
│                                             │
│  ✅ B001 (30kg) → STAGING-01              │
│  ✅ B002 (20kg) → STAGING-01              │
│                                             │
└─────────────────────────────────────────────┘
```

Design principles:
- Large font, high contrast
- No buttons — scan = submit
- Auto-advance when pick is complete
- Show scan history below input
- Show suggested location based on FIFO

### Scan Processing (Controller)

```php
public function pickScan(Request $request, string $outCode)
{
    $scanCode = $request->input('scan_code');
    $prefix = config('scan.prefix');

    // 1. Detect mode
    if (str_starts_with($scanCode, $prefix['pallet'])) {
        $mode = 'pallet';
        $code = substr($scanCode, strlen($prefix['pallet']));
        $stocks = Stock::where('stock_pallet_code', $code)
            ->where('stock_type', 'IN')
            ->where('stock_qty', '>', 0)
            ->get();
    } elseif (str_starts_with($scanCode, $prefix['location'])) {
        $mode = 'location';
        $code = substr($scanCode, strlen($prefix['location']));
        $stocks = Stock::where('stock_code_lokasi', $code)
            ->where('stock_id_product', $detail->out_detail_id_product)
            ->where('stock_type', 'IN')
            ->where('stock_qty', '>', 0)
            ->orderBy('stock_expired_date')
            ->get();
    } else {
        $mode = 'barcode';
        $code = str_starts_with($scanCode, $prefix['barcode'])
            ? substr($scanCode, strlen($prefix['barcode']))
            : $scanCode;
        $stocks = Stock::where('stock_code', $code)
            ->where('stock_type', 'IN')
            ->where('stock_qty', '>', 0)
            ->get();
    }

    // 2. Validate
    // - stocks not empty
    // - product matches keluar detail
    // - qty sufficient

    // 3. Process pick (per barcode)
    foreach ($stocks as $stock) {
        $take = min($stock->stock_qty, $remaining);
        $stock->decrement('stock_qty', $take);

        // Create KeluarRealisasi per barcode
        KeluarRealisasi::create([...]);

        // Create STAGING stock
        Stock::create([
            'stock_type' => Stock::TYPE_STAGING,
            'stock_qty'  => $take,
            // ...
        ]);

        // Consume RESERVE
        Stock::consumeReserve($soCode, $productId, $take);

        $remaining -= $take;
        if ($remaining <= 0) break;
    }

    // 4. Return JSON response
    return response()->json([
        'ok' => true,
        'picked' => $picked,
        'remaining' => $remaining,
        'done' => $remaining <= 0,
        'next_pick' => $nextPickUrl,
    ]);
}
```

### Frontend (JavaScript)

```javascript
// Auto-focus input field
// On scan (Enter key or scanner input):
// 1. POST to /wms/forklift/pick/{outCode} with scan_code
// 2. On success: update UI (progress, history, advance if done)
// 3. On error: show error message, re-focus input
// 4. Clear input field for next scan

const input = document.getElementById('scan-input');
input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        processScan(input.value);
        input.value = '';
    }
});

async function processScan(code) {
    const res = await fetch(pickUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ scan_code: code }),
    });

    const data = await res.json();
    if (data.ok) {
        updateUI(data);
        if (data.done) {
            // Auto-advance to next pick or show complete
            window.location.href = data.next_pick;
        }
    } else {
        showError(data.message);
    }

    input.focus();
}
```

## Error Handling

| Error | Message |
|-------|---------|
| Stock not found | "Code tidak dikenali" |
| Wrong product | "Barcode ini untuk produk lain" |
| Stock empty | "Stock sudah habis" |
| Already moved | "Stock sudah dipindah ke staging" |
| Location not staging | "Scan lokasi staging yang valid" |

## Files to Create/Modify

### New Files
- `config/scan.php` — scan prefix config
- `resources/views/pages/forklift/pick-scan.blade.php` — scan-only UI

### Modified Files
- `database/migrations/2026_08_03_XXXXXX_add_stock_pallet_code_to_stock_table.php` — new migration
- `app/Models/Stock.php` — add `stock_pallet_code` to fillable
- `app/Http/Controllers/Wms/ForkliftController.php` — add `pickScan()` method, update `store()` to set pallet_code
- `routes/web.php` — add pick scan route
- `.env.example` — add scan prefix config

## Testing

- Unit: scan prefix detection logic
- Feature: pallet pick creates realisasi per barcode
- Feature: location pick creates realisasi per barcode
- Feature: barcode pick creates realisasi for single stock
- Feature: error cases (wrong product, empty stock, etc.)
