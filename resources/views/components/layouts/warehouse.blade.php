<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>{{ $title ?? 'WMS Portal' }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-tint": "#3755c3",
                        "surface-dim": "#d8dadc",
                        "secondary-container": "#2170e4",
                        "primary-fixed-dim": "#b8c4ff",
                        "on-primary-fixed": "#001453",
                        outline: "#757684",
                        "on-primary-fixed-variant": "#173bab",
                        "surface-variant": "#e0e3e5",
                        "on-secondary-fixed-variant": "#004395",
                        background: "#f7f9fb",
                        "on-surface-variant": "#444653",
                        "on-secondary": "#ffffff",
                        "on-tertiary-fixed-variant": "#3f465c",
                        error: "#ba1a1a",
                        "tertiary-container": "#434b60",
                        "secondary-fixed": "#d8e2ff",
                        "primary-container": "#1e40af",
                        primary: "#00288e",
                        "on-tertiary": "#ffffff",
                        "on-primary": "#ffffff",
                        "on-background": "#191c1e",
                        "on-tertiary-container": "#b4bbd5",
                        "on-primary-container": "#a8b8ff",
                        tertiary: "#2d3449",
                        "surface-container-highest": "#e0e3e5",
                        "on-surface": "#191c1e",
                        "inverse-primary": "#b8c4ff",
                        "tertiary-fixed": "#dae2fd",
                        "surface-container-high": "#e6e8ea",
                        "surface-bright": "#f7f9fb",
                        "error-container": "#ffdad6",
                        "on-error": "#ffffff",
                        "surface-container": "#eceef0",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary-container": "#fefcff",
                        "inverse-on-surface": "#eff1f3",
                        secondary: "#0058be",
                        surface: "#f7f9fb",
                        "outline-variant": "#c4c5d5",
                        "on-secondary-fixed": "#001a42",
                        "inverse-surface": "#2d3133",
                        "secondary-fixed-dim": "#adc6ff",
                        "primary-fixed": "#dde1ff",
                        "surface-container-low": "#f2f4f6",
                        "on-tertiary-fixed": "#131b2e"
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px"
                    },
                    spacing: {
                        base: "4px",
                        "margin-desktop": "32px",
                        lg: "24px",
                        "margin-mobile": "16px",
                        xl: "32px",
                        gutter: "16px",
                        md: "16px",
                        xs: "4px",
                        sm: "8px"
                    },
                    fontFamily: {
                        headline: ["Inter"],
                        "label-caps": ["Inter"],
                        body: ["Inter"],
                        mono: ["JetBrains Mono"]
                    }
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
    </style>
    @stack('styles')
</head>
<body class="text-[#191c1e] bg-[#f7f9fb] antialiased pb-20 md:pb-0 md:pt-16 font-sans">
    <!-- TopAppBar -->
    <header class="fixed top-0 w-full z-50 bg-[#f2f4f6] border-b border-[#c4c5d5] shadow-sm flex items-center justify-between px-4 md:px-8 h-16">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-[#00288e]/10 flex items-center justify-center cursor-pointer active:opacity-80">
                @if(isset($userAvatar))
                    <img alt="User Profile" class="w-8 h-8 rounded-full" src="{{ $userAvatar }}"/>
                @else
                    <span class="material-symbols-outlined text-[#00288e]">account_circle</span>
                @endif
            </div>
            <h1 class="font-semibold text-lg text-[#00288e]">{{ $headerTitle ?? 'WMS Portal' }}</h1>
        </div>
        <nav class="hidden md:flex items-center gap-8">
            <a class="text-[#00288e] text-xs font-semibold uppercase tracking-wider border-b-2 border-[#00288e] pb-1" href="{{ route('dashboard') }}">Dashboard</a>
            <a class="text-[#444653] text-xs font-semibold uppercase tracking-wider hover:text-[#0058be] transition-colors" href="#">Inbound</a>
            <a class="text-[#444653] text-xs font-semibold uppercase tracking-wider hover:text-[#0058be] transition-colors" href="#">Outbound</a>
            <a class="text-[#444653] text-xs font-semibold uppercase tracking-wider hover:text-[#0058be] transition-colors" href="{{ route('dashboard') }}">Inventory</a>
        </nav>
        <div class="flex items-center gap-4">
            <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors cursor-pointer">notifications</button>
            <button class="material-symbols-outlined text-[#444653] hover:bg-[#eceef0] p-2 rounded-full transition-colors cursor-pointer">settings</button>
        </div>
    </header>

    {{ $slot }}

    <!-- BottomNavBar (Mobile Only) -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full flex justify-around items-center h-16 pb-safe bg-[#f2f4f6] border-t border-[#c4c5d5] shadow-lg z-50">
        <a class="flex flex-col items-center justify-center text-[#00288e] group active:scale-95 transition-all" href="{{ route('dashboard') }}">
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
        <a class="flex flex-col items-center justify-center text-[#444653] hover:text-[#0058be] active:scale-95 transition-all" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined">inventory_2</span>
            <span class="text-[10px] font-semibold uppercase">Inventory</span>
        </a>
    </nav>

    @stack('scripts')
</body>
</html>
