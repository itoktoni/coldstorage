@extends('frontend.layouts.public')

@section('title', isset($activeCategory) ? 'Dokumentasi - ' . $activeCategory->name : (isset($activeTag) ? 'Dokumentasi - ' . $activeTag->name : 'Dokumentasi & Galeri Foto'))

@section('content')
<section class="py-24 bg-surface-container-highest">
    <div class="max-w-7xl mx-auto px-8">
        {{-- Header --}}
        <div class="flex items-center gap-4 mb-12">
            <div class="h-px bg-outline-variant flex-grow"></div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface shrink-0 px-4">
                @if(isset($activeCategory))
                    Kategori: {{ $activeCategory->name }}
                @elseif(isset($activeTag))
                    Tag: {{ $activeTag->name }}
                @else
                    Dokumentasi & Galeri Foto
                @endif
            </h1>
            <div class="h-px bg-outline-variant flex-grow"></div>
        </div>

        <div class="flex gap-8">
            {{-- Sidebar: Categories & Tags --}}
            <aside class="hidden lg:block w-64 shrink-0">
                <div class="sticky top-24 space-y-6">
                    {{-- Categories --}}
                    <div class="bg-white rounded-xl border border-outline-variant/30 p-5">
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">category</span>
                            Kategori
                        </h3>
                        <div class="space-y-1.5">
                            <a href="{{ route('documentation') }}"
                               class="block px-3 py-2 rounded-lg text-sm transition-colors {{ !isset($activeCategory) ? 'bg-primary text-on-primary font-medium' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                Semua Kategori
                            </a>
                            @foreach($categories as $cat)
                                <a href="{{ route('documentation.category', $cat->slug) }}"
                                   class="block px-3 py-2 rounded-lg text-sm transition-colors {{ isset($activeCategory) && $activeCategory->id === $cat->id ? 'bg-primary text-on-primary font-medium' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                    {{ $cat->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tags --}}
                    <div class="bg-white rounded-xl border border-outline-variant/30 p-5">
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">label</span>
                            Tag
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <a href="{{ route('documentation.tag', $tag->slug) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-colors {{ isset($activeTag) && $activeTag->id === $tag->id ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:bg-primary/10 hover:text-primary' }}">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                            @if($tags->isEmpty())
                                <p class="text-sm text-outline">Belum ada tag</p>
                            @endif
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Main Content: Photo Grid --}}
            <div class="flex-1 min-w-0">
                @if($docs->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($docs as $doc)
                            <a href="{{ route('documentation.show', $doc->slug) }}"
                               class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all group border border-outline-variant/30">
                                {{-- Photo --}}
                                @if(!empty($doc->featured_image))
                                    <div class="h-64 overflow-hidden">
                                        <img src="{{ $doc->featured_image }}" alt="{{ $doc->title }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                                    </div>
                                @else
                                    <div class="h-64 bg-surface-container-low flex items-center justify-center">
                                        <span class="material-symbols-outlined text-5xl text-outline">photo_library</span>
                                    </div>
                                @endif

                                {{-- Caption --}}
                                <div class="p-5">
                                    {{-- Date --}}
                                    <div class="flex items-center gap-2 text-xs text-outline mb-2">
                                        @if($doc->published_at)
                                            <span class="material-symbols-outlined text-sm">schedule</span>
                                            <span>{{ $doc->published_at->format('d M Y') }}</span>
                                        @endif
                                    </div>

                                    {{-- Title --}}
                                    <h3 class="font-headline-md text-headline-md text-on-surface mb-2 group-hover:text-primary transition-colors line-clamp-2">
                                        {{ $doc->title }}
                                    </h3>

                                    {{-- Excerpt / Description --}}
                                    @if($doc->excerpt)
                                        <p class="text-on-surface-variant text-sm line-clamp-3 mb-3">{{ $doc->excerpt }}</p>
                                    @endif

                                    {{-- Tags --}}
                                    @if(!empty($doc->tag_ids))
                                        @php
                                            $docTags = \App\Models\Tag::whereIn('id', $doc->tag_ids ?? [])->limit(3)->get();
                                        @endphp
                                        @if($docTags->count() > 0)
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($docTags as $t)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-primary/10 text-primary">
                                                        {{ $t->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif

                                    {{-- Read more --}}
                                    <div class="mt-3 flex items-center gap-1 text-primary font-label-md text-sm">
                                        Lihat Detail <span class="material-symbols-outlined text-lg">arrow_right_alt</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-12 flex justify-center">
                        {{ $docs->links() }}
                    </div>
                @else
                    <div class="text-center py-20">
                        <span class="material-symbols-outlined text-6xl text-outline mb-4">photo_library</span>
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-2">Belum Ada Dokumentasi</h3>
                        <p class="text-on-surface-variant">Belum ada dokumentasi foto yang tersedia untuk saat ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection