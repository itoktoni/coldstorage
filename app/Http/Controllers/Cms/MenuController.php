<?php

namespace App\Http\Controllers\Cms;

use App\Concerns\ControllerTrait;
use App\Models\Menu;

class MenuController extends Controller
{
    use ControllerTrait;

    public function __construct(Menu $model)
    {
        $this->model = $model::getModel();
    }
}