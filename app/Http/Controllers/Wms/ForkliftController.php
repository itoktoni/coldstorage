<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Stock;
use App\Models\StockAssignment;
use App\Wms\MasukStatusEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Milon\Barcode\Facades\DNS2DFacade;

class ForkliftController extends Controller
{
    public function index()
    {
        $groups = MasukRealisasi::query()
            ->join('masuk_detail', 'masuk_realisasi.in_realisasi_masuk_code', '=', 'masuk_detail.in_detail_code')
            ->where('masuk_detail.in_detail_status', '!=', MasukStatusEnum::COMPLETE)
            ->whereNotNull('in_realisasi_group')
            ->with(['product', 'lokasi', 'masukDetail'])
            ->get()
            ->groupBy('in_realisasi_group')
            ->map(function ($rows) {
                $first = $rows->first();
                $product = $first->product;
                $productCategory = $product?->product_category;
                $totalQty = (float) $rows->sum('in_realisasi_qty');
                $detailCode = $first->in_realisasi_masuk_code;

                $allLokasi = Lokasi::with('gudang')->get();
                $suitableLokasi = $allLokasi->filter(function ($lokasi) use ($productCategory, $totalQty) {
                    if (! $lokasi->canAcceptCategory($productCategory)) {
                        return false;
                    }
                    if (! $lokasi->hasCapacity($totalQty)) {
                        return false;
                    }

                    return true;
                })->values();

                // Cek apakah pallet sudah pernah di-relokasi (in_realisasi_code_lokasi bukan null)
                $existingLokasiCode = $rows->pluck('in_realisasi_code_lokasi')->filter()->unique()->first();

                $suggestedLokasi = $existingLokasiCode
                    ? Lokasi::find($existingLokasiCode)
                    : $suitableLokasi->sortBy(fn ($lokasi) => $lokasi->current_qty)->first();

                $moved = Stock::where('stock_reff', $detailCode)->exists();

                return [
                    'group_code' => $first->in_realisasi_group,
                    'detail' => $first->masukDetail,
                    'product' => $product,
                    'product_category' => $productCategory,
                    'total_qty' => $totalQty,
                    'rows' => $rows,
                    'suitable_lokasi' => $suitableLokasi,
                    'suggested_lokasi' => $suggestedLokasi,
                    'completed' => $moved,
                ];
            })
            ->sortBy(fn ($g) => $g['completed'] ? 1 : 0)
            ->values();

        $pickLists = $this->buildPickLists();

        $tasks = collect();
        $putawayCount = 0;
        foreach ($groups as $group) {
            $tasks->push([
                'type' => 'putaway',
                'group' => $group,
                'group_index' => $putawayCount,
            ]);
            $putawayCount++;
        }
        foreach ($pickLists as $pickRow) {
            $tasks->push([
                'type' => 'pick',
                'pick' => $pickRow,
            ]);
        }

        return view('pages.forklift.index', [
            'tasks' => $tasks,
            'details' => $groups->map(function ($g) {
                $suggested = $g['suggested_lokasi'];

                return [
                    'group_code' => $g['group_code'],
                    'product' => $g['product']->product_nama ?? '-',
                    'qty' => number_format($g['total_qty'], 3),
                    'lokasi' => $suggested ? ($suggested->lokasi_nama.($suggested->gudang ? ' ('.$suggested->gudang->gudang_nama.')' : '')) : '-',
                    'suggested' => $suggested?->lokasi_code ?? '',
                    'suitable_lokasi' => $g['suitable_lokasi']->map(function ($lokasi) {
                        $current = (float) $lokasi->current_qty;
                        $max = $lokasi->lokasi_max_qty;
                        $left = is_null($max) ? null : max(0, (float) $max - $current);

                        return [
                            'lokasi_code' => $lokasi->lokasi_code,
                            'lokasi_nama' => $lokasi->lokasi_nama,
                            'gudang_nama' => $lokasi->gudang?->gudang_nama,
                            'lokasi_category' => $lokasi->lokasi_category,
                            'current_qty' => $current,
                            'max_qty' => is_null($max) ? null : (float) $max,
                            'capacity_left' => $left,
                            'label' => $lokasi->lokasi_nama.($lokasi->gudang ? ' ('.$lokasi->gudang->gudang_nama.')' : ''),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            'pickLists' => $pickLists,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_code' => ['required', 'string'],
            'pallet_scan' => ['required', 'string', 'same:group_code'],
            'lokasi_code' => ['required', 'string', 'exists:lokasi,lokasi_code'],
            'override' => ['nullable', 'boolean'],
        ]);

        $isOverride = (bool) ($data['override'] ?? false);

        $rows = MasukRealisasi::with('product')
            ->where('in_realisasi_group', $data['group_code'])
            ->get();

        if ($rows->isEmpty()) {
            return $this->storeError($request, 'Kode pallet tidak ditemukan');
        }

        $first = $rows->first();
        $detailCode = $first->in_realisasi_masuk_code;
        $product = $first->product;
        $totalQty = (float) $rows->sum('in_realisasi_qty');

        if (! $product) {
            return $this->storeError($request, 'Product pada pallet tidak valid');
        }

        $masukDetail = MasukDetail::findOrFail($detailCode);

        if ($masukDetail->in_detail_status !== MasukStatusEnum::READY) {
            return $this->storeError($request, 'Status belum READY');
        }

        $alreadyMoved = Stock::where('stock_reff', $data['group_code'])
            ->where('stock_type', Stock::TYPE_IN)
            ->exists();
        if ($alreadyMoved) {
            return $this->storeError($request, 'Pallet ini sudah selesai dipindahkan');
        }

        $lokasi = Lokasi::findOrFail($data['lokasi_code']);

        if (! $lokasi->canAcceptCategory($product->product_category)) {
            return $this->storeError($request, 'Lokasi ini tidak menerima kategori produk "'.$product->product_category.'"');
        }

        if (! $lokasi->hasCapacity($totalQty)) {
            return $this->storeError($request, 'Lokasi ini tidak memiliki kapasitas cukup. Sisa: '.($lokasi->lokasi_max_qty - $lokasi->current_qty).', dibutuhkan: '.$totalQty);
        }

        // Validate lokasi is the suggested one for this pallet
        $allLokasi = Lokasi::with('gudang')->get();
        $suitableLokasi = $allLokasi->filter(function ($l) use ($product, $totalQty) {
            return $l->canAcceptCategory($product->product_category) && $l->hasCapacity($totalQty);
        });

        if (! $isOverride) {
            $existingLokasiCode = $rows->pluck('in_realisasi_code_lokasi')->filter()->unique()->first();
            $suggestedLokasi = $existingLokasiCode
                ? Lokasi::find($existingLokasiCode)
                : $suitableLokasi->sortBy(fn ($l) => $l->current_qty)->first();

            if (! $suggestedLokasi || $data['lokasi_code'] !== $suggestedLokasi->lokasi_code) {
                $expected = $suggestedLokasi ? $suggestedLokasi->lokasi_nama : '-';

                return $this->storeError($request, 'Lokasi tidak sesuai. Scan harus ke "'.$expected.'"');
            }
        }

        try {
            DB::transaction(function () use ($masukDetail, $rows, $data, $totalQty) {
                // Stock sudah dibuat saat detail berstatus READY (reff = group code),
                // tinggal pindahkan lokasi. Fallback create untuk data legacy.
                $stockRows = Stock::where('stock_reff', $data['group_code'])->get();

                if ($stockRows->isNotEmpty()) {
                    Stock::where('stock_reff', $data['group_code'])
                        ->update([
                            'stock_type' => Stock::TYPE_IN,
                            'stock_code_lokasi' => $data['lokasi_code'],
                            'stock_pallet_code' => $data['group_code'],
                        ]);
                } else {
                    Stock::create([
                        'stock_id_product' => $rows->first()->in_realisasi_id_product,
                        'stock_code_lokasi' => $data['lokasi_code'],
                        'stock_qty' => $totalQty,
                        'stock_type' => 'IN',
                        'stock_expired_date' => now()->addDays(30),
                        'stock_reff' => $data['group_code'],
                        'stock_pallet_code' => $data['group_code'],
                    ]);
                }

                MasukRealisasi::whereIn('in_realisasi_id', $rows->pluck('in_realisasi_id'))
                    ->update(['in_realisasi_code_lokasi' => $data['lokasi_code']]);

                $masukDetail->update(['in_detail_status' => MasukStatusEnum::COMPLETE]);
            });

            $message = "Pallet {$data['group_code']} berhasil disimpan di {$lokasi->lokasi_nama}";

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'message' => $message]);
            }

            flash()->success($message);

            return redirect()->route('wms-forklift.index');
        } catch (\Throwable $th) {
            return $this->storeError($request, $th->getMessage());
        }
    }

    /**
     * Return a JSON error (AJAX) or a redirect-with-errors (regular form post),
     * so the scan modal can show inline feedback while normal posts keep
     * the original redirect + session-error behaviour.
     */
    protected function storeError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return redirect()->back()->withErrors(['error' => $message]);
    }

    public function relokasi(Request $request)
    {
        $request->validate([
            'group_code' => ['required', 'string'],
            'lokasi_code' => ['required', 'string', 'exists:lokasi,lokasi_code'],
        ]);

        $groupCode = $request->input('group_code');
        $lokasiCode = $request->input('lokasi_code');

        $rows = MasukRealisasi::where('in_realisasi_group', $groupCode)->get();
        if ($rows->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Pallet tidak ditemukan'], 404);
        }

        $product = $rows->first()->product;
        if (! $product) {
            return response()->json(['ok' => false, 'message' => 'Product pada pallet tidak valid'], 422);
        }

        $totalQty = (float) $rows->sum('in_realisasi_qty');
        $lokasi = Lokasi::findOrFail($lokasiCode);

        if (! $lokasi->canAcceptCategory($product->product_category)) {
            return response()->json(['ok' => false, 'message' => 'Lokasi ini tidak menerima kategori produk "'.$product->product_category.'"'], 422);
        }

        if (! $lokasi->hasCapacity($totalQty)) {
            return response()->json(['ok' => false, 'message' => 'Lokasi ini tidak memiliki kapasitas cukup. Sisa: '.($lokasi->lokasi_max_qty - $lokasi->current_qty).', dibutuhkan: '.$totalQty], 422);
        }

        $updated = MasukRealisasi::where('in_realisasi_group', $groupCode)
            ->update(['in_realisasi_code_lokasi' => $lokasiCode]);

        $lokasi = Lokasi::with('gudang')->find($lokasiCode);

        return response()->json([
            'ok' => true,
            'message' => "Lokasi pallet {$groupCode} berhasil diupdate ke {$lokasiCode}",
            'label' => $lokasi ? ($lokasi->lokasi_nama.($lokasi->gudang ? ' ('.$lokasi->gudang->gudang_nama.')' : '')) : $lokasiCode,
        ]);
    }

    public function printGroupQr(string $groupCode)
    {
        $rows = MasukRealisasi::with(['product', 'masukDetail'])
            ->where('in_realisasi_group', $groupCode)
            ->get();

        if ($rows->isEmpty()) {
            abort(404);
        }

        $first = $rows->first();
        $totalQty = (float) $rows->sum('in_realisasi_qty');

        // QR encodes scan prefix + group code so scanner strips prefix back to group code
        $scanValue = config('scan.prefix.pallet', 'P').$groupCode;
        $qrPng = DNS2DFacade::getBarcodePNG($scanValue, 'QRCODE', 8, 8);

        $pdf = Pdf::loadView('pdf.pallet-qr', [
            'groupCode' => $groupCode,
            'qrPng' => $qrPng,
            'product' => $first->product,
            'detail' => $first->masukDetail,
            'totalQty' => $totalQty,
            'rows' => $rows,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('pallet-'.$groupCode.'.pdf');
    }

    /**
     * Build the list of Keluar (outbound pick lists) ready to be picked by forklift.
     * Status: Pending / In Progress (the ones generated from SO prepare).
     */
    private function buildPickLists(): Collection
    {
        return Keluar::with(['details.product'])
            ->whereIn('out_status', ['Pending', 'In Progress'])
            ->orderBy('out_tanggal')
            ->get()
            ->map(function (Keluar $keluar) {
                $details = $keluar->details;

                $pickedQty = (float) KeluarRealisasi::whereIn('out_realisasi_id_detail', $details->pluck('out_detail_id'))
                    ->sum('out_realisasi_qty');

                $totalQty = (int) $details->sum('out_detail_qty');
                $progress = $totalQty > 0 ? min(100, (int) round($pickedQty / $totalQty * 100)) : 0;

                // Build stock source guide per detail
                $stockSources = $details->map(function (KeluarDetail $detail) {
                    $alreadyPicked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $detail->out_detail_id)
                        ->sum('out_realisasi_qty');
                    $remaining = max(0, (float) $detail->out_detail_qty - $alreadyPicked);

                    if ($remaining <= 0) {
                        return null;
                    }

                    $stocks = Stock::query()
                        ->where('stock_type', 'IN')
                        ->where('stock_id_product', $detail->out_detail_id_product)
                        ->where('stock_qty', '>', 0)
                        ->orderBy('stock_expired_date')
                        ->orderBy('stock_id')
                        ->with('lokasi.gudang')
                        ->get()
                        ->map(function (Stock $s) {
                            return [
                                'lokasi_nama' => $s->lokasi?->lokasi_nama ?? '-',
                                'gudang_nama' => $s->lokasi?->gudang?->gudang_nama ?? '-',
                                'stock_qty' => (float) $s->stock_qty,
                                'expired' => optional($s->stock_expired_date)->format('Y-m-d'),
                            ];
                        });

                    return [
                        'product_nama' => $detail->product->product_nama ?? '-',
                        'qty_remaining' => $remaining,
                        'stocks' => $stocks,
                    ];
                })->filter()->values();

                return [
                    'keluar' => $keluar,
                    'total_qty' => $totalQty,
                    'picked_rows' => $pickedQty,
                    'progress' => $progress,
                    'item_count' => $details->count(),
                    'stock_sources' => $stockSources,
                ];
            });
    }

    /**
     * Pick detail page: shows required items + guide (rak tujuan scan + staging area).
     * Forklift operator scans lokasi rack (sumber) lalu scan rak staging (A/B/C/D).
     * Saat staging discan sesuai panduan, stock berpindah rack -> staging (type STAGING).
     */
    public function pick(string $outCode)
    {
        $keluar = Keluar::with(['details.product', 'assignments.stock.lokasi.gudang'])->findOrFail($outCode);

        $rows = $keluar->details->map(function (KeluarDetail $detail) use ($keluar) {
            $alreadyPicked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $detail->out_detail_id)
                ->sum('out_realisasi_qty');

            $remaining = max(0, (float) $detail->out_detail_qty - $alreadyPicked);

            $assignments = $keluar->assignments
                ->where('stock_assignment_id_keluar_detail', $detail->out_detail_id)
                ->where('stock_assignment_status', '!=', 'Override');

            if ($assignments->isNotEmpty()) {
                $suggestedStocks = $assignments->map(function (StockAssignment $a) {
                    $stock = $a->stock;
                    $alreadyPicked = KeluarRealisasi::where('out_realisasi_id_stock', $a->stock_assignment_id_stock)
                        ->where('out_realisasi_id_detail', $a->stock_assignment_id_keluar_detail)
                        ->sum('out_realisasi_qty');
                    $pickRemaining = max(0, (float) $a->stock_assignment_qty - $alreadyPicked);

                    return [
                        'stock_id' => $stock->stock_id,
                        'lokasi_code' => $stock->stock_code_lokasi,
                        'lokasi_nama' => $stock->lokasi?->lokasi_nama ?? '-',
                        'gudang_nama' => $stock->lokasi?->gudang?->gudang_nama ?? '-',
                        'stock_code' => $stock->stock_code,
                        'stock_qty' => (float) $stock->stock_qty,
                        'expired' => optional($stock->stock_expired_date)->format('Y-m-d'),
                        'take_max' => $pickRemaining,
                        'is_assigned' => true,
                        'assignment_id' => $a->stock_assignment_id,
                    ];
                })->values();
            } else {
                $suggestedStocks = Stock::query()
                    ->where('stock_type', 'IN')
                    ->where('stock_id_product', $detail->out_detail_id_product)
                    ->where('stock_qty', '>', 0)
                    ->orderBy('stock_expired_date')
                    ->orderBy('stock_id')
                    ->with('lokasi.gudang')
                    ->get()
                    ->map(function (Stock $s) use ($remaining) {
                        return [
                            'stock_id' => $s->stock_id,
                            'lokasi_code' => $s->stock_code_lokasi,
                            'lokasi_nama' => $s->lokasi?->lokasi_nama ?? '-',
                            'gudang_nama' => $s->lokasi?->gudang?->gudang_nama ?? '-',
                            'stock_code' => $s->stock_code,
                            'stock_qty' => (float) $s->stock_qty,
                            'expired' => optional($s->stock_expired_date)->format('Y-m-d'),
                            'take_max' => min((float) $s->stock_qty, $remaining),
                            'is_assigned' => false,
                            'assignment_id' => null,
                        ];
                    })
                    ->values();
            }

            return [
                'detail' => $detail,
                'qty_requested' => (int) $detail->out_detail_qty,
                'qty_picked' => (float) $alreadyPicked,
                'qty_remaining' => $remaining,
                'suggested' => $suggestedStocks,
            ];
        });

        $totalQty = (float) $rows->sum('qty_requested');
        $totalPicked = (float) $rows->sum('qty_picked');

        return view('pages.forklift.pick', [
            'keluar' => $keluar,
            'rows' => $rows->filter(fn ($r) => $r['qty_remaining'] > 0)->values(),
            'summary' => [
                'total_qty' => $totalQty,
                'total_picked' => $totalPicked,
                'progress' => $totalQty > 0 ? min(100, (int) round($totalPicked / $totalQty * 100)) : 0,
                'done_count' => $rows->filter(fn ($r) => $r['qty_remaining'] <= 0)->count(),
            ],
        ]);
    }

    /**
     * Process scan-based pick: operator scan lokasi rack (sumber) + scan rak staging.
     * Stock IN di rack berkurang, row STAGING dibuat di lokasi staging (type STAGING).
     */
    public function pickStore(Request $request, string $outCode)
    {
        $data = $request->validate([
            'detail_id' => ['required', 'integer', 'exists:keluar_detail,out_detail_id'],
            'rack_scan' => ['required', 'string', 'exists:lokasi,lokasi_code'],
            'staging_scan' => ['required', 'string', 'exists:lokasi,lokasi_code'],
        ]);

        $keluar = Keluar::findOrFail($outCode);

        try {
            $result = DB::transaction(function () use ($keluar, $data) {
                $detail = KeluarDetail::where('out_detail_id', $data['detail_id'])
                    ->where('out_detail_code_keluar', $keluar->out_code)
                    ->lockForUpdate()
                    ->firstOrFail();

                $rackLokasi = Lokasi::findOrFail($data['rack_scan']);
                $stagingLokasi = Lokasi::findOrFail($data['staging_scan']);

                if (strtolower($stagingLokasi->lokasi_category ?? '') !== 'staging') {
                    throw new \RuntimeException('Rak staging tidak valid. Scan staging area A/B/C/D.');
                }
                if ($stagingLokasi->lokasi_code === $rackLokasi->lokasi_code) {
                    throw new \RuntimeException('Rak staging tidak boleh sama dengan lokasi rack sumber.');
                }

                $alreadyPicked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $detail->out_detail_id)
                    ->sum('out_realisasi_qty');

                $remaining = (float) $detail->out_detail_qty - $alreadyPicked;
                if ($remaining <= 0) {
                    throw new \RuntimeException('Item ini sudah terpenuhi.');
                }

                // Guide rack: FIFO stock IN untuk produk ini (oldest expired first)
                $guideLokasi = Stock::query()
                    ->where('stock_type', 'IN')
                    ->where('stock_id_product', $detail->out_detail_id_product)
                    ->where('stock_qty', '>', 0)
                    ->orderBy('stock_expired_date')
                    ->orderBy('stock_id')
                    ->with('lokasi')
                    ->get()
                    ->first();

                if (! $guideLokasi) {
                    throw new \RuntimeException('Tidak ada stok tersedia di rak untuk product ini.');
                }

                if ($data['rack_scan'] !== $guideLokasi->stock_code_lokasi) {
                    $guideNama = $guideLokasi->lokasi?->lokasi_nama ?? $guideLokasi->stock_code_lokasi;
                    throw new \RuntimeException('Rak yang discan tidak sesuai. Pick harus dari rack '.$guideNama.'.');
                }

                // Stock IN di rack sumber untuk produk ini (FIFO)
                $stocks = Stock::query()
                    ->where('stock_type', 'IN')
                    ->where('stock_id_product', $detail->out_detail_id_product)
                    ->where('stock_code_lokasi', $rackLokasi->lokasi_code)
                    ->where('stock_qty', '>', 0)
                    ->orderBy('stock_expired_date')
                    ->orderBy('stock_id')
                    ->lockForUpdate()
                    ->get();

                $available = (float) $stocks->sum('stock_qty');
                if ($available <= 0) {
                    throw new \RuntimeException('Tidak ada stok produk ini di rak '.$rackLokasi->lokasi_nama.'.');
                }

                // Forklift memindahkan SELURUH pallet di rack → rack jadi 0.
                $palletQty = $available;
                $fulfilled = min($palletQty, $remaining);
                $expiredDates = [];

                foreach ($stocks as $stock) {
                    $rowQty = (float) $stock->stock_qty;
                    $stock->decrement('stock_qty', $rowQty);

                    if ($stock->stock_expired_date) {
                        $expiredDates[] = $stock->stock_expired_date;
                    }
                }

                // Catat stock berpindah ke staging (type STAGING) — qty penuh pallet
                Stock::create([
                    'stock_id_product' => $detail->out_detail_id_product,
                    'stock_code_lokasi' => $stagingLokasi->lokasi_code,
                    'stock_qty' => $palletQty,
                    'stock_type' => Stock::TYPE_STAGING,
                    'stock_expired_date' => $expiredDates ? min($expiredDates) : null,
                    'stock_reff' => $keluar->out_code,
                ]);

                // Realisasi = qty SO yang terpenuhi dari pallet ini (bukan seluruh pallet)
                KeluarRealisasi::create([
                    'out_realisasi_id_detail' => $detail->out_detail_id,
                    'out_realisasi_id_stock' => $stocks->first()->stock_id,
                    'out_realisasi_code' => KeluarRealisasi::generateCode(),
                    'out_realisasi_qty' => $fulfilled,
                ]);

                // Fulfil SO reservation tied to this keluar detail
                $soCode = $detail->soDetail?->so?->so_code ?? '';
                Stock::consumeReserve($soCode, $detail->out_detail_id_product, $fulfilled);

                // Recompute keluar status
                $allDetails = KeluarDetail::where('out_detail_code_keluar', $keluar->out_code)->get();
                $totalPicked = 0;
                foreach ($allDetails as $d) {
                    $totalPicked += (float) KeluarRealisasi::where('out_realisasi_id_detail', $d->out_detail_id)
                        ->sum('out_realisasi_qty');
                }
                $totalQty = (float) $allDetails->sum('out_detail_qty');

                if ($totalPicked + 1e-9 >= $totalQty) {
                    $keluar->update(['out_status' => Keluar::STATUS_DONE]);
                    self::cleanupZeroStock($keluar);
                } elseif ($totalPicked > 0) {
                    $keluar->update(['out_status' => Keluar::STATUS_IN_PROGRESS]);
                }

                return [
                    'pallet_moved' => $palletQty,
                    'fulfilled' => $fulfilled,
                    'picked_total' => $totalPicked,
                    'remaining_total' => max(0, $totalQty - $totalPicked),
                    'item_remaining' => max(0, $remaining - $fulfilled),
                    'status' => $keluar->out_status,
                ];
            });

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'message' => 'Pallet dipindah ke staging ('.$result['pallet_moved'].' unit).', 'data' => $result]);
            }

