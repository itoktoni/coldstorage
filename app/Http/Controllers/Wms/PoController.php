<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Po;

class PoController extends Controller
{
    use ControllerTrait;

    public function __construct(Po $model)
    {
        $this->model = $model::getModel();
    }

    protected function getData()
    {
        return $this->model->with('details.product')->filter()->sort();
    }
}
