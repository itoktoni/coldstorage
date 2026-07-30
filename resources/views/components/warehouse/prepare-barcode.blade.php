<x-layouts.warehouse title="Prepare Barang - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Preparation</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">Prepare Items</h2>
                </div>
            </div>
        </div>

        <!-- Search/Scanner -->
        <section class="mb-6">
            <div class="relative group">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-[#757684]">qr_code_scanner</span>
                </div>
                <input
                    class="w-full bg-white border-2 border-[#00288e] text-[#191c1e] h-16 pl-14 pr-4 rounded-xl shadow-md transition-all font-mono text-lg outline-none"
                    placeholder="Scan or enter barcode..."
                    type="text"
                />
            </div>
        </section>

        <!-- Prepared Items -->
        <section class="mb-6">
            <h3 class="font-semibold text-base text-[#191c1e] mb-4">Prepared Items</h3>
            <div class="bg-white border border-[#c4c5d5] rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 border-b border-[#c4c5d5]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-[#00288e]/10 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-[#00288e] text-2xl">inventory_2</span>
                            </div>
                            <div>
                                <p class="text-base font-bold text-[#00288e]">SKU-BRG-2024-X9</p>
                                <p class="text-sm text-[#444653]">Industrial Precision Bearing - Type Z</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-[#191c1e]">50</p>
                            <p class="text-[10px] font-semibold text-[#444653] uppercase">pcs prepared</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 bg-[#eceef0]">
                    <div class="grid grid-cols-2 gap-4 text-[12px]">
                        <div class="flex justify-between">
                            <span class="text-[#444653]">Location:</span>
                            <span class="font-semibold text-[#00288e]">Rack A-101</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#444653]">Stock ID:</span>
                            <span class="font-semibold">STK-008291</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#444653]">Batch:</span>
                            <span class="font-semibold">BATCH-2024-05</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#444653]">Exp. Date:</span>
                            <span class="font-semibold text-[#ba1a1a]">Dec 2027</span>
                        </div>
                    </div>
                </div>
                <div class="p-4 flex gap-3">
                    <button class="flex-1 bg-[#ffdad6] text-[#ba1a1a] border border-[#ba1a1a]/20 h-10 rounded-lg font-semibold text-[12px] flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                        <span class="material-symbols-outlined text-[16px]">undo</span> UNDO
                    </button>
                    <button class="flex-1 bg-[#dcfce7] text-green-700 border border-green-600/20 h-10 rounded-lg font-semibold text-[12px] flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span> CONFIRM
                    </button>
                </div>
            </div>
        </section>

        <!-- Summary -->
        <section class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-base text-[#191c1e]">Session Summary</h3>
                <button class="text-[#0058be] text-[11px] font-semibold">CLEAR ALL</button>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-[#f2f4f6] rounded-lg p-3">
                    <p class="text-xl font-bold text-[#00288e]">12</p>
                    <p class="text-[10px] text-[#444653] uppercase">Items Prepared</p>
                </div>
                <div class="bg-[#f2f4f6] rounded-lg p-3">
                    <p class="text-xl font-bold text-[#00288e]">485</p>
                    <p class="text-[10px] text-[#444653] uppercase">Total Qty</p>
                </div>
                <div class="bg-[#f2f4f6] rounded-lg p-3">
                    <p class="text-xl font-bold text-green-600">5</p>
                    <p class="text-[10px] text-[#444653] uppercase">Confirmed</p>
                </div>
            </div>
        </section>
    </main>
</x-layouts.warehouse>
