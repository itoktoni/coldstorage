<?php /** @var App\Models\Customer $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()" icon="groups">
            @bind($model ?? null)
                <x-input col="6" name="customer_nama" />
                <x-input col="6" name="customer_telepon" />
                <x-input col="12" name="customer_alamat" type="textarea" />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
