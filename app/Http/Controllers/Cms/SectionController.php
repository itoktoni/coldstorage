<?php

namespace App\Http\Controllers\Cms;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Field;
use App\Models\Section;
use App\Models\Type;

class SectionController extends Controller
{
    use ControllerTrait;

    public function __construct()
    {
        $this->model = new Section;
    }

    protected function share($data = [])
    {
        $topFields = Field::whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $default = [
            'model' => $this->model,
            'contentTypes' => Type::pluck('name', 'id')->toArray(),
            'allFields' => $topFields,
        ];

        return array_merge($default, $data);
    }

    public function getCreate(GeneralRequest $request)
    {
        return $this->views($this->template());
    }
}