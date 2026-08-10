<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

 @include('layouts.head')

<body class="text-on-surface bg-surface antialiased font-body-sm overflow-x-hidden" x-data="warehouseApp()">

    {{-- Overlay for mobile drawer --}}
    <div class="fixed inset-0 bg-black/40 z-40 md:hidden transition-opacity duration-200" :class="drawerOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'" @click="drawerOpen = false"></div>

    @include('layouts.header')

    @include('layouts.mobile')

    @include('layouts.sidebar')

    {{-- Main Content --}}
    <main class="px-4 md:px-6" style="padding-top: calc(4rem + env(safe-area-inset-top)); padding-bottom: calc(8rem + env(safe-area-inset-bottom));" :class="sidebarOpen ? 'md:ml-72' : 'md:ml-0'">
        <div class="max-w-full md:max-w-[calc(100vw-18rem)] mx-auto">
            {{ $slot }}
        </div>
    </main>

    {{-- Bottom Nav (Mobile) --}}
    <x-bottom-nav />

    @stack('scripts')

    @include('layouts.script')
    @livewireScripts

    <x-printer-modal />

</body>
</html>
