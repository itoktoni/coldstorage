<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    public function show(Content $content)
    {
        return response()->json($content->getNormalizedData());
    }

    public function indexByType(string $slug)
    {
        $type = \App\Models\Type::where("slug", $slug)->firstOrFail();
        $entries = $type->contents()
            ->published()
            ->get()
            ->map(fn($entry) => $entry->getNormalizedData());

        return response()->json($entries);
    }

    public function getBlueprintSchema(string $slug)
    {
        $type = \App\Models\Type::where("slug", $slug)->firstOrFail();
        $schema = [];
        $sections = $type->sections()->where("is_active", true)->get();

        foreach ($sections as $group) {
            $schema[$group->name] = $group->getJsonSchema();
        }

        return response()->json([
            "content_type" => $type->slug,
            "type" => $type->type,
            "supports" => $type->supports,
            "sections" => $schema,
        ]);
    }
}
