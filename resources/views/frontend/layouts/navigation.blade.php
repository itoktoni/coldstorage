<nav class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 shadow-sm" id="main-nav">
    <div class="flex justify-between items-center px-8 py-4 max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img alt="ECM Logo" class="h-10 w-auto" src="https://ecm.co.id/assets/img/ecm.png" />
                <span class="font-headline-md text-headline-md font-bold text-primary hidden">ECM</span>
            </a>
        </div>
        <div class="hidden md:flex items-center space-x-8">
            <a class="font-label-md text-label-md {{ request()->is('/') ? 'text-secondary border-b-2 border-secondary pb-1' : 'text-on-surface-variant hover:text-primary' }} transition-colors duration-300" href="{{ url('/') }}">Home</a>
            @if($menu && $menu->items)
                @php
                    $navItems = collect($menu->items)->sortBy('sort_order')->values();
                @endphp
                @foreach($navItems as $item)
                    @if(!empty($item['children']) && count($item['children']) > 0)
                        <div class="relative group">
                            <button class="font-label-md text-label-md {{ request()->is($item['url'] . '*') ? 'text-secondary border-b-2 border-secondary pb-1' : 'text-on-surface-variant hover:text-primary' }} transition-colors duration-300 flex items-center gap-1 cursor-pointer">
                                {{ $item['label'] }}
                                <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="absolute left-0 top-full pt-2 hidden group-hover:block z-50">
                                <div class="bg-white rounded-lg shadow-xl border border-outline-variant/30 py-2 min-w-[220px]">
                                    @foreach($item['children'] as $child)
                                        <a href="{{ $child['url'] ?? '#' }}" target="{{ $child['target'] ?? '_self' }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors">
                                            @if(!empty($child['icon']))
                                                <span class="material-symbols-outlined text-base">{{ $child['icon'] }}</span>
                                            @endif
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <a class="font-label-md text-label-md {{ request()->is($item['url']) ? 'text-secondary border-b-2 border-secondary pb-1' : 'text-on-surface-variant hover:text-primary' }} transition-colors duration-300" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @endif
                @endforeach
            @endif
        </div>
        <div class="flex items-center gap-6">
            <a href="{{ route('search') }}" class="material-symbols-outlined text-on-surface-variant hover:text-primary transition-colors">search</a>
            <a href="{{ route('contact') }}" class="bg-primary text-on-primary px-6 py-2.5 rounded-lg font-label-md hover:opacity-90 active:scale-95 transition-all">Hubungi Kami</a>
        </div>
    </div>
</nav>
