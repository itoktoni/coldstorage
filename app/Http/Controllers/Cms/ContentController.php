<?php

namespace App\Http\Controllers\Cms;

use App\Concerns\ControllerTrait;
use App\Http\Requests\GeneralRequest;
use App\Models\Category;
use App\Models\Content;
use App\Models\Field;
use App\Models\Section;
use App\Models\Tag;
use App\Models\Type;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use ControllerTrait;

    public function __construct()
    {
        $this->model = new Content;
    }

    protected function share($data = [])
    {
        $default = [
            "model" => $this->model,
            "contentTypes" => Type::pluck("name", "id")->toArray(),
            "allTypes" => Type::all()->toArray(),
            "allSections" => Section::all()->toArray(),
            "allFields" => Field::all()->toArray(),
            "contentTypeId" => request()->input("content_type_id"),
            "categories" => Category::pluck('name', 'id'),
            "tags" => Tag::pluck('name', 'id'),
        ];

        return array_merge($default, $data);
    }

    public function getCreate(GeneralRequest $request)
    {
        return $this->views($this->template(), ['model' => $this->model]);
    }

    public function getSectionHtml($id)
    {
        $section = Section::findOrFail($id);

        $group = $section;
        $group->fields = $section->fields; // uses accessor from Section model

        $html = view('pages.content.partials.section-card', [
            'group' => $group,
            'isNewSection' => true,
        ])->render();

        return response()->json([
            'html' => $html,
            'section' => [
                'id' => $section->id,
                'name' => $section->name ?? '',
                'description' => $section->description ?? '',
            ],
        ]);
    }

    public function preview(Request $request, ?int $id = null)
    {
        $metaData = $request->input("meta", []);
        $activeSections = $request->input("active_sections", $request->input("active_field_groups", []));
        $type = null;

        if ($request->filled("content_type_id")) {
            $type = Type::find($request->input("content_type_id"));
        }

        $sections = [];
        if ($type && ! empty($activeSections)) {
            foreach ($activeSections as $sectionId) {
                $section = Section::find($sectionId);
                if (! $section) {
                    continue;
                }
                $fieldValues = [];
                $fieldIds = $section->field_ids ?? [];
                foreach ($fieldIds as $fid) {
                    $field = Field::find($fid);
                    if ($field) {
                        $fieldValues[$field->name ?? 'f_'.$fid] = $metaData[$field->name ?? ''] ?? null;
                    }
                }
                $sections[$section->name ?? 'Section '.$sectionId] = $fieldValues;
            }
        }

        return response()->json([
            "title" => $request->input("title"),
            "slug" => $request->input("slug"),
            "content" => $request->input("content"),
            "excerpt" => $request->input("excerpt"),
            "status" => $request->input("status", "draft"),
            "featured_image" => $request->input("featured_image"),
            "content_type" => $type->slug ?? null,
            "sections" => $sections,
        ]);
    }
}