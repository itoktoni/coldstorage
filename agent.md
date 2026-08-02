# Agent Notes — WMS Project (D:\laravel\whatsapp)

Catatan internal untuk agent. JANGAN diekspos ke user biasa. Update setiap ketemu aturan bisnis baru.

---

## 1. Aturan Memilih Lokasi yang Valid (Sistem Rekomendasi Rack)

Sumber kebenaran ada di:
- Controller: `app/Http\Controllers/Wms/PoDetailController.php` (method `getConvertToMasuk`, baris 41–109) — paling lengkap, dipakai saat konversi PO → Masuk Detail.
- Controller: `app/Http\Controllers/Wms/ForkliftController.php` (method `index`, baris 32–55) — versi sederhana untuk rekomendasi tunggal di forklift.
- Model: `app/Models/Lokasi.php` — method pembantu `canAcceptCategory()`, `hasCapacity()`, accessor `current_qty`.

### 1.1 Model `Lokasi` — method pembantu

```php
// accessor
public function getCurrentQtyAttribute(): float
{
    return $this->stock()->where('stock_qty', '>', 0)->sum('stock_qty');
}

// kategori: lokasi tanpa kategori menerima semua; kalau lokasi berkategori, produk tanpa kategori ditolak
public function canAcceptCategory(?string $productCategory): bool
{
    if (empty($this->lokasi_category)) return true;
    if (empty($productCategory))       return false;
    return strtolower($this->lokasi_category) === strtolower($productCategory);
}

// kapasitas: null = tak terbatas; else current_qty + tambahan <= lokasi_max_qty
public function hasCapacity(float $additionalQty = 0): bool
{
    if (is_null($this->lokasi_max_qty)) return true;
    return ($this->current_qty + $additionalQty) <= $this->lokasi_max_qty;
}
```

### 1.2 Algoritma "suitable lokasi" (PoDetailController::getConvertToMasuk)

```
allLokasi = Lokasi::with('gudang')->get()

suitableLokasi = allLokasi
  .filter(lokasi => lokasi.canAcceptCategory(productCategory)
                    && lokasi.hasCapacity(0.001))   // minimal 0.001 muat
  .map(lokasi => {
      currentQty   = (float) lokasi.current_qty;
      maxQty       = lokasi.lokasi_max_qty;
      capacityLeft = maxQty === null ? null : max(0, maxQty - currentQty);
      priority     = lokasi.lokasi_category ? 0 : 1;   // <-- lokasi berkategori lebih diprioritaskan
      return [model, current_qty, capacity_left, priority];
  })
  .sortBy([
      ['priority',     'asc'],     // berkategori dulu (0 < 1)
      ['capacity_left','desc'],    // sisa paling banyak dulu
      ['current_qty',  'asc'],     // tie-break: lebih kosong dulu
  ])

lokasiData = suitableLokasi.map(row => {
    suggestedQty = min(capacity_left ?? Infinity, remainingQty)
    remainingQty -= suggestedQty;
    return [...metadata, suggested_qty];
})
```

Catatan: kapasitas dicek dengan `hasCapacity(0.001)` (bukan `totalQty`!) saat membentuk daftar; qty di tiap baris lalu auto-distribute via `min(capacityLeft, remainingQty)`.

### 1.3 Algoritma "satu rekomendasi" (ForkliftController::index) — versi sederhana

```
suitableLokasi = allLokasi.filter(
    canAcceptCategory(productCategory) && hasCapacity(totalQty)
).sortBy(current_qty asc).first()  // <-- berbeda: pilih yang paling kosong
```

**PERBEDAAN penting antara dua versi:**
- PoDetail: prioritize lokasi berkategori dulu, lalu sisa kapasitas terbesar, lalu `current_qty` asc.
- Forklift: pakai `current_qty asc` saja (paling kosong).

Untuk fitur ReLokasi di forklift (ganti rekomendasi), harus konsisten dengan versi PoDetail — atau klarifikasi preferensi ke user.

---

## 2. Endpoint terkait

| URL | Method | Controller | Tujuan |
|---|---|---|---|
| `/wms/po-detail/{id}/convert-to-masuk` | GET | `PoDetailController@getConvertToMasuk` | Halaman alokasi qty per lokasi |
| `/wms/po-detail/{id}/convert-to-masuk` | POST | `PoDetailController@postConvertToMasuk` | Submit alokasi |
| `/wms/po-detail/{id}/convert-single` | POST | `PoDetailController@postConvertSingleRow` | Convert 1 baris via JS |
| `/wms/forklift` | GET | `ForkliftController@index` | Daftar pallet siap pindah |
| `/wms/forklift` | POST | `ForkliftController@store` | Submit scan pallet + rack |

## 3. Tabel penting

- `lokasi` — `lokasi_code` (PK string), `lokasi_nama`, `lokasi_code_gudang`, `lokasi_max_qty` (nullable), `lokasi_category` (nullable).
- `stock` — `stock_code_lokasi` FK → `lokasi_code`, `stock_qty` (hanya `> 0` dihitung).
- `masuk_realisasi` — `in_realisasi_code_lokasi` FK → `lokasi_code`.