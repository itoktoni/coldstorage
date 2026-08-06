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
                {{ $keluar->out_code }}
            </h3>

            {{-- DESKTOP --}}
            <div class="hidden md:grid grid-cols-12 gap-5">
                <div class="col-span-3">
                    <label class="block font-body-sm text-on-surface-variant mb-1">Kode Keluar</label>
                    <p class="font-body-md text-on-surface font-medium">{{ $keluar->out_code }}</p>
                </div>
                <div class="col-span-3">
                    <label class="block font-body-sm text-on-surface-variant mb-1">Tanggal</label>
                    <p class="font-body-md text-on-surface">{{ $keluar->out_tanggal?->format('d M Y') ?? '-' }}</p>
                </div>
                <div class="col-span-3">
                    <label class="block font-body-sm text-on-surface-variant mb-1">Reff</label>
                    <p class="font-body-md text-on-surface">{{ $keluar->out_reff ?? '-' }}</p>
                </div>
                <div class="col-span-3">
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

            {{-- MOBILE --}}
            <div class="md:hidden">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Kode Keluar</p>
                        <p class="text-sm font-bold text-on-surface font-mono">{{ $keluar->out_code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Status</p>
                        @php
                            $statusColors = [
                                'Pending' => 'bg-neutral/10 text-neutral',
                                'In Progress' => 'bg-warning/10 text-warning',
                                'Done' => 'bg-success/10 text-success',
                            ];
                            $statusColor = $statusColors[$keluar->out_status] ?? 'bg-neutral/10 text-neutral';
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusColor }}">
                            {{ $keluar->out_status }}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                        <p class="text-xs font-medium text-on-surface">{{ $keluar->out_tanggal?->format('d M Y') ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Reff</p>
                        <p class="text-xs font-medium text-on-surface">{{ $keluar->out_reff ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kebutuhan Item Card --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">list_alt</span>
                Kebutuhan Item
            </h3>

            {{-- DESKTOP TABLE --}}
            <div class="hidden md:block">
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

            {{-- MOBILE CARDS --}}
            <div class="md:hidden space-y-3">
                @foreach($lines as $line)
                <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-sm font-bold text-on-surface truncate">{{ $line['product']->product_nama ?? '-' }}</p>
                        <span class="text-[10px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $line['so_code'] }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-surface-container rounded-lg p-2">
                            <p class="text-[9px] text-on-surface-variant uppercase">Dibutuhkan</p>
                            <p class="text-sm font-bold text-on-surface">{{ number_format($line['qty_needed'], 0) }}</p>
                        </div>
                        <div class="bg-surface-container rounded-lg p-2">
                            <p class="text-[9px] text-on-surface-variant uppercase">Teralokasi</p>
                            <p class="text-sm font-bold text-on-surface-variant">{{ number_format($line['qty_assigned'], 0) }}</p>
                        </div>
                        <div class="bg-surface-container rounded-lg p-2">
                            <p class="text-[9px] text-on-surface-variant uppercase">Sisa</p>
                            <p class="text-sm font-bold {{ $line['qty_remaining'] > 0 ? 'text-error' : 'text-success' }}">{{ number_format($line['qty_remaining'], 0) }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
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

            {{-- DESKTOP TABLE --}}
            <div class="hidden md:block">
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

            {{-- MOBILE: Select All + Cards --}}
            <div class="md:hidden">
                <div class="flex items-center gap-2 px-1 mb-3 py-2 bg-surface-container border-b border-outline-variant rounded-lg">
                    <input type="checkbox" id="select-all-pallets-mobile"
                        class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer"
                        onchange="toggleAllPallets(this)">
                    <label for="select-all-pallets-mobile" class="text-xs font-semibold text-on-surface-variant">Pilih Semua</label>
                </div>
                <div class="space-y-3">
                    @forelse($pallets as $pallet)
                    @php
                        $isRecommended = in_array($pallet['pallet_code'], $recommended);
                        $isExpired = $pallet['expired'] && \Carbon\Carbon::parse($pallet['expired'])->isPast();
                    @endphp
                    <label class="block border {{ $isRecommended ? 'border-primary bg-primary/5' : 'border-outline-variant' }} rounded-xl p-4 shadow-sm cursor-pointer transition-colors">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" name="pallets[]" value="{{ $pallet['pallet_code'] }}"
                                class="w-5 h-5 mt-0.5 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer pallet-checkbox"
                                {{ $isRecommended ? 'checked' : '' }}
                                onchange="syncSelectAll()">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <p class="text-sm font-bold text-on-surface font-mono truncate">{{ $pallet['pallet_code'] }}</p>
                                    @if($isRecommended)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-primary/10 text-primary shrink-0">
                                        <span class="material-symbols-outlined text-xs">auto_awesome</span>
                                        FEFO
                                    </span>
                                    @endif
                                </div>
                                <p class="text-xs text-on-surface mb-2">{{ $pallet['product_nama'] }}</p>
                                <div class="grid grid-cols-3 gap-2 text-center mb-2">
                                    <div class="bg-surface-container rounded-lg p-1.5">
                                        <p class="text-[9px] text-on-surface-variant uppercase">Qty</p>
                                        <p class="text-xs font-bold text-on-surface">{{ number_format($pallet['total_qty'], 0) }}</p>
                                    </div>
                                    <div class="bg-surface-container rounded-lg p-1.5">
                                        <p class="text-[9px] text-on-surface-variant uppercase">Barcodes</p>
                                        <p class="text-xs font-bold text-on-surface">{{ $pallet['barcode_count'] }}</p>
                                    </div>
                                    <div class="bg-surface-container rounded-lg p-1.5">
                                        <p class="text-[9px] text-on-surface-variant uppercase">Expired</p>
                                        <p class="text-xs font-bold {{ $isExpired ? 'text-error' : 'text-on-surface' }}">{{ $pallet['expired'] ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-on-surface-variant">
                                    <span>{{ $pallet['lokasi_nama'] }} ({{ $pallet['gudang_nama'] }})</span>
                                </div>
                            </div>
                        </div>
                    </label>
                    @empty
                    <div class="text-center p-4 text-on-surface-variant">Tidak ada pallet tersedia.</div>
                    @endforelse
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

            {{-- DESKTOP TABLE --}}
            <div class="hidden md:block">
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

            {{-- MOBILE CARDS --}}
            <div class="md:hidden space-y-3">
                @foreach($existingTasks as $item)
                <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-sm font-bold text-on-surface font-mono truncate">{{ $item['pallet_code'] }}</p>
                        @php
                            $taskStatusColors = [
                                'Pending' => 'bg-neutral/10 text-neutral',
                                'In Progress' => 'bg-warning/10 text-warning',
                                'Done' => 'bg-success/10 text-success',
                            ];
                            $taskStatusColor = $taskStatusColors[$item['status']] ?? 'bg-neutral/10 text-neutral';
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $taskStatusColor }} shrink-0">{{ $item['status'] }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Lokasi Saat Ini</p>
                            @if($item['is_staging'])
                            <p class="text-xs font-medium text-success flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">check_circle</span>
                                {{ $item['dari'] }}
                            </p>
                            @else
                            <p class="text-xs font-medium text-on-surface">{{ $item['dari'] }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tujuan</p>
                            <p class="text-xs font-medium text-on-surface font-mono">{{ $item['task']->forklift_lokasi_tujuan ?? '-' }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="fixed left-0 right-0 lg:left-72 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-3 md:px-6 py-2 md:py-3 z-[45] md:!bottom-0" style="bottom: 4rem">
            <div class="flex items-center justify-end gap-2 md:gap-3">
                <a href="{{ route('wms-keluar.getTable') }}"
                    class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all shrink-0">
                    <span class="material-symbols-outlined text-base md:text-xl">arrow_back</span>
                    <span class="hidden sm:inline">Kembali</span>
                </a>
                @if($existingTasks->isNotEmpty())
                <a href="{{ route('wms-keluar.pickList', ['outCode' => $keluar->out_code]) }}"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-purple-100 text-purple-700 hover:bg-purple-200 transition-all active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-base md:text-xl">print</span>
                    <span class="hidden sm:inline">Print Pick List</span>
                </a>
                @endif
                <button type="submit"
                    class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-base md:text-xl">check</span>
                    <span class="hidden sm:inline">Simpan Rencana</span>
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
            const selectAllMobile = document.getElementById('select-all-pallets-mobile');
            if (selectAll) {
                selectAll.checked = all.length > 0 && all.length === checked.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }
            if (selectAllMobile) {
                selectAllMobile.checked = all.length > 0 && all.length === checked.length;
                selectAllMobile.indeterminate = checked.length > 0 && checked.length < all.length;
            }
        }
    </script>
</x-layouts::app>
