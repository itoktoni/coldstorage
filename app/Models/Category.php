<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends BaseModel
{
    use SoftDeletes;

    protected $table = 'categories';
    public $primaryKey = 'slug';
    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    public static $sortColumns = ['name', 'slug', 'sort_order'];
    public static $filterColumns = ['name', 'slug'];

    public static function field_name(): string
    {
        return 'name';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'slug');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'slug');
    }

    // ponytail: ContentEntry model deleted — using Content with the shared pivot table.
    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Content::class);
    }

    public function rules(): array
    {
        $excludeSlug = $this->exists ? $this->slug : '';

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug,' . $excludeSlug],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'string', 'exists:categories,slug'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
