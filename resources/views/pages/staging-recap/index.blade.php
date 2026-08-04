<?php /** @var \Illuminate\Support\Collection $lokasis */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Staging Recap']]" />

    <div class="content mt-4 lg:mt-0">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">Staging Recap</h2>
                <p class="text-sm text-on-surface-variant mt-1">Pilih lokasi staging untuk rekap sisa stock dan buat putaway task.</p>
            </div>
        </div>

        @if($lokasis->isEmpty())
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 text-center">
            <span class="material-symbols-outlined text-4xl text-on-surface-variant">inventory_2</span>
            <p class="text-on-surface-variant mt-2">Tidak ada stock di staging.</p>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($lokasis as $lokasi)
            <a href="{{ route('wms-staging-recap.show', $lokasi->lokasi_code) }}"
               class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 hover:border-primary hover:shadow-md transition-all group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">place</span>
                            <h3 class="font-headline-md text-headline-sm text-on-surface font-bold">{{ $lokasi->lokasi_nama }}</h3>
                        </div>
                        <p class="text-xs text-on-surface-variant mt-1 font-mono">{{ $lokasi->lokasi_code }}</p>
                    </div>
                    <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">chevron_right</span>
                </div>
                <div class="flex items-center gap-4 mt-4 pt-3 border-t border-outline-variant/50">
                    <div>
                        <div class="text-[10px] text-on-surface-variant uppercase tracking-widest">Items</div>
                        <div class="text-lg font-bold text-on-surface">{{ $lokasi->total_stock }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-on-surface-variant uppercase tracking-widest">Total Qty</div>
                        <div class="text-lg font-bold text-primary">{{ formatQty($lokasi->total_qty_sum_stock_qty ?? 0) }}</div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</x-layouts::app>
