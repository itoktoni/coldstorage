<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\KeluarDetail;
use App\Models\Product;

class KeluarDetailController extends Controller
{
    use ControllerTrait;

    public function __construct(KeluarDetail $model)
    {
        $this->model = $model::getModel();
    }

    protected function getData()
    {
        return $this->model->with(['product', 'soDetail.so', 'realisasi'])->filter()->sort();
    }

    protected function share($data = [])
    {
        return array_merge(['model' => $this->model, 'productOptions' => Product::pluck('product_nama', 'product_id')], $data);
    }
}
