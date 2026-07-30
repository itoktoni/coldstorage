<x-layouts.warehouse title="Stock Opname - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Stock Opname</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">Cycle Count</h2>
                </div>
            </div>
            <span class="bg-[#0058be] text-white text-[11px] font-bold px-3 py-1.5 rounded-full uppercase">In Progress</span>
        </div>

        <!-- Progress Overview -->
        <section class="mb-6">
            <div class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base text-[#191c1e]">Opname Progress</h3>
                    <span class="text-[12px] font-bold text-[#00288e]">42 / 150 ITEMS</span>
                </div>
                <div class="w-full bg-[#eceef0] rounded-full h-3 overflow-hidden mb-2">
                    <div class="h-full bg-[#00288e] rounded-full" style="width: 28%"></div>
                </div>
                <div class="flex justify-between text-[11px] text-[#444653]">
                    <span>Started: June 8, 2026 - 09:00</span>
                    <span>Est. Completion: June 12, 2026</span>
                </div>
            </div>
        </section>

        <!-- Current Location -->
        <section class="mb-6">
            <div class="bg-[#00288e] text-white rounded-xl p-5">
                <p class="text-[11px] font-semibold uppercase mb-2 opacity-70">Current Location</p>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-3xl">location_on</span>
                    <div>
                        <h3 class="text-2xl font-bold">Zone A - Aisle 3</h3>
                        <p class="text-sm opacity-80">Rack A-301 to A-315</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Counted Items -->
        <section class="mb-6">
            <h3 class="font-semibold text-base text-[#191c1e] mb-4">Recently Counted</h3>
            <div class="bg-white border border-[#c4c5d5] rounded-xl overflow-hidden shadow-sm">
                <div class="divide-y divide-[#c4c5d5]">
                    <!-- Item 1 -->
                    <div class="p-4 flex items-center gap-4">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-green-600">check_circle</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[#191c1e]">SKU-BRG-2024-X9</p>
                            <p class="text-[11px] text-[#444653]">Industrial Precision Bearing</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-[#00288e]">200 / 198</p>
                            <p class="text-[10px] text-[#ba1a1a]">+2 Variance</p>
                        </div>
                    </div>
                    <!-- Item 2 -->
                    <div class="p-4 flex items-center gap-4">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-green-600">check_circle</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[#191c1e]">SKU-MTR-2024-A1</p>
                            <p class="text-[11px] text-[#444653]">Electric Motor Unit</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-600">50 / 50</p>
                            <p class="text-[10px] text-green-600">Match</p>
                        </div>
                    </div>
                    <!-- Item 3 -->
                    <div class="p-4 flex items-center gap-4">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-green-600">check_circle</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[#191c1e]">SKU-CBL-2024-B2</p>
                            <p class="text-[11px] text-[#444653]">Control Cable Assembly</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-[#ba1a1a]">95 / 100</p>
                            <p class="text-[10px] text-[#ba1a1a]">-5 Variance</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Action -->
        <section class="flex gap-3">
            <button class="flex-1 bg-[#00288e] text-white h-14 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                <span class="material-symbols-outlined">add_circle</span> COUNT NEXT ITEM
            </button>
            <button class="w-14 h-14 bg-white border border-[#c4c5d5] text-[#444653] rounded-xl flex items-center justify-center active:scale-[0.98] transition-all">
                <span class="material-symbols-outlined">more_vert</span>
            </button>
        </section>
    </main>
</x-layouts.warehouse>
