<?php /** @var App\Models\MasukDetail $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-input col="6" name="in_detail_tanggal" type="date" />
                <x-select col="6" name="in_detail_id_product" :options="$productOptions" />
                <x-input col="6" name="in_detail_qty" type="number" />
                <x-input col="6" name="in_detail_status" />
                <x-input col="12" name="in_detail_catatan" type="textarea" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
