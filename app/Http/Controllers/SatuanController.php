<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Satuan;

class SatuanController extends Controller
{
    use ControllerTrait;

    public function __construct(Satuan $model)
    {
        $this->model = $model::getModel();
    }
}
