<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

define('ACTION_CREATE', 'getCreate');
define('ACTION_UPDATE', 'getUpdate');
define('ACTION_DELETE', 'getDelete');
define('ACTION_EMPTY', 'empty');
define('ACTION_TABLE', 'getTable');
define('ACTION_PRINT', 'getPrint');
define('ACTION_EXPORT', 'getExport');
define('ERROR_PERMISION', 'This action is unauthorized');
define('TOAST_SUCCESS', 'Data berhasil di proses !');
define('TOAST_FAILED', 'Proses Error !');

function formatDate($value, $datetime = false)
{
    if (empty($value)) {
        return null;
    }

    if ($datetime === false) {
        $format = 'd/m/Y';
    } elseif ($datetime === true) {
        $format = 'd/m/Y H:i:s';
    } else {
        $format = $datetime;
    }

    if ($value instanceof Carbon) {
        $value = $value->format($format);
    } elseif ($value instanceof CarbonImmutable) {
        $value = Carbon::parse($value)->format($format);
    } elseif (is_string($value)) {
        $value = Carbon::parse($value)->format($format);
    }

    return $value ?: null;
}

function formatAngka(int $value, $simbol = null)
{
    return $simbol.number_format($value, 0, ',', '.');
}

/**
 * Format angka: 1.000 (tanpa desimal jika 0), 1,255 / 1,24 (hapus trailing zero).
 * Tanda: titik ribuan, koma desimal.
 */
function formatQty($value): string
{
    $num = (float) $value;
    $integer = (int) $num;
    $decimal = $num - $integer;

    if (abs($decimal) < 0.001) {
        return number_format($integer, 0, ',', '.');
    }

    // 3 desimal, lalu hapus trailing zero
    $formatted = number_format($num, 3, ',', '.');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, ',');

    return $formatted;
}

function formatLabel($value)
{
    $label = Str::of($value);
    if ($label->contains('_')) {
        $label = $label = $label->explode('_')->last();
    } else {
        $label = $label->replace('[]', '');
    }

    return ucfirst($label);
}

function unic_string($length)
{
    $chars = array_merge(range('a', 'z'), range('A', 'Z'));
    $length = intval($length) > 0 ? intval($length) : 16;
    $max = count($chars) - 1;
    $str = '';

    while ($length--) {
        shuffle($chars);
        $rand = mt_rand(0, $max);
        $str .= $chars[$rand];
    }

    return strtoupper($str);
}

function unic_number($length)
{
    $length = intval($length) > 0 ? intval($length) : 6;
    $min = (int) str_pad('1', $length, '0');
    $max = (int) str_pad('9', $length, '9');

    return random_int($min, $max);
}

function module($action = null)
{
    $module = request()->route()->getAction('name');

    if ($action) {
        return $module.'.'.$action;
    }

    return $module;
}

function moduleRoute($action = null, $params = [])
{
    $route = route(module($action), $params);

    return $route;
}

function nominalQRIS($qris_data, $amount)
{
    $amountStr = number_format($amount, 2, '.', '');
    $amountLength = strlen($amountStr);
    $amountField = '54'.str_pad($amountLength, 2, '0', STR_PAD_LEFT).$amountStr;

    // Parse QRIS TLV: hapus field 54 (nominal) yang lama dengan benar
    $new_qris = '';
    $i = 0;
    $len = strlen($qris_data);
    while ($i < $len - 4) {
        $tag = substr($qris_data, $i, 2);
        $valueLen = (int) substr($qris_data, $i + 2, 2);
        $totalLen = 4 + $valueLen;

        if ($tag === '54') {
            // Skip field 54 (nominal lama)
            $i += $totalLen;

            continue;
        }

        // Simpan field lainnya
        $new_qris .= substr($qris_data, $i, $totalLen);
        $i += $totalLen;
    }

    // Sisa string (CRC lama)
    if ($i < $len) {
        $new_qris .= substr($qris_data, $i);
    }

    // Hilangkan CRC lama (tag 63)
    $new_qris = preg_replace('/6304.{4}$/', '', $new_qris);

    // Tambah nominal baru + CRC
    $new_qris = $new_qris.$amountField.'6304';
    $crc = strtoupper(dechex(crc16($new_qris)));
    $crc = str_pad($crc, 4, '0', STR_PAD_LEFT);

    return $new_qris.$crc;
}

function crc16($data)
{
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc = $crc << 1;
            }
            $crc &= 0xFFFF;
        }
    }

    return $crc;
}

function showSql($query = null): string
{
    if ($query === null) {
        return '';
    }

    if (is_string($query)) {
        return $query;
    }

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    if (empty($bindings)) {
        return $sql;
    }

    foreach ($bindings as $binding) {
        if (is_null($binding)) {
            $value = 'null';
        } elseif (is_bool($binding)) {
            $value = $binding ? 'true' : 'false';
        } elseif (is_numeric($binding)) {
            $value = (string) $binding;
        } else {
            $value = "'".addslashes($binding)."'";
        }

        $sql = preg_replace('/\?/', $value, $sql, 1);
    }

    return $sql;
}

