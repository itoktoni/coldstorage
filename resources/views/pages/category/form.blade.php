<?php /** @var App\Models\Category $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-input col="6" name="name" />
                <x-input col="6" name="slug" />
                <x-input col="6" name="description" type="textarea" />
                <x-input col="6" name="parent_id" type="number" placeholder="Leave empty for top-level" />
                <x-input col="6" name="sort_order" type="number" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
