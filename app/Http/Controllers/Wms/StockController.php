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
        return array_merge([
            'model'          => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'lokasiOptions'  => Lokasi::pluck('lokasi_nama', 'lokasi_code'),
            'typeOptions'    => Stock::typeOptions(),
        ], $data);
    }

    protected function getData()
    {
        $query = $this->model->addSelect([
            'stock.*',
            'product_nama',
            'lokasi_nama'
        ])->leftJoinRelationship('product')->leftJoinRelationship('lokasi')->filter()->sort();

        return $query;
    }
}
