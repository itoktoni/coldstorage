<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\Stock;

class StockController extends Controller
{
    use ControllerTrait;

    public function __construct(Stock $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge(['model' => $this->model, 'productOptions' => Product::pluck('product_nama', 'product_id'), 'lokasiOptions' => Lokasi::pluck('lokasi_nama', 'lokasi_id')], $data);
    }

    protected function getData()
    {
        return $this->model->with('product', 'lokasi')->filter()->sort();
    }
}
