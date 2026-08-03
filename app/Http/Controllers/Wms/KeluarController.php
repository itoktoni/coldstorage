<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Keluar;

class KeluarController extends Controller
{
    use ControllerTrait;

    public function __construct(Keluar $model)
    {
        $this->model = $model::getModel();
    }

    protected function getData()
    {
        return $this->model->with(['details.realisasi'])->filter()->sort();
    }
}
