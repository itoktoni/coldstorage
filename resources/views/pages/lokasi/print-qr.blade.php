<x-layouts::app title="Print QR Lokasi">
    <x-breadcrumb :items="[['url' => route('wms-lokasi.getTable'), 'label' => 'Lokasi'], ['url' => '', 'label' => 'Print QR - ' . $lokasi->lokasi_code]]" />

    {{-- Actions --}}
    <div class="flex items-center gap-2 mt-4 mb-4">
        <a href="{{ route('wms-lokasi.getTable') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-surface-container-high text-on-surface-variant text-sm font-semibold hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Kembali
        </a>
        <button onclick="printArea()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:bg-primary-container hover:text-white transition-colors active:scale-95">
            <span class="material-symbols-outlined text-lg">print</span>
            Print QR
        </button>
    </div>

    {{-- Print Area: full width, bounded height, centered QR --}}
    <div id="print-area" class="bg-white border-2 border-dashed border-outline-variant rounded-xl p-6 mb-4">
        <div class="flex items-center gap-4">
            <div class="shrink-0">
                <img src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $lokasi->lokasi_code }}" class="w-28 h-28 object-contain">
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold uppercase tracking-widest text-on-surface-variant mb-1">Lokasi QR Code</div>
                <div class="text-xl font-bold text-on-surface truncate">{{ $lokasi->lokasi_nama }}</div>
                <div class="text-sm font-mono text-primary mt-1">{{ $lokasi->lokasi_code }}</div>
                @if($lokasi->gudang)
                    <div class="text-xs text-on-surface-variant mt-1">{{ $lokasi->gudang->gudang_nama ?? '-' }}</div>
                @endif
                <div class="text-xs text-on-surface-variant mt-2">Category: {{ $lokasi->lokasi_category ?? '-' }} &middot; Max: {{ number_format($lokasi->lokasi_max_qty) }}</div>
            </div>
        </div>
    </div>

    {{-- Info card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">info</span>
            Detail Lokasi
        </h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <span class="text-on-surface-variant">Code</span>
                <p class="font-semibold text-on-surface font-mono">{{ $lokasi->lokasi_code }}</p>
            </div>
            <div>
                <span class="text-on-surface-variant">Nama</span>
                <p class="font-semibold text-on-surface">{{ $lokasi->lokasi_nama }}</p>
            </div>
            <div>
                <span class="text-on-surface-variant">Category</span>
                <p class="font-semibold text-on-surface">{{ $lokasi->lokasi_category ?? '-' }}</p>
            </div>
            <div>
                <span class="text-on-surface-variant">Max Qty</span>
                <p class="font-semibold text-on-surface">{{ number_format($lokasi->lokasi_max_qty) }}</p>
            </div>
            @if($lokasi->gudang)
            <div class="col-span-2">
                <span class="text-on-surface-variant">Gudang</span>
                <p class="font-semibold text-on-surface">{{ $lokasi->gudang->gudang_nama ?? '-' }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Print-only area: hide everything else when printing, only the QR label prints --}}
    @push('scripts')
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #print-area, #print-area * { visibility: visible !important; }
            #print-area {
                position: fixed; top: 0; left: 0;
                width: 55mm; height: 30mm;
                margin: 0; padding: 2mm;
                border: none; border-radius: 0;
                background: #fff;
                display: flex; align-items: center; gap: 2mm;
                page-break-after: avoid;
            }
            #print-area img { width: 24mm; height: 24mm; margin: 0; }
            #print-area .qr-text { flex: 1; text-align: center; }
            #print-area .qr-text .qr-name { font-size: 9pt; font-weight: bold; }
            #print-area .qr-text .qr-code { font-size: 7pt; color: #666; }
            #print-area .qr-text .qr-meta { display: none; }
        }
    </style>
    <script>
        function printArea() {
            if (window.NativeBridge && typeof NativeBridge.printPage === 'function') {
                NativeBridge.printPage();
            } else {
                window.print();
            }
        }
    </script>
    @endpush
</x-layouts::app>