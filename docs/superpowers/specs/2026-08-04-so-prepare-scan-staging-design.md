# SO Prepare: Scan Staging Stock

## Overview

Halaman `/wms/so-prepare/{soId}` harus bisa scan stock yang ada di staging (`stock_type = STAGING`) untuk di-allocate ke SO. Ketika scan berhasil:

1. Buat `keluar_realisasi` (cerminan pengurangan stock dari staging)
2. Kurangi qty di tabel `stock` (TYPE_STAGING)
3. Buat `so_prepare_detail` (track alokasi ke SO)
4. Cek apakah semua line SO terpenuhi → otomatis Done

## Current Problem

Sekarang halaman so-prepare hanya bisa scan dari `keluar_realisasi` (OUTR codes). Tapi flow forklift-task yang baru memindahkan stock ke staging langsung update `stock` table (`stock_type = STAGING`) tanpa buat `keluar_realisasi`. Jadi barang staging tidak muncul di halaman so-prepare.

## Database Changes

### `so_prepare_detail` — tambah kolom

```php
$table->unsignedBigInteger('so_prepare_detail_id_stock')->nullable()
    ->after('so_prepare_detail_id_product')
    ->comment('Stock row dari staging yang di-allocate');
```

Migration: `database/migrations/2026_08_04_XXXXXX_add_stock_id_to_so_prepare_detail_table.php`

## Data Flow

```
User scan stock_code (barcode)
→ Cari stock di DB (stock_type = STAGING, stock_qty > 0)
→ Validasi product match dengan SO
→ Hitung qty: min(sisa kebutuhan SO, sisa qty staging)
→ Buat keluar_realisasi (out_realisasi_id_detail = keluar detail, out_realisasi_id_stock = stock, out_realisasi_qty = qty)
→ Kurangi stock_qty (decrement)
→ Buat so_prepare_detail (track alokasi)
→ Cek fulfillment → kalau semua line terpenuhi → Done
```

## Controller Changes

### `getPrepareSo` — Load staging stock

**Sekarang:** Query dari `keluar_realisasi`
**Sesudah:** Query dari `stock` table (TYPE_STAGING)

```php
private function stagedStockForSo(So $so): \Illuminate\Support\Collection
{
    $productIds = $so->details->pluck('so_detail_id_product');
    $prepare = SoPrepare::where('so_prepare_id_so', $so->so_id)->first();

    $stocks = Stock::where('stock_type', Stock::TYPE_STAGING)
        ->where('stock_qty', '>', 0)
        ->whereIn('stock_id_product', $productIds)
        ->with('lokasi.gudang')
        ->get();

    $assignedByStock = $prepare
        ? $prepare->details()
            ->selectRaw('so_prepare_detail_id_stock, SUM(so_prepare_detail_qty) as total')
            ->groupBy('so_prepare_detail_id_stock')
            ->pluck('total', 'so_prepare_detail_id_stock')
        : collect();

    return $stocks->map(function ($stock) use ($assignedByStock) {
        $assigned = (float) ($assignedByStock->get($stock->stock_id) ?? 0);
        return [
            'stock_id'      => $stock->stock_id,
            'stock_code'    => $stock->stock_code,
            'product'       => $stock->product,
            'lokasi_nama'   => $stock->lokasi?->lokasi_nama ?? '-',
            'gudang_nama'   => $stock->lokasi?->gudang?->gudang_nama ?? '-',
            'stock_qty'     => (float) $stock->stock_qty,
            'qty_assigned'  => $assigned,
            'qty_remaining' => max(0, (float) $stock->stock_qty - $assigned),
        ];
    });
}
```

### `assignByScan` — Scan stock_code dari staging

**Sekarang:** Cari `keluar_realisasi` by `out_realisasi_code` atau `stock_code`
**Sesudah:** Cari `stock` by `stock_code` (TYPE_STAGING)

