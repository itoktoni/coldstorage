<?php

namespace App\Http\Controllers\Cms;

use App\Concerns\ControllerTrait;
use App\Models\Tag;

class TagController extends Controller
{
    use ControllerTrait;

    public function __construct(Tag $model)
    {
        $this->model = $model::getModel();
    }
}
