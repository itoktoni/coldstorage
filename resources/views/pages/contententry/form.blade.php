<?php
$allMeta = $model->exists ? $model->getAllMeta() : [];

// Also load container field meta directly (not in field groups)
$containerMetaValues = [];
if ($model->exists) {
    $containerMetaRows = \Illuminate\Support\Facades\DB::table('content_fields')
        ->where('content_entry_id', $model->id)
        ->get();
    foreach ($containerMetaRows as $row) {
        $containerMetaValues[$row->custom_field_id] = $row->value;
    }
}

// Determine content type
$contentTypeId = old('content_type_id', $model->content_type_id ?? request('content_type_id'));
$contentType = $contentTypeId ? \App\Models\Type::find($contentTypeId) : null;

// Load field groups for the selected content type
$fieldGroups = $contentType
    ? $contentType->fieldGroups()->where('is_active', true)->orderBy('sort_order')->get()
    : collect();


// Determine active field groups (sections)
// If _active_field_groups key exists in meta (even as empty array), it means user has explicitly managed sections.
// Only auto-detect from meta values if the key has NEVER been set (brand new entry, never saved with this feature).
$hasExplicitActiveGroups = array_key_exists('_active_field_groups', $model->meta ?? []);

$activeFieldGroups = $model->meta['_active_field_groups'] ?? [];
$requestedGroups = (array) request('add_field_group', []);
if (!empty($requestedGroups)) {
    $activeFieldGroups = array_merge($activeFieldGroups, $requestedGroups);
}
// Only auto-detect on brand new entries that have never had sections explicitly managed
if ($model->exists && empty($activeFieldGroups) && !$hasExplicitActiveGroups) {
    foreach ($fieldGroups as $group) {
        foreach ($group->fields as $field) {
            $value = $allMeta[$field->name] ?? null;
            if (!empty($value)) {
                $activeFieldGroups[] = $group->id;
                break;
            }
        }
    }
    $activeFieldGroups = array_values(array_unique($activeFieldGroups));
}
$activeFieldGroups = array_map('intval', $activeFieldGroups);
$activeGroups = $fieldGroups->whereIn('id', $activeFieldGroups)->values();
// Preserve explicit order from meta
if (!empty($activeFieldGroups)) {
    $activeGroups = $activeGroups->sortBy(function ($group) use ($activeFieldGroups) {
        return array_search($group->id, $activeFieldGroups);
    })->values();
}

$categories = \App\Models\Category::pluck('name', 'id');
$tags = \App\Models\Tag::pluck('name', 'id');
$selectedCategories = old('category_ids', $model->exists ? $model->categories->pluck('id')->toArray() : []);
$selectedTags = old('tag_ids', $model->exists ? $model->tags->pluck('id')->toArray() : []);

// Layout icons
$layoutIcons = [
    'hero' => 'icon-[tabler--photo]',
    'slider' => 'icon-[tabler--photo]',
    'cta' => 'icon-[tabler--speakerphone]',
    'image_left_right' => 'icon-[tabler--columns]',
    'text_block' => 'icon-[tabler--align-left]',
    'gallery' => 'icon-[tabler--photo]',
    'faq' => 'icon-[tabler--help-circle]',
    'pricing' => 'icon-[tabler--tag]',
    'footer' => 'icon-[tabler--shoe]',
];

$layoutColors = [
    'hero' => 'bg-purple-100 text-purple-700 border-purple-200',
    'slider' => 'bg-blue-100 text-blue-700 border-blue-200',
    'cta' => 'bg-green-100 text-green-700 border-green-200',
    'image_left_right' => 'bg-orange-100 text-orange-700 border-orange-200',
    'text_block' => 'bg-gray-100 text-gray-700 border-gray-200',
    'gallery' => 'bg-pink-100 text-pink-700 border-pink-200',
    'faq' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
    'pricing' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
    'footer' => 'bg-slate-100 text-slate-700 border-slate-200',
];

