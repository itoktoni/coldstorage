<?php /** @var App\Models\So $model */ ?>

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
                <x-table-sort field="so_code" label="{{ formatLabel('so_code', 'Kode') }}" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="customer_nama" label="{{ formatLabel('customer_nama', 'Customer') }}" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="so_tanggal" label="{{ formatLabel('so_tanggal', 'Tanggal') }}" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="so_status" label="{{ formatLabel('so_status', 'Status') }}" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>
            <x-slot:body>
                @forelse($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <td class="w-24 whitespace-nowrap">
                        <div class="flex gap-2">
                            @if($table->so_status->value === 'Confirmed')
                            <a href="{{ route('wms-so.ship', ['id' => $table->field_primary]) }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-colors"
                               title="Kirim SO">
                                <span class="material-symbols-outlined text-lg">local_shipping</span>
                            </a>
                            @endif
                            @if($table->so_status->value === 'Shipped')
                            <a href="{{ route('wms-so.cetakDelivery', ['id' => $table->field_primary]) }}" target="_blank"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors"
                               title="Cetak Delivery Order">
                                <span class="material-symbols-outlined text-lg">local_shipping</span>
                            </a>
                            <a href="{{ route('wms-so.cetakInvoice', ['id' => $table->field_primary]) }}" target="_blank"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 text-orange-700 hover:bg-orange-200 transition-colors"
                               title="Cetak Invoice">
                                <span class="material-symbols-outlined text-lg">receipt_long</span>
                            </a>
                            @endif
                            <a href="{{ route('wms-so.cetak', ['id' => $table->field_primary]) }}" target="_blank"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors"
                               title="Cetak SO">
                                <span class="material-symbols-outlined text-lg">print</span>
                            </a>
                        </div>
                    </td>
                    <td>{{ $table->so_code }}</td>
                    <td>{{ $table->customer_nama ?? '-' }}</td>
                    <td>{{ $table->so_tanggal?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $table->so_status }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($model::$sortColumns) + 2 }}" class="text-center">No data available.</td>
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
                                <p class="text-sm font-bold text-on-surface truncate">{{ $table->so_code }}</p>
                            </div>
                            @if($table->so_status->value === 'Pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600 shrink-0">Pending</span>
                            @elseif($table->so_status->value === 'Prepare')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-warning/10 text-warning shrink-0">Prepare</span>
                            @elseif($table->so_status->value === 'Confirmed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-info/10 text-info shrink-0">Confirmed</span>
                            @elseif($table->so_status->value === 'Shipped')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-primary/10 text-primary shrink-0">Shipped</span>
                            @elseif($table->so_status->value === 'Closed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-success/10 text-success shrink-0">Closed</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600 shrink-0">{{ $table->so_status }}</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->so_tanggal?->format('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Customer</p>
                                <p class="text-xs font-medium text-on-surface truncate">{{ $table->customer_nama ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                @if($table->so_status->value === 'Confirmed')
                                <a href="{{ route('wms-so.ship', ['id' => $table->field_primary]) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-colors"
                                   title="Kirim SO">
                                    <span class="material-symbols-outlined text-lg">local_shipping</span>
                                </a>
                                @endif
                                @if($table->so_status->value === 'Shipped')
                                <a href="{{ route('wms-so.cetakDelivery', ['id' => $table->field_primary]) }}" target="_blank"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors"
                                   title="Cetak Delivery Order">
                                    <span class="material-symbols-outlined text-lg">local_shipping</span>
                                </a>
                                <a href="{{ route('wms-so.cetakInvoice', ['id' => $table->field_primary]) }}" target="_blank"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 text-orange-700 hover:bg-orange-200 transition-colors"
                                   title="Cetak Invoice">
                                    <span class="material-symbols-outlined text-lg">receipt_long</span>
                                </a>

                                @endif
                                <a href="{{ route('wms-so.cetak', ['id' => $table->field_primary]) }}" target="_blank"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors"
                                   title="Cetak SO">
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
        <x-action :model="$model" :action="['create', 'delete']">
            <button type="button" onclick="prepareSelected()"
                class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-all active:scale-95 shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">inventory</span>
                <span class="hidden sm:inline">Buat Pick List</span>
            </button>
            <a href="{{ route('wms-so-prepare.index') }}"
                class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 transition-all active:scale-95 shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">assignment</span>
                <span class="hidden sm:inline">Prepare Barang</span>
            </a>
            <a href="{{ route('wms-forklift.index') }}"
                class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-orange-100 text-orange-700 hover:bg-orange-200 transition-all active:scale-95 shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">local_shipping</span>
                <span class="hidden sm:inline">Pick List</span>
            </a>
        </x-action>
    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
    <script>
    function prepareSelected() {
        const desktopIds = [...document.querySelectorAll('tbody input[type="checkbox"]:checked')].map(c => c.value);
        const ids = desktopIds.length ? desktopIds : [...mSelected];
        if (!ids.length) return alert('Pilih minimal 1 SO terlebih dahulu');
        window.location.href = '{{ route("wms-so-prepare.create") }}?so_ids[]=' + ids.join('&so_ids[]=');
    }
    </script>
</x-layouts::app>
