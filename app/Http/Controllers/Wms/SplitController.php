<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Split;
use App\Models\Stock;

class SplitController extends Controller
{
    use ControllerTrait;

    public function __construct(Split $model)
    {
        $this->model = $model::getModel();
    }

    public function getProduce()
    {
        return view('livewire.split-produce');
    }

    public function getCreate()
    {
        return redirect()->route('wms-split.produce');
    }

    protected function share($data = [])
    {
        return array_merge([
            'model' => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'stockOptions' => Stock::pluck('stock_code', 'stock_id'),
        ], $data);
    }
}
