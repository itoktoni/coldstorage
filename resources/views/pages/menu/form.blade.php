<?php /** @var App\Models\Menu $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)
                <x-input col="6" name="name" />
                <x-input col="6" name="slug" />
                <x-input col="6" name="location" placeholder="header, footer, sidebar" />
                <x-input col="6" name="sort_order" type="number" />
                <x-input col="6" name="is_active" type="true_false" />
            @endbind
        </x-card>

        <x-card label="Menu Items" :noGrid="true">
            <div id="menu-items-builder">
                <div id="menu-items-list" class="space-y-2">
                    @php $existingItems = old('items', $model->items ?? []); @endphp
                    @if(is_array($existingItems) && count($existingItems) > 0)
                        @foreach($existingItems as $index => $item)
                        <div class="menu-item-row flex items-start gap-2 p-3 bg-base-200 rounded-lg border border-base-300" data-index="{{ $index }}">
                            <div class="flex flex-col gap-2 flex-1">
                                <div class="flex flex-wrap gap-2">
                                    <input type="text" name="items[{{ $index }}][label]" value="{{ $item['label'] ?? '' }}" placeholder="Label" class="input input-bordered input-sm flex-1 min-w-[200px]" required />
                                    <input type="text" name="items[{{ $index }}][url]" value="{{ $item['url'] ?? '' }}" placeholder="URL" class="input input-bordered input-sm flex-1 min-w-[200px]" required />
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <input type="text" name="items[{{ $index }}][icon]" value="{{ $item['icon'] ?? '' }}" placeholder="Icon (optional)" class="input input-bordered input-sm flex-1 min-w-[140px]" />
                                    <select name="items[{{ $index }}][target]" class="select select-bordered select-sm flex-1 min-w-[140px]">
                                        <option value="_self" {{ ($item['target'] ?? '_self') === '_self' ? 'selected' : '' }}>Same Window</option>
                                        <option value="_blank" {{ ($item['target'] ?? '') === '_blank' ? 'selected' : '' }}>New Tab</option>
                                    </select>
                                    <input type="number" name="items[{{ $index }}][sort_order]" value="{{ $item['sort_order'] ?? $index }}" placeholder="Order" class="input input-bordered input-sm flex-1 min-w-[140px]" />
                                </div>
                                {{-- Children --}}
                                @if(!empty($item['children']))
                                <div class="ml-6 pl-3 border-l-2 border-base-300 space-y-2">
                                    @foreach($item['children'] as $childIndex => $child)
                                    <div class="menu-child-row flex items-start gap-2 p-2 bg-base-100 rounded border border-base-300">
                                        <div class="flex flex-col gap-2 flex-1">
                                            <div class="flex flex-wrap gap-2">
                                                <input type="text" name="items[{{ $index }}][children][{{ $childIndex }}][label]" value="{{ $child['label'] ?? '' }}" placeholder="Label" class="input input-bordered input-sm flex-1 min-w-[200px]" required />
                                                <input type="text" name="items[{{ $index }}][children][{{ $childIndex }}][url]" value="{{ $child['url'] ?? '' }}" placeholder="URL" class="input input-bordered input-sm flex-1 min-w-[200px]" required />
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <input type="text" name="items[{{ $index }}][children][{{ $childIndex }}][icon]" value="{{ $child['icon'] ?? '' }}" placeholder="Icon" class="input input-bordered input-sm flex-1 min-w-[140px]" />
                                                <select name="items[{{ $index }}][children][{{ $childIndex }}][target]" class="select select-bordered select-sm flex-1 min-w-[140px]">
                                                    <option value="_self" {{ ($child['target'] ?? '_self') === '_self' ? 'selected' : '' }}>Same Window</option>
                                                    <option value="_blank" {{ ($child['target'] ?? '') === '_blank' ? 'selected' : '' }}>New Tab</option>
                                                </select>
                                                <button type="button" onclick="this.closest('.menu-child-row').remove()" class="btn btn-sm btn-error btn-outline">
                                                    <i class="icon-[tabler--trash]"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                <button type="button" onclick="addChildItem(this, {{ $index }})" class="btn btn-xs btn-ghost text-primary">
                                    <i class="icon-[tabler--plus]"></i> Add Child Item
                                </button>
                            </div>
                            <button type="button" onclick="this.closest('.menu-item-row').remove()" class="btn btn-sm btn-error btn-outline mt-1">
                                <i class="icon-[tabler--trash]"></i>
                            </button>
                        </div>
                        @endforeach
                    @endif
                </div>
                <button type="button" onclick="addMenuItem()" class="btn btn-sm btn-primary mt-3">
                    <i class="icon-[tabler--plus]"></i> Add Menu Item
                </button>
            </div>

            <input type="hidden" name="items_json" id="items_json" />
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <script>
        let menuItemIndex = {{ count($existingItems ?? []) }};

        function addMenuItem() {
            const list = document.getElementById('menu-items-list');
            const html = `
                <div class="menu-item-row flex items-start gap-2 p-3 bg-base-200 rounded-lg border border-base-300" data-index="${menuItemIndex}">
                    <div class="flex flex-col gap-2 flex-1">
                        <div class="flex flex-wrap gap-2">
                            <input type="text" name="items[${menuItemIndex}][label]" placeholder="Label" class="input input-bordered input-sm flex-1 min-w-[200px]" required />
                            <input type="text" name="items[${menuItemIndex}][url]" placeholder="URL" class="input input-bordered input-sm flex-1 min-w-[200px]" required />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <input type="text" name="items[${menuItemIndex}][icon]" placeholder="Icon (optional)" class="input input-bordered input-sm flex-1 min-w-[140px]" />
                            <select name="items[${menuItemIndex}][target]" class="select select-bordered select-sm flex-1 min-w-[140px]">
                                <option value="_self">Same Window</option>
                                <option value="_blank">New Tab</option>
                            </select>
                            <input type="number" name="items[${menuItemIndex}][sort_order]" value="${menuItemIndex}" placeholder="Order" class="input input-bordered input-sm flex-1 min-w-[140px]" />
                        </div>
                        <button type="button" onclick="addChildItem(this, ${menuItemIndex})" class="btn btn-xs btn-ghost text-primary">
                            <i class="icon-[tabler--plus]"></i> Add Child Item
                        </button>
                    </div>
                    <button type="button" onclick="this.closest('.menu-item-row').remove()" class="btn btn-sm btn-error btn-outline mt-1">
                        <i class="icon-[tabler--trash]"></i>
                    </button>
                </div>`;
            list.insertAdjacentHTML('beforeend', html);
            menuItemIndex++;
        }

        function addChildItem(button, parentIndex) {
            const parentRow = button.closest('.menu-item-row');
            let childrenContainer = parentRow.querySelector('.ml-6');
            if (!childrenContainer) {
                childrenContainer = document.createElement('div');
                childrenContainer.className = 'ml-6 pl-3 border-l-2 border-base-300 space-y-2';
                button.before(childrenContainer);
            }
            const childIndex = childrenContainer.querySelectorAll('.menu-child-row').length;
            const html = `
                <div class="menu-child-row flex items-start gap-2 p-2 bg-base-100 rounded border border-base-300">
                    <div class="flex flex-col gap-2 flex-1">
                        <div class="flex flex-wrap gap-2">
                            <input type="text" name="items[${parentIndex}][children][${childIndex}][label]" placeholder="Label" class="input input-bordered input-sm flex-1 min-w-[200px]" required />
                            <input type="text" name="items[${parentIndex}][children][${childIndex}][url]" placeholder="URL" class="input input-bordered input-sm flex-1 min-w-[200px]" required />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <input type="text" name="items[${parentIndex}][children][${childIndex}][icon]" placeholder="Icon" class="input input-bordered input-sm flex-1 min-w-[140px]" />
                            <select name="items[${parentIndex}][children][${childIndex}][target]" class="select select-bordered select-sm flex-1 min-w-[140px]">
                                <option value="_self">Same Window</option>
                                <option value="_blank">New Tab</option>
                            </select>
                            <button type="button" onclick="this.closest('.menu-child-row').remove()" class="btn btn-sm btn-error btn-outline">
                                <i class="icon-[tabler--trash]"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            childrenContainer.insertAdjacentHTML('beforeend', html);
        }
    </script>
</x-layouts::app>