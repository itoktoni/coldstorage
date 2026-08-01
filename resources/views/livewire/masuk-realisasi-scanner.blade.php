<?php /** @var App\Models\MasukDetail $model */ ?>
<?php /** @var App\Wms\MasukStatusEnum $status */ ?>

<div>
    <x-breadcrumb :items="[['url' => '/wms/masuk-detail/getTable', 'label' => 'Masuk Detail'], ['url' => '', 'label' => 'Realisasikan']]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Header Info --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                Realisasikan {{ $masukDetail->in_detail_code }}
            </h3>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                    <input type="text" value="{{ $masukDetail->product->product_nama ?? '-' }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qty Direncanakan</label>
                    <input type="text" value="{{ (float) $masukDetail->in_detail_qty }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <div class="flex items-center h-10">
                        <span class="badge badge-{{ $masukDetail->in_detail_status->badgeColor() }}">
                            {{ $masukDetail->in_detail_status->description() }}
                        </span>
                    </div>
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aksi Status</label>
                    @php
                        $nextStatuses = match($masukDetail->in_detail_status) {
                            \App\Wms\MasukStatusEnum::PENDING => [\App\Wms\MasukStatusEnum::PROCESS],
                            \App\Wms\MasukStatusEnum::PROCESS => [\App\Wms\MasukStatusEnum::READY],
                            \App\Wms\MasukStatusEnum::READY => [\App\Wms\MasukStatusEnum::COMPLETE],
                            default => [],
                        };
                    @endphp
                    @if(count($nextStatuses) > 0)
                    <select x-on:change="$wire.changeStatus($el.value); $el.value = ''"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Ubah ke...</option>
                        @foreach($nextStatuses as $next)
                        <option value="{{ $next->value }}">{{ $next->description() }}</option>
                        @endforeach
                    </select>
                    @else
                    <div class="flex items-center h-10 text-sm text-on-surface-variant">
                        <span class="material-symbols-outlined text-lg mr-1">check_circle</span>
                        Selesai
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Scanner Section --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code_scanner</span>
                Scanner
            </h3>

            {{-- Flash Messages --}}
            @if($error)
            <div class="bg-error/10 border border-error rounded-xl p-4 mb-4">
                <p class="text-error font-body-sm font-semibold">{{ $error }}</p>
            </div>
            @endif
            @if($success)
            <div class="bg-success/10 border border-success rounded-xl p-4 mb-4">
                <p class="text-success font-body-sm font-semibold">{{ $success }}</p>
            </div>
            @endif

            <div class="grid grid-cols-12 gap-4">
                {{-- USB Scanner Input --}}
                <div class="col-span-8">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Barcode Input (USB Scanner)</label>
                    <input type="text" 
                           wire:model="barcodeInput" 
                           x-on:keydown.enter.prevent="$wire.scan($el.value); $el.value = ''"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="Scan barcode di sini..."
                           autofocus />
                </div>

                {{-- Camera Scanner Button --}}
                <div class="col-span-4 flex items-end">
                    <button type="button" 
                            x-on:click="$dispatch('open-camera-scanner')"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        <span class="material-symbols-outlined text-lg mr-1">photo_camera</span>
                        Scan Camera
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary Table --}}
        <div wire:key="summary-card" class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">summarize</span>
                Summary Realisasi
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-on-surface-variant bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Total Qty</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3 font-medium">{{ $item->product->product_nama ?? '-' }}</td>
                            <td class="px-4 py-3">{{ (float) $item->total_qty }}</td>
                            <td class="px-4 py-3">
                                <button wire:click="getDetail({{ $item->in_realisasi_id_product }})" 
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors text-sm">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                    Detail
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-center text-on-surface-variant">Belum ada realisasi</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail Modal --}}
        @if($selectedProductId)
        <div wire:key="detail-modal-{{ $selectedProductId }}" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeDetail">
            <div class="bg-surface-container-lowest rounded-xl p-6 max-w-lg w-full mx-4" x-on:click.stop>
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant">
                    Detail Realisasi
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-on-surface-variant bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Barcode</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scans as $scan)
                            <tr class="border-b">
                                <td class="px-4 py-3">{{ (float) $scan->in_realisasi_qty }}</td>
                                <td class="px-4 py-3 text-xs">{{ $scan->in_realisasi_barcode }}</td>
                                <td class="px-4 py-3">
                                    <button wire:click="deleteScan({{ $scan->in_realisasi_id }})" 
                                            wire:confirm="Hapus scan ini?"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors text-sm">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-center text-on-surface-variant">Tidak ada data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <button wire:click="closeDetail" 
                            class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Camera Scanner Modal --}}
        <div x-data="{ show: false }" 
             x-on:open-camera-scanner.window="show = true"
             x-on:close-camera-scanner.window="show = false"
             x-show="show"
             x-cloak
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-surface-container-lowest rounded-xl p-6 max-w-lg w-full mx-4" x-on:click.stop>
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant">
                    Scan QR Code
                </h3>
                <div id="camera-scanner" class="w-full h-64 bg-gray-200 rounded-lg mb-4"></div>
                <div class="flex justify-end">
                    <button x-on:click="show = false; $dispatch('close-camera-scanner')" 
                            class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Camera Scanner Script --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            let html5QrcodeScanner = null;

            Livewire.on('open-camera-scanner', () => {
                const scannerDiv = document.getElementById('camera-scanner');
                if (!scannerDiv) return;

                html5QrcodeScanner = new Html5QrcodeScanner("camera-scanner", {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                });

                html5QrcodeScanner.render((decodedText) => {
                    @this.scan(decodedText);
                    html5QrcodeScanner.clear();
                    Livewire.dispatch('close-camera-scanner');
                }, (error) => {
                    // Ignore scan errors
                });
            });

            Livewire.on('close-camera-scanner', () => {
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                }
            });
        });
    </script>
</div>
