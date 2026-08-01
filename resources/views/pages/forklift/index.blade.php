<?php /** @var App\Models\MasukDetail $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Forklift']]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Header Info --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">local_shipping</span>
                Forklift - Pindahkan Barang ke Lokasi
            </h3>
            <p class="text-on-surface-variant text-sm">
                Barang dengan status <span class="badge badge-info">Ready</span> siap dipindahkan ke lokasi penyimpanan.
                Lokasi yang ditampilkan sudah difilter berdasarkan <strong>kategori</strong> dan <strong>kapasitas</strong>.
            </p>
        </div>

        {{-- Ready Items --}}
        @forelse($items as $item)
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                {{ $item['detail']->in_detail_code }} - {{ $item['product']->product_nama ?? '-' }}
            </h3>

            <div class="grid grid-cols-12 gap-4 mb-4">
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                    <input type="text" value="{{ $item['product']->product_nama ?? '-' }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <div class="flex items-center h-10">
                        @if($item['product_category'])
                        <span class="badge badge-warning">{{ $item['product_category'] }}</span>
                        @else
                        <span class="text-sm text-on-surface-variant">-</span>
                        @endif
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Qty</label>
                    <input type="text" value="{{ (float) $item['total_qty'] }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-5">
                    <form action="{{ route('wms-forklift.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="detail_code" value="{{ $item['detail']->in_detail_code }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Tujuan</label>
                        <div class="flex gap-2">
                            <select name="lokasi_id" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary" required>
                                @forelse($item['suitable_lokasi'] as $lokasi)
                                @php
                                    $currentQty = $lokasi->current_qty;
                                    $maxQty = $lokasi->lokasi_max_qty;
                                    $remaining = $maxQty ? $maxQty - $currentQty : null;
                                @endphp
                                <option value="{{ $lokasi->lokasi_id }}" {{ $lokasi->lokasi_id == $item['suggested_lokasi_id'] ? 'selected' : '' }}>
                                    {{ $lokasi->lokasi_nama }}{{ $lokasi->gudang ? ' ('.$lokasi->gudang->gudang_nama.')' : '' }}
                                    @if($maxQty) [{{ (float) $currentQty }}/{{ (float) $maxQty }}]@endif
                                    {{ $lokasi->lokasi_id == $item['suggested_lokasi_id'] ? ' ★' : '' }}
                                </option>
                                @empty
                                <option value="" disabled>Tidak ada lokasi cocok</option>
                                @endforelse
                            </select>
                            @if($item['suitable_lokasi']->count() > 0)
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-success/90 transition-colors">
                                <span class="material-symbols-outlined text-lg mr-1">check_circle</span>
                                Pindahkan
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Realisasi Details --}}
            @if($item['realisasi']->count() > 0)
            <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Detail Realisasi:</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-on-surface-variant bg-gray-50">
                            <tr>
                                <th class="px-4 py-2">Barcode</th>
                                <th class="px-4 py-2">Qty</th>
                                <th class="px-4 py-2">Lokasi Asal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($item['realisasi'] as $r)
                            <tr class="border-b">
                                <td class="px-4 py-2 text-xs">{{ $r->in_realisasi_barcode ?? '-' }}</td>
                                <td class="px-4 py-2">{{ (float) $r->in_realisasi_qty }}</td>
                                <td class="px-4 py-2">{{ $r->lokasi->lokasi_nama ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
        @empty
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <div class="text-center py-8">
                <span class="material-symbols-outlined text-6xl text-on-surface-variant">check_circle</span>
                <p class="text-on-surface-variant mt-2">Tidak ada barang yang perlu dipindahkan</p>
            </div>
        </div>
        @endforelse
    </div>
</x-layouts::app>
