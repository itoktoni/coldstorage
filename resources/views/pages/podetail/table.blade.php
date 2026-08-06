<?php /** @var App\Models\PoDetail $model */ ?>

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
                <x-table-sort field="po_code" label="Purchase Order" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="po_detail_code" label="Detail Code" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="product_id" label="Product" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="po_tanggal" label="Tanggal" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="supplier_id" label="Supplier" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="po_detail_qty" label="Quantity" :sortField="$sortField" :sortDir="$sortDir" />
                <th>Prepare</th>
                <th>Sisa</th>
            </x-slot:head>
            <x-slot:body>
                @forelse ($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary">
                        <a href="{{ route('wms-po-detail-convert', ['id' => $table->field_primary]) }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-success text-white hover:bg-success/80 transition-colors"
                           title="Convert to Masuk">
                            <span class="material-symbols-outlined text-lg">assignment</span>
                        </a>
                    </x-table-action>
                    <td>{{ $table->po->po_code }}</td>
                    <td>{{ $table->po_detail_code }}</td>
                    <td>{{ $table->product->product_nama ?? 'N/A' }}</td>
                    <td>{{ formatDate($table->po->po_tanggal) }}</td>
                    <td>{{ $table->po->supplier->supplier_nama ?? 'N/A' }}</td>
                    <td>{{ (float) $table->po_detail_qty }}</td>
                    <td>{{ (float) $table->prepare_qty }}</td>
                    <td>{{ (float) $table->po_detail_qty - (float) $table->prepare_qty }}</td>

                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($model::$sortColumns) + 4 }}" class="text-center">No data available.</td>
                </tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @forelse ($data as $table)
                    @php
                        $sisa = (float) $table->po_detail_qty - (float) $table->prepare_qty;
                    @endphp
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <p class="text-sm font-bold text-on-surface truncate">{{ $table->product->product_nama ?? 'N/A' }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-primary/10 text-primary shrink-0">{{ $table->po_detail_code }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">PO Code</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->po->po_code }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                                <p class="text-xs font-medium text-on-surface">{{ formatDate($table->po->po_tanggal) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Supplier</p>
                                <p class="text-xs font-medium text-on-surface truncate">{{ $table->po->supplier->supplier_nama ?? 'N/A' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Qty</p>
                                <p class="text-xs font-bold text-on-surface">{{ formatQty($table->po_detail_qty) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Prepare</p>
                                <p class="text-xs font-medium text-on-surface">{{ formatQty($table->prepare_qty) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Sisa</p>
                                <p class="text-xs font-bold {{ $sisa > 0 ? 'text-warning' : 'text-success' }}">{{ formatQty($sisa) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                <x-table-action :model="$model" :id="$table->field_primary">
                                    <a href="{{ route('wms-po-detail-convert', ['id' => $table->field_primary]) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-success/10 text-success hover:bg-success/20 transition-colors"
                                       title="Convert to Masuk">
                                        <span class="material-symbols-outlined text-lg">inventory_2</span>
                                    </a>
                                </x-table-action>
                            </div>
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
