<?php

namespace App\Livewire\Cms;

use Livewire\Component;
use App\Models\CustomField;

class FlexibleContainer extends Component
{
    public int $fieldId;
    public string $namePrefix = "meta";
    public array $items = [];
    public array $availableLayouts = [];
    public array $layoutIcons = [];

    protected $listeners = ["refresh" => "$refresh", "layoutAdded" => "addLayout"];

    public function mount($fieldId, $namePrefix = "meta", $value = null)
    {
        $this->fieldId = $fieldId;
        $this->namePrefix = $namePrefix;

        $field = CustomField::with("children")->findOrFail($fieldId);
        $this->availableLayouts = $field->getLayouts();
        
        // Initialize layout icons
        $this->layoutIcons = [
            "hero" => "fa-image",
            "slider" => "fa-images",
            "cta" => "fa-bullhorn",
            "image_left_right" => "fa-columns",
            "text_block" => "fa-align-left",
            "gallery" => "fa-photo-film",
            "faq" => "fa-circle-question",
            "pricing" => "fa-tags",
            "footer" => "fa-shoe-prints",
        ];

        // Initialize layout colors
        $this->layoutColors = [
            "hero" => "bg-purple-100 text-purple-700 border-purple-200",
            "slider" => "bg-blue-100 text-blue-700 border-blue-200",
            "cta" => "bg-green-100 text-green-700 border-green-200",
            "image_left_right" => "bg-orange-100 text-orange-700 border-orange-200",
            "text_block" => "bg-gray-100 text-gray-700 border-gray-200",
            "gallery" => "bg-pink-100 text-pink-700 border-pink-200",
            "faq" => "bg-yellow-100 text-yellow-700 border-yellow-200",
            "pricing" => "bg-indigo-100 text-indigo-700 border-indigo-200",
            "footer" => "bg-slate-100 text-slate-700 border-slate-200",
        ];

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $this->items = is_array($value) ? array_values($value) : [];
    }

    public array $layoutColors = [];

    public function addLayout(string $layoutName)
    {
        $this->items[] = ["_layout" => $layoutName];
    }

    public function removeItem(int $index)
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function render()
    {
        $field = CustomField::with("children")->findOrFail($this->fieldId);
        return view("livewire.cms.flexible-container", [
            "field" => $field,
            "namePrefix" => $this->namePrefix,
            "availableLayouts" => $this->availableLayouts,
            "layoutIcons" => $this->layoutIcons,
            "layoutColors" => $this->layoutColors,
        ]);
    }
}