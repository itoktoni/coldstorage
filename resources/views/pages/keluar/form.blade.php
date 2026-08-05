<?php /** @var App\Models\Keluar $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)
                <x-input col="6" name="out_code" />
                <x-input col="6" name="out_tanggal" type="date" />
                <x-input col="6" name="out_reff" />
                <x-input col="6" name="out_status" />
                <x-input col="12" name="out_catatan" type="textarea" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
