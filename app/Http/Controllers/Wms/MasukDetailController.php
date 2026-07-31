<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\MasukDetail;
use App\Models\Product;

class MasukDetailController extends Controller
{
    use ControllerTrait;

    public function __construct(MasukDetail $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge(['model' => $this->model, 'productOptions' => Product::pluck('product_nama', 'product_id')], $data);
    }
}
