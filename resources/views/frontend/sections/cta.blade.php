@php
    $ctaData = $data['cta'] ?? $data;
    $title = $ctaData['title'] ?? '';
    $description = $ctaData['description'] ?? '';
    $button1Text = $ctaData['button1_text'] ?? '';
    $button1Link = $ctaData['button1_link'] ?? '#';
    $button2Text = $ctaData['button2_text'] ?? '';
    $button2Link = $ctaData['button2_link'] ?? '#';
    $image = $ctaData['image'] ?? '';
@endphp

<section class="py-24 relative overflow-hidden bg-white">
    <div class="absolute top-0 right-0 w-1/3 h-full emerald-gradient clip-path-slant opacity-10"></div>
    <div class="max-w-7xl mx-auto px-8 relative z-10 flex flex-col md:flex-row items-center gap-16">
        <div class="flex-grow">
            @if($title)
                <h2 class="font-headline-xl text-headline-xl mb-6">{!! $title !!}</h2>
            @endif
            @if($description)
                <p class="text-body-lg text-on-surface-variant max-w-xl mb-10">{{ $description }}</p>
            @endif
            <div class="flex flex-wrap gap-4">
                @if($button1Text)
                    <a href="{{ $button1Link }}" class="bg-primary text-on-primary px-10 py-5 rounded-xl font-headline-md text-lg shadow-xl shadow-primary/20 hover:scale-105 transition-all">
                        {{ $button1Text }}
                    </a>
                @endif
                @if($button2Text)
                    <a href="{{ $button2Link }}" class="bg-white border-2 border-primary text-primary px-10 py-5 rounded-xl font-headline-md text-lg hover:bg-surface-container transition-all">
                        {{ $button2Text }}
                    </a>
                @endif
            </div>
        </div>
        @if($image)
            <div class="w-full md:w-1/3 glass-card p-1 rounded-2xl rotate-3 shadow-2xl">
                <img class="rounded-xl w-full" data-alt="Premium calibration certificate template" src="{{ $image }}" />
            </div>
        @endif
    </div>
</section>
