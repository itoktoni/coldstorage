<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\KeluarRealisasi;
use App\Models\Stock;

class KeluarRealisasiController extends Controller
{
    use ControllerTrait;

    public function __construct(KeluarRealisasi $model)
    {
        $this->model = $model::getModel();
    }

    protected function getData()
    {
        return $this->model->with(['detail.keluar', 'stock'])->filter()->sort();
    }

    protected function share($data = [])
    {
        return array_merge(['model' => $this->model, 'stockOptions' => Stock::pluck('stock_code', 'stock_id')], $data);
    }
}
