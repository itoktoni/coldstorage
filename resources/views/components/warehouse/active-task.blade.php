gerge<x-layouts.warehouse title="Active Task - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">Active Task</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">#TSK-9921</h2>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="bg-[#00288e] text-white text-[11px] font-bold px-3 py-1.5 rounded-full uppercase">In Progress</span>
            </div>
        </div>

        <!-- Task Status Timeline -->
        <section class="mb-8">
            <div class="bg-white border border-[#c4c5d5] rounded-xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base text-[#191c1e]">Task Progress</h3>
                    <span class="text-[12px] font-bold text-[#00288e]">75% COMPLETE</span>
                </div>
                <div class="w-full bg-[#eceef0] rounded-full h-3 overflow-hidden">
                    <div class="h-full bg-[#00288e] rounded-full" style="width: 75%"></div>
                </div>
                <div class="flex justify-between mt-2 text-[11px] text-[#444653]">
                    <span>Started: 14:22</span>
                    <span>Est. Completion: 14:55</span>
                </div>
            </div>
        </section>

        <!-- Task Details -->
        <section class="mb-8">
            <div class="bg-white border border-[#c4c5d5] rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 border-b border-[#c4c5d5]">
                    <h3 class="font-semibold text-base text-[#191c1e] flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#00288e]">task</span> Task Details
                    </h3>
                </div>
                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Task Type</p>
                            <p class="text-sm font-medium text-[#191c1e]">Picking</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Priority</p>
                            <p class="text-sm font-medium text-[#ba1a1a]">High</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Assigned To</p>
                            <p class="text-sm font-medium text-[#191c1e]">John Doe</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-[#444653] uppercase mb-1">Due Date</p>
                            <p class="text-sm font-medium text-[#191c1e]">Today, 15:00</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Item List -->
        <section class="mb-8">
            <div class="bg-white border border-[#c4c5d5] rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 border-b border-[#c4c5d5]">
                    <h3 class="font-semibold text-base text-[#191c1e] flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#0058be]">list</span> Items to Pick (3/4)
                    </h3>
                </div>
                <div class="divide-y divide-[#c4c5d5]">
                    <!-- Item 1 - Completed -->
                    <div class="p-4 flex items-center gap-4 bg-green-50/50">
                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-white text-sm">check</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[#191c1e]">SKU-BRG-2024-X9</p>
                            <p class="text-[11px] text-[#444653]">Industrial Precision Bearing - Qty: 50</p>
                        </div>
                        <div class="text-right">
                            <span class="bg-green-100 text-green-700 text-[9px] font-bold px-2 py-0.5 rounded block">DONE</span>
                        </div>
                    </div>
                    <!-- Item 2 - Completed -->
                    <div class="p-4 flex items-center gap-4 bg-green-50/50">
                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-white text-sm">check</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[#191c1e]">SKU-MTR-2024-A1</p>
                            <p class="text-[11px] text-[#444653]">Electric Motor Unit - Qty: 25</p>
                        </div>
                        <div class="text-right">
                            <span class="bg-green-100 text-green-700 text-[9px] font-bold px-2 py-0.5 rounded block">DONE</span>
                        </div>
                    </div>
                    <!-- Item 3 - Current -->
                    <div class="p-4 flex items-center gap-4 bg-[#00288e]/5">
                        <div class="w-8 h-8 rounded-full bg-[#00288e] flex items-center justify-center shrink-0 animate-pulse">
                            <span class="material-symbols-outlined text-white text-sm">front_loader</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[#00288e]">SKU-CBL-2024-B2</p>
                            <p class="text-[11px] text-[#444653]">Control Cable Assembly - Qty: 100</p>
                        </div>
                        <div class="text-right">
                            <span class="bg-[#00288e] text-white text-[9px] font-bold px-2 py-0.5 rounded block">CURRENT</span>
                        </div>
                    </div>
                    <!-- Item 4 - Pending -->
                    <div class="p-4 flex items-center gap-4">
                        <div class="w-8 h-8 rounded-full bg-[#e6e8ea] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[#757684] text-sm">schedule</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[#444653]">SKU-HYD-2024-C3</p>
                            <p class="text-[11px] text-[#757684]">Hydraulic Pump Unit - Qty: 15</p>
                        </div>
                        <div class="text-right">
                            <span class="bg-[#e6e8ea] text-[#444653] text-[9px] font-bold px-2 py-0.5 rounded block">PENDING</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Action Buttons -->
        <section class="flex gap-3">
            <button class="flex-1 bg-[#00288e] text-white h-14 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                <span class="material-symbols-outlined">check_circle</span> COMPLETE TASK
            </button>
            <button class="w-14 h-14 bg-white border border-[#c4c5d5] text-[#444653] rounded-xl flex items-center justify-center active:scale-[0.98] transition-all">
                <span class="material-symbols-outlined">more_vert</span>
            </button>
        </section>
    </main>
</x-layouts.warehouse>
