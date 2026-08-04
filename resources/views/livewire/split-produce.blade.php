<div>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '/wms/split', 'label' => 'Split'], ['url' => '', 'label' => 'Produce']]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Error / Success --}}
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

        <div class="grid grid-cols-12 gap-5">
            {{-- Left: Form --}}
            <div class="col-span-12 lg:col-span-8">
                {{-- Target & Waste Product --}}
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
                    <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">call_split</span>
                        Split Production
                    </h3>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Produk Target (Hasil Split)</label>
                            <select wire:model.live="targetProductId" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">-- Pilih Produk Target --</option>
                                @foreach ($products as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Waste Product (Optional)</label>
                            <select wire:model.live="wasteProductId" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">-- Tidak Ada Waste --</option>
                                @foreach ($products as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Scanner --}}
                <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
                    <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">qr_code_scanner</span>
                        Scan Barcode Sumber
                    </h3>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Barcode Input (USB Scanner)</label>
                            <input type="text"
                                   wire:model="barcodeInput"
                                   x-on:keydown.enter.prevent="$wire.scanBarcode($el.value); $el.value = ''"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Scan barcode di sini..."
                                   autofocus />
                        </div>
                        <div class="col-span-4 flex items-end">
                            <button type="button"
                                    x-on:click="$dispatch('open-camera-scanner')"
                                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                <span class="material-symbols-outlined text-lg mr-1">photo_camera</span>
                                Scan Camera
                            </button>
                        </div>
                    </div>

                    {{-- Scanned Barcodes Table --}}
                    @if (count($scannedBarcodes) > 0)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barcode Sumber</label>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-on-surface-variant bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3">Barcode</th>
                                        <th class="px-4 py-3">Product</th>
                                        <th class="px-4 py-3">Qty</th>
                                        <th class="px-4 py-3">Expired</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($scannedBarcodes as $index => $scan)
                                    <tr class="border-b">
                                        <td class="px-4 py-3 text-xs font-mono">{{ $scan['stock_code'] }}</td>
                                        <td class="px-4 py-3">{{ $scan['product_nama'] }}</td>
                                        <td class="px-4 py-3">{{ number_format($scan['stock_qty'], 2) }}</td>
                                        <td class="px-4 py-3">{{ $scan['stock_expired_date'] ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <button wire:click="removeScan({{ $index }})"
                                                    class="inline-flex items-center px-2 py-1 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Qty Inputs --}}
                <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
                    <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">scale</span>
                        Quantity
                    </h3>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qty Hasil (kg)</label>
                            <input type="number" wire:model.live="qtyHasil" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary" step="0.01" min="0" />
                        </div>
                        <div class="col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qty Waste (kg)</label>
                            <input type="number" wire:model.live="qtyWaste" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary" step="0.01" min="0" />
                        </div>
                        <div class="col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Penyusutan (kg)</label>
                            <input type="text" value="{{ number_format($penyusutan, 2) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly />
                        </div>
                    </div>

                    <div class="mt-4">
                        <button wire:click="process" wire:loading.attr="disabled"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50"
                                @if (!$isValid) disabled @endif>
                            <span wire:loading.remove class="material-symbols-outlined text-lg mr-1">play_arrow</span>
                            <span wire:loading class="loading loading-spinner mr-2"></span>
                            <span wire:loading.remove>Proses Split</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right: Summary --}}
            <div class="col-span-12 lg:col-span-4">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
                    <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">info</span>
                        Ringkasan
                    </h3>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-on-surface-variant">Total Sumber</span>
                            <span class="font-medium">{{ number_format($totalSumber, 2) }} kg</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-on-surface-variant">Qty Hasil</span>
                            <span class="font-medium">{{ number_format($qtyHasil, 2) }} kg</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-on-surface-variant">Qty Waste</span>
                            <span class="font-medium">{{ number_format($qtyWaste, 2) }} kg</span>
                        </div>
                        <div class="border-t border-outline-variant pt-3 flex justify-between">
                            <span class="text-on-surface-variant">Penyusutan</span>
                            <span class="font-medium text-warning">{{ number_format($penyusutan, 2) }} kg</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    @this.scanBarcode(decodedText);
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
