<?php /** @var App\Models\Po $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-input col="6" name="po_code" />
                <x-input col="6" name="po_tanggal" type="date" />
                <x-input col="6" name="po_supplier" />
                <x-input col="6" name="po_status" />
                <x-input col="12" name="po_keterangan" type="textarea" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
