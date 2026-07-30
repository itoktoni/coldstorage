<x-layouts.warehouse title="Selected Stock - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Stock</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">Stock Details</h2>
                </div>
            </div>
        </div>

        <!-- Product Overview -->
        <section class="mb-6">
            <div class="bg-[#00288e] text-white rounded-xl p-5">
                <p class="text-[11px] font-semibold uppercase mb-2 opacity-70">Product Information</p>
                <h3 class="text-xl font-bold mb-1">SKU-BRG-2024-X9</h3>
                <p class="text-sm opacity-80 mb-4">Industrial Precision Bearing - Type Z</p>
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 rounded-lg px-4 py-2">
                        <p class="text-[10px] opacity-70 uppercase">Category</p>
                        <p class="font-semibold">Mechanical</p>
                    </div>
                    <div class="bg-white/20 rounded-lg px-4 py-2">
                        <p class="text-[10px] opacity-70 uppercase">Unit</p>
                        <p class="font-semibold">Pieces (PCS)</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stock Summary -->
        <section class="mb-6">
            <h3 class="font-semibold text-base text-[#191c1e] mb-4">Stock Summary</h3>
            <div class="bg-white border border-[#c4c5d5] rounded-xl overflow-hidden shadow-sm">
                <div class="grid grid-cols-2 divide-x divide-[#c4c5d5]">
                    <div class="p-4 text-center">
                        <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Total Available</p>
                        <p class="text-2xl font-bold text-[#00288e]">1,450</p>
                        <p class="text-[10px] text-[#444653]">PCS</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Reserved</p>
                        <p class="text-2xl font-bold text-[#ba1a1a]">320</p>
                        <p class="text-[10px] text-[#444653]">PCS</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stock Locations -->
        <section class="mb-6">
            <h3 class="font-semibold text-base text-[#191c1e] mb-4">Stock Locations</h3>
            <div class="space-y-3">
                <!-- Location 1 -->
                <div class="bg-white border border-[#c4c5d5] rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#00288e]/10 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#00288e]">location_on</span>
                            </div>
                            <div>
                                <p class="font-bold text-[#191c1e]">Rack A-101</p>
                                <p class="text-[11px] text-[#444653]">Stock ID: STK-008291</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-[#00288e]">200</p>
                            <p class="text-[10px] text-[#444653]">PCS Available</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <span class="bg-green-100 text-green-700 text-[9px] font-bold px-2 py-0.5 rounded-full">IN STOCK</span>
                        <span class="bg-[#f2f4f6] text-[#444653] text-[9px] font-bold px-2 py-0.5 rounded-full">EXP: DEC 2027</span>
                    </div>
                </div>

                <!-- Location 2 -->
                <div class="bg-white border border-[#c4c5d5] rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#0058be]/10 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#0058be]">location_on</span>
                            </div>
                            <div>
                                <p class="font-bold text-[#191c1e]">Rack B-204</p>
                                <p class="text-[11px] text-[#444653]">Stock ID: STK-009110</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-[#0058be]">156</p>
                            <p class="text-[10px] text-[#444653]">PCS Available</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <span class="bg-green-100 text-green-700 text-[9px] font-bold px-2 py-0.5 rounded-full">IN STOCK</span>
                        <span class="bg-[#f2f4f6] text-[#444653] text-[9px] font-bold px-2 py-0.5 rounded-full">EXP: NOV 2027</span>
                    </div>
                </div>

                <!-- Location 3 -->
                <div class="bg-white border border-[#c4c5d5] rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#0058be]/10 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#0058be]">location_on</span>
                            </div>
                            <div>
                                <p class="font-bold text-[#191c1e]">Rack C-005</p>
                                <p class="text-[11px] text-[#444653]">Stock ID: STK-007743</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-[#0058be]">84</p>
                            <p class="text-[10px] text-[#444653]">PCS Available</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <span class="bg-[#ffdad6] text-[#ba1a1a] text-[9px] font-bold px-2 py-0.5 rounded-full">LOW STOCK</span>
                        <span class="bg-[#f2f4f6] text-[#444653] text-[9px] font-bold px-2 py-0.5 rounded-full">EXP: JAN 2027</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Actions -->
        <section class="flex gap-3">
            <button class="flex-1 bg-[#00288e] text-white h-14 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                <span class="material-symbols-outlined">edit</span> ADJUST STOCK
            </button>
            <button class="w-14 h-14 bg-white border border-[#c4c5d5] text-[#444653] rounded-xl flex items-center justify-center active:scale-[0.98] transition-all">
                <span class="material-symbols-outlined">more_vert</span>
            </button>
        </section>
    </main>
</x-layouts.warehouse>