            flash()->success('Pallet dipindah ke staging ('.$result['pallet_moved'].' unit). Sisa SO: '.$result['remaining_total']);

            return redirect()->route('wms-forklift-pick.show', ['outCode' => $keluar->out_code]);
        } catch (\Throwable $th) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $th->getMessage()], 422);
            }
            flash()->error($th->getMessage());

            return back();
        }
    }

    /**
     * Show scan-only pick UI for forklift operator.
     */
    public function pickScan(string $outCode)
    {
        $keluar = Keluar::with(['details.product', 'assignments.stock.lokasi.gudang'])->findOrFail($outCode);

        $rows = $keluar->details->map(function (KeluarDetail $detail) use ($keluar) {
            $alreadyPicked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $detail->out_detail_id)
                ->sum('out_realisasi_qty');

            $remaining = max(0, (float) $detail->out_detail_qty - $alreadyPicked);

            $assignments = $keluar->assignments
                ->where('stock_assignment_id_keluar_detail', $detail->out_detail_id)
                ->where('stock_assignment_status', '!=', 'Override');

            if ($assignments->isNotEmpty()) {
                $suggestedStocks = $assignments->map(function (StockAssignment $a) {
                    $stock = $a->stock;
                    $alreadyPicked = KeluarRealisasi::where('out_realisasi_id_stock', $a->stock_assignment_id_stock)
                        ->where('out_realisasi_id_detail', $a->stock_assignment_id_keluar_detail)
                        ->sum('out_realisasi_qty');
                    $pickRemaining = max(0, (float) $a->stock_assignment_qty - $alreadyPicked);

                    return [
                        'stock_id' => $stock->stock_id,
                        'lokasi_code' => $stock->stock_code_lokasi,
                        'lokasi_nama' => $stock->lokasi?->lokasi_nama ?? '-',
                        'gudang_nama' => $stock->lokasi?->gudang?->gudang_nama ?? '-',
                        'stock_code' => $stock->stock_code,
                        'stock_qty' => (float) $stock->stock_qty,
                        'expired' => optional($stock->stock_expired_date)->format('Y-m-d'),
                        'take_max' => $pickRemaining,
                        'is_assigned' => true,
                    ];
                })->values();
            } else {
                $suggestedStocks = Stock::query()
                    ->where('stock_type', 'IN')
                    ->where('stock_id_product', $detail->out_detail_id_product)
                    ->where('stock_qty', '>', 0)
                    ->orderBy('stock_expired_date')
                    ->orderBy('stock_id')
                    ->with('lokasi.gudang')
                    ->get()
                    ->map(function (Stock $s) use ($remaining) {
                        return [
                            'stock_id' => $s->stock_id,
                            'lokasi_code' => $s->stock_code_lokasi,
                            'lokasi_nama' => $s->lokasi?->lokasi_nama ?? '-',
                            'gudang_nama' => $s->lokasi?->gudang?->gudang_nama ?? '-',
                            'stock_code' => $s->stock_code,
                            'stock_qty' => (float) $s->stock_qty,
                            'expired' => optional($s->stock_expired_date)->format('Y-m-d'),
                            'take_max' => min((float) $s->stock_qty, $remaining),
                            'is_assigned' => false,
                        ];
                    })
                    ->values();
            }

            return [
                'detail' => $detail,
                'product_nama' => $detail->product->product_nama ?? '-',
                'qty_requested' => (float) $detail->out_detail_qty,
                'qty_picked' => (float) $alreadyPicked,
                'qty_remaining' => $remaining,
                'suggested' => $suggestedStocks,
            ];
        });

        $totalQty = (float) $rows->sum('qty_requested');
        $totalPicked = (float) $rows->sum('qty_picked');
        $currentPick = $rows->first(fn ($r) => $r['qty_remaining'] > 0);

        return view('pages.forklift.pick-scan', [
            'keluar' => $keluar,
            'rows' => $rows,
            'current' => $currentPick,
            'summary' => [
                'total_qty' => $totalQty,
                'total_picked' => $totalPicked,
                'progress' => $totalQty > 0 ? min(100, (int) round($totalPicked / $totalQty * 100)) : 0,
                'total_items' => $rows->count(),
                'done_items' => $rows->filter(fn ($r) => $r['qty_remaining'] <= 0)->count(),
            ],
        ]);
    }

    /**
     * Process scan input from forklift operator (JSON response).
     */
    public function pickScanProcess(Request $request, string $outCode)
    {
        $data = $request->validate([
            'scan_code' => ['required', 'string', 'max:100'],
            'detail_id' => ['required', 'integer', 'exists:keluar_detail,out_detail_id'],
        ]);

        $keluar = Keluar::findOrFail($outCode);
        $detail = KeluarDetail::where('out_detail_id', $data['detail_id'])
            ->where('out_detail_code_keluar', $keluar->out_code)
            ->firstOrFail();

        try {
            $result = DB::transaction(function () use ($keluar, $detail, $data) {
                $scanCode = $data['scan_code'];
                $prefix = config('scan.prefix');

                // 1. Detect mode and find stocks
                if (str_starts_with($scanCode, $prefix['pallet'])) {
                    $mode = 'pallet';
                    $code = substr($scanCode, strlen($prefix['pallet']));
                    $stocks = Stock::where('stock_pallet_code', $code)
                        ->where('stock_type', Stock::TYPE_IN)
                        ->where('stock_qty', '>', 0)
                        ->orderBy('stock_expired_date')
                        ->lockForUpdate()
                        ->get();
                } elseif (str_starts_with($scanCode, $prefix['location'])) {
                    $mode = 'location';
                    $code = substr($scanCode, strlen($prefix['location']));
                    $stocks = Stock::where('stock_code_lokasi', $code)
                        ->where('stock_id_product', $detail->out_detail_id_product)
                        ->where('stock_type', Stock::TYPE_IN)
                        ->where('stock_qty', '>', 0)
                        ->orderBy('stock_expired_date')
                        ->lockForUpdate()
                        ->get();
                } else {
                    $mode = 'barcode';
                    $code = str_starts_with($scanCode, $prefix['barcode'])
                        ? substr($scanCode, strlen($prefix['barcode']))
                        : $scanCode;
                    $stocks = Stock::where('stock_code', $code)
                        ->where('stock_type', Stock::TYPE_IN)
                        ->where('stock_qty', '>', 0)
                        ->lockForUpdate()
                        ->get();
                }

                // 2. Validate
                if ($stocks->isEmpty()) {
                    throw new \RuntimeException('Barcode tidak dikenali atau stock tidak tersedia.');
                }

                // Check product match (for pallet/location mode)
                if ($mode !== 'barcode') {
                    $wrongProduct = $stocks->first(fn ($s) => $s->stock_id_product != $detail->out_detail_id_product);
                    if ($wrongProduct) {
                        throw new \RuntimeException('Barcode ini untuk produk lain.');
                    }
                }

                $alreadyPicked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $detail->out_detail_id)
                    ->sum('out_realisasi_qty');
                $remaining = (float) $detail->out_detail_qty - $alreadyPicked;

                if ($remaining <= 0) {
                    throw new \RuntimeException('Item ini sudah terpenuhi.');
                }

                // 3. Process pick per barcode
                $pickedItems = [];
                $left = $remaining;
                $expiredDates = [];

                foreach ($stocks as $stock) {
                    if ($left <= 0) {
                        break;
                    }

                    $take = min((float) $stock->stock_qty, $left);
                    $stock->decrement('stock_qty', $take);

                    if ($stock->stock_expired_date) {
                        $expiredDates[] = $stock->stock_expired_date;
                    }

                    // Create KeluarRealisasi per barcode
                    KeluarRealisasi::create([
                        'out_realisasi_id_detail' => $detail->out_detail_id,
                        'out_realisasi_id_stock' => $stock->stock_id,
                        'out_realisasi_code' => KeluarRealisasi::generateCode(),
                        'out_realisasi_qty' => $take,
                    ]);

                    $pickedItems[] = [
                        'stock_code' => $stock->stock_code,
                        'qty' => $take,
                    ];

                    $left -= $take;
                }

                $fulfilled = $remaining - $left;

                // Create STAGING stock
                Stock::create([
                    'stock_id_product' => $detail->out_detail_id_product,
                    'stock_code_lokasi' => 'STAGING',
                    'stock_qty' => $fulfilled,
                    'stock_type' => Stock::TYPE_STAGING,
                    'stock_expired_date' => $expiredDates ? min($expiredDates) : null,
                    'stock_reff' => $keluar->out_code,
                ]);

                // Consume RESERVE
                $soCode = $detail->soDetail?->so?->so_code ?? '';
                Stock::consumeReserve($soCode, $detail->out_detail_id_product, $fulfilled);

                // Recompute keluar status
                $allDetails = KeluarDetail::where('out_detail_code_keluar', $keluar->out_code)->get();
                $totalPicked = 0;
                foreach ($allDetails as $d) {
                    $totalPicked += (float) KeluarRealisasi::where('out_realisasi_id_detail', $d->out_detail_id)
                        ->sum('out_realisasi_qty');
                }
                $totalQty = (float) $allDetails->sum('out_detail_qty');

                if ($totalPicked + 1e-9 >= $totalQty) {
                    $keluar->update(['out_status' => Keluar::STATUS_DONE]);
                    self::cleanupZeroStock($keluar);
                } elseif ($totalPicked > 0) {
                    $keluar->update(['out_status' => 'In Progress']);
                }

                // Get next pick
                $nextPickDetail = $allDetails->first(function ($d) use ($detail) {
                    $picked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $d->out_detail_id)
                        ->sum('out_realisasi_qty');

                    return $d->out_detail_id !== $detail->out_detail_id && $picked < (float) $d->out_detail_qty;
                });

                return [
                    'ok' => true,
                    'picked_items' => $pickedItems,
                    'fulfilled' => $fulfilled,
                    'remaining' => max(0, $left),
                    'done' => max(0, $left) <= 0,
                    'total_picked' => $totalPicked,
                    'total_qty' => $totalQty,
                    'next_detail_id' => $nextPickDetail?->out_detail_id,
                ];
            });

            return response()->json($result);
        } catch (\Throwable $th) {
            return response()->json(['ok' => false, 'message' => $th->getMessage()], 422);
        }
    }

    /**
     * Hapus stock RESERVE & STAGING yang qty-nya sudah 0 setelah keluar Done.
     */
    private static function cleanupZeroStock(Keluar $keluar): void
    {
        // Hapus STAGING stock qty 0 untuk keluar ini
        Stock::where('stock_type', Stock::TYPE_STAGING)
            ->where('stock_reff', $keluar->out_code)
            ->where('stock_qty', '<=', 0)
            ->delete();

        // Hapus RESERVE stock qty 0 untuk SO terkait
        $soCodes = $keluar->details()
            ->with('soDetail.so')
            ->get()
            ->pluck('soDetail.so.so_code')
            ->filter()
            ->values();

        if ($soCodes->isNotEmpty()) {
            Stock::where('stock_type', Stock::TYPE_RESERVE)
                ->whereIn('stock_reff', $soCodes)
                ->where('stock_qty', '<=', 0)
                ->delete();
        }
    }
}
