<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends BaseModel
{
    use SoftDeletes;

    protected $table = 'tags';
    public $primaryKey = 'slug';
    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'slug'];

    public static $sortColumns = ['name', 'slug'];
    public static $filterColumns = ['name', 'slug'];

    public static function field_name(): string
    {
        return 'name';
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
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tags,slug,' . $excludeSlug],
        ];
    }
}
