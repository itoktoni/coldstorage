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
            <div class="flex gap-2">
                <button type="button"
                        x-on:click="$wire.scan(document.querySelector('[wire\\:model=barcodeInput]').value); document.querySelector('[wire\\:model=barcodeInput]').value = ''"
                    class="inline-flex items-center justify-center gap-2 h-11 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-xl">check</span>
                    Scan
                </button>
                <button type="button"
                        x-on:click="$dispatch('open-camera-scanner')"
                    class="inline-flex items-center justify-center gap-2 h-11 px-4 text-sm font-semibold rounded-lg bg-secondary-container text-on-secondary-container hover:bg-secondary-container/80 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-xl">qr_code_scanner</span>
                </button>
            </div>
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

    {{-- Pallet Terkait --}}
    @if($pallets && $pallets->isNotEmpty())
    <div class="bg-surface-container-lowest mt-4 md:mt-5 border border-outline-variant rounded-xl p-4 md:p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-3 md:pb-4 mb-3 md:mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">pallet</span>
            Pallet Terkait ({{ $pallets->count() }})
        </h3>
        <div class="md:hidden space-y-2">
            @foreach($pallets as $pallet)
            <div class="border border-outline-variant/50 rounded-lg p-3 {{ $pallet['is_staging'] ? 'bg-success/5 border-success/20' : '' }}">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-mono text-xs font-semibold text-primary">{{ $pallet['pallet_code'] }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $pallet['task_status'] === 'Done' ? 'bg-success/10 text-success' : ($pallet['task_status'] === 'Progress' ? 'bg-warning/10 text-warning' : 'bg-gray-100 text-gray-600') }}">
                        {{ $pallet['task_status'] === 'Done' ? 'Di Staging' : ($pallet['task_status'] === 'Progress' ? 'Dipindah' : 'Belum') }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs text-on-surface-variant">
                    <span>{{ $pallet['lokasi'] }} {{ $pallet['is_staging'] ? '✓' : '' }}</span>
                    <span>{{ formatQty($pallet['picked_qty']) }} / {{ formatQty($pallet['total_qty']) }} kg</span>
                </div>
                @if($pallet['total_qty'] > 0)
                <div class="mt-1.5 h-1.5 bg-surface-container-high rounded-full overflow-hidden">
                    <div class="h-full bg-primary rounded-full transition-all" style="width: {{ min(100, ($pallet['picked_qty'] / max($pallet['total_qty'], 1)) * 100) }}%"></div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead><tr class="border-b border-outline-variant">
                    <th class="py-2 px-3 text-on-surface-variant">Pallet</th><th class="py-2 px-3 text-on-surface-variant">Lokasi</th><th class="py-2 px-3 text-on-surface-variant">Status</th><th class="py-2 px-3 text-on-surface-variant text-right">Total</th><th class="py-2 px-3 text-on-surface-variant text-right">Terambil</th><th class="py-2 px-3 text-on-surface-variant">Progress</th>
                </tr></thead>
                <tbody>
                    @foreach($pallets as $pallet)
                    <tr class="border-b border-outline-variant/50 {{ $pallet['is_staging'] ? 'bg-success/5' : '' }}">
                        <td class="py-2 px-3 font-mono text-xs font-semibold">{{ $pallet['pallet_code'] }}</td>
                        <td class="py-2 px-3">@if($pallet['is_staging'])<span class="inline-flex items-center gap-1 text-success font-semibold text-xs"><span class="material-symbols-outlined text-sm">check_circle</span> {{ $pallet['lokasi'] }} (staging)</span>@else<span class="text-xs">{{ $pallet['lokasi'] }}</span>@endif</td>
                        <td class="py-2 px-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $pallet['task_status'] === 'Done' ? 'bg-success/10 text-success' : ($pallet['task_status'] === 'Progress' ? 'bg-warning/10 text-warning' : 'bg-gray-100 text-gray-600') }}">{{ $pallet['task_status'] }}</span></td>
                        <td class="py-2 px-3 text-right font-semibold">{{ formatQty($pallet['total_qty']) }}</td>
                        <td class="py-2 px-3 text-right text-primary">{{ formatQty($pallet['picked_qty']) }}</td>
                        <td class="py-2 px-3">
                            @if($pallet['total_qty'] > 0)
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-surface-container-high rounded-full overflow-hidden"><div class="h-full {{ $pallet['picked_qty'] >= $pallet['total_qty'] ? 'bg-success' : 'bg-primary' }} rounded-full transition-all" style="width: {{ min(100, ($pallet['picked_qty'] / $pallet['total_qty']) * 100) }}%"></div></div>
                                <span class="text-[10px] text-on-surface-variant whitespace-nowrap">{{ number_format(($pallet['picked_qty'] / max($pallet['total_qty'], 1)) * 100, 0) }}%</span>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

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
            <div id="camera-scanner" class="w-full rounded-lg overflow-hidden mb-4" style="min-height: 300px;"></div>
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
                    if (this.scanner) this.stopScanner();
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
                            this.error = 'Kamera tidak ditemukan.';
                            return;
                        }
                        this.currentCameraIndex = this.cameras.findIndex(d =>
                            d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')
                        );
                        if (this.currentCameraIndex === -1) this.currentCameraIndex = 0;
                        this.startWithCamera(this.cameras[this.currentCameraIndex].id, config);
                    }).catch(err => {
                        this.error = 'Tidak bisa mengakses kamera.';
                    });
                },

                startWithCamera(cameraId, config) {
                    this.scanner.start(cameraId, config,
                        (decodedText) => this.onScanSuccess(decodedText),
                        () => {}
                    ).catch(err => {
                        this.error = 'Gagal memulai kamera: ' + (err.message || err);
                    });
                },

                onScanSuccess(decodedText) {
                    this.stopScanner();
                    this.show = false;
                    if (window.Livewire) {
                        Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('scan', decodedText);
                    }
                },

                switchCamera() {
                    if (this.cameras.length <= 1) return;
                    this.currentCameraIndex = (this.currentCameraIndex + 1) % this.cameras.length;
                    this.stopScanner();
                    this.$nextTick(() => {
                        this.scanner = new Html5Qrcode('camera-scanner');
                        this.startWithCamera(this.cameras[this.currentCameraIndex].id, { fps: 15, qrbox: { width: 280, height: 150 }, aspectRatio: 1.5 });
                    });
                },

                stopScanner() {
                    if (this.scanner) {
                        try { this.scanner.stop().then(() => { this.scanner.clear(); this.scanner = null; }).catch(() => { this.scanner = null; }); } catch (e) { this.scanner = null; }
                    }
                }
            };
        }
    </script>
</div>
