<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Product;

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
        return $this->model->with(['po', 'product'])->filter()->sort();
    }
}
