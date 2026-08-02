<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\MasukRealisasi;
use App\Models\Product;

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
}
