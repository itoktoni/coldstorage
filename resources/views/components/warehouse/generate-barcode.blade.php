<x-layouts.warehouse title="Generate Barcode - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
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
                        <label class="block text-[12px] font-semibold text-[#444653] uppercase mb-2">Product Code / SKU</label>
                        <input
                            class="w-full bg-white border border-[#c4c5d5] focus:border-[#00288e] text-[#191c1e] h-12 px-4 rounded-lg transition-all outline-none"
                            placeholder="e.g., SKU-BRG-2024-X9"
                            type="text"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-semibold text-[#444653] uppercase mb-2">Quantity</label>
                            <input
                                class="w-full bg-white border border-[#c4c5d5] focus:border-[#00288e] text-[#191c1e] h-12 px-4 rounded-lg transition-all outline-none"
                                placeholder="1"
                                type="number"
                            />
                        </div>
                        <div>
                            <label class="block text-[12px] font-semibold text-[#444653] uppercase mb-2">Barcode Type</label>
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
                <div class="bg-[#f2f4f6] rounded-lg p-8 flex flex-col items-center">
                    <!-- Barcode SVG placeholder -->
                    <svg class="h-24 mb-4" viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg">
                        <rect x="0" y="0" width="4" height="80" fill="#000"/>
                        <rect x="8" y="0" width="2" height="80" fill="#000"/>
                        <rect x="14" y="0" width="6" height="80" fill="#000"/>
                        <rect x="24" y="0" width="2" height="80" fill="#000"/>
                        <rect x="30" y="0" width="4" height="80" fill="#000"/>
                        <rect x="38" y="0" width="6" height="80" fill="#000"/>
                        <rect x="48" y="0" width="2" height="80" fill="#000"/>
                        <rect x="54" y="0" width="4" height="80" fill="#000"/>
                        <rect x="62" y="0" width="2" height="80" fill="#000"/>
                        <rect x="68" y="0" width="6" height="80" fill="#000"/>
                        <rect x="78" y="0" width="4" height="80" fill="#000"/>
                        <rect x="86" y="0" width="2" height="80" fill="#000"/>
                        <rect x="92" y="0" width="4" height="80" fill="#000"/>
                        <rect x="100" y="0" width="6" height="80" fill="#000"/>
                        <rect x="110" y="0" width="2" height="80" fill="#000"/>
                        <rect x="116" y="0" width="4" height="80" fill="#000"/>
                        <rect x="124" y="0" width="2" height="80" fill="#000"/>
                        <rect x="130" y="0" width="6" height="80" fill="#000"/>
                        <rect x="140" y="0" width="2" height="80" fill="#000"/>
                        <rect x="146" y="0" width="4" height="80" fill="#000"/>
                        <rect x="154" y="0" width="2" height="80" fill="#000"/>
                        <rect x="160" y="0" width="6" height="80" fill="#000"/>
                        <rect x="170" y="0" width="4" height="80" fill="#000"/>
                        <rect x="178" y="0" width="2" height="80" fill="#000"/>
                        <rect x="184" y="0" width="4" height="80" fill="#000"/>
                        <rect x="192" y="0" width="6" height="80" fill="#000"/>
                    </svg>
                    <p class="font-mono text-lg font-bold text-[#191c1e] tracking-wider">SKU-BRG-2024-X9</p>
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
