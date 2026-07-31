<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Gudang;

class GudangController extends Controller
{
    use ControllerTrait;

    public function __construct(Gudang $model)
    {
        $this->model = $model::getModel();
    }
}
