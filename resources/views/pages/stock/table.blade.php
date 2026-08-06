<?php /** @var App\Models\Stock $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => moduleLabel()]]" />
    <div class="content mt-4 lg:mt-0">
        <x-filter :per-page="25" :fields="$fields">
            <x-slot:advanced>
                @foreach ($fields as $key => $advance)
                <x-filter-item :label="$advance" :name="$key"/>
                @endforeach
                <x-button variant="primary" class="btn-block" onclick="applyAdvanced()">Apply</x-button>
                <x-button variant="soft" class="btn-block" onclick="resetAdvanced()">Reset</x-button>
            </x-slot:advanced>
        </x-filter>

        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                <x-table-sort field="stock_code" label="Stock Code" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="stock_reff" label="Reff" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="product_nama" label="Product" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="stock_code_lokasi" label="Lokasi" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="stock_type" label="Type" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="stock_qty" label="Qty" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>
            <x-slot:body>
                @forelse($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    <td>{{ $table->stock_code }}</td>
                    <td>{{ $table->stock_reff }}</td>
                    <td>{{ $table->product->product_nama ?? $table->stock_id_product }}</td>
                    <td>{{ $table->lokasi->lokasi_nama ?? $table->stock_code_lokasi }}</td>
                    <td>{{ $table->stock_type }}</td>
                    <td>{{ $table->stock_qty }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="100">
                        <div class="text-center p-4">No data available.</div>
                    </td>
                </tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data" />
                <div class="p-3 space-y-3" id="mBody">
                    @forelse($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm cursor-pointer transition-colors" data-id="{{ $table->field_primary }}" onclick="mToggle(this)">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span data-check class="icon-[tabler--circle] size-5 text-base-content/20 shrink-0"></span>
                                <p class="text-sm font-bold text-on-surface truncate">{{ $table->product_nama ?? $table->stock_id_product }}</p>
                            </div>
                            @php
                                $typeColors = [
                                    'IN' => 'bg-success/10 text-success',
                                    'OUT' => 'bg-error/10 text-error',
                                    'MOVE' => 'bg-info/10 text-info',
                                    'ADJUST' => 'bg-warning/10 text-warning',
                                ];
                                $typeColor = $typeColors[$table->stock_type] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $typeColor }} shrink-0">{{ $table->stock_type }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Stock Code</p>
                                <p class="text-xs font-medium text-on-surface font-mono truncate">{{ $table->stock_code }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Reff</p>
                                <p class="text-xs font-medium text-on-surface truncate">{{ $table->stock_reff ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Lokasi</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->lokasi->lokasi_nama ?? $table->stock_code_lokasi }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Qty</p>
                                <p class="text-sm font-bold text-on-surface">{{ $table->stock_qty }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center p-4 text-on-surface-variant">No data available.</div>
                    @endforelse
                </div>
            </x-slot:mobile>
        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']"/>
    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
