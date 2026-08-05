<?php /** @var App\Models\Split $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)
                <x-select col="6" name="split_id_product_target" :options="$productOptions" />
                <x-select col="6" name="split_id_product_waste" :options="$productOptions" />
                <x-input col="6" name="split_qty_hasil" type="number" />
                <x-input col="6" name="split_qty_waste" type="number" />
                <x-input col="6" name="split_tanggal" type="date" />
                <x-input col="6" name="split_status" value="Draft" readonly />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
