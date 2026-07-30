<section class="slider-section py-16 px-4" data-autoplay="true" data-dots="true">
    <div class="max-w-7xl mx-auto">
        <div class="swiper">
            <div class="swiper-wrapper">
                @if(!empty($data['carousel']) && is_array($data['carousel']))
                    @foreach($data['carousel'] as $slide)
                    <div class="swiper-slide">
                        <div class="relative">
                            @if(!empty($slide['image']))
                                <img src="{{ $slide['image'] }}" alt="{{ $slide['text'] ?? '' }}" class="w-full h-full object-cover rounded-lg" />
                            @endif
                            <div class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-lg">
                                <div class="text-center text-white px-4">
                                    @if(!empty($slide['text']))
                                        <p class="text-lg mb-6">{{ $slide['text'] }}</p>
                                    @endif
                                    @if(!empty($slide['button']))
                                        <a href="#" class="btn btn-primary">{{ $slide['button'] }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>
