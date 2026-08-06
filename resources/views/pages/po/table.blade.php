<?php /** @var App\Models\Po $model */ ?>

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
                <x-table-sort field="po_code" label="{{ formatLabel('po_code', 'Po Code') }}" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="po_tanggal" label="{{ formatLabel('po_tanggal', 'Tanggal') }}" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="supplier_name" label="{{ formatLabel('supplier_name', 'Supplier') }}" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="po_status" label="{{ formatLabel('po_status', 'Status') }}" :sortField="$sortField" :sortDir="$sortDir" />

            </x-slot:head>
            <x-slot:body>
                @forelse ($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <td class="w-24 whitespace-nowrap">
                        <div class="flex gap-2">
                            @if($table->po_status !== \App\Wms\PoStatusEnum::DONE)
                            <a href="{{ moduleRoute('getUpdate', ['id' => $table->field_primary]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </a>
                            <a onclick="return confirm('Are you sure you want to delete?')" href="{{ moduleRoute('getDelete', ['id' => $table->field_primary]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </a>
                            @endif
                            <a href="{{ route('wms-po.cetak', ['id' => $table->field_primary]) }}" target="_blank"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info/20 transition-colors"
                               title="Cetak PO">
                                <span class="material-symbols-outlined text-lg">print</span>
                            </a>
                        </div>
                    </td>
                    <td>{{ $table->po_code }}</td>
                    <td>{{ formatDate($table->po_tanggal) }}</td>
                    <td>{{ $table->supplier->supplier_nama ?? 'N/A' }}</td>
                    <td>{{ $table->po_status }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($model::$sortColumns) + 2 }}" class="text-center">No data available.</td>
                </tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @forelse ($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <p class="text-sm font-bold text-on-surface truncate">{{ $table->po_code }}</p>
                            @if($table->po_status === \App\Wms\PoStatusEnum::DONE)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-success/10 text-success shrink-0">Done</span>
                            @elseif($table->po_status === \App\Wms\PoStatusEnum::PROCESS)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-warning/10 text-warning shrink-0">Process</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600 shrink-0">{{ $table->po_status }}</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                                <p class="text-xs font-medium text-on-surface">{{ formatDate($table->po_tanggal) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Supplier</p>
                                <p class="text-xs font-medium text-on-surface truncate">{{ $table->supplier->supplier_nama ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                @if($table->po_status !== \App\Wms\PoStatusEnum::DONE)
                                <a href="{{ moduleRoute('getUpdate', ['id' => $table->field_primary]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </a>
                                <a onclick="return confirm('Are you sure you want to delete?')" href="{{ moduleRoute('getDelete', ['id' => $table->field_primary]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </a>
                                @endif
                                <a href="{{ route('wms-po.cetak', ['id' => $table->field_primary]) }}" target="_blank"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info/20 transition-colors"
                                   title="Cetak PO">
                                    <span class="material-symbols-outlined text-lg">print</span>
                                </a>
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
