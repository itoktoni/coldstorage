<?php /** @var App\Models\MasukDetail $model */ ?>
<?php /** @var App\Wms\MasukStatusEnum $status */ ?>

<div>
    <x-breadcrumb :items="[['url' => '/wms/masuk-detail/getTable', 'label' => 'Masuk Detail'], ['url' => '', 'label' => 'Realisasikan']]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Header Info --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                 {{ $masukDetail->in_detail_code }}
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] text-on-surface-variant uppercase tracking-wide mb-1">Product</label>
                    <p class="text-sm font-bold text-on-surface truncate">{{ $masukDetail->product->product_nama ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <label class="block text-[10px] text-on-surface-variant uppercase tracking-wide mb-1">Qty Direncanakan</label>
                    <p class="text-sm font-bold text-on-surface">{{ (float) $masukDetail->in_detail_qty }}</p>
                </div>
                <div>
                    <label class="block text-[10px] text-on-surface-variant uppercase tracking-wide mb-1">Status</label>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $masukDetail->in_detail_status->badgeColor() }}/10 text-{{ $masukDetail->in_detail_status->badgeColor() }}">
                        {{ $masukDetail->in_detail_status->description() }}
                    </span>
                </div>
                <div class="text-right">
                    <label class="block text-[10px] text-on-surface-variant uppercase tracking-wide mb-1">Aksi Status</label>
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
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Ubah ke...</option>
                        @foreach($nextStatuses as $next)
                        <option value="{{ $next->value }}">{{ $next->description() }}</option>
                        @endforeach
                    </select>
                    @else
                    <div class="flex items-center gap-1 text-sm text-success">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        Selesai
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Staging Area --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">warehouse</span>
                Staging Area
            </h3>
            <div>
                <label class="block text-[10px] text-on-surface-variant uppercase tracking-wide mb-1">Staging Area</label>
                <select wire:model.live="stagingCode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Pilih Staging</option>
                    @foreach(\App\Models\Lokasi::where('lokasi_category', 'staging')->get() as $s)
                    <option value="{{ $s->lokasi_code }}">{{ $s->lokasi_nama }}</option>
                    @endforeach
                </select>
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

            {{-- Existing Stock Barcode Warning --}}
            @if(count($existingStockBarcodes) > 0)
            @php
                $totalScans = \App\Models\MasukRealisasi::where('in_realisasi_masuk_code', $masukDetail->in_detail_code)->count();
            @endphp
            <div class="bg-warning/10 border border-warning rounded-xl p-4 mb-4">
                <p class="text-warning font-body-sm font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">warning</span>
                    {{ count($existingStockBarcodes) }} dari {{ $totalScans }} barcode sudah terdaftar di stock
                </p>
                <ul class="mt-2 text-sm text-on-surface-variant space-y-1">
                    @foreach($existingStockBarcodes as $bc)
                    <li class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-error">block</span>
                        <code class="text-xs bg-error/10 px-2 py-0.5 rounded">{{ $bc }}</code>
                    </li>
                    @endforeach
                </ul>
                <p class="mt-2 text-xs text-on-surface-variant">Hapus barcode ini dari realisasi agar status bisa menjadi READY.</p>
            </div>
            @endif


            <div class="grid grid-cols-12 gap-4">
                {{-- USB Scanner Input --}}
                <div class="col-span-12 md:col-span-8">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Barcode Input (USB Scanner)</label>
                    <input type="text"
                           wire:model="barcodeInput"
                           x-on:keydown.enter.prevent="$wire.scan($el.value); $el.value = ''"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="Scan barcode di sini..."
                           autofocus />
                </div>

                {{-- Camera Scanner Button --}}
                <div class="col-span-12 md:col-span-4 flex items-end">
                    <button type="button"
                            x-on:click="$dispatch('open-camera-scanner')"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-on-primary rounded-lg hover:bg-primary/90 transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-lg mr-1">photo_camera</span>
                        Scan
                    </button>
                </div>
            </div>
        </div>

        {{-- Pallet Barcode (when READY) --}}
        @php
            $palletCode = \App\Models\MasukRealisasi::where('in_realisasi_masuk_code', $masukDetail->in_detail_code)
                ->whereNotNull('in_realisasi_group')
                ->value('in_realisasi_group');
        @endphp
        @if($palletCode)
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code_2</span>
                Barcode Pallet / Group
            </h3>
            <div class="mb-4">
                <div class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-1">Kode Pallet</div>
                <div class="text-xl md:text-2xl font-bold text-on-surface font-mono break-all">{{ $palletCode }}</div>
                <div class="text-xs text-on-surface-variant mt-1">
                    1 barcode ini mewakili seluruh realisasi pada {{ $masukDetail->in_detail_code }}.
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <a href="{{ route('wms-forklift.printQr', ['groupCode' => $palletCode]) }}" target="_blank"
                   class="flex-1 inline-flex items-center justify-center gap-1 px-4 py-2 bg-primary text-on-primary rounded-lg hover:bg-primary/90 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    Print PDF
                </a>
                <button type="button" wire:click="regeneratePallet" wire:confirm="Yakin ingin regenerate pallet code?"
                        class="flex-1 inline-flex items-center justify-center gap-1 px-4 py-2 bg-warning/10 text-warning hover:bg-warning/20 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-lg">refresh</span>
                    Regenerate Code
                </button>
            </div>
        </div>
        @endif

        {{-- Summary Table --}}
        <div wire:key="summary-card" class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">summarize</span>
                Summary Realisasi
            </h3>

            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
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

            {{-- Mobile Cards --}}
            <div class="md:hidden space-y-3">
                @forelse($summary as $item)
                <div class="border border-outline-variant rounded-xl p-4 bg-surface shadow-sm">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <p class="text-sm font-bold text-on-surface truncate">{{ $item->product->product_nama ?? '-' }}</p>
                        <span class="text-lg font-bold text-primary shrink-0">{{ (float) $item->total_qty }}</span>
                    </div>
                    <button wire:click="getDetail({{ $item->in_realisasi_id_product }})"
                            class="w-full inline-flex items-center justify-center gap-1 px-4 py-2 bg-primary/10 text-primary hover:bg-primary/20 rounded-lg transition-colors text-sm font-medium">
                        <span class="material-symbols-outlined text-lg">visibility</span>
                        Detail
                    </button>
                </div>
                @empty
                <div class="text-center py-8 text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl mb-2 block">inventory_2</span>
                    <p class="text-sm">Belum ada realisasi</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Detail Modal --}}
        @if($selectedProductId)
        <div wire:key="detail-modal-{{ $selectedProductId }}" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" wire:click="closeDetail">
            <div class="bg-surface-container-lowest rounded-xl p-6 max-w-lg w-full max-h-[80vh] overflow-y-auto" x-on:click.stop>
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant">
                    Detail Realisasi
                </h3>

                {{-- Desktop Table --}}
                <div class="hidden md:block overflow-x-auto">
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

                {{-- Mobile Cards --}}
                <div class="md:hidden space-y-2">
                    @forelse($scans as $scan)
                    <div class="flex items-center justify-between gap-3 p-3 border border-outline-variant rounded-lg bg-surface">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-on-surface">{{ (float) $scan->in_realisasi_qty }}</p>
                            <p class="text-[10px] font-mono text-on-surface-variant truncate">{{ $scan->in_realisasi_barcode }}</p>
                        </div>
                        <button wire:click="deleteScan({{ $scan->in_realisasi_id }})"
                                wire:confirm="Hapus scan ini?"
                                class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                    @empty
                    <div class="text-center py-8 text-on-surface-variant">
                        <p class="text-sm">Tidak ada data</p>
                    </div>
                    @endforelse
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
    </div>

    {{-- Camera Scanner Script --}}
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

                        // Prefer back camera on mobile
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
                        (errorMessage) => {
                            // Scan frame errors are expected, ignore
                        }
                    ).catch(err => {
                        this.error = 'Gagal memulai kamera: ' + (err.message || err);
                        console.error('Start camera error:', err);
                    });
                },

                onScanSuccess(decodedText) {
                    this.stopScanner();
                    this.show = false;

                    // Send to Livewire component
                    if (window.Livewire) {
                        Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('scan', decodedText);
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
