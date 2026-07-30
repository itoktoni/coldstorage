@extends('frontend.layouts.public')

@section('title', $doc->title)

@section('content')
<section class="py-24 bg-surface-container-highest">
    <div class="max-w-5xl mx-auto px-8">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-on-surface-variant mb-8">
            <a href="{{ route('documentation') }}" class="hover:text-primary transition-colors">Dokumentasi</a>
            <span class="material-symbols-outlined text-sm">chevron_right</span>
            <span class="text-on-surface font-medium">{{ $doc->title }}</span>
        </nav>

        {{-- Main Photo --}}
        @if(!empty($doc->featured_image))
            <div class="rounded-2xl overflow-hidden mb-8 shadow-lg">
                <img src="{{ $doc->featured_image }}" alt="{{ $doc->title }}" class="w-full max-h-[600px] object-cover" />
            </div>
        @endif

        {{-- Title & Meta --}}
        <div class="mb-8">
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-4">{{ $doc->title }}</h1>

            <div class="flex flex-wrap items-center gap-4 text-sm text-on-surface-variant">
                @if($doc->published_at)
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">schedule</span>
                        <span>{{ $doc->published_at->format('d F Y') }}</span>
                    </div>
                @endif

                @if($docCategories->count() > 0)
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">category</span>
                        @foreach($docCategories as $cat)
                            <a href="{{ route('documentation.category', $cat->slug) }}"
                               class="hover:text-primary transition-colors">{{ $cat->name }}</a>{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Description / Excerpt --}}
        @if($doc->excerpt)
            <div class="bg-white rounded-xl border border-outline-variant/30 p-6 mb-8">
                <p class="text-on-surface-variant text-lg leading-relaxed">{{ $doc->excerpt }}</p>
            </div>
        @endif

        {{-- Full Content --}}
        @if($doc->content)
            <div class="prose prose-lg max-w-none mb-8">
                {!! $doc->content !!}
            </div>
        @endif

        {{-- Gallery Photos --}}
        @php
            $gallery = $doc->meta['gallery'] ?? [];
            if (!is_array($gallery)) { $gallery = []; }
        @endphp
        @if(count($gallery) > 0)
            <div class="mb-8">
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-6">Galeri Foto</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($gallery as $photo)
                        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-outline-variant/30 group">
                            @if(!empty($photo['image']))
                                <div class="overflow-hidden">
                                    <img src="{{ $photo['image'] }}" alt="{{ $photo['caption'] ?? '' }}"
                                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-700" />
                                </div>
                            @endif
                            <div class="p-4">
                                @if(!empty($photo['caption']))
                                    <h3 class="font-headline-md text-headline-md text-on-surface mb-1 text-sm">{{ $photo['caption'] }}</h3>
                                @endif
                                @if(!empty($photo['description']))
                                    <p class="text-on-surface-variant text-sm">{{ $photo['description'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Tags --}}
        @if($docTags->count() > 0)
            <div class="flex flex-wrap items-center gap-2 mb-12 pb-8 border-b border-outline-variant/30">
                <span class="text-sm font-medium text-on-surface-variant mr-2">Tag:</span>
                @foreach($docTags as $tag)
                    <a href="{{ route('documentation.tag', $tag->slug) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-primary/10 text-primary hover:bg-primary hover:text-on-primary transition-colors">
                        <span class="material-symbols-outlined text-sm mr-1">label</span>
                        {{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Back link --}}
        <div class="mb-12">
            <a href="{{ route('documentation') }}"
               class="inline-flex items-center gap-2 text-primary hover:text-primary/80 transition-colors font-label-md">
                <span class="material-symbols-outlined">arrow_back</span>
                Kembali ke Dokumentasi
            </a>
        </div>

        {{-- Related Documentation --}}
        @if($related->count() > 0)
            <div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-6">Dokumentasi Lainnya</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($related as $rel)
                        <a href="{{ route('documentation.show', $rel->slug) }}"
                           class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all group border border-outline-variant/30">
                            @if(!empty($rel->featured_image))
                                <div class="h-48 overflow-hidden">
                                    <img src="{{ $rel->featured_image }}" alt="{{ $rel->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                                </div>
                            @else
                                <div class="h-48 bg-surface-container-low flex items-center justify-center">
                                    <span class="material-symbols-outlined text-4xl text-outline">photo_library</span>
                                </div>
                            @endif
                            <div class="p-4">
                                <h3 class="font-headline-md text-headline-md text-on-surface mb-1 group-hover:text-primary transition-colors line-clamp-2 text-sm">
                                    {{ $rel->title }}
                                </h3>
                                @if($rel->published_at)
                                    <p class="text-xs text-outline">{{ $rel->published_at->format('d M Y') }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endsection