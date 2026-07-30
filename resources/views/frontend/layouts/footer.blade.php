<footer class="bg-[#007A4D] py-24 text-white">
    <div class="max-w-7xl mx-auto px-8 grid grid-cols-1 md:grid-cols-4 gap-gutter">
        {{-- Company Info --}}
        <div class="md:col-span-1">
            <div class="flex items-center gap-3 mb-8">
                <img alt="ECM Logo" class="h-8 w-auto brightness-0 invert" src="https://ecm.co.id/assets/img/ecm.png" />
            </div>
            <p class="text-white/70 font-body-md mb-8">PT. Elsyahfi Calibratech Mandiri adalah Institusi Pengujian Fasilitas Kesehatan (IPFK) di Sumatera Barat yang berdiri sejak tahun 2018.</p>
            <div class="flex gap-4">
                <a href="https://www.instagram.com/ecm.elsyahfi/" target="_blank" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-secondary-container hover:text-on-secondary-container transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-xl">share</span>
                </a>
                <a href="mailto:ecm.pwk@gmail.com" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-secondary-container hover:text-on-secondary-container transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-xl">mail</span>
                </a>
                <a href="https://wa.me/6285263339098" target="_blank" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-secondary-container hover:text-on-secondary-container transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-xl">call</span>
                </a>
            </div>
        </div>

        {{-- Dynamic Footer Menu Columns --}}
        @if($footerMenu && $footerMenu->items)
            @php
                $footerItems = collect($footerMenu->items)->sortBy('sort_order')->values();
            @endphp
            @foreach($footerItems as $item)
                <div>
                    <h5 class="text-secondary-container font-headline-md mb-8">{{ $item['label'] }}</h5>
                    <ul class="space-y-4">
                        @if(!empty($item['children']))
                            @foreach($item['children'] as $child)
                                <li>
                                    <a class="text-white/70 font-label-md hover:text-white transition-colors"
                                       href="{{ $child['url'] ?? '#' }}"
                                       target="{{ $child['target'] ?? '_self' }}">
                                        @if(!empty($child['icon']))
                                            <span class="material-symbols-outlined text-sm align-middle mr-1">{{ $child['icon'] }}</span>
                                        @endif
                                        {{ $child['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            @endforeach
        @else
            {{-- Fallback: static menu if no footer menu in database --}}
            <div>
                <h5 class="text-secondary-container font-headline-md mb-8">Layanan</h5>
                <ul class="space-y-4">
                    <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="{{ url('/services') }}">Kalibrasi Alat Kesehatan</a></li>
                    <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="{{ url('/services') }}">Inspeksi Preventive</a></li>
                    <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="{{ url('/services') }}">Konsultasi Teknis</a></li>
                    <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="{{ url('/services') }}">Verifikasi Sertifikat</a></li>
                </ul>
            </div>
            <div>
                <h5 class="text-secondary-container font-headline-md mb-8">Perusahaan</h5>
                <ul class="space-y-4">
                    <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="{{ url('/sejarah') }}">Tentang Kami</a></li>
                    <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="{{ route('blog') }}">Berita</a></li>
                    <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="#">Sertifikat Akreditasi</a></li>
                    <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="{{ route('contact') }}">Hubungi Kami</a></li>
                </ul>
            </div>
        @endif

        {{-- Office & Lab Info --}}
        <div>
            <h5 class="text-secondary-container font-headline-md mb-8">Kantor & Lab</h5>
            <ul class="space-y-4">
                <li><span class="text-white/70 font-label-md flex items-start gap-2">
                    <span class="material-symbols-outlined text-sm mt-0.5">location_on</span>
                    Jl. Perkutut Komp. Perumahan Nuansa Mandiri No B4, Payakumbuh - Sumatera Barat
                </span></li>
                <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="https://wa.me/6285263339098" target="_blank">+62 852 6333 9098</a></li>
                <li><a class="text-white/70 font-label-md hover:text-white transition-colors" href="mailto:ecm.pwk@gmail.com">ecm.pwk@gmail.com</a></li>
                <li><span class="text-white/70 font-label-md">IPFK Terakreditasi KAN</span></li>
            </ul>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-8 mt-24 pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4">
        <span class="text-label-sm text-white/40">&copy; {{ date('Y') }} PT. Elsyahfi Calibratech Mandiri. All Rights Reserved.</span>
        <div class="flex gap-8 items-center">
            <span class="text-label-sm text-white/40 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-secondary-container animate-pulse"></span> Systems Operational
            </span>
            <span class="text-label-sm text-white/40">HQ: Payakumbuh, Sumatera Barat</span>
        </div>
    </div>
</footer>