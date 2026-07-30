<?php

namespace App\Services;

use App\Models\Content;
use App\Models\CustomField;

class ContainerRenderer
{
    /**
     * Render all container sections for a content entry.
     */
    public static function render(Content $entry, string $containerFieldName = 'page_builder'): string
    {
        $containerField = CustomField::where('name', $containerFieldName)
            ->where(function ($q) {
                $q->whereNull('content_type_id')->orWhere('content_type_id', '!=', 0);
            })
            ->first();

        if (!$containerField || !$containerField->isContainerType()) {
            return '';
        }

        $data = $entry->getMeta($containerFieldName);

        if (empty($data) || !is_array($data)) {
            return '';
        }

        $html = '';

        foreach ($data as $index => $section) {
            $layoutName = $section['_layout'] ?? null;
            if (!$layoutName) {
                continue;
            }

            $html .= static::renderSection($containerField, $layoutName, $section, $index);
        }

        return $html;
    }

    /**
     * Render a single section by layout name.
     */
    public static function renderSection(CustomField $containerField, string $layoutName, array $data, int $index = 0): string
    {
        $layouts = $containerField->getLayouts();
        $layoutDef = null;

        foreach ($layouts as $layout) {
            if ($layout['name'] === $layoutName) {
                $layoutDef = $layout;
                break;
            }
        }

        if (!$layoutDef) {
            return '';
        }

        $viewName = 'frontend.sections.' . $layoutName;

        if (!view()->exists($viewName)) {
            $viewName = 'frontend.sections._default';
        }

        try {
            return view($viewName, [
                'data' => $data,
                'layout' => $layoutDef,
                'fields' => $layoutDef['fields'] ?? [],
                'index' => $index,
            ])->render();
        } catch (\Throwable $e) {
            return '<!-- Section render error: ' . e($e->getMessage()) . ' -->';
        }
    }

    /**
     * Render a container field value (for nested containers).
     */
    public static function renderContainer(array $containerData, array $fieldDef): string
    {
        $mode = $fieldDef['mode'] ?? 'single';
        $html = '';

        if ($mode === 'single') {
            $html .= view('frontend.containers.single', [
                'data' => $containerData,
                'fields' => $fieldDef['fields'] ?? [],
            ])->render();
        } elseif ($mode === 'multiple') {
            $html .= view('frontend.containers.multiple', [
                'items' => $containerData,
                'fields' => $fieldDef['fields'] ?? [],
            ])->render();
        } elseif ($mode === 'flexible') {
            foreach ($containerData as $item) {
                $subLayout = $item['_layout'] ?? null;
                if ($subLayout) {
                    $html .= static::renderSection(
                        new CustomField(['layouts' => $fieldDef['layouts'] ?? []]),
                        $subLayout,
                        $item
                    );
                }
            }
        }

        return $html;
    }

    /**
     * Get a field value from data.
     */
    public static function getValue(array $data, string $fieldName, $default = null)
    {
        return $data[$fieldName] ?? $default;
    }

    /**
     * Render a field value based on its type.
     */
    public static function renderField(array $data, array $fieldDef): string
    {
        $name = $fieldDef['name'];
        $type = $fieldDef['type'] ?? 'text';
        $value = $data[$name] ?? ($fieldDef['default_value'] ?? '');

        if ($type === 'container') {
            if (empty($value) || !is_array($value)) {
                return '';
            }
            return static::renderContainer($value, $fieldDef);
        }

        return (string) $value;
    }
}
