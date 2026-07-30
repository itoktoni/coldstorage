<x-layouts.warehouse title="Putaway - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Inbound</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">Putaway Task</h2>
                </div>
            </div>
            <span class="bg-[#00288e] text-white text-[11px] font-bold px-3 py-1.5 rounded-full uppercase">Assigned</span>
        </div>

        <!-- Item to Putaway -->
        <section class="mb-6">
            <div class="bg-[#00288e] text-white rounded-xl p-5">
                <p class="text-[11px] font-semibold uppercase mb-3 opacity-70">Item to Store</p>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-3xl">inventory_2</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">SKU-BRG-2024-X9</h3>
                        <p class="text-sm opacity-80">Industrial Precision Bearing - Type Z</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 bg-white/10 rounded-lg p-4">
                    <div>
                        <p class="text-[10px] opacity-70 uppercase mb-1">Quantity</p>
                        <p class="text-lg font-bold">200 PCS</p>
                    </div>
                    <div>
                        <p class="text-[10px] opacity-70 uppercase mb-1">Inbound ID</p>
                        <p class="text-lg font-bold">INB-2024-1847</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Suggested Locations -->
        <section class="mb-6">
            <h3 class="font-semibold text-base text-[#191c1e] mb-4">Suggested Locations</h3>
            <div class="space-y-3">
                <!-- Primary Suggestion -->
                <div class="bg-white border-2 border-[#00288e] rounded-xl p-4 shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-[#00288e]/10 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#00288e] text-2xl">location_on</span>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-[#191c1e]">Rack A-101</p>
                                <p class="text-[11px] text-[#444653]">Zone A - Aisle 1 - Level 1</p>
                            </div>
                        </div>
                        <span class="bg-[#00288e] text-white text-[9px] font-bold px-2 py-1 rounded-full">RECOMMENDED</span>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mb-3 text-center">
                        <div class="bg-[#f2f4f6] rounded-lg p-2">
                            <p class="text-lg font-bold text-green-600">65%</p>
                            <p class="text-[9px] text-[#444653]">Capacity</p>
                        </div>
                        <div class="bg-[#f2f4f6] rounded-lg p-2">
                            <p class="text-lg font-bold">12m</p>
                            <p class="text-[9px] text-[#444653]">Distance</p>
                        </div>
                        <div class="bg-[#f2f4f6] rounded-lg p-2">
                            <p class="text-lg font-bold">Same SKU</p>
                            <p class="text-[9px] text-[#444653]">Storage</p>
                        </div>
                    </div>
                    <button class="w-full bg-[#00288e] text-white h-12 rounded-lg font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                        <span class="material-symbols-outlined">check</span> CONFIRM LOCATION
                    </button>
                </div>

                <!-- Alternative 1 -->
                <div class="bg-white border border-[#c4c5d5] rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#0058be]/10 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#0058be]">location_on</span>
                            </div>
                            <div>
                                <p class="font-bold text-[#191c1e]">Rack B-204</p>
                                <p class="text-[11px] text-[#444653]">Zone B - Aisle 2 - Level 2</p>
                            </div>
                        </div>
                        <span class="text-[12px] font-semibold text-[#444653]">45% Capacity</span>
                    </div>
                </div>

                <!-- Alternative 2 -->
                <div class="bg-white border border-[#c4c5d5] rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#0058be]/10 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#0058be]">location_on</span>
                            </div>
                            <div>
                                <p class="font-bold text-[#191c1e]">Rack C-005</p>
                                <p class="text-[11px] text-[#444653]">Zone C - Aisle 1 - Level 1</p>
                            </div>
                        </div>
                        <span class="text-[12px] font-semibold text-[#444653]">30% Capacity</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Manual Entry -->
        <section>
            <button class="w-full bg-white border border-[#c4c5d5] text-[#444653] h-12 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                <span class="material-symbols-outlined">edit</span> ENTER LOCATION MANUALLY
            </button>
        </section>
    </main>
</x-layouts.warehouse>
