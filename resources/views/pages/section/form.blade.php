<?php /** @var App\Models\Section $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => 'Sections'], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card label="Sections">
            @bind($model ?? null)
                <x-input col="6" name="name" />
                <x-input col="6" name="description" type="textarea" />
                <x-select col="6" name="content_type_id" :options="$contentTypes" label="Type"/>
                <x-input col="6" name="sort_order" type="number" />
                <x-select col="6" name="is_active" :options="['1' => 'Active', '0' => 'Inactive']"/>
            @endbind
        </x-card>

        <x-card label="Field Selection">
            @php
                $allFields = $allFields ?? collect();
                $selectedFields = array_map('strval', $model->field_ids ?? []);
            @endphp
            <div class="col-span-12">
                <label class="block text-sm font-medium text-gray-700 mb-3">Select which custom fields belong to this group:</label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($allFields as $field)
                    @php $isSelected = in_array((string)$field->id, $selectedFields); @endphp
                    <label class="flex items-center gap-3 px-3 py-2.5 border rounded-lg cursor-pointer transition-colors {{ $isSelected ? 'bg-blue-50 border-blue-200' : 'bg-white border-gray-200 hover:bg-gray-50' }}">
                        <input type="checkbox" name="field_ids[]" value="{{ $field->id }}"
                            {{ $isSelected ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate">{{ $field->label }}</div>
                            <div class="text-xs text-gray-400">{{ $field->name }} · {{ $field->type }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
