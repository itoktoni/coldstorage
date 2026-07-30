{{-- Mobile Drawer --}}
<div class="fixed top-0 left-0 h-full w-72 bg-surface-container-lowest z-50 md:hidden shadow-2xl flex flex-col transition-transform duration-300" :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="flex items-center justify-between px-5 h-16 border-b border-outline-variant">
        <h2 class="font-headline-md text-headline-md font-bold text-primary">WMS Portal</h2>
        <button class="p-2 hover:bg-surface-container rounded-full transition-colors" @click="drawerOpen = false">
            <span class="text-on-surface-variant">✕</span>
        </button>
    </div>
    <nav class="flex-1 py-4 px-3 pb-24 space-y-1 overflow-y-auto">
        <x-menu-items :mobile="true" />
    </nav>
</div>