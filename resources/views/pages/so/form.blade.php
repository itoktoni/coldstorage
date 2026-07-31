<?php /** @var App\Models\So $model */ ?>
@php
    $isEdit = isset($model) && $model->exists;
    $existingDetails = old('details');
    if ($existingDetails === null) {
        $existingDetails = $isEdit
            ? $model->details->map(fn ($d) => [
                'so_detail_id' => $d->so_detail_id,
                'so_detail_id_product' => $d->so_detail_id_product,
                'so_detail_qty' => $d->so_detail_qty,
            ])->values()->all()
            : [['so_detail_id' => null, 'so_detail_id_product' => '', 'so_detail_qty' => 1]];
    }
@endphp

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => $isEdit ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())" icon="point_of_sale">
            @bind($model ?? null)
                @if($isEdit)
                    <x-input col="6" name="so_code" readonly />
                @endif
                <x-input :col="$isEdit ? 6 : 12" name="so_tanggal" type="date" />
                <x-select col="6" name="so_id_customer" label="Customer" :options="$customerOptions" class="search" />
                <x-select col="6" name="so_status" :options="$statusOptions" />
                <x-input col="12" name="so_keterangan" type="textarea" />
            @endbind
        </x-card>

        <x-card label="Detail Product" icon="inventory_2" class="mt-5" :noGrid="true">
            <livewire:so-details :rows="$existingDetails" :options="$productOptions->all()" :prices="$productPrices->all()" />
            @error('details')
                <p class="font-label-caps text-label-caps text-error mt-2">{{ $message }}</p>
            @enderror
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
