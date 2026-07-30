@php
    $title = $data['title'] ?? 'Bersama Membangun Standar Kesehatan Nasional';
    $subtitle = $data['subtitle'] ?? '';
@endphp

<section class="py-32 relative overflow-hidden bg-white">
    <div class="absolute inset-0 bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-950"></div>
    <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-white/5 to-transparent clip-path-slant"></div>
    <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent"></div>

    <div class="max-w-7xl mx-auto px-8 relative z-10 text-center">
        <div class="bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-xl rounded-[2rem] p-16 border border-white/20 shadow-2xl">
            <h2 class="font-headline-xl text-headline-xl text-white mb-6 drop-shadow-lg">{!! $title !!}</h2>
            @if($subtitle)
                <p class="text-body-lg text-on-surface-variant max-w-3xl mx-auto mb-12 opacity-90 drop-shadow">{{ $subtitle }}</p>
            @endif
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                <a href="/tentang-kami" class="bg-white text-emerald-950 px-10 py-5 rounded-xl font-headline-md text-lg font-semibold shadow-xl hover:scale-105 transition-all">
                    Tentang Kami
                </a>
                <a href="/kontak" class="bg-white/10 backdrop-blur-md border-2 border-white/30 text-white px-10 py-5 rounded-xl font-headline-md text-lg hover:bg-white/20 transition-all">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </div>
</section>

<footer class="bg-emerald-950 text-white pt-20 pb-10 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>

    <div class="max-w-7xl mx-auto px-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
            <div class="lg:col-span-1">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center p-2 shadow-lg">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="font-headline-lg text-white block">ECM</span>
                        <span class="text-xs text-on-surface-variant">UTAMA INDONESIA</span>
                    </div>
                </div>
                <p class="text-body-md text-on-surface-variant max-w-xs leading-relaxed mb-8">
                    Solusi terdepan untuk kalibrasi dan inspeksi alat kesehatan. Presisi mutlak untuk standar kesehatan global.
                </p>
            </div>

            <div>
                <h3 class="font-headline-lg text-white mb-8">Layanan</h3>
                <ul class="space-y-4">
                    <li><a href="/layanan/kalibrasi" class="text-on-surface-variant hover:text-white transition-colors">Kalibrasi Alat Kesehatan</a></li>
                    <li><a href="/layanan/inspeksi" class="text-on-surface-variant hover:text-white transition-colors">Inspeksi Preventive</a></li>
                    <li><a href="/layanan/konsultasi" class="text-on-surface-variant hover:text-white transition-colors">Konsultasi Teknis</a></li>
                    <li><a href="/layanan" class="text-on-surface-variant hover:text-white transition-colors">Semua Layanan</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-headline-lg text-white mb-8">Perusahaan</h3>
                <ul class="space-y-4">
                    <li><a href="/tentang-kami" class="text-on-surface-variant hover:text-white transition-colors">Tentang Kami</a></li>
                    <li><a href="/kompetensi" class="text-on-surface-variant hover:text-white transition-colors">Kompetensi</a></li>
                    <li><a href="/berita" class="text-on-surface-variant hover:text-white transition-colors">Berita & Aktivitas</a></li>
                    <li><a href="/kontak" class="text-on-surface-variant hover:text-white transition-colors">Kontak</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-headline-lg text-white mb-8">Kontak</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary mt-1">location_on</span>
                        <span class="text-on-surface-variant">Jl. TB Simatupang No.18, Jakarta Selatan</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">call</span>
                        <span class="text-on-surface-variant">+62 21 7806715</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">mail</span>
                        <span class="text-on-surface-variant">info@ecm.co.id</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-body-sm text-on-surface-variant">&copy; {{ date('Y') }} ECM Utama Indonesia. All rights reserved.</p>
            <div class="flex items-center gap-6">
                <a href="#" class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center hover:bg-white/20 transition-colors">
                    <span class="text-lg">X</span>
                </a>
                <a href="#" class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center hover:bg-white/20 transition-colors">
                    <span class="text-lg">in</span>
                </a>
                <a href="#" class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center hover:bg-white/20 transition-colors">
                    <span class="text-lg">ig</span>
                </a>
            </div>
        </div>
    </div>
</footer>