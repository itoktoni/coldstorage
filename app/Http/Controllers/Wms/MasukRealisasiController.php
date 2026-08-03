<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\MasukRealisasi;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasukRealisasiController extends Controller
{
    use ControllerTrait;

    public function __construct(MasukRealisasi $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model'          => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'lokasiOptions'  => Lokasi::pluck('lokasi_nama', 'lokasi_code'),
            'statusOptions'  => MasukRealisasi::statusOptions(),
        ], $data);
    }

    protected function getData()
    {
        return $this->model->addSelect([
            'masuk_realisasi.*',
            'in_detail_status',
        ])->leftJoin('masuk_detail', 'masuk_realisasi.in_realisasi_masuk_code', '=', 'masuk_detail.in_detail_code')
          ->with('product', 'lokasi')
          ->filter()
          ->sort();
    }

    public function getDelete(Request $request, string $id)
    {
        $realisasi = $this->model->findOrFail($id);

        DB::transaction(function () use ($realisasi) {
            // Hapus stock yang terkait (stock_reff = in_realisasi_group)
            if ($realisasi->in_realisasi_group) {
                Stock::where('stock_reff', $realisasi->in_realisasi_group)->delete();
            }

            $realisasi->delete();
        });

        flash()->success('Masuk realisasi dan stock terkait berhasil dihapus.');

        return redirect()->route('wms-masuk-realisasi.getTable');
    }
}
