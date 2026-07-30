<x-layouts.warehouse title="Dashboard - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Dashboard Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">{{ $subtitle ?? 'Global Logistics Center' }}</p>
                <h2 class="text-2xl md:text-4xl font-bold text-[#191c1e]">{{ $title ?? 'System Overview' }}</h2>
            </div>
            <div class="flex gap-2">
                <div class="bg-[#eceef0] border border-[#c4c5d5] px-4 py-2 rounded-lg flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="font-mono text-sm text-[#444653]">NODE_ALPHA: ONLINE</span>
                </div>
            </div>
        </div>

        {{ $slot ?? '' }}
    </main>
</x-layouts.warehouse>
