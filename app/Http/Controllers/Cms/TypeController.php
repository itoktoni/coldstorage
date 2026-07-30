<?php

namespace App\Http\Controllers\Cms;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Section;
use App\Models\Type;

class TypeController extends Controller
{
    use ControllerTrait;

    public function __construct()
    {
        $this->model = new Type;
    }

    protected function share($data = [])
    {
        $sectionCounts = Section::selectRaw('content_type_id, count(*) as cnt')
            ->groupBy('content_type_id')
            ->pluck('cnt', 'content_type_id')
            ->all();

        $default = [
            "model" => $this->model,
            "typeOptions" => Type::getTypeOptions(),
            "supportsOptions" => Type::getSupportsOptions(),
            "sectionCounts" => $sectionCounts,
        ];

        return array_merge($default, $data);
    }

    public function getCreate(GeneralRequest $request)
    {
        return $this->views($this->template(), ['model' => $this->model]);
    }
}