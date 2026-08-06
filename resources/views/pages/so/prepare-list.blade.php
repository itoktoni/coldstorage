<?php /** @var \Illuminate\Support\Collection $rows */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-so.getTable'), 'label' => 'Sales Order'], ['url' => '', 'label' => 'Prepare SO (Warehouse)']]" />

    <div class="content mt-4 lg:mt-0">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">assignment</span>
                Prepare SO - Petugas Warehouse
            </h3>
            <p class="text-on-surface-variant text-sm">
                Daftar Sales Order. Status <strong>Prepare</strong> = bisa di-scan, <strong>Confirmed</strong> = sudah selesai diproses.
            </p>
        </div>

        @if($errors->any())
        <div class="bg-error/10 border border-error rounded-xl p-4 mt-5">
            <ul class="list-disc list-inside text-error font-body-sm">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- DESKTOP TABLE --}}
        <div class="hidden md:block bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-5 form-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant">
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Kode SO</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Customer</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Tanggal</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Staging</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Teralokasi</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Progress</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Status</th>
                            <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        @php $so = $row['so']; @endphp
                        <tr class="border-b border-outline-variant/50">
                            <td class="py-2 px-3 font-body-sm font-medium">{{ $so->so_code }}</td>
                            <td class="py-2 px-3 font-body-sm">{{ $so->customer->customer_nama ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm">{{ $so->so_tanggal?->format('d M Y') ?? '-' }}</td>
                            <td class="py-2 px-3 font-body-sm text-right">{{ $row['total_qty'] }}</td>
                            <td class="py-2 px-3 font-body-sm text-right text-on-surface-variant">{{ $row['picked_qty'] }}</td>
                            <td class="py-2 px-3 font-body-sm text-right text-primary">{{ $row['assigned_qty'] }}</td>
                            <td class="py-2 px-3 font-body-sm">
                                <div class="w-32 h-2 bg-outline-variant/40 border border-outline-variant rounded-full overflow-hidden">
                                    <div class="h-full {{ $row['is_done'] ? 'bg-success' : 'bg-primary' }}" style="width: {{ $row['progress'] }}%"></div>
                                </div>
                                <div class="text-xs {{ $row['is_done'] ? 'text-success' : 'text-on-surface-variant' }} mt-1">{{ $row['progress'] }}%</div>
                            </td>
                            <td class="py-2 px-3">
                                @if($row['is_done'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">Confirmed</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning">Prepare</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right">
                                @if($row['is_done'])
                                    <span class="inline-flex items-center gap-1 h-9 px-3 text-sm font-semibold rounded-lg bg-success/10 text-success cursor-not-allowed">
                                        <span class="material-symbols-outlined text-base">check_circle</span>
                                        Selesai
                                    </span>
                                @else
                                    <a href="{{ route('wms-so-prepare.show', ['soId' => $so->so_id]) }}"
                                       class="inline-flex items-center gap-1 h-9 px-3 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 transition-all">
                                        <span class="material-symbols-outlined text-base">qr_code_scanner</span>
                                        Scan
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-6 text-on-surface-variant">Tidak ada data SO.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MOBILE CARDS --}}
        <div class="md:hidden mt-5 space-y-3">
            @forelse($rows as $row)
            @php $so = $row['so']; @endphp
            <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-sm font-bold text-on-surface truncate">{{ $so->so_code }}</p>
                    @if($row['is_done'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-success/10 text-success shrink-0">Confirmed</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-warning/10 text-warning shrink-0">Prepare</span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Customer</p>
                        <p class="text-xs font-medium text-on-surface truncate">{{ $so->customer->customer_nama ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                        <p class="text-xs font-medium text-on-surface">{{ $so->so_tanggal?->format('d M Y') ?? '-' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-3 text-center">
                    <div class="bg-surface-container rounded-lg p-2">
                        <p class="text-[9px] text-on-surface-variant uppercase">Qty</p>
                        <p class="text-sm font-bold text-on-surface">{{ $row['total_qty'] }}</p>
                    </div>
                    <div class="bg-surface-container rounded-lg p-2">
                        <p class="text-[9px] text-on-surface-variant uppercase">Staging</p>
                        <p class="text-sm font-bold text-on-surface-variant">{{ $row['picked_qty'] }}</p>
                    </div>
                    <div class="bg-surface-container rounded-lg p-2">
                        <p class="text-[9px] text-on-surface-variant uppercase">Alokasi</p>
                        <p class="text-sm font-bold text-primary">{{ $row['assigned_qty'] }}</p>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] text-on-surface-variant">Progress</span>
                        <span class="text-[10px] font-bold {{ $row['is_done'] ? 'text-success' : 'text-on-surface' }}">{{ $row['progress'] }}%</span>
                    </div>
                    <div class="w-full h-2 bg-outline-variant/40 border border-outline-variant rounded-full overflow-hidden">
                        <div class="h-full {{ $row['is_done'] ? 'bg-success' : 'bg-primary' }} rounded-full transition-all" style="width: {{ $row['progress'] }}%"></div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                    <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $so->so_id }}</span>
                    @if($row['is_done'])
                        <span class="inline-flex items-center gap-1 h-8 px-3 text-xs font-semibold rounded-lg bg-success/10 text-success cursor-not-allowed">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            Selesai
                        </span>
                    @else
                        <a href="{{ route('wms-so-prepare.show', ['soId' => $so->so_id]) }}"
                           class="inline-flex items-center gap-1 h-8 px-3 text-xs font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 transition-all active:scale-95">
                            <span class="material-symbols-outlined text-base">qr_code_scanner</span>
                            Scan
                        </a>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center p-6 text-on-surface-variant">Tidak ada data SO.</div>
            @endforelse
        </div>
    </div>
</x-layouts::app>
