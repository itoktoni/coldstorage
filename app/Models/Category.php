<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends BaseModel
{
    use SoftDeletes;

    protected $table = 'categories';

    protected $fillable = ['name', 'slug', 'description', 'parent_id', 'sort_order'];

    public static $sortColumns = ['name', 'slug', 'sort_order'];

    public static $filterColumns = ['name', 'slug'];

    public static function field_name(): string
    {
        return 'name';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // ponytail: ContentEntry model deleted — using Content with the shared pivot table.
    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Content::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug,' . ($this->id ?? '')],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
