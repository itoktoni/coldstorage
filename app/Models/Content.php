<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class Content extends BaseModel
{
    use Orbital;

    protected $fillable = [
        "content_type_id",
        "title",
        "slug",
        "content",
        "excerpt",
        "status",
        "published_at",
        "author_id",
        "featured_image",
        "menu_order",
        "meta",
        "active_sections",
        "category_ids",
        "tag_ids",
    ];

    protected $attributes = [
        "status" => "draft",
        "menu_order" => 0,
    ];

    protected $casts = [
        "published_at" => "datetime",
        "meta" => "array",
        "active_sections" => "array",
        "category_ids" => "array",
        "tag_ids" => "array",
    ];

    public static $sortColumns = ["title", "slug", "status", "published_at"];
    public static $filterColumns = ["title", "slug", "status"];

    public static function field_name(): string
    {
        return "title";
    }

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->unsignedBigInteger('content_type_id')->nullable();
        $table->string('title');
        $table->string('slug')->nullable();
        $table->longText('content')->nullable();
        $table->text('excerpt')->nullable();
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->unsignedBigInteger('author_id')->nullable();
        $table->string('featured_image')->nullable();
        $table->integer('menu_order')->default(0);
        $table->json('meta')->nullable();
        $table->json('active_sections')->nullable();
        $table->json('category_ids')->nullable();
        $table->json('tag_ids')->nullable();
    }

    public static function getOrbitalDriver(): string
    {
        return 'json';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, "content_type_id");
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, "author_id");
    }

    // ponytail: belongsToMany removed — Orbit uses separate SQLite, pivot tables won't cross-connect.
    // Store category/tag IDs in meta JSON, or re-add when migrating to real DB.

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'content_type_id' => ['nullable', 'integer'],
            'author_id' => ['nullable', 'integer'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'menu_order' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function getMeta($fieldName)
    {
        $meta = $this->meta ?? [];

        return $meta[$fieldName] ?? null;
    }

    public function getAllMeta(): array
    {
        return $this->meta ?? [];
    }

    public function scopePublished($query)
    {
        return $query->where("status", "published")
            ->whereNotNull("published_at")
            ->where("published_at", "<=", now());
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === "published" &&
               $this->published_at !== null &&
               $this->published_at <= now();
    }

    public function getNormalizedData(): array
    {
        $data = [
            "id" => $this->id,
            "title" => $this->title,
            "slug" => $this->slug,
            "content" => $this->content,
            "excerpt" => $this->excerpt,
            "status" => $this->status,
            "published_at" => $this->published_at?->toIso8601String(),
            "author_id" => $this->author_id,
            "featured_image" => $this->featured_image,
            "type" => $this->type->slug ?? null,
        ];

        $meta = $this->meta ?? [];
        if (! empty($meta)) {
            $data["sections"] = $this->normalizeContainerMeta($meta);
        }

        return $data;
    }

    protected function normalizeContainerMeta(array $meta): array
    {
        $normalized = [];
        foreach ($meta as $key => $value) {
            if (str_starts_with($key, "_")) {
                continue;
            }
            if (is_array($value)) {
                $normalized[$key] = $this->addTypeToContainer($value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    protected function addTypeToContainer(array $items): array
    {
        if (isset($items["_layout"])) {
            $items["_type"] = $items["_layout"];
            unset($items["_layout"]);

            return $items;
        }
        foreach ($items as &$item) {
            if (is_array($item)) {
                if (isset($item["_layout"])) {
                    $item["_type"] = $item["_layout"];
                    unset($item["_layout"]);
                }
                $item = $this->addTypeToContainer($item);
            }
        }

        return $items;
    }

    public function getBlueprintSchema(): array
    {
        $contentType = $this->type;
        if (! $contentType) {
            return [];
        }

        $sections = [];
        $fieldGroups = $contentType->sections()->where("is_active", true)->get();

        foreach ($fieldGroups as $group) {
            $sections[$group->name] = $group->getJsonSchema();
        }

        return [
            "content_type" => $contentType->slug,
            "type" => $contentType->type,
            "supports" => $contentType->supports,
            "sections" => $sections,
        ];
    }
}