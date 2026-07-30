<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Orbit\Concerns\Orbital;

class Type extends BaseModel
{
    use Orbital;

    protected $fillable = [
        "name",
        "slug",
        "type",
        "description",
        "supports",
        "is_active",
        "menu_position",
        "menu_icon",
    ];

    protected $casts = [
        "supports" => "array",
        "is_active" => "boolean",
    ];

    protected $attributes = [
        "type" => "custom",
    ];

    public static $sortColumns = ["name", "slug", "type", "is_active", "menu_position"];

    public static $filterColumns = ["name", "slug", "type", "is_active"];

    public static function field_name(): string
    {
        return "name";
    }

    public static function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('name');
        $table->string('slug')->nullable();
        $table->string('type')->default('custom');
        $table->text('description')->nullable();
        $table->json('supports')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('menu_position')->nullable();
        $table->string('menu_icon')->nullable();
    }

    public static function getOrbitalDriver(): string
    {
        return 'json';
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, "content_type_id");
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, "content_type_id");
    }

    public function fieldGroups(): HasMany
    {
        return $this->sections();
    }

    public static function getTypeOptions(): array
    {
        return [
            "page" => "Page",
            "blog" => "Blog",
            "product" => "Product",
            "ecommerce" => "Ecommerce",
            "custom" => "Custom",
        ];
    }

    public static function getSupportsOptions(): array
    {
        return [
            "title" => "Title",
            "slug" => "Slug",
            "excerpt" => "Excerpt",
            "featured_image" => "Featured Image",
            "status" => "Status",
            "author" => "Author",
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = static::generateUniqueSlug($model->name, $model->id);
            }
        });

        static::updating(function (self $model) {
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = static::generateUniqueSlug($model->name, $model->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where("slug", $slug)
            ->when($ignoreId, fn ($q) => $q->where("id", "!=", $ignoreId))
            ->exists()) {
            $slug = $originalSlug . "-" . $counter++;
        }

        return $slug;
    }

    public function hasContents(): bool
    {
        return $this->contents()->exists();
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "slug" => ["nullable", "string", "max:255"],
            "type" => ["required", "string", "in:page,blog,product,ecommerce,custom"],
            "description" => ["nullable", "string"],
            "supports" => ["nullable", "array"],
            "supports.*" => ["string", "in:title,slug,excerpt,featured_image,status,author"],
            "is_active" => ["boolean"],
            "menu_position" => ["nullable", "integer"],
            "menu_icon" => ["nullable", "string", "max:50"],
        ];
    }
}