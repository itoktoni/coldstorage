<x-layouts.warehouse>
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Dashboard Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Global Logistics Center</p>
                <h2 class="text-2xl md:text-4xl font-bold text-[#191c1e]">System Overview</h2>
            </div>
            <div class="flex gap-2">
                <div class="bg-[#eceef0] border border-[#c4c5d5] px-4 py-2 rounded-lg flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="font-mono text-sm text-[#444653]">NODE_ALPHA: ONLINE</span>
                </div>
            </div>
        </div>

        <!-- Shortcut Menu / Quick Actions -->
        <section class="mb-8">
            <h3 class="text-xs font-semibold text-[#444653] mb-4 uppercase">Quick Actions</h3>
            <div class="grid grid-cols-4 gap-4">
                <button class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 rounded-full bg-[#1e40af]/10 flex items-center justify-center text-[#00288e] group-active:scale-95 transition-transform">
                        <span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
                    </div>
                    <span class="text-[11px] font-semibold text-[#191c1e] text-center">Scan Inbound</span>
                </button>
                <button class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 rounded-full bg-[#2170e4]/10 flex items-center justify-center text-[#0058be] group-active:scale-95 transition-transform">
                        <span class="material-symbols-outlined text-2xl">pan_tool_alt</span>
                    </div>
                    <span class="text-[11px] font-semibold text-[#191c1e] text-center">Picking</span>
                </button>
                <button class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 rounded-full bg-[#434b60]/10 flex items-center justify-center text-[#2d3449] group-active:scale-95 transition-transform">
                        <span class="material-symbols-outlined text-2xl">inventory</span>
                    </div>
                    <span class="text-[11px] font-semibold text-[#191c1e] text-center">Stock Opname</span>
                </button>
                <button class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 rounded-full bg-[#e0e3e5] flex items-center justify-center text-[#444653] group-active:scale-95 transition-transform">
                        <span class="material-symbols-outlined text-2xl">sync_alt</span>
                    </div>
                    <span class="text-[11px] font-semibold text-[#191c1e] text-center">Relocation</span>
                </button>
            </div>
        </section>

        <!-- Main Dashboard Body -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <!-- Operational Metrics Widget -->
            <div class="bg-white border border-[#c4c5d5] rounded-xl p-4 shadow-sm">
                <h3 class="font-semibold text-base mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#00288e] text-xl">analytics</span>
                    Operational Metrics
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-[#f2f4f6] border border-[#c4c5d5] p-3 rounded-lg">
                        <p class="text-[10px] font-semibold text-[#444653] mb-1 uppercase">Inbound</p>
                        <div class="flex items-end justify-between">
                            <span class="text-xl font-bold text-[#00288e]">482</span>
                            <span class="text-[10px] text-green-600 font-bold">+5%</span>
                        </div>
                    </div>
                    <div class="bg-[#f2f4f6] border border-[#c4c5d5] p-3 rounded-lg">
                        <p class="text-[10px] font-semibold text-[#444653] mb-1 uppercase">Outbound</p>
                        <div class="flex items-end justify-between">
                            <span class="text-xl font-bold text-[#0058be]">315</span>
                            <span class="text-[10px] text-red-600 font-bold">-2%</span>
                        </div>
                    </div>
                    <div class="bg-[#f2f4f6] border border-[#c4c5d5] p-3 rounded-lg">
                        <p class="text-[10px] font-semibold text-[#444653] mb-1 uppercase">Low Stock</p>
                        <div class="flex items-end justify-between">
                            <span class="text-xl font-bold text-[#ba1a1a]">12</span>
                            <span class="material-symbols-outlined text-[#ba1a1a] text-sm">warning</span>
                        </div>
                    </div>
                    <div class="bg-[#f2f4f6] border border-[#c4c5d5] p-3 rounded-lg">
                        <p class="text-[10px] font-semibold text-[#444653] mb-1 uppercase">Pending Splits</p>
                        <div class="flex items-end justify-between">
                            <span class="text-xl font-bold text-[#191c1e]">24</span>
                            <span class="text-[10px] text-[#444653]">Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warehouse Occupancy Widget -->
            <div class="bg-white border border-[#c4c5d5] rounded-xl p-4 shadow-sm flex flex-col justify-center">
                <h3 class="font-semibold text-base mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#0058be] text-xl">warehouse</span>
                    Warehouse Occupancy
                </h3>
                <div class="flex items-center gap-6">
                    <div class="relative w-24 h-24">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle class="text-[#eceef0]" cx="48" cy="48" fill="transparent" r="40" stroke="currentColor" stroke-width="10"></circle>
                            <circle class="text-[#0058be]" cx="48" cy="48" fill="transparent" r="40" stroke="currentColor" stroke-dasharray="251.3" stroke-dashoffset="80.4" stroke-linecap="round" stroke-width="10"></circle>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="font-mono text-xl font-bold">68%</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-[#444653]">Utilized</span>
                                <span class="font-bold">42,160</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-[#444653]">Available</span>
                                <span class="font-bold">19,840</span>
                            </div>
                            <div class="pt-2 border-t border-[#c4c5d5]">
                                <p class="text-[10px] text-[#444653] uppercase font-bold tracking-tight">Status: Near Optimal</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Performance Summary -->
        <div class="bg-white border border-[#c4c5d5] rounded-xl p-4 shadow-sm mb-8 md:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-base flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#2d3449] text-xl">monitoring</span>
                    Today's Performance
                </h3>
                <span class="text-[10px] font-bold text-[#444653] bg-[#eceef0] px-2 py-1 rounded">LIVE UPDATES</span>
            </div>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-end mb-1">
                        <span class="text-xs font-medium text-[#444653]">Throughput Goal (850 units)</span>
                        <span class="text-xs font-bold">796 / 850</span>
                    </div>
                    <div class="w-full h-2 bg-[#eceef0] rounded-full overflow-hidden">
                        <div class="h-full bg-[#00288e] rounded-full" style="width: 93%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 pt-2">
                    <div class="text-center">
                        <p class="text-[10px] text-[#444653] uppercase mb-1">Error Rate</p>
                        <p class="text-sm font-bold text-green-600">0.04%</p>
                    </div>
                    <div class="text-center border-x border-[#c4c5d5]">
                        <p class="text-[10px] text-[#444653] uppercase mb-1">Avg Lead Time</p>
                        <p class="text-sm font-bold">14m 22s</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-[#444653] uppercase mb-1">Fleet Util.</p>
                        <p class="text-sm font-bold">88%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity List -->
        <div class="bg-white border border-[#c4c5d5] rounded-xl p-4 shadow-sm md:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-base flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#444653] text-xl">history</span>
                    Recent Activity
                </h3>
                <button class="text-[#00288e] text-[11px] font-bold hover:underline">VIEW ALL</button>
            </div>
            <div class="space-y-0">
                <!-- Transaction 1 -->
                <div class="flex items-center gap-4 py-3 border-b border-[#c4c5d5] last:border-0">
                    <div class="bg-[#00288e]/5 p-2 rounded-lg shrink-0">
                        <span class="material-symbols-outlined text-[#00288e] text-sm">login</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-[#191c1e] truncate">#WMS-9021 Pallet Intake</p>
                        <p class="text-[11px] text-[#444653]">Aisle 4 · User: J. Doe</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="bg-green-50 text-green-700 text-[9px] font-bold px-2 py-0.5 rounded block mb-1">COMPLETE</span>
                        <p class="text-[9px] text-[#757684] font-mono">14:22</p>
                    </div>
                </div>
                <!-- Transaction 2 -->
                <div class="flex items-center gap-4 py-3 border-b border-[#c4c5d5] last:border-0">
                    <div class="bg-[#0058be]/5 p-2 rounded-lg shrink-0">
                        <span class="material-symbols-outlined text-[#0058be] text-sm">logout</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-[#191c1e] truncate">#WMS-8842 Dock 7 Outbound</p>
                        <p class="text-[11px] text-[#444653]">Priority Air · User: S. Lee</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="bg-blue-50 text-blue-700 text-[9px] font-bold px-2 py-0.5 rounded block mb-1">PROCESSING</span>
                        <p class="text-[9px] text-[#757684] font-mono">14:15</p>
                    </div>
                </div>
                <!-- Transaction 3 -->
                <div class="flex items-center gap-4 py-3 border-b border-[#c4c5d5] last:border-0">
                    <div class="bg-[#2d3449]/5 p-2 rounded-lg shrink-0">
                        <span class="material-symbols-outlined text-[#2d3449] text-sm">sync</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-[#191c1e] truncate">#WMS-9104 Stock Relocation</p>
                        <p class="text-[11px] text-[#444653]">Zone C to Zone B</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="bg-[#e6e8ea] text-[#444653] text-[9px] font-bold px-2 py-0.5 rounded block mb-1">READY</span>
                        <p class="text-[9px] text-[#757684] font-mono">13:48</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- BottomNavBar (Mobile Only) -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full flex justify-around items-center h-16 pb-safe bg-[#f2f4f6] border-t border-[#c4c5d5] shadow-lg z-50">
        <a class="flex flex-col items-center justify-center text-[#00288e] group active:scale-95 transition-all" href="#">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">dashboard</span>
            <span class="text-[10px] font-semibold uppercase">Dashboard</span>
        </a>
        <a class="flex flex-col items-center justify-center text-[#444653] hover:text-[#0058be] active:scale-95 transition-all" href="#">
            <span class="material-symbols-outlined">input</span>
            <span class="text-[10px] font-semibold uppercase">Inbound</span>
        </a>
        <a class="flex flex-col items-center justify-center text-[#444653] hover:text-[#0058be] active:scale-95 transition-all" href="#">
            <span class="material-symbols-outlined">output</span>
            <span class="text-[10px] font-semibold uppercase">Outbound</span>
        </a>
        <a class="flex flex-col items-center justify-center text-[#444653] hover:text-[#0058be] active:scale-95 transition-all" href="#">
            <span class="material-symbols-outlined">inventory_2</span>
            <span class="text-[10px] font-semibold uppercase">Inventory</span>
        </a>
    </nav>
</x-layouts.warehouse>
