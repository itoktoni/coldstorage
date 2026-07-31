<?php /** @var App\Models\KeluarRealisasi $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-input col="6" name="out_realisasi_code" />
                <x-input col="6" name="out_realisasi_id_detail" />
                <x-select col="6" name="out_realisasi_id_stock" :options="$stockOptions" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
