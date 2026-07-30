<x-layouts.warehouse title="Form - WMS Portal">
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors">arrow_back</button>
                <div>
                    <p class="text-xs font-semibold text-[#0058be] uppercase tracking-widest mb-1">{{ $formType ?? 'Form' }}</p>
                    <h2 class="text-2xl font-bold text-[#191c1e]">{{ $formTitle ?? 'New Entry' }}</h2>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <form class="space-y-6">
            {{ $slot ?? '' }}

            <!-- Form Actions -->
            <div class="flex gap-3 pt-4 border-t border-[#c4c5d5]">
                <button type="button" class="flex-1 bg-white border border-[#c4c5d5] text-[#444653] h-12 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                    Cancel
                </button>
                <button type="submit" class="flex-1 bg-[#00288e] text-white h-12 rounded-xl font-semibold flex items-center justify-center gap-2 active:scale-[0.98] transition-all shadow-sm">
                    <span class="material-symbols-outlined">save</span> Save
                </button>
            </div>
        </form>
    </main>
</x-layouts.warehouse>
