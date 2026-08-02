<?php /** @var App\Models\Lokasi $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
            <x-input col="6" name="lokasi_code" />
            <x-select col="6" name="lokasi_code_gudang" :options="$gudangOptions" />
            <x-select col="6" name="lokasi_category" :options="$categoryOptions" />
            <x-input col="6" name="lokasi_nama" />
                <x-input col="6" name="lokasi_max_qty" type="number" step="1" min="0" label="Max Qty" placeholder="Kosong = tanpa batas" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
