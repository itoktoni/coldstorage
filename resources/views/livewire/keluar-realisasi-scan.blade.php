<?php /** @var App\Models\KeluarDetail $detail */ ?>

<div>
    {{-- Keluar Detail Info --}}
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">inventory</span>
            Keluar Detail - {{ $detail->out_detail_code }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-5">
            <div>
                <div class="text-[10px] md:text-xs text-on-surface-variant uppercase tracking-widest mb-0.5 md:mb-1">Kode Keluar</div>
                <div class="text-xs md:text-body-sm font-bold font-mono">{{ $detail->out_detail_code_keluar }}</div>
            </div>
            <div>
                <div class="text-[10px] md:text-xs text-on-surface-variant uppercase tracking-widest mb-0.5 md:mb-1">Product</div>
                <div class="text-xs md:text-body-sm font-bold truncate">{{ $detail->product->product_nama ?? '-' }}</div>
            </div>
            <div>
                <div class="text-[10px] md:text-xs text-on-surface-variant uppercase tracking-widest mb-0.5 md:mb-1">Kode SO</div>
                <div class="text-xs md:text-body-sm font-bold font-mono">{{ $detail->soDetail?->so?->so_code ?? '-' }}</div>
            </div>
            <div>
                <div class="text-[10px] md:text-xs text-on-surface-variant uppercase tracking-widest mb-0.5 md:mb-1">Status</div>
                <div class="text-xs md:text-body-sm font-bold">
                    @if($qtyRemaining <= 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] md:text-xs font-medium bg-success/10 text-success">Selesai</span>
                    @elseif($qtyPicked > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] md:text-xs font-medium bg-warning/10 text-warning">Proses</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] md:text-xs font-medium bg-surface-container-high text-on-surface-variant">Belum</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-3 md:mt-4 pt-3 md:pt-4 border-t border-outline-variant">
            <div class="text-center p-2 md:p-3 bg-surface-container-low rounded-lg">
                <div class="text-lg md:text-xl font-bold">{{ formatQty($qtyNeeded) }}</div>
                <div class="text-[10px] md:text-xs text-on-surface-variant">Dibutuhkan</div>
            </div>
            <div class="text-center p-2 md:p-3 bg-primary/5 rounded-lg">
                <div class="text-lg md:text-xl font-bold text-primary">{{ formatQty($qtyPicked) }}</div>
                <div class="text-[10px] md:text-xs text-on-surface-variant">Diambil</div>
            </div>
            <div class="text-center p-2 md:p-3 {{ $qtyRemaining > 0 ? 'bg-error/5' : 'bg-success/5' }} rounded-lg">
                <div class="text-lg md:text-xl font-bold {{ $qtyRemaining > 0 ? 'text-error' : 'text-success' }}">{{ formatQty($qtyRemaining) }}</div>
                <div class="text-[10px] md:text-xs text-on-surface-variant">Sisa</div>
            </div>
        </div>
    </div>

    {{-- Scan --}}
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">qr_code_scanner</span>
            Scan Barcode Staging
        </h3>

        @if($qtyRemaining <= 0)
        <div class="bg-success/10 border border-success/30 rounded-xl p-3 md:p-4">
            <p class="text-success font-body-sm font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                Item ini sudah terpenuhi sepenuhnya.
            </p>
        </div>
        @else
        <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2 sm:gap-3">
            <div class="flex-1">
                <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Barcode / Pallet / Lokasi</label>
                <input type="text"
                       wire:model="barcodeInput"
                       x-on:keydown.enter.prevent="$wire.scan($el.value); $el.value = ''"
                       placeholder="Scan barcode, pallet (P), atau lokasi (L)"
                       class="w-full h-11 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                       autofocus />
            </div>
            <button type="button"
                    x-on:click="$wire.scan(document.querySelector('[wire\\:model=barcodeInput]').value); document.querySelector('[wire\\:model=barcodeInput]').value = ''"
                class="inline-flex items-center justify-center gap-2 h-11 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">check</span>
                Scan
            </button>
        </div>
        <p class="text-xs text-on-surface-variant mt-2">Mode: B (barcode), P (pallet), L (lokasi). Scan dari staging area.</p>
        @endif

        {{-- Flash Messages --}}
        @if($errorMsg)
        <div class="bg-error/10 border border-error rounded-xl p-3 md:p-4 mt-3 md:mt-4">
            <p class="text-error font-body-sm font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">error</span>
                {{ $errorMsg }}
            </p>
        </div>
        @endif
        @if($successMsg)
        <div class="bg-success/10 border border-success rounded-xl p-3 md:p-4 mt-3 md:mt-4">
            <p class="text-success font-body-sm font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                {{ $successMsg }}
            </p>
        </div>
        @endif
    </div>

    {{-- Realisasi History --}}
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">history</span>
            Realisasi Pick ({{ $realisasiList->count() }})
        </h3>

        @if($realisasiList->isEmpty())
        <p class="text-on-surface-variant text-sm">Belum ada realisasi dari staging.</p>
        @else

        {{-- Mobile: card list --}}
        <div class="space-y-2 md:hidden">
            @foreach($realisasiList as $r)
            <div class="flex items-center justify-between p-3 bg-surface-container-low rounded-lg border border-outline-variant/50">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium font-mono truncate">{{ $r->stock->stock_code ?? '-' }}</div>
                    <div class="text-xs text-on-surface-variant mt-0.5">
                        {{ $r->stock->stock_code_lokasi ?? '-' }} &middot; {{ $r->created_at?->format('d M H:i') ?? '-' }}
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0 ml-3">
                    <span class="text-sm font-bold text-primary">{{ formatQty($r->out_realisasi_qty) }}</span>
                    <button type="button"
                            wire:click="removeRealisasi({{ $r->out_realisasi_id }})"
                            wire:confirm="Yakin ingin menghapus realisasi ini?"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Stock Code</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Lokasi</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Waktu</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($realisasiList as $r)
                    <tr class="border-b border-outline-variant/50">
                        <td class="py-2 px-3 font-body-sm font-mono">{{ $r->stock->stock_code ?? '-' }}</td>
                        <td class="py-2 px-3 font-body-sm">{{ $r->stock->stock_code_lokasi ?? '-' }}</td>
                        <td class="py-2 px-3 font-body-sm text-right text-primary font-medium">{{ formatQty($r->out_realisasi_qty) }}</td>
                        <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $r->created_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td class="py-2 px-3 text-right">
                            <button type="button"
                                    wire:click="removeRealisasi({{ $r->out_realisasi_id }})"
                                    wire:confirm="Yakin ingin menghapus realisasi ini?"
                                class="inline-flex items-center gap-1 h-9 px-3 text-sm font-semibold rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                                <span class="material-symbols-outlined text-base">delete</span>
                                Hapus
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="mt-4 md:mt-6 mb-12 flex items-center gap-3">
        <a href="{{ route('wms-keluar-detail.getTable') }}"
           class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Kembali
        </a>
    </div>
</div>
