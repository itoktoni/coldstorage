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
                <x-input col="6" name="po_code" />
                <x-input col="6" name="po_tanggal" type="date" />
                <x-input col="6" name="po_supplier" />
                <x-select col="6" name="po_status" :options="$statusOptions" />
                <x-input col="12" name="po_keterangan" type="textarea" />
            @endbind
        </x-card>

        <x-card label="Detail Product" icon="inventory_2" class="mt-5" :noGrid="true">
            <div class="space-y-3" id="po-details">
                {{-- rows injected by JS --}}
            </div>
            @error('details')
                <p class="font-label-caps text-label-caps text-error mt-2">{{ $message }}</p>
            @enderror
            <div class="flex justify-end mt-4">
                <button type="button" id="po-add-row"
                    class="inline-flex items-center gap-1.5 h-10 px-4 rounded-lg bg-primary text-on-primary font-body-sm hover:opacity-90 transition-opacity">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Tambah Product
                </button>
            </div>
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <template id="po-row-tpl">
        <div class="po-detail-row grid grid-cols-12 gap-3 items-end border border-outline-variant rounded-lg p-3 bg-surface-container-low">
            <input type="hidden" name="details[__I__][po_detail_id]" value="" class="po-detail-id" />
            <div class="col-span-12 md:col-span-7">
                <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Product</label>
                <select name="details[__I__][po_detail_id_product]" class="po-detail-product w-full h-12 pl-4 pr-10 bg-white border border-outline-variant rounded-lg font-body-sm appearance-none cursor-pointer focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none" required>
                    <option value="">-- Silahkan Pilih --</option>
                    @foreach($productOptions as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-8 md:col-span-3">
                <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Qty</label>
                <input type="number" min="1" name="details[__I__][po_detail_qty]" value="1"
                    class="po-detail-qty w-full h-12 px-4 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none" required />
            </div>
            <div class="col-span-4 md:col-span-2 flex justify-end">
                <button type="button" class="po-remove-row inline-flex items-center justify-center h-12 w-12 rounded-lg border border-error text-error hover:bg-error hover:text-on-error transition-colors" title="Hapus">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        </div>
    </template>

    @push('scripts')
    <script>
    (function () {
        var root = document.getElementById('po-details');
        var tpl = document.getElementById('po-row-tpl');
        var addBtn = document.getElementById('po-add-row');
        var index = 0;
        var initial = @json($existingDetails);

        function addRow(data) {
            data = data || {};
            var html = tpl.innerHTML.replace(/__I__/g, String(index));
            var wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            var row = wrap.firstElementChild;

            var idInput = row.querySelector('.po-detail-id');
            var product = row.querySelector('.po-detail-product');
            var qty = row.querySelector('.po-detail-qty');

            if (data.po_detail_id) idInput.value = data.po_detail_id;
            if (data.po_detail_id_product) product.value = String(data.po_detail_id_product);
            if (data.po_detail_qty) qty.value = data.po_detail_qty;

            row.querySelector('.po-remove-row').addEventListener('click', function () {
                if (root.querySelectorAll('.po-detail-row').length <= 1) {
                    product.value = '';
                    qty.value = 1;
                    idInput.value = '';
                    return;
                }
                row.remove();
            });

            root.appendChild(row);
            index++;
        }

        addBtn.addEventListener('click', function () { addRow(); });

        if (initial && initial.length) {
            initial.forEach(function (d) { addRow(d); });
        } else {
            addRow();
        }
    })();
    </script>
    @endpush
</x-layouts::app>
