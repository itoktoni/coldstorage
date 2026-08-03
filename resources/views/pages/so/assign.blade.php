<?php /** @var App\Models\So $so */ ?>
<?php /** @var App\Models\Keluar $keluar */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[
        ['url' => route('wms-so-prepare.index'), 'label' => 'Prepare SO'],
        ['url' => route('wms-so-prepare.show', ['soId' => $so->so_id]), 'label' => $so->so_code],
        ['url' => '', 'label' => 'Assign Stock'],
    ]" />

    @if($errors->any())
    <div class="bg-error/10 border border-error rounded-xl p-4 mt-5">
        <ul class="list-disc list-inside text-error font-body-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">assignment</span>
            Assign Stock — {{ $keluar->out_code }}
        </h3>
        <div class="grid grid-cols-12 gap-5">
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">SO</div>
                <div class="font-body-sm font-bold">{{ $so->so_code }}</div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Customer</div>
                <div class="font-body-sm font-bold">{{ $so->customer->customer_nama ?? '-' }}</div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Total Qty</div>
                <div class="font-body-sm font-bold">{{ $keluar->out_qty }}</div>
            </div>
        </div>
    </div>

    <form action="{{ route('wms-so-prepare.assignStore', ['soId' => $so->so_id]) }}" method="POST">
        @csrf

        @foreach($keluar->details as $detail)
        @php
            $productId = $detail->out_detail_id_product;
            $stocks = $availableStock->get($productId, collect());
            $assigned = $existingAssignments->get($detail->out_detail_id, collect());
            $totalAssigned = (float) $assigned->sum('qty');
            $remaining = max(0, (float) $detail->out_detail_qty - $totalAssigned);
        @endphp

        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                {{ $detail->product->product_nama ?? '-' }} — Dibutuhkan: {{ $detail->out_detail_qty }} unit
                <span class="ml-auto text-sm {{ $remaining <= 0 ? 'text-success' : 'text-error' }}">
                    Sisa: {{ $remaining }} unit
                </span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant">
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">#</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Barcode</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Rak</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Gudang</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Stok Tersisa</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Expired</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty Ambil</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="assignment-rows" data-detail-id="{{ $detail->out_detail_id }}" data-product-id="{{ $productId }}">
                        @forelse($assigned as $idx => $a)
                        @php
                            $stockInfo = $stocks->firstWhere('stock_id', $a['stock_id']);
                        @endphp
                        <tr class="border-b border-outline-variant/50 assignment-row">
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $idx + 1 }}</td>
                            <td class="py-2 px-3 font-body-sm font-mono text-sm">{{ $stockInfo['stock_code'] ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm">{{ $stockInfo['lokasi_nama'] ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $stockInfo['gudang_nama'] ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm text-right">{{ $stockInfo['remaining'] ?? 0 }}</td>
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant text-right">{{ $stockInfo['expired'] ?? '-' }}</td>
                            <td class="py-2 px-3 text-right">
                                <input type="number" name="assignments[{{ $detail->out_detail_id }}_{{ $a['stock_id'] }}][qty]"
                                       value="{{ $a['qty'] }}" min="0.001" step="0.001"
                                       class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                            </td>
                            <td class="py-2 px-3 text-center">
                                <button type="button" onclick="removeAssignment(this)" class="text-error hover:text-error/80">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                            </td>
                        </tr>
                        <input type="hidden" name="assignments[{{ $detail->out_detail_id }}_{{ $a['stock_id'] }}][keluar_detail_id]" value="{{ $detail->out_detail_id }}">
                        <input type="hidden" name="assignments[{{ $detail->out_detail_id }}_{{ $a['stock_id'] }}][stock_id]" value="{{ $a['stock_id'] }}">
                        <input type="hidden" name="assignments[{{ $detail->out_detail_id }}_{{ $a['stock_id'] }}][so_detail_id]" value="{{ $detail->out_detail_id_so_detail }}">
                        @empty
                        <tr class="border-b border-outline-variant/50 assignment-row">
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant">1</td>
                            <td class="py-2 px-3" colspan="5">
                                <select name="assignments[new_{{ $detail->out_detail_id }}_0][stock_id]"
                                    class="w-full h-9 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none stock-select"
                                    data-detail-id="{{ $detail->out_detail_id }}">
                                    <option value="">— Pilih Stock —</option>
                                    @foreach($stocks as $s)
                                        @if($s['remaining'] > 0)
                                        <option value="{{ $s['stock_id'] }}" data-remaining="{{ $s['remaining'] }}">
                                            {{ $s['stock_code'] }} — {{ $s['lokasi_nama'] }} ({{ $s['remaining'] }} unit)
                                        </option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2 px-3 text-right">
                                <input type="number" name="assignments[new_{{ $detail->out_detail_id }}_0][qty]"
                                       value="{{ $remaining }}" min="0.001" step="0.001" max="{{ $remaining }}"
                                       class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                            </td>
                            <td class="py-2 px-3 text-center">
                                <button type="button" onclick="removeAssignment(this)" class="text-error hover:text-error/80">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                            </td>
                        </tr>
                        <input type="hidden" name="assignments[new_{{ $detail->out_detail_id }}_0][keluar_detail_id]" value="{{ $detail->out_detail_id }}">
                        <input type="hidden" name="assignments[new_{{ $detail->out_detail_id }}_0][so_detail_id]" value="{{ $detail->out_detail_id_so_detail }}">
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="button" onclick="addRow(this, {{ $detail->out_detail_id }}, {{ $productId }}, {{ $detail->out_detail_id_so_detail }})"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary/80">
                    <span class="material-symbols-outlined text-base">add</span>
                    Tambah Baris
                </button>
            </div>
        </div>
        @endforeach

        <div class="mt-6 mb-12 flex items-center gap-3">
            <a href="{{ route('wms-so-prepare.show', ['soId' => $so->so_id]) }}"
               class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">save</span>
                Simpan Assignments
            </button>
        </div>
    </form>

    <script>
    function removeAssignment(btn) {
        var tr = btn.closest('tr');
        var next = tr.nextElementSibling;
        while (next && next.tagName === 'INPUT' && next.type === 'hidden') {
            var toRemove = next;
            next = next.nextElementSibling;
            toRemove.remove();
        }
        tr.remove();
    }

    function addRow(btn, detailId, productId, soDetailId) {
        var tbody = btn.closest('.form-card').querySelector('.assignment-rows');
        var idx = tbody.querySelectorAll('.assignment-row').length;
        var key = 'new_' + detailId + '_' + idx;
        var selectName = 'assignments[' + key + '][stock_id]';
        var qtyName = 'assignments[' + key + '][qty]';

        var existingSelect = tbody.querySelector('.stock-select');
        var options = existingSelect ? existingSelect.innerHTML : '';

        var row = document.createElement('tr');
        row.className = 'border-b border-outline-variant/50 assignment-row';
        row.innerHTML = '<td class="py-2 px-3 font-body-sm text-on-surface-variant">' + (idx + 1) + '</td>' +
            '<td class="py-2 px-3" colspan="5">' +
                '<select name="' + selectName + '" class="w-full h-9 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none stock-select">' +
                    '<option value="">— Pilih Stock —</option>' + options +
                '</select>' +
            '</td>' +
            '<td class="py-2 px-3 text-right">' +
                '<input type="number" name="' + qtyName + '" value="0" min="0.001" step="0.001" class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />' +
            '</td>' +
            '<td class="py-2 px-3 text-center">' +
                '<button type="button" onclick="removeAssignment(this)" class="text-error hover:text-error/80">' +
                    '<span class="material-symbols-outlined text-lg">close</span>' +
                '</button>' +
            '</td>';
        tbody.appendChild(row);

        var hiddenKeluar = document.createElement('input');
        hiddenKeluar.type = 'hidden';
        hiddenKeluar.name = 'assignments[' + key + '][keluar_detail_id]';
        hiddenKeluar.value = detailId;
        tbody.appendChild(hiddenKeluar);

        var hiddenSo = document.createElement('input');
        hiddenSo.type = 'hidden';
        hiddenSo.name = 'assignments[' + key + '][so_detail_id]';
        hiddenSo.value = soDetailId;
        tbody.appendChild(hiddenSo);
    }
    </script>
</x-layouts::app>