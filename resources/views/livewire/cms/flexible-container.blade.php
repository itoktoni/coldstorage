@php
$fieldName = $namePrefix . "[" . $field->name . "]";
$children = $field->children ?? collect();
$layouts = $field->getLayouts();
@endphp

<div class="flexible-wrapper border border-gray-200 rounded-lg bg-gray-50 p-3" wire:key="flexible-{{ $field->id }}">
    <div class="flexible-items space-y-3" id="flexible-items-{{ $field->name }}">
        @forelse($items as $itemIdx => $item)
            @php
                $layoutName = $item["_layout"] ?? "";
                $layout = collect($layouts)->firstWhere("name", $layoutName);
                $layoutIcon = $layoutIcons[$layoutName] ?? "icon-[tabler--puzzle]";
                $layoutColor = $layoutColors[$layoutName] ?? "bg-gray-100 text-gray-700 border-gray-200";
            @endphp

            <div class="flexible-item border border-gray-200 rounded-lg bg-white" wire:key="item-{{ $itemIdx }}" data-index="{{ $itemIdx }}">
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 rounded-t-lg cursor-move section-handle select-none">
                    <div class="flex items-center gap-3">
                        <i class="icon-[tabler--grip-vertical] text-gray-300"></i>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border {{ $layoutColor }}">
                            <i class="{{ $layoutIcon }} text-[10px]"></i>
                            {{ $layout["label"] ?? $layoutName }}
                        </span>
                    </div>
                    <button type="button" wire:click.prevent="removeItem({{ $itemIdx }})" class="text-gray-300 hover:text-red-500 transition-colors">
                        <i class="icon-[tabler--x] text-xs"></i>
                    </button>
                </div>
                <div class="p-4 space-y-4 section-fields">
                    @foreach($children as $childField)
                        @php
                            $childValue = $item[$childField->name] ?? $childField->default_value ?? "";
                            $childNamePrefix = $fieldName . "[" . $itemIdx . "]";
                        @endphp
                        @if($childField->type === "container")
                            @include("pages.contententry.partials.container-field", ["field" => $childField, "value" => $childValue, "namePrefix" => $childNamePrefix])
                        @else
                            @include("pages.contententry.partials.basic-field", ["field" => $childField, "value" => $childValue, "namePrefix" => $childNamePrefix])
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <div id="flexible-empty-{{ $field->name }}" class="text-center py-8 text-gray-400">
                <i class="icon-[tabler--stack] text-3xl mb-2 block opacity-30"></i>
                <p class="text-sm font-medium">No sections yet</p>
                <p class="text-xs mt-1">Click Add Section to start building your page layout</p>
            </div>
        @endforelse
    </div>

    @if(count($availableLayouts) > 0)
        <div class="mt-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Add Section:</label>
            <div class="flex flex-wrap gap-2">
                @foreach($availableLayouts as $layout)
                    @php
                        $layoutIcon = $layoutIcons[$layout["name"]] ?? "icon-[tabler--puzzle]";
                        $layoutColor = $layoutColors[$layout["name"]] ?? "bg-gray-100 text-gray-700 border-gray-200";
                    @endphp
                    <button type="button" 
                        wire:click.prevent="addLayout('{{ $layout["name"] }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full border {{ $layoutColor }} hover:opacity-80 transition-opacity">
                        <i class="{{ $layoutIcon }} text-[10px]"></i>
                        {{ $layout["label"] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