```php
private function assignByScan(So $so, SoPrepare $prepare, string $scan): void
{
    // 1. Cari stock di staging
    $stock = Stock::where('stock_code', $scan)
        ->where('stock_type', Stock::TYPE_STAGING)
        ->where('stock_qty', '>', 0)
        ->first();

    if (!$stock) {
        throw new \RuntimeException('Stock tidak ditemukan di staging.');
    }

    // 2. Validasi product match SO
    $line = $so->details->firstWhere('so_detail_id_product', $stock->stock_id_product);
    if (!$line) {
        throw new \RuntimeException('Product tidak ada di SO ini.');
    }

    // 3. Hitung qty alokasi
    $assignedForLine = (float) $prepare->details()
        ->where('so_prepare_detail_id_product', $stock->stock_id_product)
        ->sum('so_prepare_detail_qty');
    $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;

    if ($lineRemaining <= 0) {
        throw new \RuntimeException('Kebutuhan SO untuk product ini sudah terpenuhi.');
    }

    $assignedForStock = (float) $prepare->details()
        ->where('so_prepare_detail_id_stock', $stock->stock_id)
        ->sum('so_prepare_detail_qty');
    $stockRemaining = (float) $stock->stock_qty - $assignedForStock;

    if ($stockRemaining <= 0) {
        throw new \RuntimeException('Stock ini sudah habis dialokasikan.');
    }

    $qty = min($lineRemaining, $stockRemaining);

    // 4. Buat keluar_realisasi (cerminan pengurangan stock)
    $keluarDetail = KeluarDetail::where('out_detail_code_keluar', $this->keluarCodeForSo($so))
        ->where('out_detail_id_product', $stock->stock_id_product)
        ->first();

    if (!$keluarDetail) {
        throw new \RuntimeException('Keluar detail tidak ditemukan untuk product ini.');
    }

    $realisasi = KeluarRealisasi::create([
        'out_realisasi_id_detail' => $keluarDetail->out_detail_id,
        'out_realisasi_id_stock'  => $stock->stock_id,
        'out_realisasi_qty'       => $qty,
    ]);

    // 5. Buat so_prepare_detail
    SoPrepareDetail::create([
        'so_prepare_detail_id_prepare'   => $prepare->so_prepare_id,
        'so_prepare_detail_id_realisasi' => $realisasi->out_realisasi_id,
        'so_prepare_detail_id_product'   => $stock->stock_id_product,
        'so_prepare_detail_id_stock'     => $stock->stock_id,
        'so_prepare_detail_qty'          => $qty,
    ]);

    // 6. Kurangi staging stock
    Stock::where('stock_id', $stock->stock_id)->decrement('stock_qty', $qty);
}
```

### `assignRealisasi` — Manual qty allocation

**Sekarang:** Allocate dari `keluar_realisasi`
**Sesudah:** Allocate dari `stock` (TYPE_STAGING), buat `keluar_realisasi` + kurangi stock

```php
private function assignRealisasi(So $so, SoPrepare $prepare, int $stockId, float $qty): void
{
    // 1. Validasi stock
    $stock = Stock::where('stock_id', $stockId)
        ->where('stock_type', Stock::TYPE_STAGING)
        ->first();

    if (!$stock) {
        throw new \RuntimeException('Stock tidak ditemukan di staging.');
    }

    // 2. Validasi product match
    $line = $so->details->firstWhere('so_detail_id_product', $stock->stock_id_product);
    if (!$line) {
        throw new \RuntimeException('Product tidak ada di SO ini.');
    }

    // 3. Validasi qty
    $assignedForLine = (float) $prepare->details()
        ->where('so_prepare_detail_id_product', $stock->stock_id_product)
        ->sum('so_prepare_detail_qty');
    $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;

    if ($qty > $lineRemaining + 0.001) {
        throw new \RuntimeException('Qty melebihi sisa kebutuhan SO. Sisa: ' . $lineRemaining);
    }

    $assignedForStock = (float) $prepare->details()
        ->where('so_prepare_detail_id_stock', $stockId)
        ->sum('so_prepare_detail_qty');
    $stockRemaining = (float) $stock->stock_qty - $assignedForStock;

    if ($qty > $stockRemaining + 0.001) {
        throw new \RuntimeException('Qty melebihi sisa stock staging. Sisa: ' . $stockRemaining);
    }

    // 4. Buat keluar_realisasi
    $keluarDetail = KeluarDetail::where('out_detail_code_keluar', $this->keluarCodeForSo($so))
        ->where('out_detail_id_product', $stock->stock_id_product)
        ->first();

    if (!$keluarDetail) {
        throw new \RuntimeException('Keluar detail tidak ditemukan untuk product ini.');
    }

    $realisasi = KeluarRealisasi::create([
        'out_realisasi_id_detail' => $keluarDetail->out_detail_id,
        'out_realisasi_id_stock'  => $stock->stock_id,
        'out_realisasi_qty'       => $qty,
    ]);

    // 5. Buat so_prepare_detail
    SoPrepareDetail::create([
        'so_prepare_detail_id_prepare'   => $prepare->so_prepare_id,
        'so_prepare_detail_id_realisasi' => $realisasi->out_realisasi_id,
        'so_prepare_detail_id_product'   => $stock->stock_id_product,
        'so_prepare_detail_id_stock'     => $stock->stock_id,
        'so_prepare_detail_qty'          => $qty,
    ]);

    // 6. Kurangi staging stock
    Stock::where('stock_id', $stock->stock_id)->decrement('stock_qty', $qty);
}
```

