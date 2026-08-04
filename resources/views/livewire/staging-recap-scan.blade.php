<?php
/** @var App\Models\Lokasi $lokasi */
/** @var array $items */
/** @var array $rackLokasis */
/** @var string $palletCode */
?>

<div>
    {{-- Header --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">place</span>
                    <h2 class="font-headline-md text-headline-md text-on-surface font-bold">{{ $lokasi->lokasi_nama }}</h2>
                </div>
                <p class="text-xs text-on-surface-variant mt-1 font-mono">{{ $lokasi->lokasi_code }} • {{ count(array_filter($items, fn($i) => !$i['removed'])) }} items</p>
            </div>
            <a href="{{ route('wms-staging-recap.index') }}"
               class="inline-flex items-center justify-center gap-2 h-10 px-4 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Kembali
            </a>
        </div>
    </div>

    {{-- Scan --}}
    <div class="bg-surface-container-lowest mt-4 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">qr_code_scanner</span>
            Tambah Barang
        </h3>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2 sm:gap-3">
            <div class="flex-1">
                <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Barcode Stock</label>
                <input type="text"
                       wire:model="barcodeInput"
                       x-on:keydown.enter.prevent="$wire.scan($el.value); $el.value = ''"
                       placeholder="Scan barcode barang di staging"
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

    {{-- Pallet Code --}}
    <div class="bg-surface-container-lowest mt-4 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">qr_code</span>
            Pallet Code
        </h3>
        <div class="flex items-center gap-3">
            <input type="text"
                   wire:model="palletCode"
                   class="flex-1 h-11 px-3 bg-white border border-outline-variant rounded-lg font-body-sm font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
            <button type="button"
                    wire:click="regeneratePallet"
                class="inline-flex items-center gap-1 h-11 px-4 text-sm font-semibold rounded-lg bg-surface-container-high text-on-surface hover:bg-surface-container-highest transition-colors">
                <span class="material-symbols-outlined text-lg">refresh</span>
                Regenerate
            </button>
        </div>
    </div>

    {{-- Rekap Stock --}}
    <div class="bg-surface-container-lowest mt-4 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
            Rekap Stock
        </h3>

        @php
            $activeItems = collect($items)->filter(fn($i) => !$i['removed']);
            $totalQty = $activeItems->sum('stock_qty');
        @endphp

        @if($activeItems->isEmpty())
        <p class="text-on-surface-variant text-sm">Tidak ada stock. Scan barcode untuk menambah.</p>
        @else

        {{-- Mobile: card list --}}
        <div class="space-y-3 md:hidden">
            @foreach($activeItems as $idx => $item)
            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/50">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ $item['product_nama'] }}</div>
                        <div class="text-xs text-on-surface-variant font-mono mt-0.5">{{ $item['stock_code'] }}</div>
                        <div class="text-xs text-on-surface-variant mt-0.5">
                            Qty: <strong class="text-primary">{{ formatQty($item['stock_qty']) }}</strong>
                        </div>
                    </div>
                    <button type="button"
                            wire:click="removeItem({{ $idx }})"
                            class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
                <div class="mt-2">
                    <label class="text-[10px] text-on-surface-variant uppercase tracking-widest">Rack Tujuan</label>
                    <select wire:model="items.{{ $idx }}.rack_code"
                            class="w-full h-9 px-3 mt-1 bg-white border border-outline-variant rounded-lg text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                        <option value="">-- Pilih Rack --</option>
                        @foreach($rackLokasis as $rack)
                        <option value="{{ $rack['code'] }}" {{ $item['rack_code'] === $rack['code'] ? 'selected' : '' }}>{{ $rack['nama'] }}</option>
                        @endforeach
                    </select>
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
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Stock Code</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Rack Tujuan</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeItems as $idx => $item)
                    <tr class="border-b border-outline-variant/50">
                        <td class="py-2 px-3 font-body-sm font-medium">{{ $item['product_nama'] }}</td>
                        <td class="py-2 px-3 font-body-sm font-mono text-on-surface-variant">{{ $item['stock_code'] }}</td>
                        <td class="py-2 px-3 font-body-sm text-right text-primary font-semibold">{{ formatQty($item['stock_qty']) }}</td>
                        <td class="py-2 px-3">
                            <select wire:model="items.{{ $idx }}.rack_code"
                                    class="w-full h-9 px-3 bg-white border border-outline-variant rounded-lg text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                                <option value="">-- Pilih Rack --</option>
                                @foreach($rackLokasis as $rack)
                                <option value="{{ $rack['code'] }}" {{ $item['rack_code'] === $rack['code'] ? 'selected' : '' }}>{{ $rack['nama'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-2 px-3 text-right">
                            <button type="button"
                                    wire:click="removeItem({{ $idx }})"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Summary --}}
        <div class="flex items-center justify-between mt-4 pt-3 border-t border-outline-variant">
            <div class="text-sm text-on-surface-variant">
                Items: <strong>{{ $activeItems->count() }}</strong> &nbsp;|&nbsp;
                Total Qty: <strong class="text-primary">{{ formatQty($totalQty) }}</strong>
            </div>
        </div>

        {{-- Confirm --}}
        <div class="mt-4">
            <button type="button"
                    wire:click="confirmRecap"
                    wire:confirm="Yakin ingin konfirmasi rekap? Stock akan dipindahkan dari staging ke rack dan forklift task akan dibuat."
                    {{ $activeItems->isEmpty() ? 'disabled' : '' }}
                class="inline-flex items-center justify-center gap-2 h-11 px-6 text-sm font-semibold rounded-lg bg-success text-on-primary hover:bg-success/90 shadow-sm transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                Konfirmasi & Buat Putaway Task
            </button>
        </div>

        @endif
    </div>
</div>
