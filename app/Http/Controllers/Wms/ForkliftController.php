<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Stock;
use App\Wms\MasukStatusEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
                    if (!$lokasi->canAcceptCategory($productCategory)) {
                        return false;
                    }
                    if (!$lokasi->hasCapacity($totalQty)) {
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
                    'detail'     => $first->masukDetail,
                    'product'    => $product,
                    'product_category' => $productCategory,
                    'total_qty'  => $totalQty,
                    'rows'       => $rows,
                    'suitable_lokasi' => $suitableLokasi,
                    'suggested_lokasi_code' => $suggestedLokasi?->lokasi_code,
                    'completed'  => $moved,
                ];
            })
            ->sortBy(fn ($g) => $g['completed'] ? 1 : 0)
            ->values();

        return view('pages.forklift.index', [
            'groups' => $groups,
            'details' => $groups->map(function ($g) {
                $suggested = collect($g['suitable_lokasi'])->firstWhere('lokasi_code', $g['suggested_lokasi_code']);
                return [
                    'group_code' => $g['group_code'],
                    'product'    => $g['product']->product_nama ?? '-',
                    'qty'        => number_format($g['total_qty'], 3),
                    'lokasi'     => $suggested ? ($suggested->lokasi_nama . ($suggested->gudang ? ' ('.$suggested->gudang->gudang_nama.')' : '')) : '-',
                    'suggested'  => $g['suggested_lokasi_code'] ?? '',
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
                            'label' => $lokasi->lokasi_nama . ($lokasi->gudang ? ' ('.$lokasi->gudang->gudang_nama.')' : ''),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_code'   => ['required', 'string'],
            'pallet_scan'  => ['required', 'string', 'same:group_code'],
            'lokasi_code'  => ['required', 'string', 'exists:lokasi,lokasi_code'],
        ]);

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

        if (!$product) {
            return $this->storeError($request, 'Product pada pallet tidak valid');
        }

        $masukDetail = MasukDetail::findOrFail($detailCode);

        if ($masukDetail->in_detail_status !== MasukStatusEnum::READY) {
            return $this->storeError($request, 'Status belum READY');
        }

        $alreadyMoved = Stock::where('stock_reff', $detailCode)->exists();
        if ($alreadyMoved) {
            return $this->storeError($request, 'Pallet ini sudah selesai dipindahkan');
        }

        $lokasi = Lokasi::findOrFail($data['lokasi_code']);

        if (!$lokasi->canAcceptCategory($product->product_category)) {
            return $this->storeError($request, 'Lokasi ini tidak menerima kategori produk "'.$product->product_category.'"');
        }

        if (!$lokasi->hasCapacity($totalQty)) {
            return $this->storeError($request, 'Lokasi ini tidak memiliki kapasitas cukup. Sisa: '.($lokasi->lokasi_max_qty - $lokasi->current_qty).', dibutuhkan: '.$totalQty);
        }

        try {
            DB::transaction(function () use ($masukDetail, $rows, $data, $totalQty, $detailCode) {
                Stock::create([
                    'stock_id_product'   => $rows->first()->in_realisasi_id_product,
                    'stock_code_lokasi'  => $data['lokasi_code'],
                    'stock_qty'          => $totalQty,
                    'stock_type'         => 'IN',
                    'stock_expired_date' => now()->addDays(30),
                    'stock_reff'         => $detailCode,
                ]);

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
            'group_code'  => ['required', 'string'],
            'lokasi_code' => ['required', 'string', 'exists:lokasi,lokasi_code'],
        ]);

        $groupCode = $request->input('group_code');
        $lokasiCode = $request->input('lokasi_code');

        $updated = MasukRealisasi::where('in_realisasi_group', $groupCode)
            ->update(['in_realisasi_code_lokasi' => $lokasiCode]);

        if ($updated === 0) {
            return response()->json(['ok' => false, 'message' => 'Pallet tidak ditemukan'], 404);
        }

        $lokasi = Lokasi::with('gudang')->find($lokasiCode);

        return response()->json([
            'ok'      => true,
            'message' => "Lokasi pallet {$groupCode} berhasil diupdate ke {$lokasiCode}",
            'label'   => $lokasi ? ($lokasi->lokasi_nama . ($lokasi->gudang ? ' ('.$lokasi->gudang->gudang_nama.')' : '')) : $lokasiCode,
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

        $qrPng = DNS2DFacade::getBarcodePNG($groupCode, 'QRCODE', 8, 8);

        $pdf = Pdf::loadView('pdf.pallet-qr', [
            'groupCode' => $groupCode,
            'qrPng'     => $qrPng,
            'product'   => $first->product,
            'detail'    => $first->masukDetail,
            'totalQty'  => $totalQty,
            'rows'      => $rows,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('pallet-' . $groupCode . '.pdf');
    }
}
