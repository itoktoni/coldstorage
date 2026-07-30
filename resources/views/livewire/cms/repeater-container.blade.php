@php
$fieldName = $namePrefix . '[' . $field->name . ']';
$children = $field->children ?? collect();
@endphp

<div class="repeater-wrapper border border-gray-200 rounded-lg bg-gray-50 p-3" id="repeater-{{ $field->name }}" wire:key="wrapper-{{ $field->id }}">
    <div class="repeater-items space-y-2" id="repeater-items-{{ $field->name }}">
        @forelse($items as $itemIdx => $item)
            <div class="repeater-item bg-white border border-gray-200 rounded-md p-3 space-y-3" wire:key="item-{{ $itemIdx }}" data-index="{{ $itemIdx }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 font-medium">#{{ $itemIdx + 1 }}</span>
                    <button type="button" wire:click.prevent="removeItem({{ $itemIdx }})" class="text-gray-300 hover:text-red-500 transition-colors">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                @foreach($children as $childField)
                    @php
                        $childValue = $item[$childField->name] ?? $childField->default_value ?? '';
                        $childName = $fieldName . '[' . $itemIdx . '][' . $childField->name . ']';
                    @endphp
                    @if($childField->type === 'container')
                        @include('pages.contententry.partials.container-field', ['field' => $childField, 'value' => $childValue, 'namePrefix' => $fieldName . '[' . $itemIdx . ']'])
                    @else
                        @include('pages.contententry.partials.basic-field', ['field' => $childField, 'value' => $childValue, 'namePrefix' => $fieldName . '[' . $itemIdx . ']'])
                    @endif
                @endforeach
            </div>
        @empty
            <div id="repeater-empty-{{ $field->name }}" class="text-center py-4 text-gray-400 text-xs">
                <i class="fas fa-inbox mb-1 block text-lg opacity-30"></i>
                No items yet. Click "Add" below.
            </div>
        @endforelse
    </div>

    <div class="mt-3 text-right">
        <button type="button" wire:click.prevent="addItem" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 transition-colors border border-blue-200">
            <i class="fas fa-plus text-[10px]"></i> Add {{ $field->label }}
        </button>
    </div>
</div>