?>
<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        {{-- Main Content Card --}}
        <x-card label="Content">
            @bind($model ?? null)
                <x-select col="6" name="content_type_id" default="{{ request()->get('content_type_id') ?? $model->content_type_id ?? '' }}" :options="$contentTypes" label="Type" />
                <x-input col="6" name="title" />
                <x-input col="6" name="slug" />
                <x-select col="6" name="status" :options="['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']" />
                <div class="col-span-12">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Excerpt</label>
                    <textarea name="excerpt" rows="3" class="w-full px-4 py-3 bg-white border {{ $errors->has('excerpt') ? 'border-error' : 'border-outline-variant' }} rounded-lg focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none transition-all font-body-sm">{{ old('excerpt', $model->excerpt ?? '') }}</textarea>
                </div>
                <div class="col-span-12">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Content</label>
                    <textarea name="content" rows="10" class="w-full cms-wysiwyg" data-wysiwyg="1">{{ old('content', $model->content ?? '') }}</textarea>
                </div>

                <x-input col="2" name="menu_order" type="number" />
                <x-input col="4" name="published_at" type="datetime-local" label="Published At" />

                 <div class="col-span-12 md:col-span-6">
                    @php $fiValue = old('featured_image', $model->featured_image ?? ''); $pickerId = 'picker_featured_image'; @endphp
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Featured Image</label>
                    <div id="{{ $pickerId }}" class="image-picker-wrapper">
                        <div id="{{ $pickerId }}_dropzone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 hover:bg-blue-50/30 transition-colors cursor-pointer"
                             ondragover="event.preventDefault(); this.classList.add('border-blue-500','bg-blue-50')"
                             ondragleave="this.classList.remove('border-blue-500','bg-blue-50')"
                             ondrop="handleImageDrop(event, '{{ $pickerId }}')">
                            <div id="{{ $pickerId }}_dropzone_content" @if($fiValue) style="display:none" @endif>
                                <i class="icon-[tabler--cloud-upload] text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500">Drag & drop image here, or click to select</p>
                                <input type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       onchange="handleImageFileSelect(event, '{{ $pickerId }}')">
                            </div>
                            <div id="{{ $pickerId }}_preview_wrap" class="flex items-center gap-3 justify-center" @if(!$fiValue) style="display:none" @endif>
                                <img id="{{ $pickerId }}_preview" src="{{ $fiValue }}" class="h-24 w-auto object-cover rounded border border-gray-200" alt="Preview">
                                <div class="flex flex-col gap-1">
                                    <button type="button" onclick="event.stopPropagation(); openImageBrowser('{{ $pickerId }}')" class="text-blue-500 hover:text-blue-700 text-xs">
                                        <i class="icon-[tabler--switch-horizontal]"></i> Change
                                    </button>
                                    <button type="button" onclick="event.stopPropagation(); imgPickerRemove('{{ $pickerId }}')" class="text-red-500 hover:text-red-700 text-xs">
                                        <i class="icon-[tabler--trash]"></i> Remove
                                    </button>
                                </div>
                            </div>
                            <div id="{{ $pickerId }}_upload_progress" class="hidden mt-2">
                                <div class="flex items-center gap-2 justify-center">
                                    <i class="icon-[tabler--loader] animate-spin text-blue-500"></i>
                                    <span class="text-sm text-blue-600">Uploading...</span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="featured_image" id="{{ $pickerId }}_input" value="{{ $fiValue }}">
                        <div class="mt-2 flex gap-2">
                            <button type="button" onclick="openImageBrowser('{{ $pickerId }}')" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2 text-sm">
                                <i class="icon-[tabler--photo] text-xs"></i> Browse Media Library
                            </button>
                            <label class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2 text-sm cursor-pointer">
                                <i class="icon-[tabler--upload] text-xs"></i> Upload File
                                <input type="file" accept="image/*" class="hidden" onchange="handleImageFileSelect(event, '{{ $pickerId }}')">
                            </label>
                        </div>
                    </div>
                </div>

            @endbind
        </x-card>

        {{-- Categories --}}
        <x-card label="Categories">
            <x-select name="category_ids[]" label="Categories" :options="$categories" :multiple="true" placeholder="Select categories" class="search" :default="$selectedCategories" />
        </x-card>

        {{-- Tags --}}
        <x-card label="Tags">
            <x-select name="tag_ids[]" label="Tags" :options="$tags" :multiple="true" placeholder="Select tags" class="search" :default="$selectedTags" />
        </x-card>

         @if(!empty($contentType))
        {{-- Dynamic Field Group Sections --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card mt-6">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">layers</span>
                Sections
            </h3>

            @if(!$contentType)
                <div class="text-center py-8 text-gray-400">
                    <i class="icon-[tabler--arrow-up] text-3xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">Select a content type above</p>
                    <p class="text-xs mt-1">Choose a content type to see available sections</p>
                </div>
            @elseif($fieldGroups->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    <i class="icon-[tabler--folder-open] text-3xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No sections available for this content type</p>
                    <p class="text-xs mt-1 mb-3">Create a section and assign it to <strong>{{ $contentType->name }}</strong></p>
                    <a href="{{ route('section.getCreate') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                        <i class="icon-[tabler--plus] text-[10px]"></i> Create Section
                    </a>
                </div>
            @else
                {{-- Active sections container --}}
                <div id="active-sections" class="space-y-4">
                    @foreach($activeGroups as $group)
                    <div class="section-card section-item border border-gray-200 rounded-lg bg-white shadow-sm" data-group-id="{{ $group->id }}">
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 rounded-t-lg section-handle select-none cursor-pointer" onclick="toggleSection(this)">
                            <div class="flex items-center gap-3">
                                <i class="icon-[tabler--grip-vertical] text-gray-300"></i>
                                <span class="section-order text-xs font-medium text-gray-500 w-6">{{ $loop->iteration }}.</span>
                                <span class="text-sm font-semibold text-gray-800">{{ $group->name }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="event.stopPropagation(); toggleSection(this)" class="p-1.5 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-200 transition-colors" title="Collapse/Expand">
                                    <i class="icon-[tabler--chevron-up] text-xs transition-transform"></i>
                                </button>
                                <button type="button" onclick="event.stopPropagation(); removeGroupSection(this)" class="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors" title="Remove Section">
                                    <i class="icon-[tabler--trash] text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4 space-y-4 section-fields">
                            @foreach($group->fields as $field)
                                @php
                                    $fieldValue = $allMeta[$field->name] ?? $field->default_value;
                                    if (is_string($fieldValue)) {
                                        $decoded = json_decode($fieldValue, true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            $fieldValue = $decoded;
                                        }
                                    }
                                @endphp
                                @if($field->type === 'container')
                                    @include('pages.contententry.partials.container-field', ['field' => $field, 'value' => $fieldValue])
                                @else
                                    @include('pages.contententry.partials.basic-field', ['field' => $field, 'value' => $fieldValue])
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Hidden inputs to track active group IDs --}}
                <div id="active-group-ids">
                    @foreach($activeGroups as $group)
                        <input type="hidden" name="active_field_groups[]" value="{{ $group->id }}" data-group-id="{{ $group->id }}">
                    @endforeach
                </div>

                {{-- Available Sections List --}}
                <div class="mt-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Available Sections</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($fieldGroups as $group)
                            @php
                                $isAdded = in_array($group->id, $activeFieldGroups);
                            @endphp
                            <button type="button"
                                data-group-id="{{ $group->id }}"
                                data-group-name="{{ $group->name }}"
                                onclick="addGroupSection(this)"
                                {{ $isAdded ? 'disabled' : '' }}
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg {{ $isAdded ? 'opacity-60 cursor-not-allowed border-green-200 bg-green-50' : 'hover:border-blue-500 hover:text-blue-600 hover:bg-blue-50 cursor-pointer' }} transition-colors text-left group shadow-sm">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $isAdded ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-600' }} text-xs">
                                    <i class="{{ $isAdded ? 'icon-[tabler--check]' : 'icon-[tabler--plus]' }}"></i>
                                </span>
                                <span class="text-sm font-medium {{ $isAdded ? 'text-green-700' : 'text-gray-800 group-hover:text-blue-600' }} transition-colors">{{ $group->name }}{{ $isAdded ? ' (added)' : '' }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                @if($activeGroups->isEmpty())
                <div id="empty-sections-state" class="text-center py-12 text-gray-400">
                    <i class="icon-[tabler--stack] text-4xl mb-3 block opacity-30"></i>
                    <p class="text-sm font-medium">No sections yet</p>
                    <p class="text-xs mt-1">Choose a section above to start building this content</p>
                </div>
                @endif
            @endif
        </div>
        @endif

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <style>
        .section-card.collapsed .section-fields { display: none; }
        .section-card.collapsed .icon-[tabler--chevron-up] { transform: rotate(180deg); }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        // Reload form when content type changes
        document.addEventListener('DOMContentLoaded', function() {
            var contentTypeSelect = document.querySelector('select[name="content_type_id"]');
            if (contentTypeSelect) {
                contentTypeSelect.addEventListener('change', function() {
                    var url = new URL(window.location.href);
                    url.searchParams.set('content_type_id', this.value);
                    window.location.href = url.toString();
                });
            }

            // Initialize Sortable for drag and drop
            var sortableEl = document.getElementById('active-sections');
            if (sortableEl) {
                new Sortable(sortableEl, {
                    handle: '.section-card > .section-handle',
                    animation: 150,
                    ghostClass: 'bg-blue-50',
                    onEnd: function(evt) {
                        updateActiveGroupOrder();
                    }
                });
            }

            // Update order before form submit
            var form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    updateActiveGroupOrder();
                });
            }

            // Close menus when clicking outside
            document.addEventListener('click', function(e) {
                document.querySelectorAll('[id^="add-menu-"]').forEach(function(menu) {
                    var wrapper = menu.closest('[id^="add-section-wrapper-"]');
                    if (wrapper && !wrapper.contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });
            });
        });

        // Toggle add section dropdown menu
        function toggleAddMenu(fieldName) {
            var menu = document.getElementById('add-menu-' + fieldName);
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        // Add a field group section via AJAX (no page refresh)
        async function addGroupSection(btn) {
            var groupId = btn.dataset.groupId;

            // Check if already active
            var existing = document.querySelector('.section-card[data-group-id="' + groupId + '"]');
            if (existing) {
                existing.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            try {
                var res = await fetch('/cms/content-entry/field-group-html/' + groupId, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                var data = await res.json();

                if (data.html) {
                    // Remove empty state if visible
                    var emptyState = document.getElementById('empty-sections-state');
                    if (emptyState) emptyState.classList.add('hidden');

                    // Append the new section card
                    var container = document.getElementById('active-sections');
                    container.insertAdjacentHTML('beforeend', data.html);

                    // Add hidden input for tracking
                    var hiddenInputs = document.getElementById('active-group-ids');
                    hiddenInputs.insertAdjacentHTML('beforeend',
                        '<input type="hidden" name="active_field_groups[]" value="' + groupId + '" data-group-id="' + groupId + '">'
                    );

                    // Disable the add button
                    btn.disabled = true;
                    btn.classList.add('opacity-60', 'cursor-not-allowed', 'border-green-200', 'bg-green-50');
                    btn.classList.remove('hover:border-blue-500', 'hover:text-blue-600', 'hover:bg-blue-50', 'cursor-pointer');
                    var iconSpan = btn.querySelector('span:first-child');
                    if (iconSpan) {
                        iconSpan.classList.remove('bg-gray-100', 'text-gray-500');
                        iconSpan.classList.add('bg-green-100', 'text-green-600');
                        var icon = iconSpan.querySelector('i');
                        if (icon) {
                            icon.classList.remove('icon-[tabler--plus]');
                            icon.classList.add('icon-[tabler--check]');
                        }
                    }
                    var textSpan = btn.querySelector('span:last-child');
                    if (textSpan) {
                        textSpan.classList.remove('text-gray-800');
                        textSpan.classList.add('text-green-700');
                        textSpan.textContent = textSpan.textContent.replace(' (added)', '') + ' (added)';
                    }

                    // Scroll to the new section
                    var newCard = container.lastElementChild;
                    if (newCard) {
                        newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }

                    updateActiveGroupOrder();

                    // Init WYSIWYG editors for the new section
                    setTimeout(window.initAllWysiwyg, 100);
                }
            } catch (e) {
                console.error('Failed to load section:', e);
                alert('Failed to load section. Please try again.');
            }
        }

        // Remove a field group section
        function removeGroupSection(btn) {
            var card = btn.closest('.section-card');
            if (!card) return;

            var groupId = card.dataset.groupId;

            // Remove hidden input
            var hiddenInput = document.querySelector('#active-group-ids input[data-group-id="' + groupId + '"]');
            if (hiddenInput) {
                hiddenInput.remove();
            }

            card.style.opacity = '0.5';
            setTimeout(function() {
                card.remove();
                updateActiveGroupOrder();
                var container = document.getElementById('active-sections');
                if (container.children.length === 0) {
                    var emptyState = document.getElementById('empty-sections-state');
                    if (emptyState) {
                        emptyState.classList.remove('hidden');
                    }
                }

                // Re-enable the "add" button for this section
                var addBtn = document.querySelector('button[data-group-id="' + groupId + '"][onclick="addGroupSection(this)"]');
                if (addBtn) {
                    addBtn.disabled = false;
                    addBtn.classList.remove('opacity-60', 'cursor-not-allowed', 'border-green-200', 'bg-green-50');
                    addBtn.classList.add('hover:border-blue-500', 'hover:text-blue-600', 'hover:bg-blue-50', 'cursor-pointer');
                    // Update icon
                    var iconSpan = addBtn.querySelector('span:first-child');
                    if (iconSpan) {
                        iconSpan.classList.remove('bg-green-100', 'text-green-600');
                        iconSpan.classList.add('bg-gray-100', 'text-gray-500');
                        var icon = iconSpan.querySelector('i');
                        if (icon) {
                            icon.classList.remove('icon-[tabler--check]');
                            icon.classList.add('icon-[tabler--plus]');
                        }
                    }
                    // Update text
                    var textSpan = addBtn.querySelector('span:last-child');
                    if (textSpan) {
                        textSpan.classList.remove('text-green-700');
                        textSpan.classList.add('text-gray-800');
                        textSpan.textContent = textSpan.textContent.replace(' (added)', '');
                    }
                }
            }, 200);
        }

        // Toggle section collapse/expand
        function toggleSection(btn) {
            var card = btn.closest('.section-card');
            if (card) {
                card.classList.toggle('collapsed');
            }
        }

        // Update hidden inputs order after drag and drop
        function updateActiveGroupOrder() {
            var container = document.getElementById('active-sections');
            var idsContainer = document.getElementById('active-group-ids');
            if (!container || !idsContainer) return;

            var newOrder = [];
            container.querySelectorAll('.section-card').forEach(function(card, index) {
                var groupId = card.dataset.groupId;
                newOrder.push(groupId);

                var orderSpan = card.querySelector('.section-order');
                if (orderSpan) {
                    orderSpan.textContent = (index + 1) + '.';
                }
            });

            idsContainer.innerHTML = '';
            newOrder.forEach(function(groupId) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'active_field_groups[]';
                input.value = groupId;
                input.dataset.groupId = groupId;
                idsContainer.appendChild(input);
            });
        }

        // Remove section
        function removeSection(btn) {
            var section = btn.closest('.section-item');
            if (section) {
                section.style.opacity = '0.5';
                section.style.transition = 'opacity 0.2s';
                setTimeout(function() {
                    section.remove();
                    checkEmptyStates();
                }, 200);
            }
        }

        // Remove a single field inside a section
        function removeSectionField(btn) {
            var field = btn.closest('.section-field');
            if (field) field.remove();
        }

        // Check empty states
        function checkEmptyStates() {
            document.querySelectorAll('.container-builder').forEach(function(container) {
                var list = container.querySelector('.sections-list');
                var emptyState = container.querySelector('[id^="empty-state-"]');
                if (list && emptyState) {
                    if (list.children.length === 0) {
                        emptyState.classList.remove('hidden');
                    } else {
                        emptyState.classList.add('hidden');
                    }
                }
            });
        }

        // Store for subFields data (avoids JSON in HTML attributes)
        const subFieldsStore = {};

        // Section index tracker
        const sectionIndexes = {};

        function initSectionIndex(fieldName) {
            if (!(fieldName in sectionIndexes)) {
                sectionIndexes[fieldName] = document.querySelectorAll('#container-' + fieldName + ' .section-item').length;
            }
            return sectionIndexes[fieldName]++;
        }

        // Layout icon/color maps
        const layoutIcons = @json($layoutIcons);
        const layoutColors = @json($layoutColors);

        // Escape HTML
        function escHtml(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function handleAddSection(btn) {
            var fieldName = btn.dataset.container;
            var layoutName = btn.dataset.layout;
            var layoutLabel = btn.dataset.label;
            var fields = JSON.parse(btn.dataset.fields || '[]');
            addSection(fieldName, layoutName, layoutLabel, fields);
            toggleAddMenu(fieldName);
        }

        function handleAddRepeaterItem(btn) {
            var repeaterId = btn.dataset.repeater;
            var containerField = btn.dataset.container;
            var secIdx = parseInt(btn.dataset.secIdx);
            var fName = btn.dataset.fname;
            // Store subFields from PHP data attribute for future use
            if (!subFieldsStore[repeaterId] && btn.dataset.subfields) {
                subFieldsStore[repeaterId] = JSON.parse(btn.dataset.subfields || '[]');
            }
            addRepeaterItem(repeaterId, containerField, secIdx, fName);
        }

        function addSection(fieldName, layoutName, layoutLabel, fields) {
            const idx = initSectionIndex(fieldName);
            const list = document.querySelector('#container-' + fieldName + ' .sections-list');

            var emptyState = document.getElementById('empty-state-' + fieldName);
            if (emptyState) emptyState.classList.add('hidden');

            const icon = layoutIcons[layoutName] || 'icon-[tabler--puzzle]';
            const colorClass = layoutColors[layoutName] || 'bg-gray-100 text-gray-700 border-gray-200';

            // Build fields HTML
            let fieldsHtml = '';
            if (fields && fields.length > 0) {
                fields.forEach(function(f) {
                    const fName = f.name;
                    const fType = f.type || 'text';
                    const fLabel = f.label || fName;

                    // Check if this is a repeater (container with mode multiple)
                    if (fType === 'container' && f.mode === 'multiple') {
                        const repeaterId = fieldName + '_' + idx + '_' + fName;
                        const subFields = f.fields || [];
                        fieldsHtml += buildRepeaterHtml(repeaterId, fieldName, idx, fName, fLabel, subFields, []);
                    } else {
                        const inputName = 'meta[' + fieldName + '][' + idx + '][' + fName + ']';
                        fieldsHtml += '<div class="section-field group relative"><button type="button" onclick="removeSectionField(this)" class="absolute top-0 right-0 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity z-10" title="Remove Field"><i class="icon-[tabler--x] text-xs"></i></button>' + buildFieldHtml(fName, fType, fLabel, inputName, '', f) + '</div>';
                    }
                });
            }

            const html = '<div class="section-item border border-gray-200 rounded-lg bg-white shadow-sm" data-index="' + idx + '">'
                + '<div class="flex items-center justify-between px-4 py-3 bg-gray-50 rounded-t-lg cursor-move section-handle select-none">'
                + '<div class="flex items-center gap-3">'
                + '<i class="icon-[tabler--grip-vertical] text-gray-300"></i>'
                + '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border ' + colorClass + '">'
                + '<i class="' + icon + ' text-[10px]"></i> ' + escHtml(layoutLabel)
                + '</span>'
                + '</div>'
                + '<div class="flex items-center gap-1">'
                + '<button type="button" onclick="toggleSection(this)" class="p-1.5 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-200 transition-colors" title="Collapse/Expand">'
                + '<i class="icon-[tabler--chevron-up] text-xs transition-transform"></i>'
                + '</button>'
                + '<button type="button" onclick="removeSection(this)" class="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors" title="Remove Section">'
                + '<i class="icon-[tabler--trash] text-xs"></i>'
                + '</button>'
                + '</div>'
                + '</div>'
                + '<input type="hidden" name="meta[' + fieldName + '][' + idx + '][_layout]" value="' + escHtml(layoutName) + '" />'
                + '<div class="section-fields px-4 py-4 space-y-3 border-t border-gray-100">'
                + fieldsHtml
                + '</div>'
                + '</div>';

            list.insertAdjacentHTML('beforeend', html);

            var newItem = list.lastElementChild;
            if (newItem) {
                newItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Build a single field HTML
        function buildFieldHtml(fName, fType, fLabel, inputName, value, fieldDef) {
            value = value || '';
            let inputHtml = '';
            const esc = escHtml;
            const lbl = esc(fLabel);
            const nm = esc(inputName);

            if (['text', 'url', 'email', 'color', 'slug'].includes(fType)) {
                inputHtml = '<input type="' + (fType === 'slug' ? 'text' : fType) + '" name="' + nm + '" value="' + esc(value) + '" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="' + lbl + '" />';
            } else if (['textarea', 'wysiwyg'].includes(fType)) {
                inputHtml = '<textarea name="' + nm + '" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="' + lbl + '">' + esc(value) + '</textarea>';
            } else if (fType === 'toggle') {
                inputHtml = '<label class="relative inline-flex items-center cursor-pointer"><input type="hidden" name="' + nm + '" value="0"><input type="checkbox" name="' + nm + '" value="1" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"><span class="ml-2 text-sm text-gray-600">' + lbl + '</span></label>';
            } else if (fType === 'image') {
                inputHtml = '<input type="text" name="' + nm + '" value="' + esc(value) + '" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="https://example.com/image.jpg" />';
            } else if (fType === 'select') {
                const opts = fieldDef && fieldDef.config && fieldDef.config.options ? fieldDef.config.options : {};
                let optHtml = '<option value="">-- Select --</option>';
                for (const [v, l] of Object.entries(opts)) {
                    optHtml += '<option value="' + esc(v) + '">' + esc(l) + '</option>';
                }
                inputHtml = '<select name="' + nm + '" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">' + optHtml + '</select>';
            } else if (['number', 'integer', 'float'].includes(fType)) {
                inputHtml = '<input type="number" name="' + nm + '" value="' + esc(value) + '" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />';
            } else if (fType === 'assets') {
                inputHtml = '<input type="text" name="' + nm + '" value="' + esc(value) + '" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="File URLs" />';
            } else {
                inputHtml = '<input type="text" name="' + nm + '" value="' + esc(value) + '" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />';
            }

            if (fType === 'toggle') {
                return inputHtml;
            }
            return '<label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">' + lbl + '</label>' + inputHtml;
        }

        // Build repeater HTML
        function buildRepeaterHtml(repeaterId, containerField, secIdx, fName, fLabel, subFields, items) {
            const esc = escHtml;
            // Store subFields in JS object (not in HTML attribute)
            subFieldsStore[repeaterId] = subFields;

            let html = '<div class="repeater-wrapper border border-gray-200 rounded-lg bg-gray-50 p-3" id="repeater-' + esc(repeaterId) + '">';
            html += '<div class="flex items-center mb-3">';
            html += '<label class="text-xs font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-list mr-1"></i> ' + esc(fLabel) + '</label>';
            html += '</div>';
            html += '<div class="repeater-items space-y-2" id="repeater-items-' + esc(repeaterId) + '">';

            items.forEach(function(item, itemIdx) {
                html += buildRepeaterItemHtml(repeaterId, containerField, secIdx, fName, subFields, itemIdx, item);
            });

            html += '</div>';

            if (items.length === 0) {
                html += '<div class="repeater-empty text-center py-4 text-gray-400 text-xs" id="repeater-empty-' + esc(repeaterId) + '"><i class="fas fa-inbox mb-1 block text-lg opacity-30"></i>No items yet. Click "Add" below.</div>';
            }

            html += '<div class="mt-3 text-right">';
            html += '<button type="button" onclick="addRepeaterItem(\'' + esc(repeaterId) + '\', \'' + esc(containerField) + '\', ' + secIdx + ', \'' + esc(fName) + '\')" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 transition-colors border border-blue-200"><i class="icon-[tabler--plus] text-[10px]"></i> Add ' + esc(fLabel.replace(/s$/, '')) + '</button>';
            html += '</div>';

            html += '</div>';
            return html;
        }

        // Build a single repeater item
        function buildRepeaterItemHtml(repeaterId, containerField, secIdx, fName, subFields, itemIdx, item) {
            const esc = escHtml;
            let html = '<div class="repeater-item bg-white border border-gray-200 rounded-md p-3 space-y-3" data-index="' + itemIdx + '">';
            html += '<div class="flex items-center justify-between"><span class="text-xs text-gray-400 font-medium">#' + (itemIdx + 1) + '</span>';
            html += '<button type="button" onclick="removeRepeaterItem(this, \'' + esc(repeaterId) + '\')" class="text-gray-400 hover:text-red-500 transition-colors" title="Remove"><i class="icon-[tabler--trash] text-xs"></i></button></div>';

            subFields.forEach(function(sf) {
                const sfName = sf.name;
                const sfType = sf.type || 'text';
                const sfLabel = sf.label || sfName;
                const sfValue = item[sfName] || sf.default_value || '';
                const sfFieldName = 'meta[' + containerField + '][' + secIdx + '][' + fName + '][' + itemIdx + '][' + sfName + ']';
                html += '<div>' + buildFieldHtml(sfName, sfType, sfLabel, sfFieldName, sfValue, sf) + '</div>';
            });

            html += '</div>';
            return html;
        }

        // Track repeater item indexes
        function getRepeaterItemCount(repeaterId) {
            var container = document.getElementById('repeater-items-' + repeaterId);
            return container ? container.children.length : 0;
        }

        // Add a repeater item
        function addRepeaterItem(repeaterId, containerField, secIdx, fName) {
            var subFields = subFieldsStore[repeaterId] || [];
            var container = document.getElementById('repeater-items-' + repeaterId);
            var emptyState = document.getElementById('repeater-empty-' + repeaterId);
            if (emptyState) emptyState.remove();

            var itemIdx = getRepeaterItemCount(repeaterId);
            var html = buildRepeaterItemHtml(repeaterId, containerField, secIdx, fName, subFields, itemIdx, {});
            container.insertAdjacentHTML('beforeend', html);

            // Scroll to new item
            var newItem = container.lastElementChild;
            if (newItem) {
                newItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Add a repeater item for server-rendered multiple containers (clones last item or empty template)
        function addRepeaterItemFromServer(fieldName) {
            var container = document.getElementById('repeater-items-' + fieldName);
            var emptyState = document.getElementById('repeater-empty-' + fieldName);
            if (!container) return;

            if (emptyState) emptyState.classList.add('hidden');

            var lastItem = container.querySelector('.repeater-item:last-of-type');
            var newItem;
            if (lastItem) {
                newItem = lastItem.cloneNode(true);
                // Use max existing index + 1 to avoid collisions after deletions
                var itemIdx = 0;
                container.querySelectorAll('.repeater-item').forEach(function(el) {
                    var idx = parseInt(el.dataset.index, 10);
                    if (!isNaN(idx) && idx >= itemIdx) itemIdx = idx + 1;
                });
                var oldIdx = lastItem.dataset.index;
                newItem.querySelectorAll('[name]').forEach(function(input) {
                    // Replace the LAST [oldIdx] bracket (repeater index), not the first
                    var search = '[' + oldIdx + ']';
                    var pos = input.name.lastIndexOf(search);
                    if (pos !== -1) {
                        input.name = input.name.substring(0, pos) + '[' + itemIdx + ']' + input.name.substring(pos + search.length);
                    }
                    input.value = input.type === 'checkbox' || input.type === 'radio' ? input.checked : '';
                    if (input.tagName === 'TEXTAREA') input.value = '';
                });
                newItem.querySelectorAll('[data-index]').forEach(function(el) {
                    el.dataset.index = itemIdx;
                });
                newItem.querySelectorAll('.text-xs.text-gray-400.font-medium').forEach(function(el) {
                    el.textContent = '#' + (itemIdx + 1);
                });
            } else {
                // Clone from the hidden template for a clean empty item
                var tpl = document.getElementById('repeater-template-' + fieldName);
                if (!tpl) return;
                var itemIdx = 0;
                var tplHtml = tpl.innerHTML
                    .replace(/__IDX_PLUS_1__/g, String(itemIdx + 1))
                    .replace(/__IDX__/g, String(itemIdx));
                var wrapper = document.createElement('div');
                wrapper.innerHTML = tplHtml.trim();
                newItem = wrapper.firstElementChild;
            }

            container.appendChild(newItem);
            newItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Show the empty state when a repeater has no items left
        function checkRepeaterEmpty(fieldName) {
            var container = document.getElementById('repeater-items-' + fieldName);
            var emptyState = document.getElementById('repeater-empty-' + fieldName);
            if (container && emptyState) {
                emptyState.classList.toggle('hidden', container.children.length > 0);
            }
        }

        // Remove a repeater item and re-index remaining items sequentially
        function removeRepeaterItem(btn, fieldName) {
            var container = document.getElementById('repeater-items-' + fieldName);
            if (!container) return;
            var item = btn.closest('.repeater-item');
            if (!item) return;
            item.remove();
            // Re-index all remaining items so names stay sequential (no gaps/collisions)
            container.querySelectorAll('.repeater-item').forEach(function(el, newIdx) {
                var oldIdx = el.dataset.index;
                el.dataset.index = newIdx;
                var label = el.querySelector('.text-xs.text-gray-400.font-medium');
                if (label) label.textContent = '#' + (newIdx + 1);
                if (oldIdx !== undefined && String(oldIdx) !== String(newIdx)) {
                    var search = '[' + oldIdx + ']';
                    el.querySelectorAll('[name]').forEach(function(input) {
                        var pos = input.name.lastIndexOf(search);
                        if (pos !== -1) {
                            input.name = input.name.substring(0, pos) + '[' + newIdx + ']' + input.name.substring(pos + search.length);
                        }
                    });
                }
            });
            checkRepeaterEmpty(fieldName);
        }

        // --- WYSIWYG (TinyMCE via CDN) ---
        window.initWysiwyg = function(el) {
            if (!el || el.dataset.wysiwygInit) return;
            if (window.tinymce && tinymce.get(el.id)) return;
            el.dataset.wysiwygInit = '1';
            tinymce.init({
                target: el,
                height: 280,
                menubar: false,
                plugins: 'lists link image table code autolink fullscreen media',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist | link image medialibrary table | removeformat code fullscreen',
                branding: false,
                promotion: false,
                relative_urls: false,
                block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre',
                // Use our Media Library modal instead of TinyMCE's default uploader
                file_picker_types: 'image',
                file_picker_callback: function(cb, value, meta) {
                    if (meta.fileType === 'image') {
                        if (typeof imgBrowser === 'undefined') return;
                        imgBrowser.open(null, function(url) {
                            cb(url, { alt: '' });
                        });
                    }
                },
                setup: function(editor) {
                    editor.on('init', function() { el.dataset.wysiwygInit = '1'; });
                    // Dedicated Media Library button: opens our modal and inserts the image
                    editor.ui.registry.addButton('medialibrary', {
                        text: 'Media',
                        icon: 'browse',
                        tooltip: 'Insert from Media Library',
                        onAction: function() {
                            if (typeof imgBrowser === 'undefined') return;
                        imgBrowser.open(null, function(url) {
                            editor.insertContent('<img src="' + url + '" alt="" />');
                        });
                        }
                    });
                }
            });
        };

        window.initAllWysiwyg = function() {
            if (!window.tinymce) return;
            var editors = document.querySelectorAll('textarea.cms-wysiwyg:not([data-wysiwyg-init])');
            editors.forEach(function(el, i) {
                if (!el.id) el.id = 'wysiwyg-' + Math.random().toString(36).slice(2);
                setTimeout(function() {
                    window.initWysiwyg(el);
                }, i * 300);
            });
        };

        // Re-init editors for any newly added repeater items
        var _origAddRepeaterItemFromServer = window.addRepeaterItemFromServer;
        window.addRepeaterItemFromServer = function(fieldName) {
            _origAddRepeaterItemFromServer(fieldName);
            setTimeout(window.initAllWysiwyg, 50);
        };

        // Auto-fill slug from title until user manually edits the slug
        (function() {
            var titleEl = document.querySelector('input[name="title"]');
            var slugEl = document.querySelector('input[name="slug"]');
            if (!titleEl || !slugEl) return;
            var slugEdited = false;
            slugEl.addEventListener('input', function() { slugEdited = true; });
            titleEl.addEventListener('input', function() {
                if (slugEdited) return;
                slugEl.value = titleEl.value.toString().toLowerCase().trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            });
        })();
    </script>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            (function() {
                var tries = 0;
                var iv = setInterval(function() {
                    if (window.tinymce) {
                        clearInterval(iv);
                        window.initAllWysiwyg();
                    } else if (++tries > 40) {
                        clearInterval(iv);
                    }
                }, 150);
            })();
        </script>
    @endpush
</x-layouts::app>