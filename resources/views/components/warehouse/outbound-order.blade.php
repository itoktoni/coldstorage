<x-layouts.warehouse title="Outbound Order - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Outbound</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">Order #OUT-2024-1847</h2>
                </div>
            </div>
            <span class="bg-[#2170e4] text-white text-[11px] font-bold px-3 py-1.5 rounded-full uppercase">Processing</span>
        </div>

        <!-- Order Info -->
        <section class="mb-6">
            <div class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Customer</p>
                        <p class="text-sm font-medium text-[#191c1e]">PT. Maju Jaya Industries</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Destination</p>
                        <p class="text-sm font-medium text-[#191c1e]">Warehouse B, Zone 2</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Due Date</p>
                        <p class="text-sm font-medium text-[#191c1e]">June 12, 2026</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Priority</p>
                        <p class="text-sm font-medium text-[#ba1a1a]">High</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Order Items -->
        <section class="mb-6">
            <h3 class="font-semibold text-base text-[#191c1e] mb-4">Order Items (4 items)</h3>
            <div class="bg-white border border-[#c4c5d5] rounded-xl overflow-hidden shadow-sm">
                <div class="divide-y divide-[#c4c5d5]">
                    <!-- Item 1 -->
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-[#00288e]/10 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#00288e] text-xl">inventory_2</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-[#191c1e]">SKU-BRG-2024-X9</p>
                                    <p class="text-[11px] text-[#444653]">Industrial Precision Bearing</p>
                                </div>
                            </div>
                            <span class="bg-green-100 text-green-700 text-[9px] font-bold px-2 py-0.5 rounded-full">PICKED</span>
                        </div>
                        <div class="flex justify-between text-[12px] pl-13">
                            <span class="text-[#444653]">Qty: 50 pcs</span>
                            <span class="text-[#00288e] font-semibold">Rack A-101</span>
                        </div>
                    </div>
                    <!-- Item 2 -->
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-[#00288e]/10 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#00288e] text-xl">inventory_2</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-[#191c1e]">SKU-MTR-2024-A1</p>
                                    <p class="text-[11px] text-[#444653]">Electric Motor Unit</p>
                                </div>
                            </div>
                            <span class="bg-[#00288e] text-white text-[9px] font-bold px-2 py-0.5 rounded-full">PICKING</span>
                        </div>
                        <div class="flex justify-between text-[12px] pl-13">
                            <span class="text-[#444653]">Qty: 25 pcs</span>
                            <span class="text-[#00288e] font-semibold">Rack B-204</span>
                        </div>
                    </div>
                    <!-- Item 3 -->
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-[#e6e8ea] rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[#757684] text-xl">inventory_2</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-[#444653]">SKU-CBL-2024-B2</p>
                                    <p class="text-[11px] text-[#757684]">Control Cable Assembly</p>
                                </div>
                            </div>
                            <span class="bg-[#e6e8ea] text-[#444653] text-[9px] font-bold px-2 py-0.5 rounded-full">PENDING</span>
                        </div>
                        <div class="flex justify-between text-[12px] pl-13">
                            <span class="text-[#757684]">Qty: 100 pcs</span>
                            <span class="text-[#757684]">--</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Actions -->
        <section class="flex gap-3">
            <button class="flex-1 bg-[#00288e] text-white h-14 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                <span class="material-symbols-outlined">local_shipping</span> PACK ORDER
            </button>
            <button class="w-14 h-14 bg-white border border-[#c4c5d5] text-[#444653] rounded-xl flex items-center justify-center active:scale-[0.98] transition-all">
                <span class="material-symbols-outlined">more_vert</span>
            </button>
        </section>
    </main>
</x-layouts.warehouse>
