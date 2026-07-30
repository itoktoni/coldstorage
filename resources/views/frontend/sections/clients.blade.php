@php
    $items = $data['clients'] ?? [];
@endphp

<section class="py-24 bg-surface-container-low">
    <div class="max-w-7xl mx-auto px-8">
        <div class="text-center mb-12">
            <span class="font-label-sm text-primary tracking-[0.25em] uppercase mb-2 block">Daftar Klien Kami</span>
            <h2 class="font-headline-md text-headline-md text-on-surface">Dipercaya oleh Institusi Terkemuka</h2>
        </div>
        <div class="client-slid">
            <div class="cl-track">
                @foreach($items as $item)
                    <div class="cl-item">
                        <div class="cl-card">
                            <span class="material-symbols-outlined text-4xl text-primary/40 mb-2">{{ $item['icon'] ?? '' }}</span>
                            <span class="font-label-md text-on-surface-variant text-center">{{ $item['name'] ?? '' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <button class="cl-btn cl-prev" aria-label="Prev"><span class="material-symbols-outlined text-on-surface text-[20px]">chevron_left</span></button>
            <button class="cl-btn cl-next" aria-label="Next"><span class="material-symbols-outlined text-on-surface text-[20px]">chevron_right</span></button>
        </div>
        <div class="cl-dots">
            @foreach($items as $index => $item)
                @if($index % 5 === 0)
                    <span class="cl-dot {{ $index === 0 ? 'active' : '' }}" data-group="{{ $index }}"></span>
                @endif
            @endforeach
        </div>
    </div>
</section>
