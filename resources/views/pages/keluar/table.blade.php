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
                        @if($table->has_so_detail)
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
                <div class="p-3 space-y-3" id="mBody">
                    @forelse($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm cursor-pointer transition-colors" data-id="{{ $table->field_primary }}" onclick="mToggle(this)">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span data-check class="icon-[tabler--circle] size-5 text-base-content/20 shrink-0"></span>
                                <p class="text-sm font-bold text-on-surface truncate font-mono">{{ $table->out_code }}</p>
                            </div>
                            @php
                                $statusColors = [
                                    'Pending'     => 'bg-gray-100 text-gray-600',
                                    'In Progress' => 'bg-warning/10 text-warning',
                                    'Done'        => 'bg-success/10 text-success',
                                ];
                                $color = $statusColors[$table->out_status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $color }} shrink-0">{{ $table->out_status }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->out_tanggal?->format('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Reff</p>
                                <p class="text-xs font-medium text-on-surface truncate">{{ $table->out_reff ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Total Qty</p>
                                <p class="text-xs font-bold text-on-surface">{{ number_format($table->out_qty, 0) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Detail</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->detail_count }} item</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                @if($table->has_so_detail)
                                <a href="{{ route('wms-keluar-prepare.show', ['outCode' => $table->field_primary]) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info/20 transition-colors"
                                   title="Prepare SO">
                                    <span class="material-symbols-outlined text-lg">inventory</span>
                                </a>
                                @endif
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
