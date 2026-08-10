<div>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '/wms/split', 'label' => 'Split'], ['url' => '', 'label' => 'Produce']]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Messages --}}
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

        {{-- 1. Source --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                Produk Asal (Sumber)
            </h3>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 sm:col-span-8">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Produk <span class="text-error">*</span></label>
                    <select wire:model.live="sourceProductId" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">-- Pilih Produk Asal --</option>
                        @foreach ($products as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-12 sm:col-span-4 flex items-end">
                    <button wire:click="saveSource" wire:loading.attr="disabled"
                            class="w-full h-10 inline-flex items-center justify-center px-4 bg-primary text-on-primary rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium shadow-sm disabled:opacity-50">
                        <span wire:loading.remove class="material-symbols-outlined text-sm mr-1">save</span>
                        <span wire:loading class="loading loading-spinner mr-2"></span>
                        <span wire:loading.remove>Simpan</span>
                    </button>
                </div>
            </div>

            @if ($splitId)
            <div class="mt-3 p-3 bg-primary/5 border border-primary/20 rounded-lg">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                    <span class="text-sm text-primary font-medium">Split #{{ $splitId }} — {{ $sourceProductName }}</span>
                </div>
            </div>
            @endif
        </div>

        {{-- 2. Targets --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">call_split</span>
                Target Hasil Split
            </h3>

            @if ($splitId)
                {{-- Generate Form --}}
                <div class="bg-surface-container rounded-lg p-4 mb-4">
                    <h4 class="text-sm font-semibold text-on-surface mb-3">Generate Massal</h4>
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-12 sm:col-span-5">
                            <label class="block text-xs text-on-surface-variant mb-1">Produk Target</label>
                            <select wire:model="generateProductId" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">-- Pilih --</option>
                                @foreach ($products as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-5 sm:col-span-3">
                            <label class="block text-xs text-on-surface-variant mb-1">Qty per Item</label>
                            <input type="number" wire:model="generateQty" step="0.01" min="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="2.5" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <label class="block text-xs text-on-surface-variant mb-1">Banyaknya</label>
                            <input type="number" wire:model="generateJumlah" min="1"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="4" />
                        </div>
                        <div class="col-span-3 sm:col-span-2 flex items-end">
                            <button wire:click="generateTargets" wire:loading.attr="disabled"
                                    class="w-full h-10 inline-flex items-center justify-center px-3 bg-primary text-on-primary rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium shadow-sm disabled:opacity-50">
                                <span wire:loading.remove class="material-symbols-outlined text-sm">auto_fix_high</span>
                                <span wire:loading class="loading loading-spinner"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Target List --}}
                <div class="space-y-3">
                    @forelse ($targets as $index => $target)
                    <div class="border border-outline-variant rounded-lg p-3 bg-surface">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-on-surface-variant">Target #{{ $index + 1 }}</span>
                            <button wire:click="removeTarget({{ $index }})" class="text-error hover:text-error/80">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </div>
                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-12 sm:col-span-5">
                                <label class="block text-xs text-on-surface-variant mb-1">Produk</label>
                                <select wire:model="targets.{{ $index }}.product_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($products as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <label class="block text-xs text-on-surface-variant mb-1">Qty</label>
                                <input type="number" wire:model="targets.{{ $index }}.qty" step="0.01" min="0"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary" />
                            </div>
                            <div class="col-span-6 sm:col-span-4">
                                <label class="block text-xs text-on-surface-variant mb-1">Jumlah</label>
                                <input type="number" wire:model="targets.{{ $index }}.jumlah" min="1"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary" />
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6 text-on-surface-variant text-sm">
                        <span class="material-symbols-outlined text-4xl text-outline mb-2">add_circle</span>
                        <p>Belum ada target. Klik + atau generate di atas.</p>
                    </div>
                    @endforelse
                </div>

                @if (count($targets) > 0)
                <div class="mt-3 flex justify-end">
                    <button wire:click="addTarget" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors text-sm font-medium">
                        <span class="material-symbols-outlined text-sm">add</span>
                        Tambah Manual
                    </button>
                </div>
                @endif
            @else
                <div class="text-center py-8 text-on-surface-variant text-sm">
                    <span class="material-symbols-outlined text-4xl text-outline mb-2">info</span>
                    <p>Simpan produk asal terlebih dahulu untuk menambah target.</p>
                </div>
            @endif
        </div>

        {{-- 3. Waste --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">delete</span>
                Waste Product (Opsional)
            </h3>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 sm:col-span-8">
                    <select wire:model.live="wasteProductId" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">-- Tidak Ada Waste --</option>
                        @foreach ($products as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 4. Scanner --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code_scanner</span>
                Scan Barcode Sumber
            </h3>

            {{-- Source Product Badge --}}
            @if ($sourceProductName)
            <div class="mb-4 p-4 bg-primary/5 border-2 border-primary/30 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-2xl">inventory_2</span>
                    </div>
                    <div class="flex-1">
                        <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-0.5">Produk Asal</div>
                        <div class="text-lg font-bold text-primary">{{ $sourceProductName }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-on-surface-variant">Barcode</div>
                        <div class="text-lg font-bold text-on-surface">{{ count($scannedBarcodes) }}</div>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-8">
                    <input type="text"
                           wire:model="barcodeInput"
                           x-on:keydown.enter.prevent="$wire.scanBarcode($el.value); $el.value = ''"
                           @if ($sourceProductName) placeholder="Scan barcode produk yang sama..." @else placeholder="Scan barcode di sini..." @endif
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary"
                           autofocus />
                </div>
                <div class="col-span-4 flex items-end">
                    <button type="button"
                            x-on:click="$dispatch('open-camera-scanner')"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        <span class="material-symbols-outlined text-lg mr-1">photo_camera</span>
                        Scan
                    </button>
                </div>
            </div>

            {{-- Scanned Barcodes --}}
            @if (count($scannedBarcodes) > 0)
            <div class="mt-4 space-y-2">
                @foreach ($scannedBarcodes as $index => $scan)
                <div class="flex items-center gap-3 p-3 border border-outline-variant rounded-lg bg-surface">
                    <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">barcode</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-on-surface truncate">{{ $scan['stock_code'] }}</div>
                        <div class="text-xs text-on-surface-variant">{{ $scan['product_nama'] }} — {{ formatQty($scan['stock_qty']) }} kg</div>
                    </div>
                    <button wire:click="removeScan({{ $index }})" class="text-error hover:text-error/80">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- 5. Summary --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">info</span>
                Ringkasan
            </h3>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Total Sumber</span>
                    <span class="font-medium">{{ formatQty($totalSumber) }} </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Total Hasil ({{ count($targets) }} target)</span>
                    <span class="font-medium">{{ formatQty($totalHasil) }} </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Waste</span>
                    <span class="font-medium">{{ formatQty($qtyWaste) }} </span>
                </div>
                <div class="border-t border-outline-variant pt-3 flex items-center justify-between gap-3">
                    <span class="text-on-surface-variant">Penyusutan (kg)</span>
                    <input type="number" wire:model="penyusutan" step="0.01" min="0"
                           class="w-28 border border-gray-300 rounded-lg px-3 py-1.5 text-sm text-right focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-on-surface-variant">Expired Date (opsional)</span>
                    <input type="date" wire:model="expiredDate"
                           class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>
            </div>

            <div class="mt-4">
                <button wire:click="process" wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-success text-on-success rounded-lg hover:bg-success/90 transition-colors text-sm font-medium shadow-sm disabled:opacity-50"
                        @if (count($scannedBarcodes) == 0 || count($targets) == 0) disabled @endif>
                    <span wire:loading.remove class="material-symbols-outlined text-lg mr-1">play_arrow</span>
                    <span wire:loading class="loading loading-spinner mr-2"></span>
                    <span wire:loading.remove>Proses Split</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Camera Scanner Modal --}}
    <div x-data="cameraScanner()"
         x-on:open-camera-scanner.window="open()"
         x-on:close-camera-scanner.window="close()"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-surface-container-lowest rounded-xl p-6 max-w-lg w-full mx-4" x-on:click.stop>
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant">
                Scan Barcode / QR Code
            </h3>
            <div x-ref="scannerRegion" id="camera-scanner" class="w-full rounded-lg overflow-hidden mb-4" style="min-height: 300px;"></div>
            <template x-if="error">
                <div class="bg-error/10 border border-error rounded-lg p-3 mb-4">
                    <p class="text-error text-sm" x-text="error"></p>
                </div>
            </template>
            <div class="flex justify-between items-center">
                <button x-on:click="switchCamera()"
                        x-show="cameras.length > 1"
                        class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <span class="material-symbols-outlined text-lg mr-1">cameraswitch</span>
                    Ganti Kamera
                </button>
                <button x-on:click="close()"
                        class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        function cameraScanner() {
            return {
                show: false,
                scanner: null,
                cameras: [],
                currentCameraIndex: 0,
                error: null,

                open() {
                    this.show = true;
                    this.error = null;
                    this.$nextTick(() => this.startScanner());
                },

                close() {
                    this.stopScanner();
                    this.show = false;
                },

                startScanner() {
                    if (this.scanner) {
                        this.stopScanner();
                    }

                    this.scanner = new Html5Qrcode('camera-scanner');

                    const config = {
                        fps: 15,
                        qrbox: { width: 280, height: 150 },
                        aspectRatio: 1.5,
                        formatsToSupport: [
                            Html5QrcodeSupportedFormats.QR_CODE,
                            Html5QrcodeSupportedFormats.CODE_128,
                            Html5QrcodeSupportedFormats.CODE_39,
                            Html5QrcodeSupportedFormats.EAN_13,
                            Html5QrcodeSupportedFormats.EAN_8,
                            Html5QrcodeSupportedFormats.UPC_A,
                            Html5QrcodeSupportedFormats.UPC_E,
                            Html5QrcodeSupportedFormats.ITF,
                        ]
                    };

                    Html5Qrcode.getCameras().then(devices => {
                        this.cameras = devices || [];
                        if (this.cameras.length === 0) {
                            this.error = 'Kamera tidak ditemukan. Pastikan izin kamera diizinkan.';
                            return;
                        }

                        this.currentCameraIndex = this.cameras.findIndex(d =>
                            d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')
                        );
                        if (this.currentCameraIndex === -1) this.currentCameraIndex = 0;

                        this.startWithCamera(this.cameras[this.currentCameraIndex].id, config);
                    }).catch(err => {
                        this.error = 'Tidak bisa mengakses kamera. Pastikan izin kamera diizinkan di browser.';
                        console.error('Camera error:', err);
                    });
                },

                startWithCamera(cameraId, config) {
                    this.scanner.start(
                        cameraId,
                        config,
                        (decodedText) => {
                            this.onScanSuccess(decodedText);
                        },
                        (errorMessage) => {}
                    ).catch(err => {
                        this.error = 'Gagal memulai kamera: ' + (err.message || err);
                        console.error('Start camera error:', err);
                    });
                },

                onScanSuccess(decodedText) {
                    this.stopScanner();
                    this.show = false;

                    if (window.Livewire) {
                        Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('scanBarcode', decodedText);
                    }
                },

                switchCamera() {
                    if (this.cameras.length <= 1) return;
                    this.currentCameraIndex = (this.currentCameraIndex + 1) % this.cameras.length;
                    this.stopScanner();
                    const config = {
                        fps: 15,
                        qrbox: { width: 280, height: 150 },
                        aspectRatio: 1.5,
                    };
                    this.$nextTick(() => {
                        this.scanner = new Html5Qrcode('camera-scanner');
                        this.startWithCamera(this.cameras[this.currentCameraIndex].id, config);
                    });
                },

                stopScanner() {
                    if (this.scanner) {
                        try {
                            this.scanner.stop().then(() => {
                                this.scanner.clear();
                                this.scanner = null;
                            }).catch(() => {
                                this.scanner = null;
                            });
                        } catch (e) {
                            this.scanner = null;
                        }
                    }
                }
            };
        }
    </script>
</div>
