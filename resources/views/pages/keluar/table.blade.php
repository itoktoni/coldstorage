<?php /** @var App\Models\Keluar $model */ ?>

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
                <x-table-sort field="out_code" label="Kode Keluar" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="out_tanggal" label="Tanggal" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="out_reff" label="Reff" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="out_qty" label="Qty" :sortField="$sortField" :sortDir="$sortDir" />
                <th class="text-center">Detail</th>
                <x-table-sort field="out_status" label="Status" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>
            <x-slot:body>
                @forelse($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary">
                        @if($table->so_id)
                                <a href="{{ route('wms-keluar-prepare.show', ['outCode' => $table->field_primary]) }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info/20 transition-colors"
                           title="Prepare SO">
                            <span class="material-symbols-outlined text-lg">inventory</span>
                        </a>
                        @endif
                    </x-table-action>
                    <td class="font-mono text-sm">{{ $table->out_code }}</td>
                    <td>{{ $table->out_tanggal?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $table->out_reff ?? '-' }}</td>
                    <td class="text-right font-medium">{{ number_format($table->out_qty, 0) }}</td>
                    <td class="text-center">{{ $table->detail_count }}</td>
                    <td>
                        @php
                            $statusColors = [
                                'Pending'     => 'bg-neutral/10 text-neutral',
                                'In Progress' => 'bg-warning/10 text-warning',
                                'Done'        => 'bg-success/10 text-success',
                            ];
                            $color = $statusColors[$table->out_status] ?? 'bg-neutral/10 text-neutral';
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                            {{ $table->out_status }}
                        </span>
                    </td>
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
                        <x-table-mobile-header title="{{ $table->out_code }}" />
                        <x-table-mobile-text label="Tanggal" :text="$table->out_tanggal?->format('d M Y') ?? '-'" />
                        <x-table-mobile-text label="Reff" :text="$table->out_reff ?? '-'" />
                        <x-table-mobile-text label="Total Qty" :text="number_format($table->out_qty, 0)" />
                        <x-table-mobile-text label="Detail" :text="$table->detail_count . ' item'" />
                        <x-table-mobile-text label="Status" :text="$table->out_status" />
                        <x-table-mobile-footer :label="$table->field_primary">
                            <x-table-action :model="$model" :id="$table->field_primary">
                                @if($table->so_id)
                        <a href="{{ route('wms-keluar-prepare.show', ['outCode' => $table->field_primary]) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info/20 transition-colors"
                                   title="Prepare SO">
                                    <span class="material-symbols-outlined text-lg">inventory</span>
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
