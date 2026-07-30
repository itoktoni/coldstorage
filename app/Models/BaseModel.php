<?php

namespace App\Models;

use Abbasudo\Purity\Traits\Filterable;
use Abbasudo\Purity\Traits\Sortable;
use App\Concerns\DefaultEntity;
use App\Concerns\OptionTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperBaseModel
 */
class BaseModel extends Model
{
    use DefaultEntity, Filterable, OptionTrait, Sortable;

    // Remove hardcoded products table reference since it's not part of our application
    // Each model should define its own table name
    protected $primaryKey = 'id';

    public $timestamps = true;

    public $incrementing = true;

    /**
     * Columns available for filtering.
     */
    public static $filterColumns = [];

    /**
     * Columns available for sorting.
     */
    public static $sortColumns = [];

    /**
     * Accessor: $table->field_primary in blade templates → model ID.
     */
    public function getFieldPrimaryAttribute(): mixed
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function rules(): array
    {
        return [
            $this->field_name() => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
