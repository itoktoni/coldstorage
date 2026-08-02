<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Product;
use App\Models\Stock;
use App\Wms\MasukStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        return $this->model->with(['po', 'po.supplier', 'product'])->filter()->sort();
    }

    public function getConvertToMasuk(Request $request, int $id)
    {
        $poDetail = $this->model->with(['po', 'product'])->findOrFail($id);
        $productCategory = $poDetail->product->product_category;
        $totalQty = (float) $poDetail->po_detail_qty;

        $allLokasi = Lokasi::with('gudang')->get();

        $suitableLokasi = $allLokasi
            ->filter(function ($lokasi) use ($productCategory) {
                return $lokasi->canAcceptCategory($productCategory) && $lokasi->hasCapacity(0.001);
            })
            ->map(function ($lokasi) {
                $currentQty = (float) $lokasi->current_qty;
                $maxQty = $lokasi->lokasi_max_qty;
                $capacityLeft = is_null($maxQty) ? null : max(0, (float) $maxQty - $currentQty);

                return [
                    'model' => $lokasi,
                    'current_qty' => $currentQty,
                    'capacity_left' => $capacityLeft,
                    'priority' => $lokasi->lokasi_category ? 0 : 1,
                ];
            })
            ->sortBy([
                ['priority', 'asc'],
                ['capacity_left', 'desc'],
                ['current_qty', 'asc'],
            ])
            ->values();

        $remainingQty = $totalQty;
        $lokasiData = $suitableLokasi->map(function ($row) use (&$remainingQty) {
            $lokasi = $row['model'];
            $currentQty = $row['current_qty'];
            $capacityLeft = $row['capacity_left'];

            $suggestedQty = 0;
            if ($remainingQty > 0) {
                if (is_null($capacityLeft)) {
                    $suggestedQty = $remainingQty;
                } else {
                    $suggestedQty = min($capacityLeft, $remainingQty);
                }
                $remainingQty -= $suggestedQty;
            }

            return [
                'lokasi_code' => $lokasi->lokasi_code,
                'lokasi_nama' => $lokasi->lokasi_nama,
                'gudang_nama' => $lokasi->gudang?->gudang_nama,
                'lokasi_category' => $lokasi->lokasi_category,
                'current_qty' => $currentQty,
                'max_qty' => $lokasi->lokasi_max_qty,
                'capacity_left' => $capacityLeft,
                'suggested_qty' => $suggestedQty,
            ];
        });

        return view('pages.podetail.convert', [
            'poDetail' => $poDetail,
            'product' => $poDetail->product,
            'totalQty' => $totalQty,
            'productCategory' => $productCategory,
            'lokasiData' => $lokasiData,
            'suitableCount' => $suitableLokasi->count(),
            'remainingQty' => $remainingQty,
        ]);
    }

    public function postConvertToMasuk(Request $request, int $id)
    {
        $poDetail = $this->model->with(['po', 'product'])->findOrFail($id);

        $raw = $request->input('lokasi_allocations', []);
        $allocations = collect($raw)
            ->filter(fn ($row) => isset($row['lokasi_code']) && (float) ($row['qty'] ?? 0) > 0)
            ->values()
            ->all();

        if (empty($allocations)) {
            return redirect()->back()->withErrors(['error' => 'Minimal satu alokasi lokasi harus diisi']);
        }

        $totalAllocated = array_sum(array_map(fn ($row) => (float) $row['qty'], $allocations));

        if (abs($totalAllocated - (float) $poDetail->po_detail_qty) > 0.001) {
            return redirect()->back()->withErrors(['error' => 'Total alokasi qty harus sama dengan qty PO Detail (' . $poDetail->po_detail_qty . ')']);
        }

        $validated = validator(['rows' => $allocations], [
            'rows.*.lokasi_code' => ['required', 'string', 'exists:lokasi,lokasi_code'],
            'rows.*.qty' => ['required', 'numeric', 'min:0.001'],
        ])->validate();

        try {
            DB::transaction(function () use ($poDetail, $allocations) {
                $masukDetail = MasukDetail::create([
                    'in_detail_code'       => MasukDetail::generateCode(),
                    'in_detail_reff'       => $poDetail->po_detail_code,
                    'in_detail_tanggal'    => now()->toDateString(),
                    'in_detail_status'     => MasukStatusEnum::PENDING,
                    'in_detail_id_product' => $poDetail->po_detail_id_product,
                    'in_detail_qty'        => $poDetail->po_detail_qty,
                    'in_detail_catatan'    => 'Dikonversi dari PO ' . $poDetail->po->po_code,
                ]);

                foreach ($allocations as $allocation) {
                    MasukRealisasi::create([
                        'in_realisasi_masuk_code' => $masukDetail->in_detail_code,
                        'in_realisasi_id_product' => $poDetail->po_detail_id_product,
                        'in_realisasi_qty'        => $allocation['qty'],
                        'in_realisasi_code_lokasi' => $allocation['lokasi_code'],
                        'in_realisasi_barcode'    => $this->generateBarcodeContent($poDetail, $allocation['qty']),
                    ]);
                }
            });

            flash()->success('Berhasil dikonversi ke Masuk Detail dengan alokasi lokasi!');

            return redirect()->route('wms-masuk-detail.getTable');
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function postConvertSingleRow(Request $request, int $id)
    {
        $poDetail = $this->model->with(['po', 'product'])->findOrFail($id);

        $request->validate([
            'lokasi_code' => ['required', 'string', 'exists:lokasi,lokasi_code'],
            'qty'        => ['required', 'numeric', 'min:0.001'],
        ]);

        $lokasi = Lokasi::findOrFail($request->lokasi_code);
        $qty = (float) $request->qty;

        if ($request->expectsJson()) {
            try {
                $validator = validator($request->all(), [
                    'lokasi_code' => ['required', 'string', 'exists:lokasi,lokasi_code'],
                    'qty'         => ['required', 'numeric', 'min:0.001'],
                ]);
                if ($validator->fails()) {
                    return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
                }

                if (!is_null($lokasi->lokasi_max_qty) && $qty > (float) $lokasi->lokasi_max_qty) {
                    return response()->json(['ok' => false, 'message' => 'Qty melebihi max capacity lokasi (' . $lokasi->lokasi_max_qty . ')'], 422);
                }

                $alreadyConverted = (float) MasukRealisasi::whereHas('masukDetail', function ($q) use ($poDetail) {
                        $q->where('in_detail_reff', $poDetail->po_detail_code);
                    })
                    ->sum('in_realisasi_qty');

                if (($alreadyConverted + $qty) > ((float) $poDetail->po_detail_qty + 0.001)) {
                    $sisa = max(0, (float) $poDetail->po_detail_qty - $alreadyConverted);
                    return response()->json(['ok' => false, 'message' => "Qty melebihi sisa PO. Sisa yang bisa dikonversi: {$sisa}"], 422);
                }

                $masukDetail = DB::transaction(function () use ($poDetail, $lokasi, $qty) {
                    $detail = MasukDetail::create([
                        'in_detail_code'       => MasukDetail::generateCode(),
                        'in_detail_reff'       => $poDetail->po_detail_code,
                        'in_detail_tanggal'    => now()->toDateString(),
                        'in_detail_status'     => MasukStatusEnum::PENDING,
                        'in_detail_id_product' => $poDetail->po_detail_id_product,
                        'in_detail_qty'        => $qty,
                        'in_detail_catatan'    => 'Dikonversi dari PO ' . $poDetail->po->po_code,
                    ]);

                    MasukRealisasi::create([
                        'in_realisasi_masuk_code' => $detail->in_detail_code,
                        'in_realisasi_id_product' => $poDetail->po_detail_id_product,
                        'in_realisasi_qty'        => $qty,
                        'in_realisasi_code_lokasi' => $lokasi->lokasi_code,
                        'in_realisasi_barcode'    => $this->generateBarcodeContent($poDetail, $qty),
                    ]);

                    return $detail;
                });

                return response()->json([
                    'ok' => true,
                    'message' => "Berhasil konversi {$qty} ke {$lokasi->lokasi_nama}",
                    'masuk_detail_code' => $masukDetail->in_detail_code,
                ]);
            } catch (\Throwable $th) {
                return response()->json(['ok' => false, 'message' => $th->getMessage()], 500);
            }
        }

        if (!is_null($lokasi->lokasi_max_qty) && $qty > (float) $lokasi->lokasi_max_qty) {
            return redirect()->back()->withErrors(['error' => 'Qty melebihi max capacity lokasi (' . $lokasi->lokasi_max_qty . ')']);
        }

        $alreadyConverted = (float) MasukRealisasi::whereHas('masukDetail', function ($q) use ($poDetail) {
                $q->where('in_detail_reff', $poDetail->po_detail_code);
            })
            ->sum('in_realisasi_qty');

        if (($alreadyConverted + $qty) > ((float) $poDetail->po_detail_qty + 0.001)) {
            $sisa = max(0, (float) $poDetail->po_detail_qty - $alreadyConverted);
            return redirect()->back()->withErrors(['error' => "Qty melebihi sisa PO. Sisa yang bisa dikonversi: {$sisa}"]);
        }

        try {
            DB::transaction(function () use ($poDetail, $lokasi, $qty) {
                $masukDetail = MasukDetail::create([
                    'in_detail_code'       => MasukDetail::generateCode(),
                    'in_detail_reff'       => $poDetail->po_detail_code,
                    'in_detail_tanggal'    => now()->toDateString(),
                    'in_detail_status'     => MasukStatusEnum::PENDING,
                    'in_detail_id_product' => $poDetail->po_detail_id_product,
                    'in_detail_qty'        => $qty,
                    'in_detail_catatan'    => 'Dikonversi dari PO ' . $poDetail->po->po_code,
                ]);

                MasukRealisasi::create([
                    'in_realisasi_masuk_code' => $masukDetail->in_detail_code,
                    'in_realisasi_id_product' => $poDetail->po_detail_id_product,
                    'in_realisasi_qty'        => $qty,
                    'in_realisasi_code_lokasi' => $lokasi->lokasi_code,
                    'in_realisasi_barcode'    => $this->generateBarcodeContent($poDetail, $qty),
                ]);
            });

            flash()->success("Berhasil konversi {$qty} ke {$lokasi->lokasi_nama}!");

            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    protected function generateBarcodeContent(PoDetail $poDetail, float $qty): string
    {
        $productCode = $poDetail->product->product_code ?? 'PROD';
        $timestamp   = now()->format('YmdHis');

        return implode('#', [
            $productCode,
            $timestamp,
            (string) $qty,
            '-',
        ]);
    }
}