function ddSql($query = null): void
{
    $sql = showSql($query);
    $bindings = is_object($query) && method_exists($query, 'getBindings') ? $query->getBindings() : [];

    dump($sql);
    if (! empty($bindings)) {
        dump('Bindings:', $bindings);
    }
    exit;
}

function cleanText(?string $text): ?string
{
    if ($text === null) {
        return null;
    }

    $text = preg_replace('/[\x{4E00}-\x{9FFF}]/u', '', $text);
    $text = preg_replace('/[\x{3400}-\x{4DBF}]/u', '', $text);
    $text = preg_replace('/[\x{F900}-\x{FAFF}]/u', '', $text);
    $text = preg_replace('/[\x{2E80}-\x{2EFF}]/u', '', $text);
    $text = preg_replace('/[\x{3000}-\x{303F}]/u', '', $text);
    $text = preg_replace('/[\x{3040}-\x{309F}]/u', '', $text);
    $text = preg_replace('/[\x{30A0}-\x{30FF}]/u', '', $text);
    $text = preg_replace('/[\x{31F0}-\x{31FF}]/u', '', $text);
    $text = preg_replace('/[\x{FF00}-\x{FFEF}]/u', '', $text);
    $text = preg_replace('/[\x{AC00}-\x{D7AF}]/u', '', $text);
    $text = preg_replace('/[\x{1100}-\x{11FF}]/u', '', $text);
    $text = preg_replace('/[\x{0600}-\x{06FF}]/u', '', $text);
    $text = preg_replace('/[\x{0750}-\x{077F}]/u', '', $text);
    $text = preg_replace('/[\x{FB50}-\x{FDFF}]/u', '', $text);
    $text = preg_replace('/[\x{FE70}-\x{FEFF}]/u', '', $text);
    $text = preg_replace('/[\x{0400}-\x{04FF}]/u', '', $text);
    $text = preg_replace('/[\x{0E00}-\x{0E7F}]/u', '', $text);
    $text = preg_replace('/[\x{0980}-\x{09FF}]/u', '', $text);
    $text = preg_replace('/[\x{0900}-\x{097F}]/u', '', $text);
    $text = preg_replace('/[\x{0E80}-\x{0EFF}]/u', '', $text);

    $text = preg_replace('/[^\P{C}\n\r]+/u', '', $text);
    $text = preg_replace('/[^\S\n\r]+/u', ' ', $text);
    $text = preg_replace('/ *\n */', "\n", $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    return trim($text);
}

/**
 * Render a single custom field input for content entry forms.
 */
function renderFieldInput($fName, $fType, $fValue, $fieldName, $fLabel, $fieldDef = [])
{
    $escaped = htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8');
    $escapedLabel = htmlspecialchars($fLabel, ENT_QUOTES, 'UTF-8');
    $escapedValue = htmlspecialchars(is_array($fValue) ? json_encode($fValue) : (string) $fValue, ENT_QUOTES, 'UTF-8');

    $html = '';
    if (in_array($fType, ['text', 'url', 'email', 'color', 'slug'])) {
        $inputType = $fType === 'slug' ? 'text' : $fType;
        $html = '<input type="'.$inputType.'" name="'.$escaped.'" value="'.$escapedValue.'" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="'.$escapedLabel.'" />';
    } elseif (in_array($fType, ['textarea', 'wysiwyg'])) {
        $html = '<textarea name="'.$escaped.'" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="'.$escapedLabel.'">'.htmlspecialchars((string) $fValue, ENT_QUOTES, 'UTF-8').'</textarea>';
    } elseif ($fType === 'toggle') {
        $checked = $fValue ? 'checked' : '';
        $html = '<label class="relative inline-flex items-center cursor-pointer"><input type="hidden" name="'.$escaped.'" value="0"><input type="checkbox" name="'.$escaped.'" value="1" '.$checked.' class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"><span class="ml-2 text-sm text-gray-600">'.$escapedLabel.'</span></label>';
    } elseif ($fType === 'image') {
        $html = '<input type="text" name="'.$escaped.'" value="'.$escapedValue.'" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="https://example.com/image.jpg" />';
        if ($fValue && ! is_array($fValue)) {
            $html .= '<img src="'.htmlspecialchars((string) $fValue, ENT_QUOTES, 'UTF-8').'" class="mt-2 h-16 w-24 object-cover rounded border" alt="Preview">';
        }
    } elseif ($fType === 'select') {
        $opts = $fieldDef['config']['options'] ?? [];
        $html = '<select name="'.$escaped.'" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"><option value="">-- Select --</option>';
        foreach ($opts as $optVal => $optLabel) {
            $sel = $fValue == $optVal ? 'selected' : '';
            $html .= '<option value="'.htmlspecialchars($optVal, ENT_QUOTES, 'UTF-8').'" '.$sel.'>'.htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8').'</option>';
        }
        $html .= '</select>';
    } elseif (in_array($fType, ['number', 'integer', 'float'])) {
        $html = '<input type="number" name="'.$escaped.'" value="'.$escapedValue.'" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />';
    } elseif ($fType === 'assets') {
        $html = '<input type="text" name="'.$escaped.'" value="'.$escapedValue.'" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="File URLs" />';
    } else {
        $html = '<input type="text" name="'.$escaped.'" value="'.$escapedValue.'" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />';
    }

    return $html;
}
