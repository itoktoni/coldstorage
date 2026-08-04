<?php /** @var App\Models\Product $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())">
            @bind($model ?? null)
                <x-select col="4" name="product_category" :options="$categoryOptions ?? []" placeholder="Pilih kategori..." />
                <x-input col="4" name="product_nama" />
                <x-input col="4" name="product_harga" type="number" />
                <x-select col="4" name="product_status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="Pilih status..." />
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
