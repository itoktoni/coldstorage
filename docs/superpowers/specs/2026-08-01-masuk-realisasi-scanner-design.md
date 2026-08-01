# Masuk Realisasi Barcode Scanner Design

## Overview

Replace the manual qty input form on Masuk Detail "Realisasikan" with a Livewire-based barcode scanner that supports both mobile camera and desktop USB scanner.

## Requirements

- **Tech**: Livewire + Alpine.js + html5-qrcode
- **Scanner**: Camera (mobile) + USB barcode scanner (desktop)
- **QR format**: `{product_code}#{timestamp}#{qty}#{expired_date}` separated by `#`
- **Validation**: Scanned product_code must match the masuk detail's product
- **Storage**: Save to `masuk_realisasi` table, status stays Pending
- **Location**: Staging (no status change, no stock entry yet)
- **List display**: Summary grouped by product with total qty
- **Detail view**: Button to see individual scans (qty + barcode only)
- **Delete**: Can delete individual scans from the form
- **No refresh**: Livewire handles real-time updates

## Database

### masuk_realisasi table (existing)

| Column | Type | Notes |
|--------|------|-------|
| in_realisasi_id | bigint PK | Auto-increment |
| in_realisasi_masuk_code | varchar(50) FK | → masuk_detail.in_detail_code |
| in_realisasi_code | varchar(50) unique | Auto-generated INR-YYYYMMDD-XXXX |
| in_realisasi_id_product | bigint FK | → product.product_id |
| in_realisasi_qty | int | Quantity from barcode |
| in_realisasi_id_lokasi | bigint FK | → lokasi.lokasi_id (staging) |
| in_realisasi_group | int nullable | Group identifier |
| timestamps | | created_at, updated_at |

## Architecture

### Livewire Component: `MasukRealisasiScanner`

**Properties:**
- `$masukDetail` — the MasukDetail model instance
- `$summary` — collection grouped by product_id with total qty
- `$scans` — collection of individual scans for detail view
- `$selectedProductId` — product_id being viewed in detail
- `$barcodeInput` — bound to text input for USB scanner
- `$cameraActive` — toggle camera scanner modal
- `$error` — flash error message
- `$success` — flash success message

**Methods:**
- `mount($masukDetailId)` — load masuk detail, initialize summary
- `scan($barcodeContent)` — parse barcode, validate product, save to DB, refresh summary
- `parseBarcode($content)` — extract product_code, qty from barcode string
- `getDetail($productId)` — load individual scans for a product
- `deleteScan($realisasiId)` — delete a scan, refresh summary
- `closeDetail()` — close detail modal

### Camera Scanner (Alpine.js + html5-qrcode)

- Button "Scan Camera" opens modal
- html5-qrcode scans QR code
- On success: calls Livewire `scan()` method with decoded content
- Modal closes after successful scan

### USB Scanner (Text Input)

- Hidden text input field with auto-focus
- USB scanner types barcode content + Enter
- On Enter: calls Livewire `scan()` method
- Input clears after each scan

## Data Flow

```
1. User scans barcode (camera or USB)
2. Barcode content: "P0000000005#20260801173000#2.5#20260815"
3. Livewire scan() method:
   a. Parse: product_code=P0000000005, qty=2.5
   b. Validate: product_code exists in product table
   c. Validate: product matches masuk_detail.in_detail_id_product
   d. Create masuk_realisasi record (status Pending)
   e. Refresh summary (grouped by product, total qty)
4. UI updates in real-time (no page refresh)
```

## UI Layout

### Header Card
- Product name, qty planned, status (readonly)

### Scanner Section
- Text input for USB scanner (auto-focus)
- "Scan Camera" button (opens modal)
- Flash messages (success/error)

### Summary Table
| Product | Total Qty | Actions |
|---------|-----------|---------|
| Product A | 10 | Detail, Delete |

### Detail Modal
- Shows individual scans for selected product
| Qty | Barcode | Actions |
|-----|---------|---------|
| 2.5 | P0000000005#...#2.5#... | Delete |

## Validation Rules

1. Barcode must be parseable (format: `product_code#timestamp#qty#expired`)
2. product_code must exist in product table
3. product must match the masuk_detail's product
4. qty from barcode must be > 0

## Error Handling

- Invalid barcode format → "Format barcode tidak valid"
- Product not found → "Product tidak ditemukan"
- Product mismatch → "Product tidak sesuai dengan masuk detail"
- DB error → "Gagal menyimpan realisasi"

## Files to Create/Modify

1. `app/Livewire/MasukRealisasiScanner.php` — new Livewire component
2. `resources/views/livewire/masuk-realisasi-scanner.blade.php` — new view
3. `app/Http/Controllers/Wms/MasukDetailController.php` — update `getRealisasikan` to use Livewire
4. `resources/views/pages/masukdetail/realisasikan.blade.php` — replace with Livewire component
5. `composer.json` — may need livewire dependency check
