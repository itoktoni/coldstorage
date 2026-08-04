<?php /** @var App\Models\So $so */ ?>
<?php /** @var App\Models\SoPrepare $prepare */ ?>
<?php /** @var array $lines */ ?>
<?php /** @var \Illuminate\Support\Collection $stockRows */ ?>

<div>
    {{-- SO Info --}}
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">point_of_sale</span>
            Sales Order - {{ $so->so_code }}
        </h3>
        <div class="grid grid-cols-3 gap-3 md:gap-5">
            <div>
                <div class="text-[10px] md:text-xs text-on-surface-variant uppercase tracking-widest mb-0.5 md:mb-1">Customer</div>
                <div class="text-xs md:text-body-sm font-bold truncate">{{ $so->customer->customer_nama ?? '-' }}</div>
            </div>
            <div>
                <div class="text-[10px] md:text-xs text-on-surface-variant uppercase tracking-widest mb-0.5 md:mb-1">Tanggal</div>
                <div class="text-xs md:text-body-sm font-bold">{{ $so->so_tanggal?->format('d M Y') ?? '-' }}</div>
            </div>
            <div>
                <div class="text-[10px] md:text-xs text-on-surface-variant uppercase tracking-widest mb-0.5 md:mb-1">Status</div>
                <div class="text-xs md:text-body-sm font-bold">
                    @if($prepare->so_prepare_status === 'Done')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] md:text-xs font-medium bg-success/10 text-success">Done</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] md:text-xs font-medium bg-warning/10 text-warning">Pending</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Scan --}}
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">qr_code_scanner</span>
            Scan Barang Staging
        </h3>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2 sm:gap-3">
            <div class="flex-1">
                <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Stock Code</label>
                <input type="text"
                       wire:model="barcodeInput"
                       x-on:keydown.enter.prevent="$wire.scan($el.value); $el.value = ''"
                       placeholder="Scan barcode stock dari staging"
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
        <p class="text-xs text-on-surface-variant mt-2">Scan otomatis alokasikan sisa qty SO dari stock staging.</p>

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

    {{-- Alokasi Saat Ini --}}
    @if($allocations->isNotEmpty())
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">history</span>
            Alokasi Saat Ini
        </h3>

        {{-- Mobile: card list --}}
        <div class="space-y-2 md:hidden">
            @foreach($allocations as $alloc)
            <div class="flex items-center justify-between p-3 bg-surface-container-low rounded-lg border border-outline-variant/50">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">{{ $alloc->product->product_nama ?? '-' }}</div>
                    <div class="text-xs text-primary font-semibold mt-0.5">{{ formatQty($alloc->so_prepare_detail_qty) }}</div>
                </div>
                <button type="button"
                        wire:click="removeAllocation({{ $alloc->so_prepare_detail_id }})"
                        wire:confirm="Yakin ingin menghapus alokasi ini?"
                    class="ml-3 shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                    <span class="material-symbols-outlined text-lg">delete</span>
                </button>
            </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Qty Dialokasi</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allocations as $alloc)
                    <tr class="border-b border-outline-variant/50">
                        <td class="py-2 px-3 font-body-sm font-medium">{{ $alloc->product->product_nama ?? '-' }}</td>
                        <td class="py-2 px-3 font-body-sm text-primary">{{ formatQty($alloc->so_prepare_detail_qty) }}</td>
                        <td class="py-2 px-3 text-right">
                            <button type="button"
                                    wire:click="removeAllocation({{ $alloc->so_prepare_detail_id }})"
                                    wire:confirm="Yakin ingin menghapus alokasi ini?"
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
    </div>
    @endif

    {{-- Line Status --}}
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">checklist</span>
            Kebutuhan Item SO
        </h3>

        {{-- Mobile: card list --}}
        <div class="space-y-2 md:hidden">
            @foreach($lines as $line)
            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/50">
                <div class="text-sm font-medium truncate">{{ $line['detail']->product->product_nama ?? '-' }}</div>
                <div class="flex items-center justify-between mt-1.5 text-xs">
                    <span class="text-on-surface-variant">Dibutuhkan: <strong>{{ formatQty($line['qty_needed']) }}</strong></span>
                    <span class="text-primary">Teralokasi: <strong>{{ formatQty($line['qty_assigned']) }}</strong></span>
                </div>
                <div class="mt-1 text-xs {{ $line['qty_remaining'] <= 0 ? 'text-success' : 'text-error' }}">
                    Sisa: <strong>{{ formatQty($line['qty_remaining']) }}</strong>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Dibutuhkan</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Teralokasi</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $line)
                    <tr class="border-b border-outline-variant/50">
                        <td class="py-2 px-3 font-body-sm font-medium">{{ $line['detail']->product->product_nama ?? '-' }}</td>
                        <td class="py-2 px-3 font-body-sm text-right">{{ formatQty($line['qty_needed']) }}</td>
                        <td class="py-2 px-3 font-body-sm text-right text-primary">{{ formatQty($line['qty_assigned']) }}</td>
                        <td class="py-2 px-3 font-body-sm text-right {{ $line['qty_remaining'] <= 0 ? 'text-success' : 'text-error' }}">{{ formatQty($line['qty_remaining']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @php
            $allFulfilled = collect($lines)->every(fn($l) => $l['qty_remaining'] <= 0);
            $hasAllocations = $allocations->isNotEmpty();
            $isPending = $prepare->so_prepare_status !== 'Done';
        @endphp

        @if($isPending && $hasAllocations && !$allFulfilled)
        <div class="mt-3 md:mt-4 bg-warning/10 border border-warning/30 rounded-xl p-3 md:p-4">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-warning text-xl mt-0.5">info</span>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-on-surface">Selesaikan Prepare (Partial)</p>
                    <p class="text-xs text-on-surface-variant mt-1">Masih ada item yang belum terpenuhi sepenuhnya. Invoice akan dibuat dari qty yang benar-benar dialokasi.</p>
                    <button type="button"
                            wire:click="completePrepare"
                            wire:confirm="Yakin ingin menyelesaikan prepare? Qty yang belum terpenuhi tidak akan dikirim."
                            class="mt-3 inline-flex items-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-warning text-on-warning hover:bg-warning/90 shadow-sm transition-all active:scale-95">
                        <span class="material-symbols-outlined text-xl">check_circle</span>
                        Selesaikan Prepare
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Stock Tersedia di Staging --}}
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
            Stock Tersedia di Staging
        </h3>

        @if($stockRows->isEmpty())
        <p class="text-on-surface-variant text-sm">Tidak ada stock di staging untuk product di SO ini.</p>
        @else

        {{-- Mobile: card list --}}
        <div class="space-y-3 md:hidden">
            @foreach($stockRows as $sr)
            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/50">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold font-mono truncate">{{ $sr['stock_code'] }}</div>
                        <div class="text-xs text-on-surface-variant truncate mt-0.5">{{ $sr['product']->product_nama ?? '-' }}</div>
                        <div class="text-[10px] text-on-surface-variant mt-0.5">{{ $sr['lokasi_nama'] }} ({{ $sr['gudang_nama'] }})</div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs text-on-surface-variant">Qty: <strong>{{ formatQty($sr['stock_qty']) }}</strong></div>
                        <div class="text-xs text-primary">Terpakai: <strong>{{ formatQty($sr['qty_assigned']) }}</strong></div>
                        <div class="text-xs {{ $sr['qty_remaining'] <= 0 ? 'text-success' : 'text-on-surface-variant' }}">Sisa: <strong>{{ formatQty($sr['qty_remaining']) }}</strong></div>
                    </div>
                </div>
                @if($sr['qty_remaining'] > 0)
                <div class="flex items-center gap-2 mt-3">
                    <input type="number"
                           wire:model="assignQtys.{{ $sr['stock_id'] }}"
                           value="{{ rtrim(rtrim(number_format($sr['qty_remaining'], 3, '.', ''), '0'), '.') }}"
                           min="0.001"
                           max="{{ $sr['qty_remaining'] }}"
                           step="0.001"
                           class="flex-1 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                    <button type="button"
                            wire:click="assignStock({{ $sr['stock_id'] }}, $wire.get('assignQtys.{{ $sr['stock_id'] }}'))"
                        class="shrink-0 inline-flex items-center gap-1 h-9 px-4 text-sm font-semibold rounded-lg bg-success text-on-primary hover:bg-success/90 transition-all active:scale-95">
                        <span class="material-symbols-outlined text-base">add_link</span>
                        Alokasi
                    </button>
                </div>
                @else
                <div class="mt-2 text-xs text-success font-semibold">Habis</div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Stock Code</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Lokasi</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Terpakai</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Sisa</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Alokasi Manual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockRows as $sr)
                    <tr class="border-b border-outline-variant/50">
                        <td class="py-2 px-3 font-body-sm font-mono">{{ $sr['stock_code'] }}</td>
                        <td class="py-2 px-3 font-body-sm">{{ $sr['product']->product_nama ?? '-' }}</td>
                        <td class="py-2 px-3 font-body-sm">{{ $sr['lokasi_nama'] }} <span class="text-on-surface-variant">({{ $sr['gudang_nama'] }})</span></td>
                        <td class="py-2 px-3 font-body-sm text-right">{{ formatQty($sr['stock_qty']) }}</td>
                        <td class="py-2 px-3 font-body-sm text-right text-primary">{{ formatQty($sr['qty_assigned']) }}</td>
                        <td class="py-2 px-3 font-body-sm text-right {{ $sr['qty_remaining'] <= 0 ? 'text-success' : 'text-on-surface-variant' }}">{{ formatQty($sr['qty_remaining']) }}</td>
                        <td class="py-2 px-3 text-right">
                            @if($sr['qty_remaining'] > 0)
                            <div class="inline-flex items-center gap-2 justify-end">
                                <input type="number"
                                       wire:model="assignQtys.{{ $sr['stock_id'] }}"
                                       value="{{ rtrim(rtrim(number_format($sr['qty_remaining'], 3, '.', ''), '0'), '.') }}"
                                       min="0.001"
                                       max="{{ $sr['qty_remaining'] }}"
                                       step="0.001"
                                       class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                                <button type="button"
                                        wire:click="assignStock({{ $sr['stock_id'] }}, $wire.get('assignQtys.{{ $sr['stock_id'] }}'))"
                                    class="inline-flex items-center gap-1 h-9 px-3 text-sm font-semibold rounded-lg bg-success text-on-primary hover:bg-success/90 transition-all active:scale-95">
                                    <span class="material-symbols-outlined text-base">add_link</span>
                                    Alokasi
                                </button>
                            </div>
                            @else
                            <span class="text-xs text-success font-semibold">Habis</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="mt-4 md:mt-6 mb-12 flex items-center gap-3">
        <a href="{{ route('wms-so-prepare.index') }}"
           class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Kembali
        </a>
    </div>
</div>
