<?php /** @var App\Models\MasukDetail $model */ ?>

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
                <x-table-sort field="in_detail_code" label="Code" :sortField="$sortField" :sortDir="$sortDir" />
                <th>Product</th>
                <x-table-sort field="in_detail_reff" label="PO Detail" :sortField="$sortField" :sortDir="$sortDir" />
                <th>Supplier</th>
                <x-table-sort field="in_detail_tanggal" label="Tanggal" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="in_detail_status" label="Status" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>
            <x-slot:body>
                @forelse ($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary">
                        @if ($table->in_detail_status !== \App\Wms\MasukStatusEnum::COMPLETE)
                        <a href="{{ route('wms-masuk-detail.realisasikan', ['id' => $table->field_primary]) }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-success/10 text-success hover:bg-success/20 transition-colors"
                           title="Realisasikan">
                            <span class="material-symbols-outlined text-lg">inventory_2</span>
                        </a>
                        @endif
                    </x-table-action>
                    <td>{{ $table->in_detail_code }}</td>
                    <td>{{ $table->product->product_nama ?? '-' }}</td>
                    <td>{{ $table->in_detail_reff }}</td>
                    <td>{{ $table->supplier_nama }}</td>
                    <td>{{ $table->in_detail_tanggal->format('d M Y') }}</td>
                    <td>
                        <span class="badge badge-{{ $table->in_detail_status->badgeColor() }}">
                            {{ $table->in_detail_status->description() }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">No data available.</td>
                </tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <x-table-mobile-list>
                    @forelse ($data as $table)
                    <x-table-mobile-item :id="$table->field_primary">
                        <x-table-mobile-header title="{{ $table->in_detail_code }}" />
                        <x-table-mobile-text label="Product" :text="$table->product->product_nama ?? '-'" />
                        <x-table-mobile-text label="PO Detail" :text="$table->in_detail_reff" />
                        <x-table-mobile-text label="Supplier" :text="$table->supplier_nama" />
                        <x-table-mobile-text label="Tanggal" :text="$table->in_detail_tanggal->format('d M Y')" />
                        <x-table-mobile-text label="Status" :text="$table->in_detail_status->description()" />
                        <x-table-mobile-footer :label="$table->field_primary">
                            <x-table-action :model="$model" :id="$table->field_primary">
                                @if ($table->in_detail_status !== \App\Wms\MasukStatusEnum::COMPLETE)
                                <a href="{{ route('wms-masuk-detail.realisasikan', ['id' => $table->field_primary]) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-success/10 text-success hover:bg-success/20 transition-colors"
                                   title="Realisasikan">
                                    <span class="material-symbols-outlined text-lg">inventory_2</span>
                                </a>
                                @endif
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
