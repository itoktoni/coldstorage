<x-layouts::app title="Dashboard - Warehouse Management System">
    <div>
        {{-- Header --}}
        <div class="mb-6">
            <p class="text-xs font-semibold text-primary uppercase tracking-widest mb-1">Warehouse Management System</p>
            <h2 class="text-2xl font-bold text-on-surface">System Overview</h2>
        </div>

        {{-- Operational Metrics --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card mb-5">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">analytics</span>
                Operational Metrics
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary">shelves</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Total Stock</span>
                    </div>
                    <span class="text-2xl font-bold text-primary">{{ number_format($stats['total_stock']) }}</span>
                    <span class="text-xs text-on-surface-variant">unit</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-success">login</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Inbound Hari Ini</span>
                    </div>
                    <span class="text-2xl font-bold text-success">{{ $stats['inbound_today'] }}</span>
                    <span class="text-xs text-on-surface-variant">transaksi</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-error/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-error">logout</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Outbound Hari Ini</span>
                    </div>
                    <span class="text-2xl font-bold text-error">{{ $stats['outbound_today'] }}</span>
                    <span class="text-xs text-on-surface-variant">transaksi</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-warning">forklift</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Forklift Pending</span>
                    </div>
                    <span class="text-2xl font-bold text-warning">{{ $stats['pending_forklift'] }}</span>
                    <span class="text-xs text-on-surface-variant">task</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-secondary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-secondary">call_split</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Split Draft</span>
                    </div>
                    <span class="text-2xl font-bold text-secondary">{{ $stats['pending_split'] }}</span>
                    <span class="text-xs text-on-surface-variant">belum diproses</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-on-surface/5 flex items-center justify-center">
                            <span class="material-symbols-outlined text-on-surface">category</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Produk Aktif</span>
                    </div>
                    <span class="text-2xl font-bold text-on-surface">{{ $stats['total_product'] }}</span>
                    <span class="text-xs text-on-surface-variant">produk</span>
                </div>
            </div>
        </div>

        {{-- Warehouse Occupancy --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card mb-5">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">warehouse</span>
                Warehouse Occupancy
            </h3>

            @forelse($warehouses as $wh)
            @php
                $isWhFull = $wh['total_percent'] >= 100;
                $isWhAlmostFull = $wh['total_percent'] >= 90 && !$isWhFull;
            @endphp
            <div class="mb-6 last:mb-0 {{ $isWhFull ? 'bg-error/5 rounded-xl p-4 -mx-2' : '' }}">
                {{-- Gudang Header --}}
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        @if($isWhFull)
                        <div class="w-10 h-10 rounded-lg bg-error/10 flex items-center justify-center animate-pulse">
                            <span class="material-symbols-outlined text-error">block</span>
                        </div>
                        @elseif($isWhAlmostFull)
                        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-warning">warning</span>
                        </div>
                        @else
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary">home_work</span>
                        </div>
                        @endif
                        <div>
                            <p class="text-sm font-bold text-on-surface">{{ $wh['name'] }}</p>
                            <p class="text-[10px] text-on-surface-variant uppercase tracking-widest">{{ $wh['code'] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        @if($isWhFull)
                            <span class="bg-error text-on-error text-[10px] font-bold px-2 py-0.5 rounded-full">PENUH</span>
                        @elseif($isWhAlmostFull)
                            <span class="bg-warning text-on-warning text-[10px] font-bold px-2 py-0.5 rounded-full">HAMPIR PENUH</span>
                        @endif
                        <span class="block text-lg font-bold {{ $isWhFull ? 'text-error' : ($isWhAlmostFull ? 'text-warning' : 'text-success') }}">
                            {{ $wh['total_percent'] }}%
                        </span>
                        <p class="text-[10px] text-on-surface-variant">
                            {{ number_format($wh['total_current']) }} / {{ number_format($wh['total_max']) }} unit
                        </p>
                    </div>
                </div>

                {{-- Gudang Progress Bar --}}
                <div class="w-full h-3 bg-surface-container rounded-full overflow-hidden mb-4">
                    <div class="h-full rounded-full transition-all {{ $isWhFull ? 'bg-error animate-pulse' : ($isWhAlmostFull ? 'bg-warning' : 'bg-success') }}"
                         style="width: {{ min($wh['total_percent'], 100) }}%"></div>
                </div>

                {{-- Lokasi List --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($wh['lokasi'] as $loc)
                    @php
                        $isFull = $loc['percent'] >= 100;
                        $isAlmostFull = $loc['percent'] >= 90 && !$isFull;
                    @endphp
                    <div class="bg-surface-container rounded-lg p-3 border {{ $isFull ? 'border-error border-2' : ($isAlmostFull ? 'border-warning border-2' : 'border-outline-variant/50') }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                @if($isFull)
                                <span class="material-symbols-outlined text-error text-lg animate-pulse">block</span>
                                @elseif($isAlmostFull)
                                <span class="material-symbols-outlined text-warning text-lg">warning</span>
                                @else
                                <span class="material-symbols-outlined text-success text-lg">check_circle</span>
                                @endif
                                <div>
                                    <p class="text-xs font-bold text-on-surface">{{ $loc['name'] }}</p>
                                    <p class="text-[9px] text-on-surface-variant font-mono">{{ $loc['code'] }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                @if($isFull)
                                    <span class="bg-error text-on-error text-[9px] font-bold px-2 py-0.5 rounded-full">PENUH</span>
                                @elseif($isAlmostFull)
                                    <span class="bg-warning text-on-warning text-[9px] font-bold px-2 py-0.5 rounded-full">HAMPIR PENUH</span>
                                @endif
                                <span class="block text-sm font-bold {{ $isFull ? 'text-error' : ($isAlmostFull ? 'text-warning' : 'text-success') }}">
                                    {{ $loc['percent'] }}%
                                </span>
                            </div>
                        </div>
                        <div class="w-full h-2 bg-surface-container-lowest rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $isFull ? 'bg-error animate-pulse' : ($isAlmostFull ? 'bg-warning' : 'bg-success') }}"
                                 style="width: {{ min($loc['percent'], 100) }}%"></div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-[9px] {{ $isFull ? 'text-error font-bold' : 'text-on-surface-variant' }}">{{ number_format($loc['current_qty']) }} terisi</span>
                            <span class="text-[9px] text-on-surface-variant">max {{ number_format($loc['max_qty']) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if(!$loop->last)
                <div class="border-b border-outline-variant mt-6"></div>
                @endif
            </div>
            @empty
            <div class="text-center py-8 text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl mb-2 block">warehouse</span>
                <p class="text-sm">Belum ada gudang</p>
            </div>
            @endforelse
        </div>

        <div class="grid grid-cols-12 gap-5">
            {{-- Pending Forklift Tasks --}}
            <div class="col-span-12 md:col-span-6">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant">
                        <h3 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">forklift</span>
                            Forklift Tasks
                        </h3>
                        <a href="{{ route('wms-forklift-task.index') }}" class="text-primary text-xs font-semibold hover:underline">Lihat Semua</a>
                    </div>
                    @forelse($pendingForklift as $task)
                    <div class="flex items-center gap-4 py-3 border-b border-outline-variant last:border-0">
                        <div class="bg-warning/10 p-2 rounded-lg shrink-0">
                            <span class="material-symbols-outlined text-warning text-sm">{{ $task->forklift_type === 'putaway' ? 'input' : 'output' }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-on-surface truncate">{{ $task->forklift_pallet_code }}</p>
                            <p class="text-xs text-on-surface-variant">{{ $task->forklift_lokasi_asal }} → {{ $task->forklift_lokasi_tujuan }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="bg-warning/10 text-warning text-[10px] font-bold px-2 py-0.5 rounded">{{ strtoupper($task->forklift_type) }}</span>
                            <p class="text-[10px] text-on-surface-variant mt-1">{{ $task->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl mb-2 block">check_circle</span>
                        <p class="text-sm">Semua task sudah selesai</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Low Stock + Recent Activity --}}
            <div class="col-span-12 md:col-span-6">
                {{-- Low Stock --}}
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card mb-5">
                    <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-error text-xl">warning</span>
                        Low Stock Alert
                    </h3>
                    @forelse($lowStock as $stock)
                    <div class="flex items-center gap-3 py-3 border-b border-outline-variant last:border-0">
                        <div class="w-10 h-10 rounded-lg bg-error/10 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-error text-lg">inventory</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-on-surface truncate">{{ $stock->product->product_nama ?? '-' }}</p>
                            <p class="text-xs text-on-surface-variant">{{ $stock->lokasi->lokasi_nama ?? $stock->stock_code_lokasi }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-lg font-bold text-error">{{ number_format($stock->stock_qty) }}</span>
                            <p class="text-[10px] text-on-surface-variant">unit</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6 text-on-surface-variant">
                        <span class="material-symbols-outlined text-3xl mb-2 block">check_circle</span>
                        <p class="text-sm">Stock semua aman</p>
                    </div>
                    @endforelse
                </div>

                {{-- Recent Activity --}}
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant">
                        <h3 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">history</span>
                            Recent Activity
                        </h3>
                        <a href="{{ route('wms-stock-flow.index') }}" class="text-primary text-xs font-semibold hover:underline">Lihat Semua</a>
                    </div>
                    @forelse($recentLogs as $log)
                    <div class="flex items-center gap-4 py-3 border-b border-outline-variant last:border-0">
                        <div class="{{ $log['iconBg'] }} p-2 rounded-lg shrink-0">
                            <span class="material-symbols-outlined {{ $log['iconColor'] }} text-sm">{{ $log['icon'] }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-on-surface truncate">{{ $log['title'] }}</p>
                            <p class="text-xs text-on-surface-variant">{{ $log['subtitle'] }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-sm font-bold {{ $log['type'] === 'OUT' ? 'text-error' : 'text-success' }}">
                                {{ $log['type'] === 'OUT' ? '-' : '+' }}{{ number_format($log['qty']) }}
                            </span>
                            <p class="text-[10px] text-on-surface-variant">{{ $log['time'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6 text-on-surface-variant">
                        <span class="material-symbols-outlined text-3xl mb-2 block">inventory_2</span>
                        <p class="text-sm">Belum ada aktivitas stock</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
