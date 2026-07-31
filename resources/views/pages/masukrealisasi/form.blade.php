<?php /** @var App\Models\MasukRealisasi $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-input col="6" name="in_realisasi_masuk_code" />
                <x-input col="6" name="in_realisasi_code" />
                <x-select col="6" name="in_realisasi_id_product" :options="$productOptions" />
                <x-input col="6" name="in_realisasi_qty" type="number" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