### `postPrepareSo` — Handle scan + manual assignment

Ubah input validation: `assign.*.realisasi_id` → `assign.*.stock_id`

```php
public function postPrepareSo(GeneralRequest $request, string $soId)
{
    $data = $request->validate([
        'stock_scan' => ['nullable', 'string'],
        'assign'     => ['nullable', 'array'],
        'assign.*'   => ['nullable', 'array'],
        'assign.*.stock_id' => ['nullable', 'integer'],
        'assign.*.qty'      => ['nullable', 'numeric', 'min:0'],
    ]);

    // ... (same flow, but assign uses stock_id instead of realisasi_id)
}
```

### `prepareLineStatus` — Update assigned calculation

```php
private function prepareLineStatus(So $so, SoPrepare $prepare): array
{
    return $so->details->map(function (SoDetail $d) use ($prepare) {
        $assigned = (float) $prepare->details()
            ->where('so_prepare_detail_id_product', $d->so_detail_id_product)
            ->sum('so_prepare_detail_qty');

        return [
            'detail'       => $d,
            'qty_needed'   => (float) $d->so_detail_qty,
            'qty_assigned' => $assigned,
            'qty_remaining'=> max(0, (float) $d->so_detail_qty - $assigned),
        ];
    })->all();
}
```

## View Changes — `prepare-so.blade.php`

### Staged Stock Table

Ganti "Barang Staging (Hasil Picking Forklift)" jadi "Stock Tersedia di Staging"

Tabel:
| Stock Code | Product | Lokasi | Qty | Terpakai | Sisa | Alokasi Manual |

### Scan Input

Scan otomatis alokasikan sisa qty SO dari staging stock:
- Input: `stock_scan` (stock_code dari barcode)
- Submit ke `wms-so-prepare.update` (POST)
- Otomatis alokasi min(sisa kebutuhan, sisa stock)

### Manual Alokasi

Per baris stock, input qty manual + button alokasi:
- Input: `assign[stock_id][qty]` + hidden `stock_id`
- Submit ke `wms-so-prepare.update` (POST)

## Error Handling

| Error | Message |
|-------|---------|
| Stock not found | "Stock tidak ditemukan di staging" |
| Bukan STAGING | "Stock ini bukan di staging area" |
| Product mismatch | "Product tidak ada di SO ini" |
| Qty exceeds need | "Qty melebihi sisa kebutuhan SO" |
| Qty exceeds stock | "Qty melebihi sisa stock staging" |
| Keluar detail missing | "Keluar detail tidak ditemukan" |

## Status Management

| Kondisi | Action |
|---------|--------|
| Scan pertama kali | `so_prepare_status` = `Pending` (default) |
| Semua line terpenuhi | `so_prepare_status` = `Done`, `so_status` = `Confirmed` |
| Scan error | Tidak ada perubahan, flash error |

## Files to Modify

| File | Changes |
|------|---------|
| `database/migrations/2026_08_04_XXXXXX_add_stock_id_to_so_prepare_detail_table.php` | Add `so_prepare_detail_id_stock` column |
| `app/Models/SoPrepareDetail.php` | Add `so_prepare_detail_id_stock` to `$fillable` |
| `app/Http/Controllers/Wms/SoController.php` | `getPrepareSo`, `assignByScan`, `assignRealisasi`, `postPrepareSo`, `prepareLineStatus` |
| `resources/views/pages/so/prepare-so.blade.php` | Tabel staging dari stock_rows, input scan |

## Testing

- Feature: scan valid stock_code dari staging → buat realisasi + kurangi stock
- Feature: scan stock_code yang tidak ada → error "Stock tidak ditemukan"
- Feature: scan stock_type != STAGING → error "bukan di staging"
- Feature: scan product mismatch → error "Product tidak ada di SO"
- Feature: scan qty melebihi sisa kebutuhan → error
- Feature: semua line terpenuhi → status prepare = Done, SO = Confirmed
- Feature: manual allocation dari tabel staging
