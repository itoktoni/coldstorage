@php
    $cards = $data['services'] ?? [];
@endphp

<section class="py-24 bg-surface-container-low">
    <div class="max-w-7xl mx-auto px-8">
        <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
            <div class="max-w-2xl">
                <div class="w-12 h-1 bg-secondary-container mb-6"></div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-4">Pelayanan Kami</h2>
                <p class="text-on-surface-variant text-body-lg">Solusi teknis terdepan untuk fasilitas kesehatan modern, memastikan setiap alat diagnostik beroperasi dalam batas presisi yang ketat.</p>
            </div>
            <a class="text-primary font-label-md font-bold inline-flex items-center gap-2 hover:gap-3 transition-all shrink-0" href="#">
                Semua Layanan <span class="material-symbols-outlined">arrow_right_alt</span>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($cards as $card)
                <div class="bg-white rounded-xl p-8 border border-outline-variant/30 hover:shadow-xl transition-all group">
                    <div class="w-14 h-14 bg-surface-container-low text-primary flex items-center justify-center rounded-lg mb-6 group-hover:bg-primary group-hover:text-on-primary transition-colors">
                        <span class="material-symbols-outlined text-[32px]">{{ $card['icon'] ?? '' }}</span>
                    </div>
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-4">{{ $card['title'] ?? '' }}</h3>
                    <p class="text-on-surface-variant text-[15px] mb-6 leading-relaxed">{{ $card['description'] ?? '' }}</p>
                    @if(!empty($card['features']))
                        <div class="h-px bg-outline-variant/30 mb-6"></div>
                        <ul class="space-y-3 font-body-md text-[13px] text-outline">
                            @foreach($card['features'] as $feature)
                                <li class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-secondary-container rounded-full"></span>
                                    {{ $feature['text'] ?? '' }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
