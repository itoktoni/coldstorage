<?php /** @var App\Models\KeluarDetail $model */ ?>

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
                <x-table-sort field="out_detail_code" label="Kode Detail" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="out_detail_code_keluar" label="Kode Keluar" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="out_detail_id_product" label="Product" :sortField="$sortField" :sortDir="$sortDir" />
                <th>Kode SO</th>
                <x-table-sort field="out_detail_qty" label="Qty" :sortField="$sortField" :sortDir="$sortDir" />
                <th class="text-center">Realisasi</th>
                <th class="text-center">Aksi</th>
            </x-slot:head>
            <x-slot:body>
                @forelse($data as $table)
                @php
                    $picked = $table->picked_qty;
                    $qty = (float) $table->out_detail_qty;
                    $pct = $qty > 0 ? round($picked / $qty * 100) : 0;
                    $isDone = $pct >= 100;
                @endphp
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    <td class="font-mono text-sm">{{ $table->out_detail_code }}</td>
                    <td class="font-mono text-sm">{{ $table->out_detail_code_keluar }}</td>
                    <td>{{ $table->product_nama ?? '-' }}</td>
                    <td class="font-mono text-sm">{{ $table->so_code ?? '-' }}</td>
                    <td class="text-right font-medium">{{ number_format($table->out_detail_qty, 0) }}</td>
                    <td class="text-right">
                        <span class="text-sm">{{ number_format($picked, 0) }} / {{ number_format($qty, 0) }}</span>
                        @if($isDone)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success ml-1">Selesai</span>
                        @elseif($pct > 0)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning ml-1">{{ $pct }}%</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('wms-keluar-realisasi-scan.show', $table->field_primary) }}"
                           class="inline-flex items-center gap-1 h-8 px-3 text-xs font-semibold rounded-lg {{ $isDone ? 'bg-success/10 text-success' : 'bg-primary/10 text-primary' }} hover:bg-{{ $isDone ? 'success' : 'primary' }}/20 transition-colors">
                            <span class="material-symbols-outlined text-sm">{{ $isDone ? 'check_circle' : 'qr_code_scanner' }}</span>
                            {{ $isDone ? 'Selesai' : 'Realisasi' }}
                        </a>
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
                        <x-table-mobile-header title="{{ $table->out_detail_code }}" />
                        <x-table-mobile-text label="Keluar" :text="$table->out_detail_code_keluar" />
                        <x-table-mobile-text label="Product" :text="$table->product_nama ?? '-'" />
                        <x-table-mobile-text label="SO" :text="$table->so_code ?? '-'" />
                        <x-table-mobile-text label="Qty" :text="number_format($table->out_detail_qty, 0)" />
                        <x-table-mobile-text label="Realisasi" :text="number_format($table->picked_qty, 0) . ' / ' . number_format($table->out_detail_qty, 0)" />
                        <x-table-mobile-footer :label="$table->field_primary">
                            <a href="{{ route('wms-keluar-realisasi-scan.show', $table->field_primary) }}"
                               class="inline-flex items-center gap-1 h-8 px-3 text-xs font-semibold rounded-lg {{ $table->picked_qty >= $table->out_detail_qty ? 'bg-success/10 text-success' : 'bg-primary/10 text-primary' }} hover:bg-primary/20 transition-colors">
                                <span class="material-symbols-outlined text-sm">{{ $table->picked_qty >= $table->out_detail_qty ? 'check_circle' : 'qr_code_scanner' }}</span>
                                {{ $table->picked_qty >= $table->out_detail_qty ? 'Selesai' : 'Realisasi' }}
                            </a>
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
