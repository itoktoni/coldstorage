<x-layouts::app title="Dashboard - CMS Portal">
    <div>
        <section class="mb-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                $quickActions = [
                    ['label' => 'New Content', 'route' => route('content.getCreate'), 'icon' => 'add_circle', 'bgClass' => 'bg-primary-container/10', 'iconClass' => 'text-primary'],
                    ['route' => route('section.getTable'), 'label' => 'Sections', 'icon' => 'view_module', 'bgClass' => 'bg-secondary-container/10', 'iconClass' => 'text-secondary'],
                    ['route' => route('category.getTable'), 'label' => 'Categories', 'icon' => 'category', 'bgClass' => 'bg-tertiary-container/10', 'iconClass' => 'text-tertiary'],
                    ['route' => route('tag.getTable'), 'label' => 'Tags', 'icon' => 'label', 'bgClass' => 'bg-surface-variant', 'iconClass' => 'text-on-surface-variant'],
                ];
                @endphp
                @foreach($quickActions as $action)
                <a href="{{ $action['route'] }}" class="flex flex-col items-center gap-2 group bg-surface-container-lowest border border-outline-variant rounded-xl p-3 shadow-sm hover:shadow-md transition-all">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center group-active:scale-95 transition-transform border border-outline-variant shadow-sm {{ $action['bgClass'] }}">
                        <span class="material-symbols-outlined text-2xl {{ $action['iconClass'] }}">{{ $action['icon'] }}</span>
                    </div>
                    <span class="font-label-caps text-label-caps text-on-surface text-center">{{ $action['label'] }}</span>
                </a>
                @endforeach
            </div>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="widget-card">
                <h3 class="font-headline-md text-headline-md mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">analytics</span>
                    Content Overview
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    @php
                    $metrics = [
                        ['label' => 'Content', 'value' => $stats['total_content'] ?? '0', 'icon' => 'library_books', 'valueClass' => 'text-primary'],
                        ['label' => 'Sections', 'value' => $stats['total_sections'] ?? '0', 'icon' => 'view_module', 'valueClass' => 'text-secondary'],
                        ['label' => 'Categories', 'value' => $stats['total_categories'] ?? '0', 'icon' => 'category', 'valueClass' => 'text-tertiary'],
                        ['label' => 'Tags', 'value' => $stats['total_tags'] ?? '0', 'icon' => 'label', 'valueClass' => 'text-on-surface'],
                    ];
                    @endphp
                    @foreach($metrics as $metric)
                    <div class="bg-surface-container-lowest border border-outline-variant p-3 rounded-lg">
                        <p class="font-label-caps text-label-caps text-on-surface-variant mb-1 uppercase">{{ $metric['label'] }}</p>
                        <div class="flex items-end justify-between">
                            <span class="font-headline-md text-headline-md {{ $metric['valueClass'] }}">{{ $metric['value'] }}</span>
                            <span class="material-symbols-outlined text-sm {{ $metric['valueClass'] }}">{{ $metric['icon'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="widget-card">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-primary-container/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-xl">description</span>
                    </div>
                    <h3 class="font-headline-md text-headline-md text-on-surface">Content Types</h3>
                </div>
                <div class="space-y-3">
                    @forelse($cmsTypes ?? [] as $type)
                    <div class="flex items-center justify-between p-3 bg-surface-container rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary text-lg">{{ $type->icon ?? 'article' }}</span>
                            <div>
                                <p class="font-body-sm text-body-sm font-semibold text-on-surface">{{ $type->name }}</p>
                                <p class="font-label-caps text-label-caps text-on-surface-variant">{{ $type->contents_count ?? 0 }} entries</p>
                            </div>
                        </div>
                        <a href="{{ route('content.getTable', ['type' => $type->slug]) }}" class="text-primary hover:underline font-label-caps text-label-caps">View</a>
                    </div>
                    @empty
                    <div class="text-center py-8 text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl mb-2 block">add_circle</span>
                        <p class="font-body-sm text-body-sm">No content types yet. Create one to get started.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="widget-card md:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-headline-md text-headline-md flex items-center gap-2">
                        <span class="material-symbols-outlined text-on-surface-variant text-xl">history</span>
                        Recent Activity
                    </h3>
                </div>
                <div class="space-y-0">
                    @forelse($recentContents as $activity)
                    <div class="flex items-center gap-4 py-3 border-b border-outline-variant last:border-0">
                        <div class="p-2 rounded-lg shrink-0 {{ $activity['iconBg'] ?? 'bg-primary/5' }}">
                            <span class="material-symbols-outlined text-sm {{ $activity['iconColor'] ?? 'text-primary' }}">{{ $activity['icon'] ?? 'edit' }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-body-sm text-body-sm font-semibold text-on-surface truncate">{{ $activity['title'] }}</p>
                            <p class="font-label-caps text-label-caps text-on-surface-variant">{{ $activity['subtitle'] }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="font-label-caps text-[9px] font-bold px-2 py-0.5 rounded block mb-1 {{ $activity['statusClass'] ?? 'bg-green-50 text-green-700' }}">{{ $activity['status'] ?? 'PUBLISHED' }}</span>
                            <p class="text-[9px] text-outline font-data-mono text-data-mono">{{ $activity['time'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl mb-2 block">edit_note</span>
                        <p class="font-body-sm text-body-sm">No recent activity. Start creating content!</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
