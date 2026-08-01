<?php /** @var App\Models\Po $model */ ?>
@php
    $isEdit = isset($model) && $model->exists;
    $existingDetails = old('details');
    if ($existingDetails === null) {
        $existingDetails = $isEdit
            ? $model->details->map(fn ($d) => [
                'po_detail_id' => $d->po_detail_id,
                'po_detail_id_product' => $d->po_detail_id_product,
                'po_detail_qty' => $d->po_detail_qty,
            ])->values()->all()
            : [['po_detail_id' => null, 'po_detail_id_product' => '', 'po_detail_qty' => 1]];
    }
@endphp

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => ucfirst(module())], ['url' => '', 'label' => $isEdit ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="ucfirst(module())" icon="receipt_long">
            @bind($model ?? null)

                @if($isEdit)
                    <x-input col="6" name="po_code" readonly />
                @endif
                <x-input :col="$isEdit ? 6 : 12" name="po_tanggal" type="date"  />
                <x-select col="6" name="po_id_supplier" :options="$supplierOptions" class="search" />
                <x-select col="6" name="po_status" :options="$statusOptions" />
                <x-textarea col="12" name="po_keterangan" />
            @endbind
        </x-card>

        <x-card label="Detail Product" icon="inventory_2" class="mt-5" :noGrid="true">
            <livewire:po-details :rows="$existingDetails" :options="$productOptions->all()" />
            @error('details')
                <p class="font-label-caps text-label-caps text-error mt-2">{{ $message }}</p>
            @enderror
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
