<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Gudang;
use App\Models\Lokasi;

class LokasiController extends Controller
{
    use ControllerTrait;

    public function __construct(Lokasi $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model' => $this->model,
            'categoryOptions'   => Category::getOptions(),
            'gudangOptions' => Gudang::pluck('gudang_nama', 'gudang_id')
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with('gudang')->filter()->sort();
    }
}
