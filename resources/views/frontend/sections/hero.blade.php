@php
    $slides = $data['hero'] ?? [];
@endphp

<div class="hero-main-slider">
    <div class="hs-track">
        @foreach($slides as $index => $slide)
            <div class="hs-slide {{ $index === 0 ? 'active' : '' }}">
                <div class="absolute inset-0 z-0">
                    @if(!empty($slide['image']))
                        <img data-alt="{{ $slide['subtitle'] ?? '' }}" src="{{ $slide['image'] }}" />
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-r from-background/95 via-background/60 to-transparent z-10"></div>
                </div>
                <div class="relative z-20 max-w-7xl mx-auto px-4 md:px-8 w-full">
                    <div class="hs-content max-w-2xl glass-card p-6 md:p-12 rounded-xl">
                        @if(!empty($slide['subtitle']))
                            <span class="font-label-md text-primary tracking-[0.2em] uppercase mb-4 block">{{ $slide['subtitle'] }}</span>
                        @endif
                        @if(!empty($slide['title']))
                            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-6 leading-tight">{!! $slide['title'] !!}</h1>
                        @endif
                        @if(!empty($slide['description']))
                            <p class="font-body-lg text-body-lg text-on-surface-variant mb-10">{{ $slide['description'] }}</p>
                        @endif
                        <div class="flex flex-wrap gap-4">
                            @if(!empty($slide['button1_text']))
                                <a href="{{ $slide['button1_link'] ?? '#' }}" class="bg-primary text-on-primary px-6 md:px-8 py-3 md:py-4 rounded-lg font-label-md text-base md:text-lg flex items-center gap-2 hover:shadow-lg transition-all group">
                                    {{ $slide['button1_text'] }}
                                    <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                                </a>
                            @endif
                            @if(!empty($slide['button2_text']))
                                <a href="{{ $slide['button2_link'] ?? '#' }}" class="border border-outline px-6 md:px-8 py-3 md:py-4 rounded-lg font-label-md text-base md:text-lg hover:bg-surface-container transition-all">
                                    {{ $slide['button2_text'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <button class="hs-arrow hs-prev" aria-label="Prev"><span class="material-symbols-outlined text-on-surface">chevron_left</span></button>
    <button class="hs-arrow hs-next" aria-label="Next"><span class="material-symbols-outlined text-on-surface">chevron_right</span></button>
    <div class="hs-dots">
        @foreach($slides as $index => $slide)
            <span class="hs-dot {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}"></span>
        @endforeach
    </div>
</div>
