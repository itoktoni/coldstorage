<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;

class Field extends BaseModel
{
    use Orbital;

    protected $fillable = [
        "name", "label", "type", "config", "rules", "is_required",
        "default_value", "sort_order", "parent_id", "mode", "min", "max",
        "collapsed", "sortable", "cloneable", "layouts", "type_id",
    ];

    protected $casts = [
        "config" => "array",
        "rules" => "array",
        "is_required" => "boolean",
        "layouts" => "array",
        "sort_order" => "integer",
        "min" => "integer",
        "max" => "integer",
        "collapsed" => "boolean",
        "sortable" => "boolean",
        "cloneable" => "boolean",
    ];

    protected $attributes = [
        "sort_order" => 0,
        "is_required" => false,
        "mode" => "multiple",
    ];

    public static $sortColumns = ["name", "label", "type", "is_required", "mode"];
    public static $filterColumns = ["name", "label", "type", "mode"];

    public static function field_name(): string
    {
        return "label";
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string'],
            'default_value' => ['nullable', 'string'],
            'is_required' => ['nullable'],
            'sort_order' => ['nullable', 'integer'],
            'config_options' => ['nullable', 'string'],
            'children' => ['nullable', 'array'],
            'children.*.label' => ['nullable', 'string'],
            'children.*.name' => ['nullable', 'string'],
            'children.*.type' => ['nullable', 'string'],
            'children.*.is_required' => ['nullable'],
            'children.*.sort_order' => ['nullable', 'integer'],
            'children.*.id' => ['nullable', 'integer'],
            'children.*.children' => ['nullable', 'array'],
        ];
    }

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('name');
        $table->string('label')->nullable();
        $table->string('type')->nullable();
        $table->json('config')->nullable();
        $table->json('rules')->nullable();
        $table->boolean('is_required')->default(false);
        $table->text('default_value')->nullable();
        $table->integer('sort_order')->default(0);
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->string('mode')->nullable();
        $table->integer('min')->nullable();
        $table->integer('max')->nullable();
        $table->boolean('collapsed')->nullable();
        $table->boolean('sortable')->nullable();
        $table->boolean('cloneable')->nullable();
        $table->json('layouts')->nullable();
        $table->unsignedBigInteger('type_id')->nullable();
    }

    public static function getOrbitalDriver(): string
    {
        return 'json';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Field::class, "parent_id");
    }

    public function children(): HasMany
    {
        return $this->hasMany(Field::class, "parent_id");
    }

    public static function getTypeOptions(): array
    {
        return [
            "text" => "Text",
            "textarea" => "Textarea",
            "wysiwyg" => "WYSIWYG",
            "number" => "Number",
            "email" => "Email",
            "url" => "URL",
            "date" => "Date",
            "boolean" => "Boolean",
            "select" => "Select",
            "radio" => "Radio",
            "checkbox" => "Checkbox",
            "image" => "Image",
            "gallery" => "Gallery",
            "file" => "File",
            "video" => "Video",
            "color" => "Color",
            "container" => "Container",
        ];
    }

    public static function getContainerModes(): array
    {
        return ["single", "multiple", "flexible"];
    }

    public function isContainerType(): bool
    {
        return ! empty($this->mode) && in_array($this->mode, self::getContainerModes());
    }

    public function getLayouts(): array
    {
        return $this->layouts ?: [];
    }

    public function getJsonSchema(): array
    {
        $schema = [
            "name" => $this->name,
            "label" => $this->label,
            "type" => $this->type,
        ];

        if ($this->is_required) {
            $schema["required"] = true;
        }

        if (! empty($this->rules)) {
            $schema["rules"] = $this->rules;
        }

        if ($this->type === "select" && ! empty($this->config["options"])) {
            $schema["options"] = $this->config["options"];
        }

        if ($this->mode === "flexible") {
            $schema["mode"] = "flexible";
            $schema["layouts"] = $this->getLayouts();
        }

        if (in_array($this->mode, ["multiple", "flexible"])) {
            $schema["min"] = $this->min;
            $schema["max"] = $this->max;
        }

        if ($this->isContainerType() && $this->children->isNotEmpty()) {
            $schema["fields"] = $this->children->map(fn ($child) => $child->getJsonSchema())->toArray();
        }

        return $schema;
    }
}