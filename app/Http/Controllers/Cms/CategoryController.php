<?php

namespace App\Http\Controllers\Cms;

use App\Concerns\ControllerTrait;
use App\Models\Category;

class CategoryController extends Controller
{
    use ControllerTrait;

    public function __construct(Category $model)
    {
        $this->model = $model::getModel();
    }
}
