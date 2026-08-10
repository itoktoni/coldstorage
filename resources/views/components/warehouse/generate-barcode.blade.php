@props([
    'qrPng' => null,
    'label' => 'LOC-01',
])

<x-layouts.warehouse title="Generate Barcode - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-on-surface-variant hover:bg-surface-container p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Tools</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">Generate Barcode</h2>
                </div>
            </div>
        </div>

        <!-- Input Section -->
        <section class="mb-6">
            <div class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[12px] font-semibold text-on-surface-variant uppercase mb-2">Product Code / SKU</label>
                        <input
                            class="w-full bg-white border border-[#c4c5d5] focus:border-[#00288e] text-[#191c1e] h-12 px-4 rounded-lg transition-all outline-none"
                            placeholder="e.g., SKU-BRG-2024-X9"
                            type="text"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-semibold text-on-surface-variant uppercase mb-2">Quantity</label>
                            <input
                                class="w-full bg-white border border-[#c4c5d5] focus:border-[#00288e] text-[#191c1e] h-12 px-4 rounded-lg transition-all outline-none"
                                placeholder="1"
                                type="number"
                            />
                        </div>
                        <div>
                            <label class="block text-[12px] font-semibold text-on-surface-variant uppercase mb-2">Barcode Type</label>
                            <select class="w-full bg-white border border-[#c4c5d5] focus:border-[#00288e] text-[#191c1e] h-12 px-4 rounded-lg transition-all outline-none">
                                <option>CODE128</option>
                                <option>QR_CODE</option>
                                <option>EAN13</option>
                            </select>
                        </div>
                    </div>
                    <button class="w-full bg-[#00288e] text-white h-12 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                        <span class="material-symbols-outlined">qr_code</span> GENERATE
                    </button>
                </div>
            </div>
        </section>

        <!-- Generated Barcode Preview -->
        <section>
            <div class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm">
                <h3 class="font-semibold text-base mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#00288e]">preview</span> Preview
                </h3>
                <div class="bg-[#f2f4f6] rounded-lg p-4 flex justify-center overflow-x-auto">
                    <div class="w-[55mm] h-[30mm] shrink-0 bg-white border border-[#c4c5d5] p-[2mm] flex items-center gap-[2mm] overflow-hidden">
                        <div class="w-[24mm] h-[24mm] shrink-0">
                            @if($qrPng)
                                <img class="block w-full h-full" src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $label }}">
                            @else
                                <div class="w-full h-full border border-dashed border-[#c4c5d5] flex items-center justify-center text-[7px] text-on-surface-variant text-center">QR belum tersedia</div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0 text-center text-[12px] font-bold text-[#191c1e] wrap-break-word">
                            {{ $label }}
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button class="flex-1 bg-[#f2f4f6] text-[#00288e] border border-[#00288e]/20 h-12 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                        <span class="material-symbols-outlined">print</span> Print
                    </button>
                    <button class="flex-1 bg-[#00288e] text-white h-12 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                        <span class="material-symbols-outlined">download</span> Download
                    </button>
                </div>
            </div>
        </section>
    </main>
</x-layouts.warehouse>
