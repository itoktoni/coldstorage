<?php /** @var App\Models\PoDetail $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => ucfirst(module())]]" />
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
            </x-slot:head>
            <x-slot:body>
                @forelse ($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary">
                        <a href="{{ route('wms-po-detail-convert', ['id' => $table->field_primary]) }}"
                           onclick="return confirm('Convert PO Detail ini ke Masuk Detail?')"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-success text-white hover:bg-success/80 transition-colors"
                           title="Convert to Masuk">
                            <span class="material-symbols-outlined text-lg">swap_horiz</span>
                        </a>
                    </x-table-action>
                    <td>{{ $table->po->po_code }}</td>
                    <td>{{ $table->po_detail_code }}</td>
                    <td>{{ $table->product->product_nama ?? 'N/A' }}</td>
                    <td>{{ formatDate($table->po->po_tanggal) }}</td>
                    <td>{{ $table->po->supplier->supplier_nama ?? 'N/A' }}</td>
                    <td>{{ (float) $table->po_detail_qty }}</td>

                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($model::$sortColumns) + 2 }}" class="text-center">No data available.</td>
                </tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <x-table-mobile-list>
                    @forelse ($data as $table)
                    <x-table-mobile-item :id="$table->field_primary">
                        <x-table-mobile-header title="{{ $table->{head($model::$sortColumns)} }}" />
                        @foreach ($model::$sortColumns as $column)
                        <x-table-mobile-text :label="formatLabel($column)" :text="$table->$column" />
                        @endforeach
                        <x-table-mobile-footer :label="$table->field_primary">
                            <x-table-action :model="$model" :id="$table->field_primary">
                                <a href="{{ route('wms-po-detail-convert', ['id' => $table->field_primary]) }}"
                                   onclick="return confirm('Convert PO Detail ini ke Masuk Detail?')"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-success text-white hover:bg-success/80 transition-colors"
                                   title="Convert to Masuk">
                                    <span class="material-symbols-outlined text-lg">swap_horiz</span>
                                </a>
                            </x-table-action>
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
