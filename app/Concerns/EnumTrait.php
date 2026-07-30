<?php

namespace App\Concerns;

trait EnumTrait
{
    public static function getOptions($value = false): array
    {
        // 1. Gunakan self::cases() untuk Native PHP Enum
        $collect = collect(self::cases());

        // 2. Karena value bisa berupa string atau int, kita gunakan is_scalar() atau langsung sesuaikan
        if ($value && is_array($value)) {
            $collect = $collect->whereIn('value', $value);
        } elseif ($value && (is_int($value) || is_string($value))) {
            $collect = $collect->where('value', $value);
        }

        $data = [];
        foreach ($collect as $item) {
            // 3. Panggil method description() yang ada di file Enum Anda
            $data[$item->value] = $item->description();
        }

        return $data;
    }

    public static function getApi($value = false): array
    {
        $collect = collect(self::cases());

        if ($value && is_array($value)) {
            $collect = $collect->whereIn('value', $value);
        } elseif ($value && (is_int($value) || is_string($value))) {
            $collect = $collect->where('value', $value);
        }

        $data = [];
        foreach ($collect as $item) {
            // 4. Panggil method description() untuk nama API yang rapi
            $data[] = ['id' => $item->value, 'name' => $item->description()];
        }

        return $data;
    }
}
