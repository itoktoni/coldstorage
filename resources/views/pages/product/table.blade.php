<?php /** @var App\Models\Product $model */ ?>

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
                @foreach ($model::$sortColumns as $column)
                <th>{{ formatLabel($column) }}</th>
                @endforeach
                <th>Status</th>
                <th class="text-end">Qty</th>
            </x-slot:head>
            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    @foreach ($model::$sortColumns as $column)
                    <td>{{ $table->$column }}</td>
                    @endforeach
                    <td>
                        @if ($table->product_status === 'active')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($table->qty, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data" />
                <x-table-mobile-list>
                    @foreach($data as $table)
                    <x-table-mobile-item :id="$table->field_primary">
                        <x-table-mobile-header title="{{ $table->product_nama }}" />
                        <x-table-mobile-text label="Harga" value="{{ number_format($table->product_harga, 2) }}" />
                        <x-table-mobile-text label="Status" value="{{ $table->product_status ?? 'active' }}" />
                        <x-table-mobile-text label="Qty" value="{{ number_format($table->qty, 0, ',', '.') }}" />
                        <x-table-mobile-text label="Tanggal" value="{{ $table->tanggal ?? '-' }}" />
                        <x-table-mobile-footer :label="$table->field_primary">
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    <a href="{{ route('wms-product.getQrcode', $table->product_id) }}"
                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
                       title="QR Code">
                        <span class="material-symbols-outlined text-lg">qr_code</span>
                    </a>
                        </x-table-mobile-footer>
                    </x-table-mobile-item>
                    @endforeach
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
