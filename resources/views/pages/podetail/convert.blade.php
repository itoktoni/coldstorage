<?php /** @var App\Models\PoDetail $poDetail */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-po-detail.getTable'), 'label' => 'PO Detail'], ['url' => '', 'label' => 'Convert to Masuk']]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Header Info --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">swap_horiz</span>
                Convert to Masuk Detail
            </h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">PO Detail</label>
                    <input type="text" value="{{ $poDetail->po_detail_code }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                    <input type="text" value="{{ $product->product_nama ?? '-' }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <div class="flex items-center h-10">
                        @if($productCategory)
                        <span class="badge badge-warning">{{ $productCategory }}</span>
                        @else
                        <span class="text-sm text-on-surface-variant">-</span>
                        @endif
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sisa Qty</label>
                    <input type="text" value="{{ (float) $remainingQty }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 font-semibold text-primary" readonly />
                    @if($alreadyConverted > 0)
                    <p class="text-xs text-on-surface-variant mt-1">Dari {{ (float) $totalQty }} ({{ (float) $alreadyConverted }} sudah masuk)</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Location Allocation Form --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">location_on</span>
                Alokasi Lokasi
                <span class="text-sm font-normal text-on-surface-variant ml-auto">
                    @if($suitableCount > 0)
                    {{ $suitableCount }} lokasi tersedia
                    @else
                    <span class="text-error">Tidak ada lokasi tersedia</span>
                    @endif
                </span>
            </h3>

            @if(session('error'))
            <div class="bg-error/10 border border-error rounded-xl p-4 mb-4">
                <p class="text-error font-body-sm font-semibold">{{ session('error') }}</p>
            </div>
            @endif
            @if($errors->any())
            <div class="bg-error/10 border border-error rounded-xl p-4 mb-4">
                <ul class="text-error font-body-sm">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-on-surface-variant bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">Lokasi</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Current</th>
                            <th class="px-4 py-3">Max</th>
                            <th class="px-4 py-3">Sisa</th>
                            <th class="px-4 py-3">Alokasi Qty</th>
                            <th class="px-4 py-3">Staging</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lokasiData as $lokasi)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $lokasi['lokasi_nama'] }}</div>
                                @if($lokasi['gudang_nama'])
                                <div class="text-xs text-on-surface-variant">{{ $lokasi['gudang_nama'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($lokasi['lokasi_category'])
                                <span class="badge badge-info text-xs">{{ $lokasi['lokasi_category'] }}</span>
                                @else
                                <span class="text-xs text-on-surface-variant">Semua</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ (float) $lokasi['current_qty'] }}</td>
                            <td class="px-4 py-3">
                                {!! $lokasi['max_qty'] ? (float) $lokasi['max_qty'] : '<span class="text-success">∞</span>' !!}
                            </td>
                            <td class="px-4 py-3">
                                <span class="{{ ($lokasi['capacity_left'] ?? 0) < 10 ? 'text-error' : '' }}">
                                    {{ $lokasi['capacity_left'] !== null ? (float) $lokasi['capacity_left'] : '∞' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <input type="number"
                                           step="0.001"
                                           name="lokasi_allocations[{{ $lokasi['lokasi_code'] }}][qty]"
                                           value="{{ $lokasi['suggested_qty'] ?? 0 }}"
                                           max="{{ min($lokasi['capacity_left'] ?? $remainingQty, $remainingQty) }}"
                                           min="0"
                                           class="w-28 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary"
                                           data-remaining="{{ min($lokasi['capacity_left'] ?? $remainingQty, $remainingQty) }}"
                                           data-lokasi-code="{{ $lokasi['lokasi_code'] }}">
                                    <input type="hidden" name="lokasi_allocations[{{ $lokasi['lokasi_code'] }}][lokasi_code]" value="{{ $lokasi['lokasi_code'] }}">
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <select name="lokasi_allocations[{{ $lokasi['lokasi_code'] }}][staging_code]"
                                        class="w-32 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">Pilih Staging</option>
                                    @foreach($stagingOptions as $sc => $sn)
                                    <option value="{{ $sc }}">{{ $sn }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <button type="button"
                                        class="inline-flex items-center px-2.5 py-2 bg-primary/10 text-primary hover:bg-primary/20 rounded-lg transition-colors text-xs"
                                        title="Convert row ini saja ke Masuk Detail"
                                        onclick="convertSingle({{ $poDetail->po_detail_id }}, '{{ $lokasi['lokasi_code'] }}', this)">
                                    <span class="material-symbols-outlined text-base">inventory_2</span>
                                    Convert
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="6" class="px-4 py-3 text-right">Total Alokasi:</td>
                            <td class="px-4 py-3">
                                <span id="total-allocation" class="text-primary">{{ number_format($lokasiData->sum('suggested_qty'), 3) }}</span>
                                / {{ number_format($remainingQty, 3) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script>
        const TARGET_QTY = {{ (float) $remainingQty }};
        const allInputs = () => Array.from(document.querySelectorAll('input[data-remaining]'));

        function redistribute(edited) {
            const inputs = allInputs();
            let editedVal = parseFloat(edited.value) || 0;

            const editedCap = parseFloat(edited.dataset.remaining) || 0;
            if (editedCap > 0 && editedVal > editedCap) {
                editedVal = editedCap;
                edited.value = editedVal;
                showToast('Melebihi Kapasitas', 'Qty melebihi sisa capacity lokasi ini (' + editedCap + ')');
            }
            if (editedVal > TARGET_QTY) {
                editedVal = TARGET_QTY;
                edited.value = editedVal;
                showToast('Melebihi Qty PO', 'Total alokasi melebihi qty PO (' + TARGET_QTY + ')');
            }

            inputs.forEach(i => { if (i !== edited) i.value = ''; });

            let remaining = TARGET_QTY - editedVal;
            for (const i of inputs) {
                if (i === edited || remaining <= 0.0001) continue;
                const cap = parseFloat(i.dataset.remaining) || 0;
                const fill = Math.min(remaining, cap > 0 ? cap : remaining);
                if (fill > 0) {
                    i.value = fill.toFixed(3);
                    remaining -= fill;
                }
            }
            updateTotal();
        }

        allInputs().forEach(input => {
            input.addEventListener('input', updateTotal);
            input.addEventListener('change', function() {
                redistribute(this);
            });
        });

        function updateTotal() {
            let total = 0;
            allInputs().forEach(input => { total += parseFloat(input.value) || 0; });
            document.getElementById('total-allocation').textContent = total.toFixed(3);
        }

        async function convertSingle(poDetailId, lokasiCode, btn) {
            const row = btn.closest('tr');
            const input = row.querySelector('input[data-lokasi-code]');
            const qty = parseFloat(input.value) || 0;

            if (qty <= 0) {
                showToast('Qty Tidak Valid', 'Qty harus lebih dari 0');
                return;
            }

            const max = parseFloat(input.dataset.remaining) || 0;
            if (max > 0 && qty > max) {
                showToast('Melebihi Kapasitas', 'Qty melebihi sisa capacity lokasi ini (' + max + ')');
                input.value = max;
                updateTotal();
                return;
            }

            const stagingSelect = row.querySelector('select[name*="[staging_code]"]');
            const stagingCode = stagingSelect ? stagingSelect.value : '';

            btn.disabled = true;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">progress_activity</span>';

            try {
                const url = "{{ url('/wms/po-detail') }}/" + poDetailId + "/convert-single";
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value
                    || '';

                const formData = new FormData();
                formData.append('_token', csrf);
                formData.append('lokasi_code', lokasiCode);
                formData.append('qty', qty);
                formData.append('staging_code', stagingCode);

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                    redirect: 'manual',
                });

                if (res.type === 'opaqueredirect' || (res.status === 0 && res.redirected)) {
                    window.location.reload();
                    return;
                }

                let data = {};
                try { data = await res.json(); } catch (_) {}

                if (!res.ok || data.ok === false) {
                    showToast('Gagal Convert', 'Gagal convert: ' + (data.message || ('HTTP ' + res.status)));
                    return;
                }

                showToast('Berhasil', data.message || 'Berhasil konversi ' + qty + ' ke lokasi');

                if (data.masuk_detail_code) {
                    window.location.href = "{{ url('/wms/masuk-detail') }}/" + data.masuk_detail_code + "/realisasikan";
                } else {
                    window.location.reload();
                }
            } catch (err) {
                showToast('Terjadi Kesalahan', 'Terjadi kesalahan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }
    </script>
</x-layouts::app>
