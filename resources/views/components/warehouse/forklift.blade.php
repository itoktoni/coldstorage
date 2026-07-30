<x-layouts.warehouse title="Forklift - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-4">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Equipment</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">Forklift Fleet</h2>
                </div>
            </div>
        </div>

        <!-- Forklift Status Cards -->
        <section class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Forklift 1 -->
                <div class="bg-white border-2 border-[#00288e] rounded-xl p-5 shadow-md">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-[18px] font-bold text-[#00288e]">FL-001</h3>
                            <p class="text-[11px] text-[#444653]">Toyota 8FGU25</p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-green-600">bolt</span>
                        </div>
                    </div>
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-[12px]">
                            <span class="text-[#444653]">Battery</span>
                            <span class="font-bold text-green-600">85%</span>
                        </div>
                        <div class="w-full bg-[#eceef0] rounded-full h-2">
                            <div class="h-full bg-green-500 rounded-full" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-[#444653] mb-4">
                        <span class="material-symbols-outlined text-[14px]">person</span>
                        <span>Assigned: John Doe</span>
                    </div>
                    <button class="w-full bg-[#f2f4f6] text-[#00288e] border border-[#00288e]/20 h-10 rounded-lg font-semibold text-[12px] flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                        <span class="material-symbols-outlined text-[16px]">radio_button_checked</span> ACTIVE
                    </button>
                </div>

                <!-- Forklift 2 -->
                <div class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-[18px] font-bold text-[#191c1e]">FL-002</h3>
                            <p class="text-[11px] text-[#444653]">Hyster H50FT</p>
                        </div>
                        <div class="w-10 h-10 bg-[#e6e8ea] rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-[#444653]">schedule</span>
                        </div>
                    </div>
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-[12px]">
                            <span class="text-[#444653]">Battery</span>
                            <span class="font-bold">100%</span>
                        </div>
                        <div class="w-full bg-[#eceef0] rounded-full h-2">
                            <div class="h-full bg-[#00288e] rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-[#444653] mb-4">
                        <span class="material-symbols-outlined text-[14px]">person_off</span>
                        <span>Available</span>
                    </div>
                    <button class="w-full bg-[#00288e] text-white h-10 rounded-lg font-semibold text-[12px] flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                        <span class="material-symbols-outlined text-[16px]">login</span> CHECK OUT
                    </button>
                </div>

                <!-- Forklift 3 -->
                <div class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-[18px] font-bold text-[#191c1e]">FL-003</h3>
                            <p class="text-[11px] text-[#444653]">Komatsu FG25HT</p>
                        </div>
                        <div class="w-10 h-10 bg-[#eceef0] rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-[#444653]">build</span>
                        </div>
                    </div>
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-[12px]">
                            <span class="text-[#444653]">Battery</span>
                            <span class="font-bold text-[#444653]">--</span>
                        </div>
                        <div class="w-full bg-[#eceef0] rounded-full h-2">
                            <div class="h-full bg-[#757684] rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-[#ba1a1a] mb-4">
                        <span class="material-symbols-outlined text-[14px]">warning</span>
                        <span>Maintenance</span>
                    </div>
                    <button disabled class="w-full bg-[#eceef0] text-[#757684] h-10 rounded-lg font-semibold text-[12px] flex items-center justify-center gap-2 cursor-not-allowed">
                        <span class="material-symbols-outlined text-[16px]">block</span> UNAVAILABLE
                    </button>
                </div>
            </div>
        </section>

        <!-- Fleet Summary -->
        <section class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm">
            <h3 class="font-semibold text-base mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#0058be]">analytics</span> Fleet Summary
            </h3>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-[#f2f4f6] rounded-lg p-4">
                    <p class="text-2xl font-bold text-green-600">1</p>
                    <p class="text-[11px] text-[#444653] uppercase">Active</p>
                </div>
                <div class="bg-[#f2f4f6] rounded-lg p-4">
                    <p class="text-2xl font-bold text-[#00288e]">1</p>
                    <p class="text-[11px] text-[#444653] uppercase">Available</p>
                </div>
                <div class="bg-[#f2f4f6] rounded-lg p-4">
                    <p class="text-2xl font-bold text-[#ba1a1a]">1</p>
                    <p class="text-[11px] text-[#444653] uppercase">Maintenance</p>
                </div>
            </div>
        </section>
    </main>
</x-layouts.warehouse>
