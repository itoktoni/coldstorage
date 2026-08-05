<?php /** @var App\Models\MasukRealisasi $model */ ?>

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
                <x-table-sort field="in_realisasi_code" label="Kode" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="in_realisasi_id_product" label="Product" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="in_realisasi_qty" label="Qty" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="in_realisasi_code_lokasi" label="Lokasi" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="in_detail_status" label="Status" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>
            <x-slot:body>
                @forelse($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <td class="w-24 whitespace-nowrap">
                        <div class="flex gap-2">
                            @if ($table->in_detail_status !== 'complete')
                            <a href="{{ moduleRoute('getDelete', ['id' => $table->field_primary]) }}" onclick="return confirm('Are you sure you want to delete?')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </a>
                            @else
                            <span class="inline-flex items-center px-2 py-1 text-xs text-on-surface-variant bg-surface-container rounded-lg">
                                <span class="material-symbols-outlined text-sm mr-1">lock</span> Done
                            </span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $table->in_realisasi_code }}</td>
                    <td>{{ $table->product->product_nama ?? '-' }}</td>
                    <td>{{ $table->in_realisasi_qty }}</td>
                    <td>{{ $table->lokasi->lokasi_nama ?? $table->in_realisasi_code_lokasi }}</td>
                    <td><x-badge :type="$table->status_badge">{{ ucfirst($table->in_detail_status) }}</x-badge></td>
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
                <x-table-mobile-list>
                    @forelse($data as $table)
                    <x-table-mobile-item :id="$table->field_primary">
                        <x-table-mobile-header title="{{ $table->in_realisasi_code }}" />
                        <x-table-mobile-text label="Product" :text="$table->product->product_nama ?? '-'" />
                        <x-table-mobile-text label="Qty" :text="$table->in_realisasi_qty" />
                        <x-table-mobile-text label="Lokasi" :text="$table->lokasi->lokasi_nama ?? $table->in_realisasi_code_lokasi" />
                        <div class="flex items-center justify-between py-2 px-4">
                            <span class="text-on-surface-variant text-sm">Status</span>
                            <x-badge :type="$table->status_badge">{{ ucfirst($table->in_detail_status) }}</x-badge>
                        </div>
                        <x-table-mobile-footer :label="$table->field_primary">
                            <div class="flex gap-2">
                                @if ($table->in_detail_status !== 'complete')
                                <a href="{{ moduleRoute('getDelete', ['id' => $table->field_primary]) }}" onclick="return confirm('Are you sure you want to delete?')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </a>
                                @else
                                <span class="inline-flex items-center px-2 py-1 text-xs text-on-surface-variant bg-surface-container rounded-lg">
                                    <span class="material-symbols-outlined text-sm mr-1">lock</span> Done
                                </span>
                                @endif
                            </div>
                        </x-table-mobile-footer>
                    </x-table-mobile-item>
                    @empty
                    <x-table-mobile-item>
                        <div class="text-center p-4">No data available.</div>
                    </x-table-mobile-item>
                    @endforelse
                </x-table-mobile-list>
            </x-slot:mobile>
        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']"/>
    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
