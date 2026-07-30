<x-layouts.warehouse title="Barang - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Search Bar -->
        <section class="mb-6">
            <div class="relative group">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-[#757684]">search</span>
                </div>
                <input
                    class="w-full bg-white border border-[#c4c5d5] focus:border-[#00288e] text-[#191c1e] h-14 pl-12 pr-12 rounded-xl shadow-sm transition-all font-sans outline-none"
                    placeholder="Search Product ID or Name..."
                    type="text"
                />
                <div class="absolute inset-y-0 right-4 flex items-center cursor-pointer text-[#0058be]">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">barcode_scanner</span>
                </div>
            </div>
        </section>

        <!-- Selected Product Highlight -->
        <section class="mb-8">
            <div class="bg-white border border-[#c4c5d5] rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <p class="text-[12px] font-semibold text-[#444653] uppercase mb-1 tracking-wider">SELECTED PRODUCT</p>
                        <h2 class="text-[18px] font-bold text-[#00288e] tracking-wide">SKU-BRG-2024-X9</h2>
                        <p class="text-[16px] text-[#191c1e]">Industrial Precision Bearing - Type Z</p>
                    </div>
                    <div class="bg-[#2170e4]/10 border border-[#2170e4]/20 rounded-lg p-4 flex flex-col items-end min-w-[140px]">
                        <p class="text-[12px] font-semibold text-[#0058be] uppercase mb-1">TOTAL AVAILABLE</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-[#00288e]">1,450</span>
                            <span class="text-[12px] font-semibold text-[#444653]/70">PCS</span>
                        </div>
                    </div>
                </div>
                <div class="bg-[#eceef0] px-4 py-3 flex gap-3 overflow-x-auto border-t border-[#c4c5d5]">
                    <span class="bg-[#1e40af]/30 text-[#00288e] px-3 py-1.5 rounded-full text-[11px] font-semibold flex items-center gap-1 shrink-0">
                        <span class="material-symbols-outlined text-[14px]">category</span> Mechanical
                    </span>
                    <span class="bg-[#ffdad6] text-[#ba1a1a] px-3 py-1.5 rounded-full text-[11px] font-semibold flex items-center gap-1 shrink-0 border border-[#ffdad6]">
                        <span class="material-symbols-outlined text-[14px]">warning</span> Low Stock Alert
                    </span>
                </div>
            </div>
        </section>

        <!-- Rack Suggestions Section -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[20px] font-semibold text-[#191c1e] flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#00288e]">analytics</span> Rack Suggestions
                </h3>
                <button class="text-[#00288e] text-[12px] font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">sort</span> SORTED BY QTY
                </button>
            </div>
            <div class="space-y-3">
                <!-- Suggestion Item 1 (Primary Choice) -->
                <div class="group relative bg-white border-2 border-[#00288e] transition-all duration-200 p-4 rounded-xl shadow-md">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-[#00288e]">location_on</span>
                                <span class="text-[20px] font-semibold text-[#191c1e]">Rack A-101</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-[#444653] bg-[#e6e8ea] px-1.5 py-0.5 rounded">STK-008291</span>
                                <span class="bg-green-100 text-green-700 text-[11px] font-semibold px-2 py-0.5 rounded-full border border-green-200">STOCK TYPE: IN</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[18px] font-bold text-[#191c1e]">200 <span class="text-[12px] font-semibold text-[#444653]">PCS</span></p>
                            <p class="text-[10px] font-bold text-green-600 uppercase">AVAILABLE</p>
                        </div>
                    </div>
                    <button class="w-full bg-[#00288e] text-white h-12 rounded-lg font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                        <span class="material-symbols-outlined">front_loader</span> PICK FROM THIS RACK
                    </button>
                </div>

                <!-- Suggestion Item 2 -->
                <div class="group relative bg-white border border-[#c4c5d5] hover:border-[#0058be] transition-all duration-200 p-4 rounded-xl shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-[#0058be]">location_on</span>
                                <span class="text-[20px] font-semibold text-[#191c1e]">Rack B-204</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-[#444653] bg-[#e6e8ea] px-1.5 py-0.5 rounded">STK-009110</span>
                                <span class="bg-green-100 text-green-700 text-[11px] font-semibold px-2 py-0.5 rounded-full border border-green-200">STOCK TYPE: IN</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[18px] font-bold text-[#191c1e]">156 <span class="text-[12px] font-semibold text-[#444653]">PCS</span></p>
                            <p class="text-[10px] font-bold text-green-600 uppercase">AVAILABLE</p>
                        </div>
                    </div>
                    <button class="w-full bg-[#f2f4f6] text-[#00288e] border border-[#00288e]/20 h-12 rounded-lg font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all hover:bg-[#00288e]/10">
                        <span class="material-symbols-outlined">front_loader</span> PICK FROM THIS RACK
                    </button>
                </div>

                <!-- Suggestion Item 3 -->
                <div class="group relative bg-white border border-[#c4c5d5] hover:border-[#0058be] transition-all duration-200 p-4 rounded-xl shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-[#0058be]">location_on</span>
                                <span class="text-[20px] font-semibold text-[#191c1e]">Rack C-005</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-[#444653] bg-[#e6e8ea] px-1.5 py-0.5 rounded">STK-007743</span>
                                <span class="bg-green-100 text-green-700 text-[11px] font-semibold px-2 py-0.5 rounded-full border border-green-200">STOCK TYPE: IN</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[18px] font-bold text-[#191c1e]">84 <span class="text-[12px] font-semibold text-[#444653]">PCS</span></p>
                            <p class="text-[10px] font-bold text-green-600 uppercase">AVAILABLE</p>
                        </div>
                    </div>
                    <button class="w-full bg-[#f2f4f6] text-[#00288e] border border-[#00288e]/20 h-12 rounded-lg font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all hover:bg-[#00288e]/10">
                        <span class="material-symbols-outlined">front_loader</span> PICK FROM THIS RACK
                    </button>
                </div>
            </div>
        </section>

        <!-- Operational Info Card -->
        <section class="mt-8">
            <div class="relative overflow-hidden rounded-xl bg-[#00288e] text-white p-6 shadow-lg">
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-outlined">security</span>
                        <h4 class="text-[20px] font-semibold">Operational Insight</h4>
                    </div>
                    <p class="text-[16px] text-white/80 max-w-[300px] leading-relaxed">
                        Suggestion engine optimized for <span class="text-[#adc6ff] font-bold">First-Expired-First-Out (FEFO)</span> and distance proximity. Secure logistics protocols active.
                    </p>
                </div>
                <div class="absolute -right-6 -bottom-6 opacity-10">
                    <span class="material-symbols-outlined text-[140px] font-bold">shield</span>
                </div>
            </div>
        </section>
    </main>
</x-layouts.warehouse>
