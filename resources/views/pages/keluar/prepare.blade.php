@php /** @var App\Models\Keluar $keluar */ /** @var array $lines */ /** @var \Illuminate\Support\Collection $pallets */ /** @var array $recommended */ /** @var \Illuminate\Support\Collection $existingTasks */ @endphp

<x-layouts::app title="Prepare Keluar {{ $keluar->out_code }}">
    <x-breadcrumb :items="[
        ['url' => '/dashboard', 'label' => 'Home'],
        ['url' => route('wms-keluar.getTable'), 'label' => 'Keluar'],
        ['url' => '', 'label' => 'Prepare Keluar'],
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

    <form action="{{ route('wms-keluar-prepare.update', ['outCode' => $keluar->out_code]) }}" method="POST">
        @csrf

        {{-- Header Card --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory</span>
                Prepare Keluar {{ $keluar->out_code }}
            </h3>
            <div class="grid grid-cols-12 gap-5">
                <div class="col-span-12 md:col-span-3">
                    <label class="block font-body-sm text-on-surface-variant mb-1">Kode Keluar</label>
                    <p class="font-body-md text-on-surface font-medium">{{ $keluar->out_code }}</p>
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="block font-body-sm text-on-surface-variant mb-1">Tanggal</label>
                    <p class="font-body-md text-on-surface">{{ $keluar->out_tanggal?->format('d M Y') ?? '-' }}</p>
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="block font-body-sm text-on-surface-variant mb-1">Reff</label>
                    <p class="font-body-md text-on-surface">{{ $keluar->out_reff ?? '-' }}</p>
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="block font-body-sm text-on-surface-variant mb-1">Status</label>
                    @php
                        $statusColors = [
                            'Pending' => 'bg-neutral/10 text-neutral',
                            'In Progress' => 'bg-warning/10 text-warning',
                            'Done' => 'bg-success/10 text-success',
                        ];
                        $statusColor = $statusColors[$keluar->out_status] ?? 'bg-neutral/10 text-neutral';
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                        {{ $keluar->out_status }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Kebutuhan Item Card --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">list_alt</span>
                Kebutuhan Item
            </h3>
            <div class="grid grid-cols-12 gap-5">
                <div class="col-span-12">
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
                                    <td class="py-2 px-3 font-body-sm font-medium">{{ $line['so_code'] }}</td>
                                    <td class="py-2 px-3 font-body-sm">{{ $line['product']->product_nama ?? '-' }}</td>
                                    <td class="py-2 px-3 font-body-sm text-right font-medium">{{ number_format($line['qty_needed'], 0) }}</td>
                                    <td class="py-2 px-3 font-body-sm text-right">{{ number_format($line['qty_assigned'], 0) }}</td>
                                    <td class="py-2 px-3 font-body-sm text-right font-medium {{ $line['qty_remaining'] > 0 ? 'text-error' : 'text-success' }}">
                                        {{ number_format($line['qty_remaining'], 0) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pallet Selection Card --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-2 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">pallet</span>
                Pilih Pallet untuk Dipindah ke Staging
            </h3>
            <p class="font-body-sm text-on-surface-variant mb-4">
                Sistem merekomendasikan pallet berdasarkan FEFO + qty terkecil duluan.
            </p>
            <div class="grid grid-cols-12 gap-5">
                <div class="col-span-12">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-outline-variant">
                                    <th class="py-2 px-3 w-10">
                                        <input type="checkbox" id="select-all-pallets"
                                            class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer"
                                            onchange="toggleAllPallets(this)">
                                    </th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Pallet</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Barcodes</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Expired</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Lokasi</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-center">Rekomendasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pallets as $pallet)
                                @php
                                    $isRecommended = in_array($pallet['pallet_code'], $recommended);
                                    $isExpired = $pallet['expired'] && \Carbon\Carbon::parse($pallet['expired'])->isPast();
                                @endphp
                                <tr class="border-b border-outline-variant/50 {{ $isRecommended ? 'bg-primary/5' : '' }}">
                                    <td class="py-2 px-3">
                                        <input type="checkbox" name="pallets[]" value="{{ $pallet['pallet_code'] }}"
                                            class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer pallet-checkbox"
                                            {{ $isRecommended ? 'checked' : '' }}
                                            onchange="syncSelectAll()">
                                    </td>
                                    <td class="py-2 px-3 font-body-sm font-medium font-mono">{{ $pallet['pallet_code'] }}</td>
                                    <td class="py-2 px-3 font-body-sm">{{ $pallet['product_nama'] }}</td>
                                    <td class="py-2 px-3 font-body-sm text-right font-medium">{{ number_format($pallet['total_qty'], 0) }}</td>
                                    <td class="py-2 px-3 font-body-sm text-right">{{ $pallet['barcode_count'] }}</td>
                                    <td class="py-2 px-3 font-body-sm {{ $isExpired ? 'text-error font-medium' : '' }}">
                                        {{ $pallet['expired'] ?? '-' }}
                                    </td>
                                    <td class="py-2 px-3 font-body-sm">
                                        <span class="font-body-sm">{{ $pallet['lokasi_nama'] }}</span>
                                        <span class="font-body-xs text-on-surface-variant">({{ $pallet['gudang_nama'] }})</span>
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        @if($isRecommended)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                            <span class="material-symbols-outlined text-sm mr-0.5">auto_awesome</span>
                                            FEFO
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="text-center p-4 font-body-sm text-on-surface-variant">
                                            Tidak ada pallet tersedia.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Existing Tasks Card --}}
        @if($existingTasks->isNotEmpty())
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">forklift</span>
                Tugas Pemindahan yang Sudah Ada
            </h3>
            <div class="grid grid-cols-12 gap-5">
                <div class="col-span-12">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-outline-variant">
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Pallet</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Lokasi Saat Ini</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Tujuan</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($existingTasks as $item)
                                <tr class="border-b border-outline-variant/50">
                                    <td class="py-2 px-3 font-body-sm font-medium font-mono">{{ $item['pallet_code'] }}</td>
                                    <td class="py-2 px-3 font-body-sm">
                                        @if($item['is_staging'])
                                        <span class="inline-flex items-center gap-1 text-success">
                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                            {{ $item['dari'] }}
                                        </span>
                                        @else
                                            {{ $item['dari'] }}
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 font-body-sm font-mono">{{ $item['task']->forklift_lokasi_tujuan ?? '-' }}</td>
                                    <td class="py-2 px-3">
                                        @php
                                            $taskStatusColors = [
                                                'Pending' => 'bg-neutral/10 text-neutral',
                                                'In Progress' => 'bg-warning/10 text-warning',
                                                'Done' => 'bg-success/10 text-success',
                                            ];
                                            $taskStatusColor = $taskStatusColors[$item['status']] ?? 'bg-neutral/10 text-neutral';
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $taskStatusColor }}">
                                            {{ $item['status'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="fixed left-0 right-0 lg:left-72 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-4 md:px-6 py-3 z-[45]" style="bottom: 0">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('wms-keluar.getTable') }}"
                    class="inline-flex items-center justify-center gap-2 h-10 px-4 md:px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                    <span class="material-symbols-outlined text-xl">arrow_back</span>
                    Kembali
                </a>
                @if($existingTasks->isNotEmpty())
                <a href="{{ route('wms-keluar.pickList', ['outCode' => $keluar->out_code]) }}"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-purple-100 text-purple-700 hover:bg-purple-200 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-xl">print</span>
                    Print Pick List
                </a>
                @endif
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-xl">check</span>
                    Simpan Rencana Pemindahan
                </button>
            </div>
        </div>
    </form>

    <script>
        function toggleAllPallets(source) {
            document.querySelectorAll('.pallet-checkbox').forEach(cb => {
                cb.checked = source.checked;
            });
        }

        function syncSelectAll() {
            const all = document.querySelectorAll('.pallet-checkbox');
            const checked = document.querySelectorAll('.pallet-checkbox:checked');
            const selectAll = document.getElementById('select-all-pallets');
            if (selectAll) {
                selectAll.checked = all.length > 0 && all.length === checked.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }
        }
    </script>
</x-layouts::app>

