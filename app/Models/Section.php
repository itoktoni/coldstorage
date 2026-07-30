<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class Section extends BaseModel
{
    use Orbital;

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->sort_order = $model->sort_order ?? 0;
        });
    }

    protected $fillable = [
        "name",
        "description",
        "icon",
        "content_type_id",
        "field_ids",
        "sort_order",
        "is_active",
    ];

    protected $casts = [
        "field_ids" => "array",
        "is_active" => "boolean",
    ];

    public static $sortColumns = ["name", "content_type_id", "sort_order", "is_active"];
    public static $filterColumns = ["name"];

    public static function field_name(): string
    {
        return "name";
    }

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('icon')->nullable();
        $table->unsignedBigInteger('content_type_id')->nullable();
        $table->json('field_ids')->nullable();
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
    }

    public static function getOrbitalDriver(): string
    {
        return 'json';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, "content_type_id");
    }

    public function getFieldsAttribute()
    {
        if (empty($this->field_ids)) {
            return collect();
        }

        $fields = Field::with("children")->whereIn("id", $this->field_ids)->get();
        // ponytail: collection sort avoids DB-specific FIELD() — swap to DB sort if perf matters
        $ids = array_flip($this->field_ids);

        return $fields->sortBy(fn ($f) => $ids[$f->id] ?? PHP_INT_MAX)->values();
    }

    public function getJsonSchema(): array
    {
        return [
            "name" => $this->name,
            "label" => $this->name,
            "description" => $this->description,
            "icon" => $this->icon,
            "fields" => $this->fields->map(fn ($field) => $field->getJsonSchema())->toArray(),
        ];
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
            "icon" => ["nullable", "string", "max:50"],
            "content_type_id" => ["required"],
            "field_ids" => ["nullable", "array"],
            "sort_order" => ["nullable", "integer"],
            "is_active" => ["boolean"],
        ];
    }
}