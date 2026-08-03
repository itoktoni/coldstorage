<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Product;
use App\Models\Stock;
use App\Wms\MasukStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasukDetailController extends Controller
{
    use ControllerTrait;

    public function __construct(MasukDetail $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model'          => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'lokasiOptions'  => Lokasi::pluck('lokasi_nama', 'lokasi_code'),
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with(['product', 'poDetail.po.supplier'])->filter()->sort();
    }

    public function getDelete(Request $request, string $id)
    {
        $masukDetail = $this->model->findOrFail($id);

        DB::transaction(function () use ($masukDetail) {
            // Cari group code dari masuk_realisasi terkait
            $groupCode = MasukRealisasi::where('in_realisasi_masuk_code', $masukDetail->in_detail_code)
                ->whereNotNull('in_realisasi_group')
                ->value('in_realisasi_group');

            // Hapus stock by stock_reff (in_detail_code atau group_code)
            Stock::where('stock_reff', $masukDetail->in_detail_code)->delete();
            if ($groupCode) {
                Stock::where('stock_reff', $groupCode)->delete();
                Stock::where('stock_pallet_code', $groupCode)->delete();
            }

            // Hapus masuk_detail (cascade hapus masuk_realisasi via FK)
            $masukDetail->delete();
        });

        flash()->success('Masuk detail dan stock terkait berhasil dihapus.');

        return redirect()->route('wms-masuk-detail.getTable');
    }

    public function getRealisasikan(Request $request, string $id)
    {
        return $this->views('pages.masukdetail.realisasikan', [
            'masukDetailId' => $id,
        ]);
    }

    public function postRealisasikan(Request $request, string $id)
    {
        $masukDetail = $this->model->findOrFail($id);

        $data = $request->validate([
            'realisasi_qty'   => ['required', 'numeric', 'min:0.001', 'lte:'.$masukDetail->in_detail_qty],
            'realisasi_lokasi' => ['required', 'string', 'exists:lokasi,lokasi_code'],
        ]);

        try {
            DB::transaction(function () use ($masukDetail, $data) {
                // Generate atau ambil group code (PAL-xxx) untuk pallet
                $existingGroup = MasukRealisasi::where('in_realisasi_masuk_code', $masukDetail->in_detail_code)
                    ->whereNotNull('in_realisasi_group')
                    ->value('in_realisasi_group');
                $groupCode = $existingGroup ?: MasukRealisasi::generateGroupCode();

                MasukRealisasi::create([
                    'in_realisasi_masuk_code' => $masukDetail->in_detail_code,
                    'in_realisasi_id_product' => $masukDetail->in_detail_id_product,
                    'in_realisasi_qty'        => $data['realisasi_qty'],
                    'in_realisasi_code_lokasi' => $data['realisasi_lokasi'],
                    'in_realisasi_group'      => $groupCode,
                ]);

                // Hitung total yang sudah direalisasi
                $totalRealisasi = MasukRealisasi::where('in_realisasi_masuk_code', $masukDetail->in_detail_code)
                    ->sum('in_realisasi_qty');

                if ($totalRealisasi >= $masukDetail->in_detail_qty) {
                    $masukDetail->update(['in_detail_status' => MasukStatusEnum::READY]);
                } else {
                    $masukDetail->update(['in_detail_status' => MasukStatusEnum::PROCESS]);
                }

                // Tambah stok dengan pallet_code = group code (PAL-xxx)
                Stock::create([
                    'stock_id_product'   => $masukDetail->in_detail_id_product,
                    'stock_code_lokasi'  => $data['realisasi_lokasi'],
                    'stock_qty'          => $data['realisasi_qty'],
                    'stock_type'         => 'IN',
                    'stock_expired_date' => now()->addDays(30),
                    'stock_reff'         => $groupCode,
                    'stock_pallet_code'  => $groupCode,
                ]);
            });

            flash()->success('Realisasi berhasil! Stok telah ditambahkan.');

            return redirect()->route('wms-masuk-detail.getTable');
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
    }
}
