<?php /** @var App\Models\Stock $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-input col="6" name="stock_code" />
                <x-select col="6" name="stock_id_product" :options="$productOptions" />
                <x-select col="6" name="stock_id_lokasi" :options="$lokasiOptions" />
                <x-input col="6" name="stock_qty" type="number" />
                <x-input col="6" name="stock_expired_date" type="date" />
                <x-input col="6" name="stock_type" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
