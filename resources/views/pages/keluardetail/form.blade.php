<?php /** @var App\Models\KeluarDetail $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)
                <x-input col="6" name="out_detail_code_keluar" />
                <x-select col="6" name="out_detail_id_product" :options="$productOptions" />
                <x-input col="6" name="out_detail_code" />
                <x-input col="6" name="out_detail_qty" type="number" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
