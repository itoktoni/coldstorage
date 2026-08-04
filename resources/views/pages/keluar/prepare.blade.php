<?php /** @var App\Models\Keluar $keluar */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-keluar.getTable'), 'label' => 'Keluar'], ['url' => '', 'label' => 'Prepare Keluar']]" />

    <div class="content mt-4 lg:mt-0">
        <form id="keluar-prepare-form" action="{{ route('wms-keluar-prepare.update', ['outCode' => $keluar->out_code]) }}" method="POST">
        @csrf
        {{-- Header Info --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory</span>
                Prepare Keluar {{ $keluar->out_code }}
            </h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Keluar</label>
                    <input type="text" value="{{ $keluar->out_code }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="text" value="{{ $keluar->out_tanggal?->format('d M Y') ?? '-' }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reff</label>
                    <input type="text" value="{{ $keluar->out_reff ?? '-' }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <input type="text" value="{{ $keluar->out_status }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
            </div>
        </div>

        {{-- Kebutuhan Item --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">checklist</span>
                Kebutuhan Item Keluar
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant">
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">SO</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Dibutuhkan</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Teralokasi</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $line)
                        <tr class="border-b border-outline-variant/50">
                            <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $line['so_code'] }}</td>
                            <td class="py-2 px-3 font-body-sm font-medium">{{ $line['product']->product_nama ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm text-right">{{ number_format($line['qty_needed'], 3) }}</td>
                            <td class="py-2 px-3 font-body-sm text-right text-primary">{{ number_format($line['qty_assigned'], 3) }}</td>
                            <td class="py-2 px-3 font-body-sm text-right {{ $line['qty_remaining'] <= 0 ? 'text-success' : 'text-error' }}">{{ number_format($line['qty_remaining'], 3) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Stock Tersedia --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">warehouse</span>
                Stock Tersedia
            </h3>

            @if($availableStock->isEmpty())
            <p class="text-on-surface-variant text-sm">Tidak ada stock tersedia.</p>
            @else
            @foreach($lines as $line)
            @php
                $productId = $line['product']->product_id ?? null;
                $stocks = $productId ? ($availableStock->get($productId) ?? collect()) : collect();
                $existingForDetail = $existingAssignments->get($line['detail']->out_detail_id, collect());
            @endphp
            @if($stocks->isNotEmpty() && $line['qty_remaining'] > 0)
            <div class="mb-6 last:mb-0">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-sm font-semibold text-on-surface">{{ $line['so_code'] }} - {{ $line['product']->product_nama ?? '-' }}</span>
                    <span class="text-xs text-on-surface-variant">— Sisa butuh: <strong class="text-error">{{ number_format($line['qty_remaining'], 3) }}</strong></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-on-surface-variant bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Stock Code</th>
                                <th class="px-4 py-3">Lokasi</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Sisa</th>
                                <th class="px-4 py-3">Expired</th>
                                <th class="px-4 py-3">Alokasi Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stocks as $stock)
                            @if($stock['remaining'] > 0)
                            @php
                                $alreadyAssigned = (float) $existingForDetail->where('stock_assignment_id_stock', $stock['stock_id'])->sum('stock_assignment_qty');
                            @endphp
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs">{{ $stock['stock_code'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-xs">{{ $stock['lokasi_nama'] }}</div>
                                    <div class="text-xs text-on-surface-variant">{{ $stock['gudang_nama'] }}</div>
                                </td>
                                <td class="px-4 py-3">{{ number_format($stock['stock_qty'], 3) }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $stock['remaining'] < 10 ? 'text-error' : '' }}">{{ number_format($stock['remaining'], 3) }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ $stock['expired'] ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <input type="number"
                                               step="0.001"
                                               name="assign[{{ $line['detail']->out_detail_id }}][{{ $stock['stock_id'] }}][qty]"
                                               value="{{ $alreadyAssigned > 0 ? number_format($alreadyAssigned, 3, '.', '') : (($suggestions[$line['detail']->out_detail_id . '_' . $stock['stock_id']] ?? 0) > 0 ? number_format($suggestions[$line['detail']->out_detail_id . '_' . $stock['stock_id']], 3, '.', '') : '') }}"
                                               min="0"
                                               max="{{ min($stock['remaining'], $line['qty_remaining']) }}"
                                               class="w-28 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary assign-input"
                                               data-max="{{ $stock['remaining'] }}"
                                               data-detail-id="{{ $line['detail']->out_detail_id }}"
                                               data-detail-remaining="{{ $line['qty_remaining'] }}">
                                        <input type="hidden" name="assign[{{ $line['detail']->out_detail_id }}][{{ $stock['stock_id'] }}][stock_id]" value="{{ $stock['stock_id'] }}">
                                        <input type="hidden" name="assign[{{ $line['detail']->out_detail_id }}][{{ $stock['stock_id'] }}][keluar_detail_id]" value="{{ $line['detail']->out_detail_id }}">
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            @endforeach
            @endif
        </div>
    </div>

        </form>

        <div class="mt-6 mb-12 flex items-center gap-3">
            <a href="{{ route('wms-keluar.getTable') }}"
               class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                Kembali
            </a>
            <button type="submit" form="keluar-prepare-form"
                    class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-success text-on-primary hover:bg-success/90 transition-all active:scale-95">
                <span class="material-symbols-outlined text-base">save</span>
                Simpan Alokasi
            </button>
        </div>

    <script>
        document.querySelectorAll('.assign-input').forEach(input => {
            input.addEventListener('change', function() {
                const val = parseFloat(this.value) || 0;
                const max = parseFloat(this.dataset.max) || 0;
                if (val > max) {
                    this.value = max;
                    showToast('Melebihi Kapasitas', 'Qty melebihi sisa stock (' + max + ')');
                }
            });
        });
    </script>
</x-layouts::app>
