<x-layouts::app title="Print QR Pallet">
    <x-breadcrumb :items="[['url' => route('wms-forklift.index'), 'label' => 'Forklift'], ['url' => '', 'label' => 'Print QR - ' . $groupCode]]" />

    {{-- Actions --}}
    <div class="flex items-center gap-2 mt-4 mb-4">
        <a href="{{ route('wms-forklift.getTable') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-surface-container-high text-on-surface-variant text-sm font-semibold hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Kembali
        </a>
        <button onclick="printArea()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:bg-primary-container hover:text-white transition-colors active:scale-95">
            <span class="material-symbols-outlined text-lg">print</span>
            Print QR
        </button>
    </div>

    {{-- Print Area: full width, bounded height --}}
    <div id="print-area" class="bg-white border-2 border-dashed border-outline-variant rounded-xl p-6 mb-4">
        <div class="flex items-center gap-4">
            <div class="shrink-0">
                <img src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $groupCode }}" class="w-28 h-28 object-contain">
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold uppercase tracking-widest text-on-surface-variant mb-1">Pallet QR Code</div>
                <div class="text-xl font-bold text-on-surface font-mono">{{ $groupCode }}</div>
                <div class="text-sm text-on-surface mt-1">{{ $product->product_nama ?? '-' }}</div>
                <div class="text-sm text-on-surface-variant mt-1">Qty Total: {{ number_format($totalQty, 3) }}</div>
                @if($detail)
                    <div class="text-xs text-on-surface-variant mt-1">Ref: {{ $detail->in_detail_code }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Detail card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">info</span>
            Detail Pallet
        </h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <span class="text-on-surface-variant">Group Code</span>
                <p class="font-semibold text-on-surface font-mono">{{ $groupCode }}</p>
            </div>
            <div>
                <span class="text-on-surface-variant">Product</span>
                <p class="font-semibold text-on-surface">{{ $product->product_nama ?? '-' }}</p>
            </div>
            <div>
                <span class="text-on-surface-variant">Qty Total</span>
                <p class="font-semibold text-on-surface">{{ number_format($totalQty, 3) }}</p>
            </div>
            @if($detail)
            <div>
                <span class="text-on-surface-variant">Ref</span>
                <p class="font-semibold text-on-surface">{{ $detail->in_detail_code }}</p>
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #print-area, #print-area * { visibility: visible !important; }
            #print-area {
                position: fixed; top: 0; left: 0;
                width: 72mm; height: 72mm;
                margin: 0; padding: 4mm;
                border: none; border-radius: 0;
                background: #fff;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                page-break-after: avoid;
            }
            #print-area img { width: 50mm; height: 50mm; margin: 0 auto 3mm; }
            #print-area .qr-text .qr-name { font-size: 12pt; font-weight: bold; }
            #print-area .qr-text .qr-meta { font-size: 9pt; }
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