@extends('frontend.layouts.public')

@section('title', $entry?->title ?? 'Hubungi Kami')

@section('content')
    @if($data)
        @foreach($data['sections'] as $template => $value)
            @includeIf('frontend.sections.' . $template, ['data' => $value])
        @endforeach
    @else
        {{-- Fallback jika belum ada content type contact --}}
        <section class="py-24 bg-surface">
            <div class="max-w-7xl mx-auto px-8">
                <div class="flex items-center gap-4 mb-16">
                    <div class="h-px bg-outline-variant flex-grow"></div>
                    <h1 class="font-headline-lg text-headline-lg text-on-surface shrink-0 px-4">Hubungi Kami</h1>
                    <div class="h-px bg-outline-variant flex-grow"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                    {{-- Contact Info --}}
                    <div>
                        <h2 class="font-headline-md text-headline-md text-on-surface mb-8">Informasi Kontak</h2>
                        <div class="space-y-8">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-primary-container/10 rounded-xl flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-primary">location_on</span>
                                </div>
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-1">Alamat</h4>
                                    <p class="text-on-surface-variant">Jl. Raya Payakumbuh - Tanjung Pati, Sumatera Barat</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-primary-container/10 rounded-xl flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-primary">call</span>
                                </div>
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-1">Telepon</h4>
                                    <p class="text-on-surface-variant">+62 852 6374 8190</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-primary-container/10 rounded-xl flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-primary">mail</span>
                                </div>
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-1">Email</h4>
                                    <p class="text-on-surface-variant">info@ecm.co.id</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-primary-container/10 rounded-xl flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-primary">schedule</span>
                                </div>
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-1">Jam Kerja</h4>
                                    <p class="text-on-surface-variant">Senin - Jumat: 08:00 - 17:00 WIB</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Contact Form --}}
                    <div class="bg-white rounded-2xl p-8 border border-outline-variant/30 shadow-sm">
                        <h2 class="font-headline-md text-headline-md text-on-surface mb-6">Kirim Pesan</h2>
                        <form action="#" method="POST" class="space-y-6">
                            @csrf
                            <div>
                                <label class="font-label-md text-label-md text-on-surface block mb-2">Nama Lengkap</label>
                                <input type="text" name="name" required
                                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all font-body-md" />
                            </div>
                            <div>
                                <label class="font-label-md text-label-md text-on-surface block mb-2">Email</label>
                                <input type="email" name="email" required
                                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all font-body-md" />
                            </div>
                            <div>
                                <label class="font-label-md text-label-md text-on-surface block mb-2">Subjek</label>
                                <input type="text" name="subject" required
                                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all font-body-md" />
                            </div>
                            <div>
                                <label class="font-label-md text-label-md text-on-surface block mb-2">Pesan</label>
                                <textarea name="message" rows="5" required
                                    class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all font-body-md"></textarea>
                            </div>
                            <button type="submit" class="w-full bg-primary text-on-primary py-3.5 rounded-xl font-label-md hover:opacity-90 active:scale-95 transition-all">
                                Kirim Pesan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection