<?php /** @var App\Models\Users $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="name" />
                <x-input col="6" name="email" />
                <x-input col="6" type="password" name="password" />
                <x-select col="6" name="role" :options="$role"/>

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
