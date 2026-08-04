<?php /** @var App\Models\Split $model */ ?>

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
                <th>Tanggal</th>
                <th>Status</th>
                <th>Target Product</th>
                <th>Waste Product</th>
                <th>Qty Hasil</th>
                <th>Qty Waste</th>
                <th>Penyusutan</th>
            </x-slot:head>
            <x-slot:body>
                @forelse ($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <td class="flex gap-1">
                        <a href="{{ route('wms-split.produce') }}" class="bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1 rounded-lg text-sm inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm align-middle">play_arrow</span> Produce
                        </a>
                        <x-table-action :model="$model" :id="$table->field_primary" />
                    </td>
                    <td>{{ $table->split_tanggal?->format('d M Y') ?? '-' }}</td>
                    <td>
                        @if ($table->split_status === 'Processed')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">Processed</span>
                        @elseif ($table->split_status === 'Draft')
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs">Draft</span>
                        @else
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs">{{ $table->split_status }}</span>
                        @endif
                    </td>
                    <td>{{ $table->productTarget->product_nama ?? '-' }}</td>
                    <td>{{ $table->productWaste->product_nama ?? '-' }}</td>
                    <td>{{ number_format($table->split_qty_hasil, 2) }}</td>
                    <td>{{ number_format($table->split_qty_waste, 2) }}</td>
                    <td>{{ number_format($table->split_qty_penyusutan, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No data available.</td>
                </tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <x-table-mobile-list>
                    @forelse ($data as $table)
                    <x-table-mobile-item :id="$table->field_primary">
                        <x-table-mobile-header title="{{ $table->productTarget->product_nama ?? 'Split' }}" />
                        <x-table-mobile-text :label="'Tanggal'" :text="$table->split_tanggal?->format('d M Y') ?? '-'" />
                        <x-table-mobile-text :label="'Status'" :text="$table->split_status" />
                        <x-table-mobile-text :label="'Qty Hasil'" :text="number_format($table->split_qty_hasil, 2)" />
                        <x-table-mobile-text :label="'Penyusutan'" :text="number_format($table->split_qty_penyusutan, 2)" />
                        <x-table-mobile-footer :label="$table->field_primary">
                            <x-table-action :model="$model" :id="$table->field_primary" />
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
