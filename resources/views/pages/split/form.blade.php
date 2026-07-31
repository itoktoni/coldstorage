<?php /** @var App\Models\Split $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-select col="6" name="split_id_product" :options="$productOptions" />
                <x-select col="6" name="split_id_stock" :options="$stockOptions" />
                <x-input col="6" name="split_qty_new" type="number" />
                <x-input col="6" name="split_qty_old" type="number" />
                <x-input col="6" name="split_qty_waste" type="number" />
                <x-input col="6" name="split_tanggal" type="date" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
