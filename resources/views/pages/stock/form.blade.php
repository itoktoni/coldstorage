<?php /** @var App\Models\Stock $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                @if(isset($model) && $model->exists)
                    <x-input col="6" name="stock_code" readonly />
                @endif
                <x-select col="6" name="stock_id_product" :options="$productOptions" />
                <x-select col="6" name="stock_id_lokasi" :options="$lokasiOptions" />
                <x-input col="2" name="stock_qty" type="number" />
                <x-input col="4" name="stock_expired_date" type="date" />
                <x-select col="6" name="stock_type" :options="$typeOptions" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
