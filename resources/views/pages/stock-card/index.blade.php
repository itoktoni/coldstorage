<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Kartu Stock']]" />

    <div class="content mt-4 lg:mt-0">

        {{-- Filter --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">filter_list</span>
                Filter
            </h3>
            <form method="GET" action="{{ route('wms-stock-card.index') }}" class="grid grid-cols-12 gap-4">
                <div class="col-span-12 sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                    <select name="product_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Semua Product</option>
                        @foreach($productOptions as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['product_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-12 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aksi</label>
                    <select name="action" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Semua</option>
                        <option value="CREATE" {{ ($filters['action'] ?? '') === 'CREATE' ? 'selected' : '' }}>Create</option>
                        <option value="INCREASE" {{ ($filters['action'] ?? '') === 'INCREASE' ? 'selected' : '' }}>Increase</option>
                        <option value="DECREASE" {{ ($filters['action'] ?? '') === 'DECREASE' ? 'selected' : '' }}>Decrease</option>
                        <option value="TYPE_CHANGE" {{ ($filters['action'] ?? '') === 'TYPE_CHANGE' ? 'selected' : '' }}>Type Change</option>
                        <option value="RELOCATION" {{ ($filters['action'] ?? '') === 'RELOCATION' ? 'selected' : '' }}>Relocation</option>
                        <option value="DELETE" {{ ($filters['action'] ?? '') === 'DELETE' ? 'selected' : '' }}>Delete</option>
                    </select>
                </div>
                <div class="col-span-12 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>
                <div class="col-span-12 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>
                <div class="col-span-12 sm:col-span-3 flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium">
                        <span class="material-symbols-outlined text-sm mr-1">search</span> Filter
                    </button>
                    <a href="{{ route('wms-stock-card.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm font-medium">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Log Table --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">history</span>
                Riwayat Stock
                <span class="text-sm font-normal text-on-surface-variant ml-auto">{{ $logs->total() }} record</span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant">
                            <th class="py-3 px-3 font-label-md text-on-surface-variant">Waktu</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant">Kode</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant">Aksi</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant">Product</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant">Lokasi</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant">Type</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant text-right">Qty Sebelum</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant text-right">Qty Sesudah</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant text-right">Qty Aktual</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant">Reff</th>
                            <th class="py-3 px-3 font-label-md text-on-surface-variant">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr class="border-b border-outline-variant/50 hover:bg-gray-50">
                            <td class="py-2.5 px-3 text-xs whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i:s') ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-xs font-mono">{{ $log->stock_log_code }}</td>
                            <td class="py-2.5 px-3">
                                @php
                                    $actionColors = [
                                        'CREATE'     => 'bg-success/10 text-success',
                                        'INCREASE'   => 'bg-primary/10 text-primary',
                                        'DECREASE'   => 'bg-error/10 text-error',
                                        'TYPE_CHANGE'=> 'bg-warning/10 text-warning',
                                        'RELOCATION' => 'bg-info/10 text-info',
                                        'DELETE'     => 'bg-error/10 text-error',
                                        'UPDATE'     => 'bg-surface-variant text-on-surface-variant',
                                    ];
                                    $color = $actionColors[$log->action] ?? 'bg-surface-variant text-on-surface-variant';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-xs">{{ $log->product?->product_nama ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-xs">{{ $log->lokasi?->lokasi_nama ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-xs font-mono">{{ $log->stock_type }}</td>
                            <td class="py-2.5 px-3 text-xs text-right">{{ $log->stock_qty_before !== null ? number_format($log->stock_qty_before, 3) : '-' }}</td>
                            <td class="py-2.5 px-3 text-xs text-right font-medium">{{ $log->stock_qty_after !== null ? number_format($log->stock_qty_after, 3) : '-' }}</td>
                            <td class="py-2.5 px-3 text-xs text-right">{{ number_format($log->stock_qty, 3) }}</td>
                            <td class="py-2.5 px-3 text-xs font-mono text-on-surface-variant">{{ $log->stock_reff ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-xs text-on-surface-variant max-w-[200px] truncate" title="{{ $log->description }}">{{ $log->description ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="py-8 text-center text-on-surface-variant">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="mt-4">
                {{ $logs->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</x-layouts::app>
