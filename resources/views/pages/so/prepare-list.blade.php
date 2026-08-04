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
                Daftar Sales Order berstatus <strong>Prepare</strong>. Forklift sudah mengambil barang ke staging,
                sekarang petugas warehouse tinggal scan SO lalu scan barang staging untuk tiap SO.
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

        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-5 form-card">
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
                                    <div class="h-full bg-primary" style="width: {{ $row['progress'] }}%"></div>
                                </div>
                                <div class="text-xs text-on-surface-variant mt-1">{{ $row['progress'] }}%</div>
                            </td>
                            <td class="py-2 px-3 text-right">
                                <a href="{{ route('wms-so-prepare.show', ['soId' => $so->so_id]) }}"
                                   class="inline-flex items-center gap-1 h-9 px-3 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 transition-all">
                                    <span class="material-symbols-outlined text-base">qr_code_scanner</span>
                                    Scan
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-on-surface-variant">Tidak ada SO berstatus Prepare.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::app>